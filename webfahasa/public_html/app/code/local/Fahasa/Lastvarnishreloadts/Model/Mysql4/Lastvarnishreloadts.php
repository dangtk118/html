<?php

/**
 * @author Thang Pham
 */

class Fahasa_Lastvarnishreloadts_Model_Mysql4_Lastvarnishreloadts extends Mage_Core_Model_Mysql4_Abstract{
    public function _construct() {
        $this->_init('lastvarnishreloadts/lastvarnishreloadts', 'cache_id');        
    }
}
