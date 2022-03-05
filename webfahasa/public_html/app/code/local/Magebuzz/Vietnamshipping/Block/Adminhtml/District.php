<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_District extends Mage_Adminhtml_Block_Widget_Grid_Container {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  public function __construct() {
    $this->_controller = 'adminhtml_district';
    $this->_blockGroup = $this->getModuleStr();
    $this->_headerText = Mage::helper($this->getModuleStr())->__('Manage District');
    $this->_addButtonLabel = Mage::helper($this->getModuleStr())->__('Add District');
    parent::__construct();
  }
}