<?php

class Fahasa_Codfee_Block_Adminhtml_Rule_Edit_Tab_Action extends Magebuzz_Vietnamshipping_Block_Adminhtml_Rule_Edit_Tab_Action {

    protected function getModuleStr() {
        return "codfee";
    }

    protected function _prepareForm() {
        parent::_prepareForm();
        $form = $this->getForm();
        
        $applyToShippingElement = $form->getElement("apply_to_shipping");
        $applyToShippingElement->addClass("no-display");
        $applyToShippingElement->setData("label", "");
        
        $actionsFieldsetElement = $form->getElement("actions_fieldset");
        $actionsFieldsetElement->addClass("no-display");
        $actionsFieldsetElement->setData("legend", "");
    }
}
