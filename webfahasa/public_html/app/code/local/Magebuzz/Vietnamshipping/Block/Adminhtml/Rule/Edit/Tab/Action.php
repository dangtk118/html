<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Block_Adminhtml_Rule_Edit_Tab_Action extends Mage_Adminhtml_Block_Widget_Form {
    
  protected function getModuleStr() { 
    return "vietnamshipping";
  } 
  
  protected function _prepareForm() {
		$model = Mage::registry('rule_data');
        //$form = new Varien_Data_Form(array('id' => 'edit_form1', 'action' => $this->getData('action'), 'method' => 'post'));
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');
        $fieldset = $form->addFieldset('action_fieldset', array('legend'=>Mage::helper($this->getModuleStr())->__('Update prices using the following information')));
        $fieldset->addField('apply_to_shipping', 'select', array(
            'label'     => Mage::helper($this->getModuleStr())->__('Apply to Shipping'),
            'title'     => Mage::helper($this->getModuleStr())->__('Apply to Shipping'),
            'name'      => 'apply_to_shipping',
            'values'    => Mage::getSingleton($this->getModuleStr() . '/rule')->toOptionArray(),
        ));
        $fieldset->addField('simple_action', 'select', array(
            'label'     => Mage::helper($this->getModuleStr())->__('Apply'),
            'name'      => 'simple_action',
            'options'    => array(
                Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION => Mage::helper($this->getModuleStr())->__('Percent amount discount'),
                Mage_SalesRule_Model_Rule::CART_FIXED_ACTION => Mage::helper($this->getModuleStr())->__('Fixed amount discount'),
                Magebuzz_Vietnamshipping_Model_Rule::BY_FIXED_INCREASE_ACTION => 'Fixed amount Shipping Increase',
            ),
        ));
        $fieldset->addField('discount_amount', 'text', array(
            'name' => 'discount_amount',
            'required' => false,
            'class' => 'validate-not-negative-number',
            'label' => Mage::helper($this->getModuleStr())->__('Discount Amount'),
        ));
        

        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('adminhtml/promo_quote/newActionHtml/form/rule_actions_fieldset'));

        $fieldset = $form->addFieldset('actions_fieldset', array(
            'legend'=>Mage::helper($this->getModuleStr())->__('Apply the rule only to cart items matching the following conditions (leave blank for all items)')
        ))->setRenderer($renderer);

        $fieldset->addField('actions', 'text', array(
            'name' => 'actions',
            'label' => Mage::helper($this->getModuleStr())->__('Apply To'),
            'title' => Mage::helper($this->getModuleStr())->__('Apply To'),
            'required' => true,
        ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/actions'));

       
        
        Mage::dispatchEvent('adminhtml_block_salesrule_actions_prepareform', array('form' => $form));

        $form->setValues($model->getData());

        if ($model->isReadonly()) {
            foreach ($fieldset->getElements() as $element) {
                $element->setReadonly(true, true);
            }
        }
        //$form->setUseContainer(true);

        $this->setForm($form);
  }
}