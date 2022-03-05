<?php
class Fahasa_Reviewsaction_Model_Mysql4_Reviewsaction extends Mage_Core_Model_Mysql4_Abstract{
    public function _construct() {
        $this->_init('reviewsaction/reviewsaction', 'id');
    }            
}