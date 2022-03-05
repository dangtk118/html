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
class Bss_MultiStoreViewPricingCatalogRule_Model_Rule_Product_Price extends Mage_CatalogRule_Model_Rule_Product_Price
{
	/**
     * Apply price rule price to price index table
     *
     * @param Varien_Db_Select $select
     * @param array|string $indexTable
     * @param string $entityId
     * @param string $customerGroupId
     * @param string $websiteId
     * @param array $updateFields       the array fields for compare with rule price and update
     * @param string $websiteDate
     * @return Mage_CatalogRule_Model_Rule_Product_Price
     */
    public function applyPriceRuleToIndexTable(Varien_Db_Select $select, $indexTable, $entityId, $customerGroupId,
        $websiteId, $updateFields, $websiteDate, $storeId = null)
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::applyPriceRuleToIndexTable($select, $indexTable, $entityId, $customerGroupId,
        $websiteId, $updateFields, $websiteDate);

        $this->_getResource()->applyPriceRuleToIndexTable($select, $indexTable, $entityId, $customerGroupId, $websiteId,
            $updateFields, $websiteDate, $storeId);

        return $this;
    }
}