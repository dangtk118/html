<?php

class TTS_Momopay_Model_Mysql4_Momopay_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('momopay/momopay');
    }
}