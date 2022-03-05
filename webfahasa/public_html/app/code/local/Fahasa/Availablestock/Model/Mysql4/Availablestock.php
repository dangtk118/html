<?php
class Fahasa_Availablestock_Model_Mysql4_Availablestock extends Mage_Core_Model_Mysql4_Abstract{
    public function _construct() {
        $this->_init('availablestock/availablestock', 'id');        
    }
}
