<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Rule extends Mage_Adminhtml_Block_Widget_Grid_Container {
  
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  public function __construct() {
    $this->_controller = 'adminhtml_rule';
    $this->_blockGroup = $this->getModuleStr();
    $this->_headerText = Mage::helper($this->getModuleStr())->__('Manage Rules');
    $this->_addButtonLabel = Mage::helper($this->getModuleStr())->__('Add Rule');
    parent::__construct();
  }
}