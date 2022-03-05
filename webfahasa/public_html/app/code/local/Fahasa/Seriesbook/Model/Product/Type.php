<?php

class Fahasa_Seriesbook_Model_Product_Type extends Mage_Catalog_Model_Product_Type_Abstract
{
    const TYPE_SERIESSET_PRODUCT = 'series';
    
    public function prepareForCart(Varien_Object $buyRequest, $product = null)
    {
	// you will process parameters for product before addtocart here
    }

    public function isVirtual($product = null)
    {
	// return True if this product is virtual and false if this product isn't virtual product
	return true;
    }
}