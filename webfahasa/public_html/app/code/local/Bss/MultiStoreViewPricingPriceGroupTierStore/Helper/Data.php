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
class Bss_MultiStoreViewPricingPriceGroupTierStore_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function checkProductAdmin() {
		if(Mage::app()->getRequest()->getModuleName(). '_' . Mage::app()->getRequest()->getControllerName() == 'admin_catalog_product') return true;

		return false;
	}

	public function getTierPriceOption($store = false) {
		if($store) {
			return Mage::getStoreConfig('multistoreviewpricing/general/tier_price', $store);
		}
		return Mage::getStoreConfig('multistoreviewpricing/general/tier_price');
	}
}
	 