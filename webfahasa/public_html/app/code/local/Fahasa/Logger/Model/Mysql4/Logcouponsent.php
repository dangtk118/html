<?php

/**
 * @author Thang Pham
 */
class Fahasa_Logger_Model_Mysql4_Logcouponsent extends Mage_Core_Model_Mysql4_Abstract{
    public function _construct() {
        $this->_init('logger/logcouponsent', 'id');        
    }
}
