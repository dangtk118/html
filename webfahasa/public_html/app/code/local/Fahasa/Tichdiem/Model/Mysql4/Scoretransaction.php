<?php

/**
 * @author Thang Pham
 */
class Fahasa_Tichdiem_Model_Mysql4_Scoretransaction extends Mage_Core_Model_Mysql4_Abstract{
    
    public function _construct() {
        $this->_init('tichdiem/scoretransaction', 'trans_id');
    }    
}
