<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Shippingweight_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
  public function __construct() {
		parent::__construct();
		$this->setId('shippingweight_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('vietnamshipping')->__('Rule Information'));
  }

  protected function _beforeToHtml() {
		$this->addTab('form_section', array(
			'label'     => Mage::helper('vietnamshipping')->__('Rule Information'),
			'title'     => Mage::helper('vietnamshipping')->__('Rule Information'),
			'content'   => $this->getLayout()->createBlock('vietnamshipping/adminhtml_shippingweight_edit_tab_form')->toHtml(),
		));
	 
		return parent::_beforeToHtml();
  }
}