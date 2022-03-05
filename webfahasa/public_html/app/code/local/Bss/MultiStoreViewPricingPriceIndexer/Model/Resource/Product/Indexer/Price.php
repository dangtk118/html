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
class Bss_MultiStoreViewPricingPriceIndexer_Model_Resource_Product_Indexer_Price extends Mage_Catalog_Model_Resource_Product_Indexer_Price
{
	/**
     * Define main index table
     *
     */
    protected function _construct()
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_construct();

        $this->_init('multistoreviewpricingpriceindexer/product_index_price', 'entity_id');
    }

    /**
     * Retrieve temporary index table name
     *
     * @param unknown_type $table
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
     * Prepare tier price index table
     *
     * @param int|array $entityIds the entity ids limitation
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Price
     */
    protected function _prepareTierPriceIndex($entityIds = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_prepareTierPriceIndex($entityIds);

        $write = $this->_getWriteAdapter();
        $table = $this->_getTierPriceIndexTable();
        if (!empty($entityIds)) {
            $select  = $write->select()
                ->from(array('i' => $table), null)
                ->where('i.entity_id IN(?)', $entityIds);
            $query   = $select->deleteFromSelect('i');
            $write->query($query);
        }else {
            $write->delete($table);
        }

        $tier_default_admin = Mage::helper('multistoreviewpricingpricegrouptierstore')->getTierPriceOption(0);

        $websiteExpression = $write->getCheckSql('tp.website_id = 0', 'ROUND(tp.value * cwd.rate, 4)', 'tp.value');
        $select = $write->select()
            ->from(
                array('tp' => $this->getValueTable('catalog/product', 'tier_price')),
                array('entity_id'))
            ->join(
                array('cg' => $this->getTable('customer/customer_group')),
                'tp.all_groups = 1 OR (tp.all_groups = 0 AND tp.customer_group_id = cg.customer_group_id)',
                array('customer_group_id'))
            ->join(
                array('cw' => $this->getTable('core/website')),
                'tp.website_id = 0 OR tp.website_id = cw.website_id',
                array('website_id'))
            ->join(
                array('cs' => $this->getTable('core/store')),
                'cs.website_id = cw.website_id',
                array('store_id'))
            ->join(
                array('cwd' => $this->_getWebsiteDateTable()),
                'cw.website_id = cwd.website_id',
                array())
            ->joinLeft(
                array('tier_config' => $this->getTable('core/config_data')),
                'tier_config.scope_id = cs.store_id AND tier_config.scope = "stores" AND tier_config.path = "multistoreviewpricing/general/tier_price"',
                array())
            ->where('cw.website_id != 0')
            ->where("IFNULL(tier_config.value, $tier_default_admin) != 1")
            ->columns(new Zend_Db_Expr("MIN({$websiteExpression})"))
            ->group(array('tp.entity_id', 'cg.customer_group_id', 'cw.website_id', 'cs.store_id'));

        if (!empty($entityIds)) {
            $select->where('tp.entity_id IN(?)', $entityIds);
        }

        $query = $select->insertFromSelect($table);
        $write->query($query);

        //update tier price for store view
        $websiteExpression = 
            $write->getCheckSql(
                'tp_default.status = 1',
                'null',
                'tp.value'
            );
        $select = $write->select()
            ->from(
                array('tp' => $this->getTable('multistoreviewpricingpricegrouptierstore/tier_price')),
                array('entity_id'))
            ->join(
                array('cg' => $this->getTable('customer/customer_group')),
                'tp.all_groups = 1 OR (tp.all_groups = 0 AND tp.customer_group_id = cg.customer_group_id)',
                array('customer_group_id'))
            ->join(
                array('cw' => $this->getTable('core/store')),
                'tp.store_id = cw.store_id',
                array('website_id'))
            ->join(
                array('cwd' => $this->_getWebsiteDateTable()),
                'cw.website_id = cwd.website_id',
                array())
            ->joinLeft(
                array('tp_default' => $this->getTable('multistoreviewpricingpricegrouptierstore/tierDefault')),
                'tp_default.product_id = tp.entity_id AND tp_default.store_id = tp.store_id',
                array())
            ->columns('store_id', 'tp')
            ->columns(array('price' => new Zend_Db_Expr("MIN({$websiteExpression})")))
            ->where('cw.website_id != 0')
            ->having('price is not null')
            ->group(array('tp.entity_id', 'cg.customer_group_id', 'cw.website_id', 'tp.store_id'));

        if (!empty($entityIds)) {
            $select->where('tp.entity_id IN(?)', $entityIds);
        }

        $query = $select->insertFromSelect($table);
        $write->query($query);

        return $this;
    }

    /**
     * Prepare group price index table
     *
     * @param int|array $entityIds the entity ids limitation
     * @return Mage_Catalog_Model_Resource_Product_Indexer_Price
     */
    protected function _prepareGroupPriceIndex($entityIds = null)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) 
            return parent::_prepareGroupPriceIndex($entityIds);

        $write = $this->_getWriteAdapter();
        $table = $this->_getGroupPriceIndexTable();
        
        if (!empty($entityIds)) {
            $select  = $write->select()
                ->from(array('i' => $table), null)
                ->where('i.entity_id IN(?)', $entityIds);
            $query   = $select->deleteFromSelect('i');
            $write->query($query);
        }else {
            $write->delete($table);
        }
        

        $websiteExpression = $write->getCheckSql('gp.website_id = 0', 'ROUND(gp.value * cwd.rate, 4)', 'gp.value');

        $select = $write->select()
            ->from(
                array('gp' => $this->getValueTable('catalog/product', 'group_price')),
                array('entity_id'))
            ->join(
                array('cg' => $this->getTable('customer/customer_group')),
                'gp.all_groups = 1 OR (gp.all_groups = 0 AND gp.customer_group_id = cg.customer_group_id)',
                array('customer_group_id'))
            ->join(
                array('cw' => $this->getTable('core/website')),
                'gp.website_id = 0 OR gp.website_id = cw.website_id',
                array('website_id'))
            ->join(
                array('cs' => $this->getTable('core/store')),
                'cs.website_id = cw.website_id',
                array('store_id'))
            ->join(
                array('cwd' => $this->_getWebsiteDateTable()),
                'cw.website_id = cwd.website_id',
                array())
            ->where('cw.website_id != 0')
            ->columns(new Zend_Db_Expr("MIN({$websiteExpression})"))
            ->group(array('gp.entity_id', 'cg.customer_group_id', 'cw.website_id', 'cs.store_id'));

        if (!empty($entityIds)) {
            $select->where('gp.entity_id IN(?)', $entityIds);
        }

        $query = $select->insertFromSelect($table);
        $write->query($query);

        //update group price for store view
        $select = $write->select()
            ->from(
                array('gp' => $this->getTable('multistoreviewpricingpricegrouptierstore/group_price')),
                array('entity_id'))
            ->join(
                array('cg' => $this->getTable('customer/customer_group')),
                'gp.all_groups = 1 OR (gp.all_groups = 0 AND gp.customer_group_id = cg.customer_group_id)',
                array('customer_group_id'))
            ->join(
                array('cw' => $this->getTable('core/store')),
                'gp.store_id = cw.store_id',
                array('website_id'))
            ->join(
                array('cwd' => $this->_getWebsiteDateTable()),
                'cw.website_id = cwd.website_id',
                array())
            ->columns('store_id', 'gp')
            ->columns('value', 'gp')
            ->where('cw.website_id != 0')
            ->group(array('gp.entity_id', 'cg.customer_group_id', 'cw.website_id', 'gp.store_id'));

        if (!empty($entityIds)) {
            $select->where('gp.entity_id IN(?)', $entityIds);
        }

        $query = $select->insertFromSelect($table);
        $write->query($query);

        return $this;
    }
}
