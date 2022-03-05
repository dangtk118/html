<?php

/**
 * @author Thang Pham
 */
class Fahasa_Tichdiem_Model_Mysql4_Totalscore extends Mage_Core_Model_Mysql4_Abstract{
    
    public function _construct() {
        $this->_init('tichdiem/totalscore', 'customer_email');
        // The primary key is not an auto_increment field
        $this->_isPkAutoIncrement = false;
    }
}
