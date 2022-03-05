<?php

class TTS_Vnpay_Model_Mysql4_Vnpay_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('vnpay/vnpay');
    }
}