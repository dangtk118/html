<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Shippingweight extends Mage_Adminhtml_Block_Widget_Grid_Container {
  
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  public function __construct() {
    $this->_controller = 'adminhtml_shippingweight';
    $this->_blockGroup = $this->getModuleStr();
    $this->_headerText = Mage::helper($this->getModuleStr())->__('Manage Shipping Weight');
    $this->_addButtonLabel = Mage::helper($this->getModuleStr())->__('Add Rule');
    parent::__construct();
  }
}