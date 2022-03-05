<?php

class Fahasa_Freeshipamount_Model_Sales_Quote_Address_Total_Freeshipamountdiscount extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    protected $_code = 'freeship_amount';

    public function collect(Mage_Sales_Model_Quote_Address $address) {
        parent::collect($address);
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {
        $amt = $address->getFreeshipAmount();
        if ($amt) {
            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => Mage::helper('sales')->__('Freeship Amount'),
                'value' => $amt
            ));
        }
        return $this;
    }

}
