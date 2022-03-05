<?php

class Fahasa_Redeem_Model_Mysql4_Redeem extends Mage_Core_Model_Mysql4_Abstract {

    protected function _construct() {
        $this->_init("redeem/redeem", "id");
    }

}
