<?php

class Fahasa_Customer_Model_Observer {

    public function createCustomer($observer) {
        $customer = $observer->getEvent()->getCustomer();
        Mage::helper("fahasa_customer/fpoint")->createCustomer($customer);
    }

    public function updateCustomer($observer) {
	if(!Mage::registry('is_create_customer')) {
	    $customer = $observer->getCustomer();
	    Mage::helper("fahasa_customer/fpoint")->updateCustomer($customer);
	}
    }

}
