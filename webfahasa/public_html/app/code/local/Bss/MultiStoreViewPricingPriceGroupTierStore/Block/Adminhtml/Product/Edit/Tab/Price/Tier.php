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
class Bss_MultiStoreViewPricingPriceGroupTierStore_Block_Adminhtml_Product_Edit_Tab_Price_Tier extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Tier
{
	public function __construct()
    {
    	if(!Mage::helper('multistoreviewpricing')->isScopePrice())
    		return parent::__construct();

        $store = Mage::app()->getRequest()->getParam('store',false);
        if($store) {
            $this->setTemplate('bss/multistoreviewpricing/catalog/product/edit/price/tier_default.phtml');
        }else {
            parent::__construct();
        }
        
    }
}
