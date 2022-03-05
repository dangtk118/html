<?php

/**
 *
 * @author Thang Pham
 */
class Fahasa_Vip_Model_Mysql4_Viplevel extends Mage_Core_Model_Mysql4_Abstract{
    
    public function _construct() {
        $this->_init('vip/viplevel', 'id');
    }        
}
