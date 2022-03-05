<?php

class Fahasa_Availablestock_Model_Availablestock extends Mage_Core_Model_Abstract{
    public function _construct() {
        $this->_init('availablestock/availablestock');
    }
    
    public function loadByMultiple($customer_email, $isbn){
        $collection = $this->getCollection()
            ->addFieldToFilter('customer_email', $customer_email)
            ->addFieldToFilter('isbn', $isbn);
        return $collection;
    }
}