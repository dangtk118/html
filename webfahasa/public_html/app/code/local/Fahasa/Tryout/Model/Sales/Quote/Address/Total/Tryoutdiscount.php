<?php

class Fahasa_Tryout_Model_Sales_Quote_Address_Total_Tryoutdiscount extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    protected $_code = 'tryout_discount';

    public function collect(Mage_Sales_Model_Quote_Address $address) {
        parent::collect($address);
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address) {
        $amt = $address->getTryoutDiscount();
        if ($amt) {
            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => Mage::helper('sales')->__('F-point Discount'),
                'value' => $amt
            ));
        }
        return $this;
    }

}
