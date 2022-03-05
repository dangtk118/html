<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Mysql4_Province extends Mage_Core_Model_Mysql4_Abstract {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }     
        
	public function _construct() {    
		// Note that the vietnamshipping_id refers to the key field in your database table.
		$this->_init($this->getModuleStr() . '/province', 'province_id');
	}
}