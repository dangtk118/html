<?php

class Fahasa_Reviewcouponcode_Model_Mysql4_Reviewcouponcode extends Mage_Core_Model_Mysql4_Abstract{
    public function _construct() {
        $this->_init('reviewcouponcode/reviewcouponcode', 'review_id');
        // The primary key is not an auto_increment field
        $this->_isPkAutoIncrement = false;
    }
}
