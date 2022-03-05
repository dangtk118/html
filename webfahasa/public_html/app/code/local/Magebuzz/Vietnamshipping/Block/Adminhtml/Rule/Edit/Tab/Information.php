<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Rule_Edit_Tab_Information extends Mage_Adminhtml_Block_Widget_Form {
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  protected function _prepareForm() {
		$form = new Varien_Data_Form();
		$this->setForm($form);
		$fieldset = $form->addFieldset('information_form', array('legend'=>Mage::helper($this->getModuleStr())->__('Rule Information')));
	 
		$fieldset->addField('rule_name', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Rule Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'rule_name',
		));
    $fieldset->addField('description', 'textarea', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Discription'),
			'class'     => 'required-entry',
			'required'  => false,
			'name'      => 'description',
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
    if (!Mage::app()->isSingleStoreMode()) {
			$field = $fieldset->addField('store_id', 'multiselect', array(
				'name'      => 'stores[]',
				'label'     => Mage::helper($this->getModuleStr())->__('Store View'),
				'title'     => Mage::helper($this->getModuleStr())->__('Store View'),
				'required'  => true,
				'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
			));
		}
		else {
			$fieldset->addField('store_id', 'hidden', array(
					'name'      => 'stores[]',
					'value'     => Mage::app()->getStore(true)->getId()
			));
			$model->setStoreId(Mage::app()->getStore(true)->getId());
		}
    $fieldset->addField('customer_groups', 'multiselect', array(
			'name'      => 'customer_groups[]',
			'label'     => Mage::helper($this->getModuleStr())->__('Customer Groups'),
			'title'     => Mage::helper($this->getModuleStr())->__('Customer Groups'),
			'required'  => true,
			'values'    => Mage::getResourceModel('customer/group_collection')
			//	->addFieldToFilter('customer_group_id', array('gt'=> 0))
			//->load()
			->toOptionArray()
		));
      $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset->addField('from_date', 'date', array(
            'name'   => 'from_date',
            'label'  => Mage::helper($this->getModuleStr())->__('From Date'),
            'title'  => Mage::helper($this->getModuleStr())->__('From Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso
        ));
        $fieldset->addField('to_date', 'date', array(
            'name'   => 'to_date',
            'label'  => Mage::helper($this->getModuleStr())->__('To Date'),
            'title'  => Mage::helper($this->getModuleStr())->__('To Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso
        ));
	 
		$fieldset->addField('priority', 'text', array(
			'label'     => Mage::helper($this->getModuleStr())->__('Priority'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'priority',
		));
                
        //add custom accept apply coupon: whether customer can apply freeship code coupon with the shipping rule.
        //Ex: some rules have action increase shipping fee and we don't want shipping fee discount
        $fieldset->addField('accept_apply_coupon', 'select', array(
            'label' => Mage::helper('salesrule')->__('Accept Apply Coupon'),
            'title' => Mage::helper('salesrule')->__('Accept Apply Coupon'),
            'name' => 'accept_apply_coupon',
//            'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'options'    => array(
                '0' => 'No accept apply whole amount', //no accept apply coupon for the whole amount, include basic shipping amount
                '1' => 'Accept apply default amount',
                '2' => 'Accept apply whole amount' //accept apply coupon for the whole amount, include basic shipping amount + increase shipping amount
            ),
            
        ));
        
        if (Mage::getSingleton('adminhtml/session')->getRuleData()) {
			$form->setValues(Mage::getSingleton('adminhtml/session')->getRuleData());
			Mage::getSingleton('adminhtml/session')->setRuleData(null);
		} elseif ( Mage::registry('rule_data') ) {
      $data = Mage::registry('rule_data')->getData();  	
			if (isset($data['rule_id']) && $data['rule_id'] != 0) {
	      $model = Mage::getModel($this->getModuleStr() . '/rule')
					->load($data['rule_id']);
	      $data['customer_groups'] = unserialize($model->getCustomerGroups());
			}
      $form->setValues($data);
		}
		return parent::_prepareForm();
  }
}