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
class Bss_MultiStoreViewPricingPriceIndexer_Model_Resource_Product_Indexer_Price_Default extends Mage_Catalog_Model_Resource_Product_Indexer_Price_Default
{
	/**
     * Define main price index table
     *
     */
    protected function _construct()
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_construct();

        $this->_init('multistoreviewpricingpriceindexer/product_index_price', 'entity_id');
    }

    /**
     * Retrieve final price temporary index table name
     *
     * @see _prepareDefaultFinalPriceTable()
     *
     * @return string
     */
    protected function _getDefaultFinalPriceTable()
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_getDefaultFinalPriceTable();

        if ($this->useIdxTable()) {
            return $this->getTable('multistoreviewpricingpriceindexer/product_price_indexer_final_idx');
        }
        return $this->getTable('multistoreviewpricingpriceindexer/product_price_indexer_final_tmp');
    }

    /**
     * Prepare products default final price in temporary index table
     *
     * @param int|array $entityIds  the entity ids limitation
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Price_Default
     */
    protected function _prepareFinalPriceData($entityIds = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_prepareFinalPriceData($entityIds);

        $this->_prepareDefaultFinalPriceTable();

        $write  = $this->_getWriteAdapter();
        $select = $write->select()
            ->from(array('e' => $this->getTable('catalog/product')), array('entity_id'))
            ->join(
                array('cg' => $this->getTable('customer/customer_group')),
                '',
                array('customer_group_id'))
            ->join(
                array('cw' => $this->getTable('core/website')),
                '',
                array('website_id'))
            ->join(
                array('cwd' => $this->_getWebsiteDateTable()),
                'cw.website_id = cwd.website_id',
                array())
            ->join(
                array('csg' => $this->getTable('core/store_group')),
                'csg.website_id = cw.website_id AND cw.default_group_id = csg.group_id',
                array())
            ->join(
                array('cs' => $this->getTable('core/store')),
                'cs.store_id != 0 and cs.is_active = 1',
                array('store_id'))
            ->join(
                array('pw' => $this->getTable('catalog/product_website')),
                'pw.product_id = e.entity_id AND pw.website_id = cw.website_id',
                array())
            ->joinLeft(
                array('tp' => $this->_getTierPriceIndexTable()),
                'tp.entity_id = e.entity_id AND tp.website_id = cw.website_id AND tp.store_id = cs.store_id'
                    . ' AND tp.customer_group_id = cg.customer_group_id',
                array())
            ->joinLeft(
                array('gp' => $this->_getGroupPriceIndexTable()),
                'gp.entity_id = e.entity_id AND gp.website_id = cw.website_id AND gp.store_id = cs.store_id'
                    . ' AND gp.customer_group_id = cg.customer_group_id',
                array())
            ->where('e.type_id = ?', $this->getTypeId());

        // add enable products limitation
        $statusCond = $write->quoteInto('=?', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $this->_addAttributeToSelect($select, 'status', 'e.entity_id', 'cs.store_id', $statusCond, true);
        if (Mage::helper('core')->isModuleEnabled('Mage_Tax')) {
            $taxClassId = $this->_addAttributeToSelect($select, 'tax_class_id', 'e.entity_id', 'cs.store_id');
        } else {
            $taxClassId = new Zend_Db_Expr('0');
        }
        $select->columns(array('tax_class_id' => $taxClassId));

        $price          = $this->_addAttributeToSelect($select, 'price', 'e.entity_id', 'cs.store_id');
        $specialPrice   = $this->_addAttributeToSelect($select, 'special_price', 'e.entity_id', 'cs.store_id');
        $specialFrom    = $this->_addAttributeToSelect($select, 'special_from_date', 'e.entity_id', 'cs.store_id');
        $specialTo      = $this->_addAttributeToSelect($select, 'special_to_date', 'e.entity_id', 'cs.store_id');
        $currentDate    = $write->getDatePartSql('cwd.website_date');
        $groupPrice     = $write->getCheckSql('gp.price IS NULL', "{$price}", 'gp.price');

        $specialFromDate    = $write->getDatePartSql($specialFrom);
        $specialToDate      = $write->getDatePartSql($specialTo);

        $specialFromUse     = $write->getCheckSql("{$specialFromDate} <= {$currentDate}", '1', '0');
        $specialToUse       = $write->getCheckSql("{$specialToDate} >= {$currentDate}", '1', '0');
        $specialFromHas     = $write->getCheckSql("{$specialFrom} IS NULL", '1', "{$specialFromUse}");
        $specialToHas       = $write->getCheckSql("{$specialTo} IS NULL", '1', "{$specialToUse}");
        $finalPrice         = $write->getCheckSql("{$specialFromHas} > 0 AND {$specialToHas} > 0"
            . " AND {$specialPrice} < {$price}", $specialPrice, $price);
        $finalPrice         = $write->getCheckSql("{$groupPrice} < {$finalPrice}", $groupPrice, $finalPrice);

        $select->columns(array(
            'orig_price'       => $price,
            'price'            => $finalPrice,
            'min_price'        => $finalPrice,
            'max_price'        => $finalPrice,
            'tier_price'       => new Zend_Db_Expr('tp.min_price'),
            'base_tier'        => new Zend_Db_Expr('tp.min_price'),
            'group_price'      => new Zend_Db_Expr('gp.price'),
            'base_group_price' => new Zend_Db_Expr('gp.price'),
        ));

        if (!is_null($entityIds)) {
            $select->where('e.entity_id IN(?)', $entityIds);
        }

        /**
         * Add additional external limitation
         */
        Mage::dispatchEvent('prepare_catalog_product_index_select', array(
            'select'        => $select,
            'entity_field'  => new Zend_Db_Expr('e.entity_id'),
            'website_field' => new Zend_Db_Expr('cw.website_id'),
            'store_field'   => new Zend_Db_Expr('cs.store_id')
        ));

        $query = $select->insertFromSelect($this->_getDefaultFinalPriceTable(), array(), false);
        $write->query($query);

        /**
         * Add possibility modify prices from external events
         */
        $select = $write->select()
            ->join(array('wd' => $this->_getWebsiteDateTable()),
                'i.website_id = wd.website_id',
                array());
        Mage::dispatchEvent('prepare_catalog_product_price_index_table', array(
            'index_table'       => array('i' => $this->_getDefaultFinalPriceTable()),
            'select'            => $select,
            'entity_id'         => 'i.entity_id',
            'customer_group_id' => 'i.customer_group_id',
            'website_id'        => 'i.website_id',
            'website_date'      => 'wd.website_date',
            'update_fields'     => array('price', 'min_price', 'max_price'),
            'store_id'          => 'i.store_id'
        ));

        return $this;
    }

    /**
     * Mode Final Prices index to primary temporary index table
     *
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Price_Default
     */
    protected function _movePriceDataToIndexTable()
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_movePriceDataToIndexTable();

        $columns = array(
            'entity_id'         => 'entity_id',
            'customer_group_id' => 'customer_group_id',
            'website_id'        => 'website_id',
            'store_id'        => 'store_id',
            'tax_class_id'      => 'tax_class_id',
            'price'             => 'orig_price',
            'final_price'       => 'price',
            'min_price'         => 'min_price',
            'max_price'         => 'max_price',
            'tier_price'        => 'tier_price',
            'group_price'       => 'group_price',
        );

        $write  = $this->_getWriteAdapter();
        $table  = $this->_getDefaultFinalPriceTable();
        $select = $write->select()
            ->from($table, $columns);

        $query = $select->insertFromSelect($this->getIdxTable(), array(), false);
        $write->query($query);

        $write->delete($table);

        return $this;
    }

    /**
     * Retrieve table name for product tier price index
     *
     * @return string
     */
    protected function _getTierPriceIndexTable()
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_getTierPriceIndexTable();

        return $this->getTable('multistoreviewpricingpriceindexer/product_index_tier_price');
    }

    /**
     * Retrieve table name for product group price index
     *
     * @return string
     */
    protected function _getGroupPriceIndexTable()
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_getGroupPriceIndexTable();

        return $this->getTable('multistoreviewpricingpriceindexer/product_index_group_price');
    }

    /**
     * Retrieve temporary index table name
     *
     * @param string $table
     * @return string
     */
    public function getIdxTable($table = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::getIdxTable($table);
        
        if ($this->useIdxTable()) {
            return $this->getTable('multistoreviewpricingpriceindexer/product_price_indexer_idx');
        }
        return $this->getTable('multistoreviewpricingpriceindexer/product_price_indexer_tmp');
    }
}