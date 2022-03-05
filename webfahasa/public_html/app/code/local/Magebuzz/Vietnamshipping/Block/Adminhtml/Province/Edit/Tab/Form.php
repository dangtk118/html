<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Province_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('province_form', array('legend'=>Mage::helper($this->getModuleStr())->__('Province')));
	 
		$fieldset->addField('province_name', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'province_name',
		));
    $fieldset->addField('province_code', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Code'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'province_code',
		));

	
	
		$fieldset->addField('status', 'select', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Status'),
			'name'      => 'status',
			'values'    => array(
				array(
					'value'     => 1,
					'label'     => Mage::helper($this->getModuleStr())->__('Enabled'),
				),
				array(
					'value'     => 2,
					'label'     => Mage::helper($this->getModuleStr())->__('Disabled'),
				),
			),
		));
	 
		$fieldset->addField('area_id', 'select', array(
          'name'      => 'area_id',
          'label'     => Mage::helper($this->getModuleStr())->__('Area'),
          'title'     => Mage::helper($this->getModuleStr())->__('Area'),
          'required'  => false,
					'values'    => Mage::getResourceSingleton($this->getModuleStr() . '/area_collection')->toOptionArray(),
      ));
	 
		if (Mage::getSingleton('adminhtml/session')->getProvinceData()) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getProvinceData());
			Mage::getSingleton('adminhtml/session')->setProvinceData(null);
		} elseif ( Mage::registry('province_data') ) {
			$form->setValues(Mage::registry('province_data')->getData());
		}
		return parent::_prepareForm();
  }
}