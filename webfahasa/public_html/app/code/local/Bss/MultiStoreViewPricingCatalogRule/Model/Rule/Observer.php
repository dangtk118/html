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
class Bss_MultiStoreViewPricingCatalogRule_Model_Rule_Observer extends Mage_CatalogRule_Model_Observer
{
	/**
     * Apply catalog price rules to product on frontend
     *
     * @param   Varien_Event_Observer $observer
     *
     * @return  Mage_CatalogRule_Model_Observer
     */
    public function processFrontFinalPrice($observer)
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::processFrontFinalPrice($observer);

        $product    = $observer->getEvent()->getProduct();
        $pId        = $product->getId();
        $storeId    = $product->getStoreId();

        if ($observer->hasDate()) {
            $date = $observer->getEvent()->getDate();
        } else {
            $date = Mage::app()->getLocale()->storeTimeStamp($storeId);
        }

        if ($observer->hasWebsiteId()) {
            $wId = $observer->getEvent()->getWebsiteId();
        } else {
            $wId = Mage::app()->getStore($storeId)->getWebsiteId();
        }

        if ($observer->hasCustomerGroupId()) {
            $gId = $observer->getEvent()->getCustomerGroupId();
        } elseif ($product->hasCustomerGroupId()) {
            $gId = $product->getCustomerGroupId();
        } else {
            $gId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        }

