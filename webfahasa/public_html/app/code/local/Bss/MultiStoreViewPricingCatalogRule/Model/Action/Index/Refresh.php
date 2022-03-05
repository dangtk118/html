<?php
/**
* BSS Commerce Co.
*
* NOTICE OF LICENSE
*
* This source file is subject to the EULA
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://bsscommerce.com/Bss-Commerce-License.txt
*
* =================================================================
*                 MAGENTO EDITION USAGE NOTICE
* =================================================================
* This package designed for Magento COMMUNITY edition
* BSS Commerce does not guarantee correct work of this extension
* on any other Magento edition except Magento COMMUNITY edition.
* BSS Commerce does not provide extension support in case of
* incorrect edition usage.
* =================================================================
*
* @category   BSS
* @package    Bss_MultiStoreViewPricing
* @author     Extension Team
* @copyright  Copyright (c) 2015-2016 BSS Commerce Co. ( http://bsscommerce.com )
* @license    http://bsscommerce.com/Bss-Commerce-License.txt
*/
class Bss_MultiStoreViewPricingCatalogRule_Model_Action_Index_Refresh extends Mage_CatalogRule_Model_Action_Index_Refresh
{
    /**
     * Run reindex
     */
    public function execute()
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::execute();

        $this->_app->dispatchEvent('catalogrule_before_apply', array('resource' => $this->_resource));

        /** @var $coreDate Mage_Core_Model_Date */
        $coreDate  = $this->_factory->getModel('core/date');
        $timestamp = $coreDate->gmtTimestamp('Today');

        foreach ($this->_app->getWebsites(false) as $website) {
            /** @var $website Mage_Core_Model_Website */
            if ($website->getDefaultStore()) {
                foreach ($website->getGroups() as $group) {
                    $stores = $group->getStores();
                    foreach ($stores as $store) {
                        $this->_reindex($website, $timestamp, $store);
                    }
                }
            }
        }

