<?php
class Fahasa_Amazonrating_Model_Mysql4_Amazonrating extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('amazonrating/amazonrating', 'sku');
    }
}