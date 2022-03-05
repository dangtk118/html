<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Shippingweight_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {
  protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('shippingweight_form', array('legend'=>Mage::helper('vietnamshipping')->__('Shipping weight')));
	 
		$fieldset->addField('rule_name', 'text', array(
			'label'     => Mage::helper('vietnamshipping')->__('Rule Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'rule_name',
		));
    $fieldset->addField('from_weight', 'text', array(
			'label'     => Mage::helper('vietnamshipping')->__('From Weight (g)'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'from_weight',
		));
    
    $fieldset->addField('to_weight', 'text', array(
			'label'     => Mage::helper('vietnamshipping')->__('To Weight (g)'),
			'name'      => 'to_weight',
		));
    $fieldset->addField('weight_step', 'text', array(
			'label'     => Mage::helper('vietnamshipping')->__('Weight Step'),
			'name'      => 'weight_step',
		));
    $fieldset->addField('price_step', 'text', array(
			'label'     => Mage::helper('vietnamshipping')->__('Price Step'),
			'name'      => 'price_step',
		));
    $fieldset->addField('price', 'text', array(
			'label'     => Mage::helper('vietnamshipping')->__('Price'),
			'name'      => 'price',
		));
	
		$fieldset->addField('status', 'select', array(
			'label'     => Mage::helper('vietnamshipping')->__('Status'),
			'name'      => 'status',
			'values'    => array(
				array(
					'value'     => 1,
					'label'     => Mage::helper('vietnamshipping')->__('Enabled'),
				),

				array(
					'value'     => 0,
					'label'     => Mage::helper('vietnamshipping')->__('Disabled'),
				),
			),
		));
	
		if (Mage::getSingleton('adminhtml/session')->getShippingweightData()) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getShippingweightData());
			Mage::getSingleton('adminhtml/session')->setShippingweightData(null);
		} elseif ( Mage::registry('shippingweight_data') ) {
			$form->setValues(Mage::registry('shippingweight_data')->getData());
		}
		return parent::_prepareForm();
  }
}