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
class Bss_MultiStoreViewPricingPriceGroupTierStore_Model_Observer
{
	public function rendererAttributes($observer) {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) return;
        
		$form = $observer->getEvent()->getForm();
		$tierPrice = $form->getElement('tier_price_for_store');
		$groupPrice = $form->getElement('group_price_for_store');
		if ($tierPrice) {
			if(Mage::registry('current_product') && Mage::registry('current_product')->getTypeId() == 'bundle') {
				$tierPrice->setRenderer(
					Mage::app()->getLayout()->createBlock('multistoreviewpricingpricegrouptierstore/catalog_product_edit_tab_price_tier')
					->setPriceValidation('validate-greater-than-zero validate-percents')
					->setPriceColumnHeader(Mage::helper('bundle')->__('Percent Discount'))
					);
			}else {
				$tierPrice->setRenderer(
					Mage::app()->getLayout()->createBlock('multistoreviewpricingpricegrouptierstore/catalog_product_edit_tab_price_tier')
					);
			}
		}

		if ($groupPrice) {
			if(Mage::registry('current_product') && Mage::registry('current_product')->getTypeId() == 'bundle') {
				$groupPrice->setRenderer(
					Mage::app()->getLayout()->createBlock('multistoreviewpricingpricegrouptierstore/catalog_product_edit_tab_price_group')
					->setIsPercent(true)
					->setPriceValidation('validate-greater-than-zero validate-percents')
					->setPriceColumnHeader(Mage::helper('bundle')->__('Percent Discount'))
					);
			}else {
				$groupPrice->setRenderer(
					Mage::app()->getLayout()->createBlock('multistoreviewpricingpricegrouptierstore/catalog_product_edit_tab_price_group')
					);
			}
		}
	}

	public function saveProductAfter($observer) {
        if(!Mage::helper('multistoreviewpricing')->isScopePrice()) return;

		$product = $observer->getEvent()->getProduct();
		$store = Mage::app()->getRequest()->getParam('store',0);
		$tier_default = Mage::helper('multistoreviewpricingpricegrouptierstore')->getTierPriceOption($store);
		if ($tier_default != 1 && $store > 0 && $product && $product->getId() > 0) {

			$default_value_tier = Mage::app()->getRequest()->getParam('tier_price_store_view_default',0);
			$model = Mage::getModel('multistoreviewpricingpricegrouptierstore/tierDefault')->getCollection()
			->addFieldToSelect('*')
			->addFieldToFilter('product_id', $product->getId())
			->addFieldToFilter('store_id', $store)
			->getFirstItem();

			$model->setProductId($product->getId())
			->setStoreId($store)
			->setStatus($default_value_tier)
			->save();
		}
	}
}
