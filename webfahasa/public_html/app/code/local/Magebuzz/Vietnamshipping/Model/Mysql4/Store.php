<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Mysql4_Store extends Mage_Core_Model_Mysql4_Abstract {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }     
        
	public function _construct() {    
		// Note that the vietnamshipping_id refers to the key field in your database table.
		$this->_init($this->getModuleStr() . '/store', 'vietnamshipping_store_id');
	}
}