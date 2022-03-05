<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Mysql4_Province_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
        protected function getModuleStr() { 
            return "vietnamshipping";
        } 
        
	public function _construct() {
		parent::_construct();
		$this->_init($this->getModuleStr() . '/province');
	}
  protected function  _toOptionArray($valueField = 'province_id', $labelField = 'province_name', $additional = array()) {
		$toOptionArray = parent:: _toOptionArray($valueField, $labelField, $additional);
    $arrayFirst = array('' =>  Mage::helper($this->getModuleStr())->__('Please select province'));
    return array_merge($arrayFirst,$toOptionArray);        
	}
}