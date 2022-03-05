<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Province_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  public function __construct() {
		parent::__construct();
		$this->setId('province_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper($this->getModuleStr())->__('Province Information'));
  }

  protected function _beforeToHtml() {
		$this->addTab('form_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Province Information'),
			'title'     => Mage::helper($this->getModuleStr())->__('Province Information'),
			'content'   => $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_province_edit_tab_form')->toHtml(),
		));
	 $this->addTab('district_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('District'),
			'title'     => Mage::helper($this->getModuleStr())->__('District'),
			'content'   => $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_province_edit_tab_district')->toHtml(),
		));
		return parent::_beforeToHtml();
  }
}