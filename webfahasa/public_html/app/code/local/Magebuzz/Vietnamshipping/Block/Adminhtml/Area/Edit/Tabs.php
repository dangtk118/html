<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Area_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
  
  protected function getModuleStr() { 
    return "vietnamshipping";
  }  
    
  public function __construct() {
		parent::__construct();
		$this->setId('area_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper($this->getModuleStr())->__('Area Information'));
  }

  protected function _beforeToHtml() {
		$this->addTab('area_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Area Information'),
			'title'     => Mage::helper($this->getModuleStr())->__('Area Information'),
			'content'   => $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_area_edit_tab_area')->toHtml(),
		));
    $this->addTab('province_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Province'),
			'title'     => Mage::helper($this->getModuleStr())->__('Province'),
			'class'			=> 'ajax',
			'url'   => $this->getUrl('*/*/provincelist', array('_current'=>true, 'id'=>$this->getRequest()->getParam('id'))),
			//'content'   => $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_area_edit_tab_province')->toHtml(),
		));
    $this->addTab('district_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('District'),
			'title'     => Mage::helper($this->getModuleStr())->__('District'),
			'class'			=> 'ajax',
			'url'   => $this->getUrl('*/*/districtlist', array('_current'=>true, 'id'=>$this->getRequest()->getParam('id'))),
		));
		return parent::_beforeToHtml();
  }
}