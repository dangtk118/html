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
class Bss_MultiStoreViewPricingPriceIndexer_Model_Resource_Product_Indexer_Price_Downloadable extends Bss_MultiStoreViewPricingPriceIndexer_Model_Resource_Product_Indexer_Price_Default
{
	/**
     * Reindex temporary (price result data) for all products
     *
     * @return Mage_Downloadable_Model_Resource_Indexer_Price
     */
    public function reindexAll()
    {
        $this->useIdxTable(true);
        $this->beginTransaction();
        try {
            $this->_prepareFinalPriceData();
            $this->_applyCustomOption();
            $this->_applyDownloadableLink();
            $this->_movePriceDataToIndexTable();
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
        return $this;
    }

    /**
     * Reindex temporary (price result data) for defined product(s)
     *
     * @param int|array $entityIds
     * @return Mage_Downloadable_Model_Resource_Indexer_Price
     */
    public function reindexEntity($entityIds)
    {
        $this->_prepareFinalPriceData($entityIds);
        $this->_applyCustomOption();
        $this->_applyDownloadableLink();
        $this->_movePriceDataToIndexTable();

        return $this;
    }

    /**
     * Retrieve downloadable links price temporary index table name
     *
     * @see _prepareDefaultFinalPriceTable()
     *
     * @return string
     */
    protected function _getDownloadableLinkPriceTable()
    {
        if ($this->useIdxTable()) {
            return $this->getTable('multistoreviewpricingpriceindexer/downloadable_product_price_indexer_idx');
        }
        return $this->getTable('multistoreviewpricingpriceindexer/downloadable_product_price_indexer_tmp');
    }

    /**
     * Prepare downloadable links price temporary index table
     *
     * @return Mage_Downloadable_Model_Resource_Indexer_Price
     */
    protected function _prepareDownloadableLinkPriceTable()
    {
        $this->_getWriteAdapter()->delete($this->_getDownloadableLinkPriceTable());
        return $this;
    }

    /**
     * Calculate and apply Downloadable links price to index
     *
     * @return Mage_Downloadable_Model_Resource_Indexer_Price
     */
    protected function _applyDownloadableLink()
    {
        $write  = $this->_getWriteAdapter();
        $table  = $this->_getDownloadableLinkPriceTable();

        $this->_prepareDownloadableLinkPriceTable();

        $dlType = $this->_getAttribute('links_purchased_separately');

        $ifPrice = $write->getIfNullSql('dlpw.price_id', 'dlpd.price');

        $select = $write->select()
            ->from(
                array('i' => $this->_getDefaultFinalPriceTable()),
                array('entity_id', 'customer_group_id', 'website_id', 'store_id'))
            ->join(
                array('dl' => $dlType->getBackend()->getTable()),
                "dl.entity_id = i.entity_id AND dl.attribute_id = {$dlType->getAttributeId()}"
                    . " AND dl.store_id = 0",
                array())
            ->join(
                array('dll' => $this->getTable('downloadable/link')),
                'dll.product_id = i.entity_id',
                array())
            ->join(
                array('dlpd' => $this->getTable('downloadable/link_price')),
                'dll.link_id = dlpd.link_id AND dlpd.website_id = 0',
                array())
            ->joinLeft(
                array('dlpw' => $this->getTable('downloadable/link_price')),
                'dlpd.link_id = dlpw.link_id AND dlpw.website_id = i.website_id',
                array())
            ->where('dl.value = ?', 1)
            ->group(array('i.entity_id', 'i.customer_group_id', 'i.website_id', 'i.store_id'))
            ->columns(array(
                'min_price' => new Zend_Db_Expr('MIN('.$ifPrice.')'),
                'max_price' => new Zend_Db_Expr('SUM('.$ifPrice.')')
            ));

        $query = $select->insertFromSelect($table);
        $write->query($query);

        $ifTierPrice = $write->getCheckSql('i.tier_price IS NOT NULL', '(i.tier_price + id.min_price)', 'NULL');
        $ifGroupPrice = $write->getCheckSql('i.group_price IS NOT NULL', '(i.group_price + id.min_price)', 'NULL');

        $select = $write->select()
            ->join(
                array('id' => $table),
                'i.entity_id = id.entity_id AND i.customer_group_id = id.customer_group_id'
                    .' AND i.website_id = id.website_id AND i.store_id = id.store_Id',
                array())
            ->columns(array(
                'min_price'   => new Zend_Db_Expr('i.min_price + id.min_price'),
                'max_price'   => new Zend_Db_Expr('i.max_price + id.max_price'),
                'tier_price'  => new Zend_Db_Expr($ifTierPrice),
                'group_price' => new Zend_Db_Expr($ifGroupPrice),
            ));

        $query = $select->crossUpdateFromSelect(array('i' => $this->_getDefaultFinalPriceTable()));
        $write->query($query);

        $write->delete($table);

        return $this;
    }
}