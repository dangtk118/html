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
class Bss_MultiStoreViewPricingPriceGroupTierStore_Model_Product_Rewrite_Attribute_Backend_Groupprice extends Mage_Catalog_Model_Product_Attribute_Backend_Groupprice
{
	public function afterLoad($object)
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
    		return parent::afterLoad($object);
    	
        $storeId   = $object->getStoreId();
        $websiteId = null;
        if ($this->getAttribute()->isScopeGlobal()) {
            $websiteId = 0;
        } else if ($storeId) {
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
        }

        $data = $this->_getResource()->loadPriceData($object->getId(), $websiteId);
        foreach ($data as $k => $v) {
            $data[$k]['website_price'] = $v['price'];
            if ($v['all_groups']) {
                $data[$k]['cust_group'] = Mage_Customer_Model_Group::CUST_GROUP_ALL;
            }
        }

        if(!Mage::helper('multistoreviewpricingpricegrouptierstore')->checkProductAdmin()) {
            $groups = Mage::getResourceSingleton('multistoreviewpricingpricegrouptierstore/product_attribute_backend_groupprice')->getPriceStoreData($object->getId(), $storeId , $object->getCustomerGroupId());
            if(count($groups) > 0) {
                foreach($groups as $group) {
                    $data = array();
                    $group['website_price'] = $group['price'];
                    $data[] = $group;
                }
            }
        }

        if (!$object->getData('_edit_mode') && $websiteId) {
            $data = $this->preparePriceData($data, $object->getTypeId(), $websiteId);
        }

        $object->setData($this->getAttribute()->getName(), $data);
        $object->setOrigData($this->getAttribute()->getName(), $data);

        $valueChangedKey = $this->getAttribute()->getName() . '_changed';
        $object->setOrigData($valueChangedKey, 0);
        $object->setData($valueChangedKey, 0);

        return $this;
    }
}