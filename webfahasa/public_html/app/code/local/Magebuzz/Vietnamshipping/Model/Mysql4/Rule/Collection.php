<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Mysql4_Rule_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        } 
        
	public function _construct() {
		parent::_construct();
		$this->_init($this->getModuleStr() . '/rule');
	}
  protected function  _toOptionArray($valueField = 'rule_id', $labelField = 'rule_name', $additional = array()) {
		return parent:: _toOptionArray($valueField, $labelField, $additional);
	}
}