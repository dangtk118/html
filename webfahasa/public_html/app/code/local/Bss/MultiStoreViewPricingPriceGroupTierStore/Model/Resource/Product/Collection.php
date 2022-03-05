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
class Bss_MultiStoreViewPricingPriceGroupTierStore_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
	/**
     * Add tier price data to loaded items
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function addTierPriceData()
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
    		return parent::addTierPriceData();

        if ($this->getFlag('tier_price_added')) {
            return $this;
        }

        $tierPrices = array();
        $productIds = array();
        $productIdsDefault = array();
        $storeId = $this->getStoreId();
        foreach ($this->getItems() as $item) {
        	$use_tier_default = true;
	        $tier_default = Mage::helper('multistoreviewpricingpricegrouptierstore')->getTierPriceOption($storeId);
	        if($tier_default == 1) {
	            $use_tier_default = false;
	        }else {
	            $tier_default = Mage::getModel('multistoreviewpricingpricegrouptierstore/tierDefault')->getCollection()
	            ->addFieldToSelect('*')
	            ->addFieldToFilter('product_id', $item->getId())
	            ->addFieldToFilter('store_id', $storeId)
                ->addFieldToFilter('status', 0)
	            ->getFirstItem();
	            if($tier_default && $tier_default->getId() != '') {
	                $use_tier_default = false;
	            }
	        }

	        if($use_tier_default) {
	        	$productIdsDefault[] = $item->getId();
	        }else {
	        	$productIds[] = $item->getId();
	        }

            $tierPrices[$item->getId()] = array();
        }
        if (!$productIds) {
            return $this;
        }

        /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $attribute = $this->getAttribute('tier_price');
        if ($attribute->isScopeGlobal()) {
            $websiteId = 0;
        } else if ($this->getStoreId()) {
            $websiteId = Mage::app()->getStore($this->getStoreId())->getWebsiteId();
        }

        $adapter   = $this->getConnection();
        $columns   = array(
            'price_id'      => 'value_id',
            'store_id'    => 'store_id',
            'all_groups'    => 'all_groups',
            'cust_group'    => 'customer_group_id',
            'price_qty'     => 'qty',
            'price'         => 'value',
            'product_id'    => 'entity_id'
        );
        $select  = $adapter->select()
            ->from($this->getTable('multistoreviewpricingpricegrouptierstore/tier_price'), $columns)
            ->where('entity_id IN(?)', $productIds)
            ->order(array('entity_id','qty'));

        $select->where('store_id = ?', $storeId);

        // if ($websiteId == '0') {
        //     $select->where('website_id = ?', $websiteId);
        // } else {
        //     $select->where('website_id IN(?)', array('0', $websiteId));
        // }

        foreach ($adapter->fetchAll($select) as $row) {
            $tierPrices[$row['product_id']][] = array(
                'website_id'    => $websiteId,
                'cust_group'    => $row['all_groups'] ? Mage_Customer_Model_Group::CUST_GROUP_ALL : $row['cust_group'],
                'price_qty'     => $row['price_qty'],
                'price'         => $row['price'],
                'website_price' => $row['price'],

            );
        }

        /* @var $backend Mage_Catalog_Model_Product_Attribute_Backend_Tierprice */
        $backend = $attribute->getBackend();

        foreach ($this->getItems() as $item) {
            $data = $tierPrices[$item->getId()];
            if (!empty($data) && $websiteId) {
                $data = $backend->preparePriceData($data, $item->getTypeId(), $websiteId);
            }
            $item->setData('tier_price', $data);
        }

        $this->setFlag('tier_price_added', true);
        return $this;
    }
}
