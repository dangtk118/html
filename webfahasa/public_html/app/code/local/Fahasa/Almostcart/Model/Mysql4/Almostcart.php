<?php

/**
 * @author Thang Pham
 */
class Fahasa_Almostcart_Model_Mysql4_Almostcart extends Mage_Core_Model_Mysql4_Abstract{
    
    public function _construct() {
        $this->_init('almostcart/almostcart', 'id');        
    }
}