        $key = $this->_getRulePricesKey(array($date, $wId, $gId, $pId, $storeId));
        if (!isset($this->_rulePrices[$key])) {
            $rulePrice = Mage::getResourceModel('catalogrule/rule')
                ->getRulePrice($date, $wId, $gId, $pId, $storeId);
            $this->_rulePrices[$key] = $rulePrice;
        }
        if ($this->_rulePrices[$key]!==false) {
            $finalPrice = min($product->getData('final_price'), $this->_rulePrices[$key]);
            $product->setFinalPrice($finalPrice);
        }
        return $this;
    }

    /**
     * Apply catalog price rules to product in admin
     *
     * @param   Varien_Event_Observer $observer
     *
     * @return  Mage_CatalogRule_Model_Observer
     */
    public function processAdminFinalPrice($observer)
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::processAdminFinalPrice($observer);
        
        $product = $observer->getEvent()->getProduct();
        $storeId = $product->getStoreId();
        $date = Mage::app()->getLocale()->storeDate($storeId);
        $key = false;

        if ($ruleData = Mage::registry('rule_data')) {
            $wId = $ruleData->getWebsiteId();
            $gId = $ruleData->getCustomerGroupId();
            $pId = $product->getId();

            $key = $this->_getRulePricesKey(array($date, $wId, $gId, $pId, $storeId));
        }
        elseif (!is_null($storeId) && !is_null($product->getCustomerGroupId())) {
            $wId = Mage::app()->getStore($storeId)->getWebsiteId();
            $gId = $product->getCustomerGroupId();
            $pId = $product->getId();
            $key = $this->_getRulePricesKey(array($date, $wId, $gId, $pId, $storeId));
        }

        if ($key) {
            if (!isset($this->_rulePrices[$key])) {
                $rulePrice = Mage::getResourceModel('catalogrule/rule')
                    ->getRulePrice($date, $wId, $gId, $pId, $storeId);
                $this->_rulePrices[$key] = $rulePrice;
            }
            if ($this->_rulePrices[$key]!==false) {
                $finalPrice = min($product->getData('final_price'), $this->_rulePrices[$key]);
                $product->setFinalPrice($finalPrice);
            }
        }

        return $this;
    }

    /**
     * Calculate minimal final price with catalog rule price
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_CatalogRule_Model_Observer
     */
    public function prepareCatalogProductPriceIndexTable(Varien_Event_Observer $observer)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::prepareCatalogProductPriceIndexTable($observer);

        $select             = $observer->getEvent()->getSelect();

        $indexTable         = $observer->getEvent()->getIndexTable();
        $entityId           = $observer->getEvent()->getEntityId();
        $customerGroupId    = $observer->getEvent()->getCustomerGroupId();
        $websiteId          = $observer->getEvent()->getWebsiteId();
        $websiteDate        = $observer->getEvent()->getWebsiteDate();
        $updateFields       = $observer->getEvent()->getUpdateFields();
        $storeId            = $observer->getEvent()->getStoreId();

        Mage::getSingleton('catalogrule/rule_product_price')
            ->applyPriceRuleToIndexTable($select, $indexTable, $entityId, $customerGroupId, $websiteId,
                $updateFields, $websiteDate, $storeId);

        return $this;
    }

    public function prepareCatalogProductCollectionPrices(Varien_Event_Observer $observer)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::prepareCatalogProductCollectionPrices($observer);

        /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $collection = $observer->getEvent()->getCollection();
        $store      = Mage::app()->getStore($observer->getEvent()->getStoreId());
        $storeId = $store->getId();
        $websiteId  = $store->getWebsiteId();
        if ($observer->getEvent()->hasCustomerGroupId()) {
            $groupId = $observer->getEvent()->getCustomerGroupId();
        } else {
            /* @var $session Mage_Customer_Model_Session */
            $session = Mage::getSingleton('customer/session');
            if ($session->isLoggedIn()) {
                $groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
            } else {
                $groupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
            }
        }
        if ($observer->getEvent()->hasDate()) {
            $date = $observer->getEvent()->getDate();
        } else {
            $date = Mage::app()->getLocale()->storeTimeStamp($store);
        }

        $productIds = array();
        /* @var $product Mage_Core_Model_Product */
        foreach ($collection as $product) {
            $key = $this->_getRulePricesKey(array($date, $websiteId, $groupId, $product->getId(), $storeId));
            if (!isset($this->_rulePrices[$key])) {
                $productIds[] = $product->getId();
            }
        }

        if ($productIds) {
            $rulePrices = Mage::getResourceModel('catalogrule/rule')
                ->getRulePrices($date, $websiteId, $groupId, $productIds, $storeId);
            foreach ($productIds as $productId) {
                $key = $this->_getRulePricesKey(array($date, $websiteId, $groupId, $productId, $storeId));
                $this->_rulePrices[$key] = isset($rulePrices[$productId]) ? $rulePrices[$productId] : false;
            }
        }

        return $this;
    }

    /**
     * Preload all price rules for all items in quote
     *
     * @param   Varien_Event_Observer $observer
     *
     * @return  Mage_CatalogRule_Model_Observer
     */
    public function preloadPriceRules(Varien_Event_Observer $observer)
    {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::preloadPriceRules($observer);
        
        $quote = $observer->getQuote();
        $date = Mage::app()->getLocale()->storeTimeStamp($quote->getStoreId());
        $wId = $quote->getStore()->getWebsiteId();
        $gId = $quote->getCustomerGroupId();
        $sId = $quote->getStoreId();

        $productIds = array();
        foreach ($quote->getAllItems() as $item) {
            $productIds[] = $item->getProductId();
        }

        $cacheKey = spl_object_hash($quote);

        if (!isset($this->_preloadedPrices[$cacheKey])) {
            $this->_preloadedPrices[$cacheKey] = Mage::getResourceSingleton('catalogrule/rule')
                 ->getRulePrices($date, $wId, $gId, $productIds, $sId);
        }

        foreach ($this->_preloadedPrices[$cacheKey] as $pId => $price) {
            $key = $this->_getRulePricesKey(array($date, $wId, $gId, $pId, $sId));
            $this->_rulePrices[$key] = $price;
        }

        return $this;
    }

    /**
     * Generate key for rule prices
     *
     * @param array
     */
    protected function _getRulePricesKey($keyInfo)
    {
        return implode('|', $keyInfo);
    }
}
