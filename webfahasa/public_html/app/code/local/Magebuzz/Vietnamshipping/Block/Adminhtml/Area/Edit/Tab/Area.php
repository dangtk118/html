<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Area_Edit_Tab_Area extends Mage_Adminhtml_Block_Widget_Form {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  }
          
  protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('area_form', array('legend'=>Mage::helper($this->getModuleStr())->__('Area Information')));
	 
		$fieldset->addField('area_name', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Area Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'area_name',
		));
    $fieldset->addField('area_code', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Area Code'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'area_code',
		));
     $fieldset->addField('price_shipping_normal', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Price for Shipping Normal'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'price_shipping_normal',
     // 'after_element_html' => '<strong>'.Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol().'</strong>',
		));
    
		$fieldset->addField('shipping_express', 'select', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Shipping Express'),
			'name'      => 'shipping_express',
      'onchange'  => 'changeSelect();',
			'values'    => array(
				array(
					'value'     => 1,
					'label'     => Mage::helper($this->getModuleStr())->__('Yes'),
				),
				array(
					'value'     => 0,
					'label'     => Mage::helper($this->getModuleStr())->__('No'),
				),
			),
		));
    $fieldset->addField('shipping_express_price', 'select', array(
			'name'      => 'shipping_express_price',
      'onchange'  => 'changeSelectOptionPrice();',
			'values'    => array(
				array(
					'value'     => 1,
					'label'     => Mage::helper($this->getModuleStr())->__('Fixed Price for Shipping Express'),
				),
				array(
					'value'     => 0,
					'label'     => Mage::helper($this->getModuleStr())->__('Price by Shipping Weights'),
				),
			),
		));
    $fieldset->addField('shipping_express_fixed_price', 'text', array(
			'required'  => false,
			'name'      => 'shipping_express_fixed_price',
		));
    //Nextday
    $fieldset->addField('shipping_sameday', 'select', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Shipping Nextday'),
			'name'      => 'shipping_sameday',
      'onchange'  => 'changeSelectsamedayOption();',
			'values'    => array(
				array(
					'value'     => 1,
					'label'     => Mage::helper($this->getModuleStr())->__('Yes'),
				),
				array(
					'value'     => 0,
					'label'     => Mage::helper($this->getModuleStr())->__('No'),
				),
			),
		));
    $fieldset->addField('shipping_sameday_fixed_price', 'text', array(
	    'required'  => false,
	    'name'      => 'shipping_sameday_fixed_price',
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
		if (Mage::getSingleton('adminhtml/session')->getAreaData()) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getAreaData());
			Mage::getSingleton('adminhtml/session')->setAreaData(null);
		} elseif ( Mage::registry('area_data') ) {
			$form->setValues(Mage::registry('area_data')->getData());
		}
		return parent::_prepareForm();
  }
}