<?php

class TTS_Airpay_Model_Mysql4_Airpay_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('airpay/airpay');
    }
}