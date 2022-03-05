<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_District_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  public function __construct() {
		parent::__construct();
		$this->setId('district_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper($this->getModuleStr())->__('District Information'));
  }

  protected function _beforeToHtml() {
		$this->addTab('form_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('District Information'),
			'title'     => Mage::helper($this->getModuleStr())->__('District Information'),
			'content'   => $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_district_edit_tab_form')->toHtml(),
		));
	 
		return parent::_beforeToHtml();
  }
}