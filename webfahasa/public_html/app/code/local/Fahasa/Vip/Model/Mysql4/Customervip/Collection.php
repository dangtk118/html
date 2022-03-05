<?php

/**
 * @author Thang Pham
 */
class Fahasa_Vip_Model_Mysql4_Customervip_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    public function _construct() {
        $this->_init('vip/customervip');
    }        
}
