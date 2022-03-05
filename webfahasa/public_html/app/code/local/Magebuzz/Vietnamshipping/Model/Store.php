<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Store extends Mage_SalesRule_Model_Rule {  
        
        protected function getModuleStr() { 
            return "vietnamshipping";
        }      
        
	public function _construct() {
		parent::_construct();
		$this->_init($this->getModuleStr() . '/store');
	}
   
}