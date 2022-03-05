<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Rule extends Mage_SalesRule_Model_Rule {  
    const BY_FIXED_INCREASE_ACTION = 'cart_fixed_increase';
    
    
    const NO_ACCEPT_APPLY_WHOLE_AMOUNT = 0;
    const ACCEPT_APPLY_DEFAULT_AMOUNT = 1;
    const ACCEPT_APPLY_COUPON_WHOLE_AMOUNT = 2;

    protected function getModuleStr() { 
            return "vietnamshipping";
        }
        
	public function _construct() {
		parent::_construct();
		$this->_init($this->getModuleStr() . '/rule');
	}
  public function toOptionArray() {   
    return array(
      array('value' => 'free', 'label'=>Mage::helper('adminhtml')->__('Free Shipping')),
      array('value' => 'discount_shipping_normal', 'label'=>Mage::helper('adminhtml')->__('Discount Shipping Normal')),
      array('value' => 'discount_shipping_sameday', 'label'=>Mage::helper('adminhtml')->__('Discount Shipping Sameday')),
      array('value' => 'discount_shipping_express', 'label'=>Mage::helper('adminhtml')->__('Discount Shipping Express'))
      );
  }  
}