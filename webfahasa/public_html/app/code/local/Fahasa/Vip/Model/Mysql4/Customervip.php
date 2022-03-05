<?php

/**
 *
 * @author Thang Pham
 */
class Fahasa_Vip_Model_Mysql4_Customervip extends Mage_Core_Model_Mysql4_Abstract{
    
    public function _construct() {
        $this->_init('vip/customervip', 'customer_email');
    }            
}
