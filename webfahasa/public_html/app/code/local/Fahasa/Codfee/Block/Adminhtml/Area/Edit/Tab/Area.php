<?php

class Fahasa_Codfee_Block_Adminhtml_Area_Edit_Tab_Area extends Magebuzz_Vietnamshipping_Block_Adminhtml_Area_Edit_Tab_Area {

    protected function getModuleStr() {
        return "codfee";
    }
    
    protected function _prepareForm() {
        parent::_prepareForm();
        $form = $this->getForm();
        
        $shippingExpressElement = $form->getElement("shipping_express");
        $shippingExpressElement->addClass("no-display");
        $shippingExpressElement->setData("label", "");
        
        $shippingExpressPriceElement = $form->getElement("shipping_express_price");
        $shippingExpressPriceElement->addClass("no-display");
            
        $shippingExpressFixedPriceElement = $form->getElement("shipping_express_fixed_price");
        $shippingExpressFixedPriceElement->addClass("no-display");
    }

}
