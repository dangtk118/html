<?php

class Fahasa_Facebookuser_Model_Mysql4_Facebookuser extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('facebookuser/facebookuser', 'facebook_id');
        $this->_isPkAutoIncrement = false;
    }

}
