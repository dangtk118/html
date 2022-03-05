<?php

class Fahasa_Freeship_Model_Sales_Quote_Address_Total_Freeship extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    protected $_code = 'is_freeship';

    public function collect(Mage_Sales_Model_Quote_Address $address) {
        parent::collect($address);
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {
        $amt = Mage::getSingleton('checkout/session')->getData('onestepcheckout_freeship');
        if ($amt != 0) {
            if ($address->getShippingMethod() == "vietnamshippingnormal_vietnamshippingnormal") {
                $address->addTotal(array(
                    'code' => $this->getCode(),
                    'title' => Mage::helper('sales')->__('Use Freeship'),
                    'value' => TRUE
                ));
            }
        }
        return $this;
    }

}
