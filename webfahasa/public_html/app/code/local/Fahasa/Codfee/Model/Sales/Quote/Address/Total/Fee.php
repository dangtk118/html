<?php

class Fahasa_Codfee_Model_Sales_Quote_Address_Total_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract {
    
    public function __construct()
    {
        $this->setCode('codfee');
    }    
    
    public function collect(Mage_Sales_Model_Quote_Address $address) {
        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;   //this makes only address type shipping to come through
        }        
        
        // freeshipping_freeshipping use for pickup location
        // no apply cod
        if (
                // web
                (array_key_exists('shipping_method', $_POST) &&
                $_POST['shipping_method'] === "freeshipping_freeshipping") ||
                // mobile app
                $address->getShippingMethod() === "freeshipping_freeshipping"
        ) {
            return $this;
        }

        if ((array_key_exists('payment_method', $_POST) && 
                $_POST['payment_method'] === "cashondelivery") ||
                (array_key_exists('payment', $_POST) && 
                $_POST['payment']['method'] === "cashondelivery")) {
            $quote = $address->getQuote();            
            $payableAmount = $address->getBaseSubtotalInclTax() + $address->getBaseShippingInclTax();
            $codAmount = Mage::helper('codfee')->calculateCodFee($address, $payableAmount);
            if ($codAmount > 0.00001) {
                $address->setCodfee($codAmount);                        
                $quote->setCodfee($codAmount);            
                $address->setGrandTotal($address->getGrandTotal() + $codAmount);
                $address->setBaseGrandTotal($address->getBaseGrandTotal() +$codAmount);
            }
        }
        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_address $address) {
        $amt = $address->getCodfee();
        if ($amt != null && $amt > 0.000001) {
            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => Mage::helper('codfee')->__('COD Fee'),
                'value' => $amt
            ));
        }
        return $this;
    }
}
