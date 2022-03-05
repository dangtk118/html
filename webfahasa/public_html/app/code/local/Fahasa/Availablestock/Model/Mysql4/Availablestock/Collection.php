<?php
class Fahasa_Availablestock_Model_Mysql4_Availablestock_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    public function _construct() {
        $this->_init('availablestock/availablestock');
    }
}