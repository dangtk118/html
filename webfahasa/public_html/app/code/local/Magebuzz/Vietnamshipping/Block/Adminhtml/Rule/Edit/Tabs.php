<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Rule_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  public function __construct() {
		parent::__construct();
		$this->setId('rule_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper($this->getModuleStr())->__('Rule Information'));
  }

  protected function _beforeToHtml() {
		$this->addTab('information_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Rule Information'),
			'title'     => Mage::helper($this->getModuleStr())->__('Rule Information'),
			'content'   => $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_rule_edit_tab_information')->toHtml(),
		));
    $this->addTab('conditions_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Conditions'),
			'title'     => Mage::helper($this->getModuleStr())->__('Conditions'),
			'content'   => $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_rule_edit_tab_conditions')->toHtml(),
		));
    $this->addTab('action_section', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Actions'),
			'title'     => Mage::helper($this->getModuleStr())->__('Actions'),
			'content'   => $this->getLayout()->createBlock($this->getModuleStr() . '/adminhtml_rule_edit_tab_action')->toHtml(),
		));
		return parent::_beforeToHtml();
  }
}