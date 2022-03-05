<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Mysql4_Area_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        } 
        
	public function _construct() {
		parent::_construct();
		$this->_init($this->getModuleStr() . '/area');
	}
  	protected function  _toOptionArray($valueField = 'area_id', $labelField = 'area_name', $additional = array()) {
		$toOptionArray = parent:: _toOptionArray($valueField, $labelField, $additional);
    $arrayFirst = array('' =>  Mage::helper($this->getModuleStr())->__('Please select area'));
    return array_merge($arrayFirst,$toOptionArray); 
	}
  
    public function  toOptionAreaArray($valueField = 'area_id', $labelField = 'area_name', $additional = array()) {
    return parent:: _toOptionArray($valueField, $labelField, $additional);
	}
}