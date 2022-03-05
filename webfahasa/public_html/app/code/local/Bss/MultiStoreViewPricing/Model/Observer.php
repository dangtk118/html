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
class Bss_MultiStoreViewPricing_Model_Observer {
	public function applyLimitations($observer) {
		if(!Mage::helper('multistoreviewpricing')->isScopePrice())
            return $this;

        $collection = $observer->getCollection();
        $fromPart = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
        if (isset($fromPart['price_index'])) {
        	$indexTable = $collection->getTable('catalog/product_index_price');
        	$indexTableStore = $collection->getTable('multistoreviewpricingpriceindexer/product_index_price');

        	$select = $collection->getSelect();
        	if($fromPart['price_index']['tableName'] == $indexTable) {
        		$fromPart['price_index']['tableName'] = $indexTableStore;
        		$select->setPart(Zend_Db_Select::FROM, $fromPart);
        		$select->where('price_index.store_id = ?', $collection->getStoreId());
        	}
        }
	}
}

