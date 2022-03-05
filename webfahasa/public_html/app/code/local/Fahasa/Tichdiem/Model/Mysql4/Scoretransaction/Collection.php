<?php
/**
 * @author Thang Pham
 */
class Fahasa_Tichdiem_Model_Mysql4_Scoretransaction_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    public function _construct() {
        $this->_init('tichdiem/scoretransaction');
    }
    
    public function setLoad($load){
        $this->_setIsLoaded($load);
        return $this;
    }
}
