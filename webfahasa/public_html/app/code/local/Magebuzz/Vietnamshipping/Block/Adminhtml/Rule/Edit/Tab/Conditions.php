<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Rule_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  protected function _prepareForm() {
	$form = new Varien_Data_Form();
		$form->setHtmlIdPrefix('rule_');
		$model = Mage::registry('rule_data');
		$renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('adminhtml/promo_quote/newConditionHtml/form/rule_conditions_fieldset'));
		$fieldset = $form->addFieldset('conditions_fieldset', array(
            'legend'=>Mage::helper($this->getModuleStr())->__('Apply the rule only if the following conditions are met (leave blank for all products)'))
        )->setRenderer($renderer);
		$fieldset->addField('conditions', 'text', array(
            'name' => 'conditions',
            'label' => Mage::helper($this->getModuleStr())->__('Conditions'),
            'title' => Mage::helper($this->getModuleStr())->__('Conditions'),
            'required' => true,
        ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));
    $fieldsetArea = $form->addFieldset('area_fieldset', array(
            'legend'=>Mage::helper($this->getModuleStr())->__('Area'))
        );
    $fieldsetArea->addField('area_id', 'multiselect', array(
          'name'      => 'area_id',
          'label'     => Mage::helper($this->getModuleStr())->__('Area'),
          'title'     => Mage::helper($this->getModuleStr())->__('Area'),
          'required'  => false,
					'values'    => Mage::getResourceSingleton($this->getModuleStr() . '/area_collection')->toOptionAreaArray(),
      )); 
    
     
			$data = $model->getData();        
			if (isset($data['rule_id']) && $data['rule_id'] != 0) {
	      $modelRule = Mage::getModel($this->getModuleStr() . '/rule')
					->load($data['rule_id']);
	      $data['area_id'] = unserialize($model->getAreaId());
			}
      $form->setValues($data);
		$this->setForm($form);
  }
}