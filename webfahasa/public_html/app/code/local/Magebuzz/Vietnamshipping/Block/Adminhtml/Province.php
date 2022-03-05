<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Province extends Mage_Adminhtml_Block_Widget_Grid_Container {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  public function __construct() {
    $this->_controller = 'adminhtml_province';
    $this->_blockGroup = $this->getModuleStr();
    $this->_headerText = Mage::helper($this->getModuleStr())->__('Manage Province');
    $this->_addButtonLabel = Mage::helper($this->getModuleStr())->__('Add Province');
    parent::__construct();
  }
}