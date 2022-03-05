<?php

class Fahasa_Facebookuser_Model_Mysql4_Facebookconfirm extends Mage_Core_Model_Mysql4_Abstract{
    
    public function _construct(){
        $this->_init('facebookuser/facebookconfirm', 'id');
        $this->_isPkAutoIncrement = false;
    }
    
}