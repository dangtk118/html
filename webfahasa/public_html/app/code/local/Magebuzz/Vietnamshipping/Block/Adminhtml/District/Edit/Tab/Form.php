<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_District_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
        
  protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('district_form', array('legend'=>Mage::helper($this->getModuleStr())->__('District')));
	 
		$fieldset->addField('district_name', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('District Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'district_name',
		));
    $fieldset->addField('district_code', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('District Code'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'district_code',
		));
    $fieldset->addField('province_id', 'select', array(
          'name'      => 'province_id',
          'label'     => Mage::helper($this->getModuleStr())->__('Province'),
          'title'     => Mage::helper($this->getModuleStr())->__('Province'),
          'required'  => true,
					'values'    => Mage::getResourceSingleton($this->getModuleStr() . '/province_collection')->toOptionArray(),
      ));
    	$fieldset->addField('area_id', 'select', array(
          'name'      => 'area_id',
          'label'     => Mage::helper($this->getModuleStr())->__('Area'),
          'title'     => Mage::helper($this->getModuleStr())->__('Area'),
          'required'  => false,
          'values'    => Mage::getResourceSingleton($this->getModuleStr() . '/area_collection')->toOptionArray(),
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
	   
	
	 
		if (Mage::getSingleton('adminhtml/session')->getDistrictData()) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getDistrictData());
			Mage::getSingleton('adminhtml/session')->setDistrictData(null);
		} elseif ( Mage::registry('district_data') ) {
			$form->setValues(Mage::registry('district_data')->getData());
		}
		return parent::_prepareForm();
  }
}