        $this->_prepareGroupWebsite($timestamp);
        $this->_prepareAffectedProduct();
    }

    /**
     * Reindex catalog prices by website for timestamp
     *
     * @param Mage_Core_Model_Website $website
     * @param int $timestamp
     */
    protected function _reindex(Mage_Core_Model_Website $website, $timestamp, $store = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::_reindex($website, $timestamp);

        $this->_createTemporaryTable();
        $this->_connection->query(
            $this->_connection->insertFromSelect(
                $this->_prepareTemporarySelect($website, $store),
                $this->_getTemporaryTable()
            )
        );
        $this->_removeOldIndexData($website, $store);
        $this->_fillIndexData($website, $timestamp, $store);
    }

    /**
     * Prepare temporary data
     *
     * @param Mage_Core_Model_Website $website
     * @return Varien_Db_Select
     */
    protected function _prepareTemporarySelect(Mage_Core_Model_Website $website, $store = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::_prepareTemporarySelect($website);

        /** @var $catalogFlatHelper Mage_Catalog_Helper_Product_Flat */
        $catalogFlatHelper = $this->_factory->getHelper('catalog/product_flat');

        /** @var $eavConfig Mage_Eav_Model_Config */
        $eavConfig = $this->_factory->getSingleton('eav/config');
        $priceAttribute = $eavConfig->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'price');

        $select = $this->_connection->select()
            ->from(
                array('rp' => $this->_resource->getTable('catalogrule/rule_product')),
                array()
            )
            ->joinInner(
                array('r' => $this->_resource->getTable('catalogrule/rule')),
                'r.rule_id = rp.rule_id',
                array()
            )
            ->where('rp.website_id = ?', $website->getId())
            ->order(
                array('rp.product_id', 'rp.customer_group_id', 'rp.sort_order', 'rp.rule_product_id')
            )
            ->joinLeft(
                array(
                    'pg' => $this->_resource->getTable('catalog/product_attribute_group_price')
                ),
                'pg.entity_id = rp.product_id AND pg.customer_group_id = rp.customer_group_id'
                    . ' AND pg.website_id = rp.website_id',
                array()
            )
            ->joinLeft(
                array(
                    'pgd' => $this->_resource->getTable('catalog/product_attribute_group_price')
                ),
                'pgd.entity_id = rp.product_id AND pgd.customer_group_id = rp.customer_group_id'
                    . ' AND pgd.website_id = 0',
                array()
            )
            ->joinLeft(
                array(
                    'pg_store' => $this->_resource->getTable('multistoreviewpricingpricegrouptierstore/group_price')
                ),
                'pg_store.entity_id = rp.product_id AND pg_store.customer_group_id = rp.customer_group_id'
                    . ' AND pg_store.store_id = '.$store->getId(),
                array()
            );

        $storeId = $store->getId();

        if ($catalogFlatHelper->isEnabled() && $storeId && $catalogFlatHelper->isBuilt($storeId)) {
            $select->joinInner(
                array('p' => $this->_resource->getTable('catalog/product_flat') . '_' . $storeId),
                'p.entity_id = rp.product_id',
                array()
            );
            if(version_compare(Mage::getVersion(), '1.9.3.0') >= 0) {
                $priceColumn = $this->_connection->getIfNullSql(
                    'pg_store.value',
                    $this->_connection->getIfNullSql(
                        $this->_connection->getIfNullSql(
                            $this->_connection->getCheckSql(
                                'pg.is_percent = 1',
                                'p.price * (100 - pg.value)/100',
                                'pg.value'
                            ),
                            $this->_connection->getCheckSql(
                                'pgd.is_percent = 1',
                                'p.price * (100 - pgd.value)/100',
                                'pgd.value'
                            )
                        ),
                        'p.price'
                    )
                );
            }else {
                $priceColumn = $this->_connection->getIfNullSql(
                    'pg_store.value',
                    $this->_connection->getIfNullSql(
                        $this->_connection->getIfNullSql(
                            'pg.value',
                            'pgd.value'
                        ),
                        'p.price'
                    )
                );
            }
        } else {
            $select->joinInner(
                    array(
                        'pd' => $this->_resource->getTable(array('catalog/product', $priceAttribute->getBackendType()))
                    ),
                    'pd.entity_id = rp.product_id AND pd.store_id = 0 AND pd.attribute_id = '
                        . $priceAttribute->getId(),
                    array()
                )
                ->joinLeft(
                    array(
                        'p' => $this->_resource->getTable(array('catalog/product', $priceAttribute->getBackendType()))
                    ),
                    'p.entity_id = rp.product_id AND p.store_id = ' . $storeId
                        . ' AND p.attribute_id = pd.attribute_id',
                    array()
                );
            if(version_compare(Mage::getVersion(), '1.9.3.0') >= 0) {
                $priceColumn = $this->_connection->getIfNullSql(
                    'pg_store.value',
                    $this->_connection->getIfNullSql(
                        $this->_connection->getIfNullSql(
                            $this->_connection->getCheckSql(
                                'pg.is_percent = 1',
                                $this->_connection->getIfNullSql(
                                    'p.value',
                                    'pd.value'
                                ) . ' * (100 - pg.value)/100',
                                'pg.value'
                            ),
                            $this->_connection->getCheckSql(
                                'pgd.is_percent = 1',
                                $this->_connection->getIfNullSql(
                                    'p.value',
                                    'pd.value'
                                ) . ' * (100 - pgd.value)/100',
                                'pgd.value'
                            )
                        ),
                        $this->_connection->getIfNullSql(
                            'p.value',
                            'pd.value'
                        )
                    )
                );

            }else {
                $priceColumn = $this->_connection->getIfNullSql(
                    'pg_store.value',
                    $this->_connection->getIfNullSql(
                        $this->_connection->getIfNullSql(
                            'pg.value',
                            'pgd.value'
                        ),
                        $this->_connection->getIfNullSql(
                            'p.value',
                            'pd.value'
                        )
                    )
                );
            }
        }

        $select->columns(
            array(
                'grouped_id' => $this->_connection->getConcatSql(
                    array('rp.product_id', 'rp.customer_group_id'),
                    '-'
                ),
                'product_id'        => 'rp.product_id',
                'customer_group_id' => 'rp.customer_group_id',
                'from_date'         => 'r.from_date',
                'to_date'           => 'r.to_date',
                'action_amount'     => 'rp.action_amount',
                'action_operator'   => 'rp.action_operator',
                'action_stop'       => 'rp.action_stop',
                'sort_order'        => 'rp.sort_order',
                'price'             => $priceColumn,
                'rule_product_id'   => 'rp.rule_product_id',
                'from_time'         => 'rp.from_time',
                'to_time'           => 'rp.to_time'
            )
        );

        return $select;
    }

    /**
     * Remove old index data
     *
     * @param Mage_Core_Model_Website $website
     */
    protected function _removeOldIndexData(Mage_Core_Model_Website $website, $store = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::_removeOldIndexData($website);

        $this->_connection->delete(
            $this->_resource->getTable('multistoreviewpricingcatalogrule/rule_product_price'),
            array('store_id = ?' => $store->getId())
        );
    }

    /**
     * Fill Index Data
     *
     * @param Mage_Core_Model_Website $website
     * @param int $time
     */
    protected function _fillIndexData(Mage_Core_Model_Website $website, $time, $store = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::_fillIndexData($website, $time);

        $this->_connection->query(
            $this->_connection->insertFromSelect(
                $this->_prepareIndexSelect($website, $time,$store),
                $this->_resource->getTable('multistoreviewpricingcatalogrule/rule_product_price')
            )
        );
    }

    /**
     * Prepare index select
     *
     * @param Mage_Core_Model_Website $website
     * @param $time
     * @return Varien_Db_Select
     */
    protected function _prepareIndexSelect(Mage_Core_Model_Website $website, $time, $store = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::_prepareIndexSelect($website, $time);

        $nA = $this->_connection->quote('N/A');
        $this->_connection->query('SET @price := 0');
        $this->_connection->query('SET @group_id := NULL');
        $this->_connection->query('SET @action_stop := NULL');

        $indexSelect = $this->_connection->select()
            ->from(array('cppt' => $this->_getTemporaryTable()), array())
            ->order(array('cppt.grouped_id', 'cppt.sort_order', 'cppt.rule_product_id'))
            ->columns(
                array(
                    'customer_group_id' => 'cppt.customer_group_id',
                    'product_id'        => 'cppt.product_id',
                    'rule_price'        => $this->_calculatePrice(),
                    'latest_start_date' => 'cppt.from_date',
                    'earliest_end_date' => 'cppt.to_date',
                    new Zend_Db_Expr(
                        $this->_connection->getCaseSql(
                            '',
                            array(
                                $this->_connection->getIfNullSql(
                                    new Zend_Db_Expr('@group_id'),
                                    $nA
                                ) . ' != cppt.grouped_id' => new Zend_Db_Expr('@action_stop := cppt.action_stop'),
                                $this->_connection->getIfNullSql(
                                    new Zend_Db_Expr('@group_id'),
                                    $nA
                                ) . ' = cppt.grouped_id' => '@action_stop := '
                                    . $this->_connection->getIfNullSql(
                                        new Zend_Db_Expr('@action_stop'),
                                        new Zend_Db_Expr(0)
                                    ) . ' + cppt.action_stop',
                            )
                        )
                    ),
                    new Zend_Db_Expr('@group_id := cppt.grouped_id'),
                    'from_time'         => 'cppt.from_time',
                    'to_time'           => 'cppt.to_time'
                )
            );

        $select = $this->_connection->select()
            ->from($indexSelect, array())
            ->joinInner(
                array(
                    'dates' => $this->_connection->select()->union(
                        array(
                            new Zend_Db_Expr(
                                'SELECT ' . $this->_connection->getDateAddSql(
                                    $this->_connection->fromUnixtime($time),
                                    -1,
                                    Varien_Db_Adapter_Interface::INTERVAL_DAY
                                ) . ' AS rule_date'
                            ),
                            new Zend_Db_Expr('SELECT ' . $this->_connection->fromUnixtime($time) . ' AS rule_date'),
                            new Zend_Db_Expr(
                                'SELECT ' . $this->_connection->getDateAddSql(
                                    $this->_connection->fromUnixtime($time),
                                    1,
                                    Varien_Db_Adapter_Interface::INTERVAL_DAY
                                ) . ' AS rule_date'
                            ),
                        )
                    )
                ),
                '1=1',
                array()
            )
            ->columns(
                array(
                    'rule_product_price_id' => new Zend_Db_Expr('NULL'),
                    'rule_date'             => 'dates.rule_date',
                    'customer_group_id'     => 'customer_group_id',
                    'product_id'            => 'product_id',
                    'rule_price'            => 'MIN(rule_price)',
                    'store_id'            => new Zend_Db_Expr($store->getId()),
                    'latest_start_date'     => 'latest_start_date',
                    'earliest_end_date'     => 'earliest_end_date',
                )
            )
            ->where(new Zend_Db_Expr($this->_connection->getUnixTimestamp('dates.rule_date') . " >= from_time"))
            ->where(
                $this->_connection->getCheckSql(
                    new Zend_Db_Expr('to_time = 0'),
                    new Zend_Db_Expr(1),
                    new Zend_Db_Expr($this->_connection->getUnixTimestamp('dates.rule_date') . " <= to_time")
                )
            )
            ->group(array('customer_group_id', 'product_id', 'dates.rule_date'));

        return $select;
    }
}
