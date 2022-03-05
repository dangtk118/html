<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Shippingweight extends Mage_Core_Model_Abstract {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }
        
	public function _construct() {
		parent::_construct();
		$this->_init($this->getModuleStr() . '/shippingweight');
	}
}