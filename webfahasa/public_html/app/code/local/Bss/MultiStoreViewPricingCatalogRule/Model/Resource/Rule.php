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
class Bss_MultiStoreViewPricingCatalogRule_Model_Resource_Rule extends Mage_CatalogRule_Model_Resource_Rule
{
	/**
     * Get catalog rules product price for specific date, website and
     * customer group
     *
     * @param int|string $date
     * @param int $wId
     * @param int $gId
     * @param int $pId
     *
     * @return float|bool
     */
    public function getRulePrice($date, $wId, $gId, $pId, $sId = null)
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::getRulePrice($date, $wId, $gId, $pId);

        $data = $this->getRulePrices($date, $wId, $gId, array($pId), $sId);
        if (isset($data[$pId])) {
            return $data[$pId];
        }

        return false;
    }

    /**
     * Retrieve product prices by catalog rule for specific date, website and customer group
     * Collect data with  product Id => price pairs
     *
     * @param int|string $date
     * @param int $websiteId
     * @param int $customerGroupId
     * @param array $productIds
     *
     * @return array
     */
    public function getRulePrices($date, $websiteId, $customerGroupId, $productIds, $storeId = null)
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return parent::getRulePrices($date, $websiteId, $customerGroupId, $productIds);
        
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('multistoreviewpricingcatalogrule/rule_product_price'), array('product_id', 'rule_price'))
            ->where('rule_date = ?', $this->formatDate($date, false))
            ->where('store_id = ?', $storeId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('product_id IN(?)', $productIds);
        return $adapter->fetchPairs($select);
    }
}