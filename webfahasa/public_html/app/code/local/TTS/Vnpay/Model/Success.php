<?php

class TTS_Vnpay_Model_Success extends Mage_Core_Model_Abstract
{
 public function _construct()
    {
        parent::_construct();
        $this->_init('vnpay/vnpay');
		
		
    }
 public function loadByIncrementId($incrementId)
    {
        return $this->loadByAttribute('vnp_TxnRef', $incrementId);
    }

    /**
     * Load order by custom attribute value. Attribute value should be unique
     *
     * @param string $attribute
     * @param string $value
     * @return Mage_Sales_Model_Order
     */
    public function loadByAttribute($attribute, $value)
    {
        $this->load($value, $attribute);
        return $this;
    }
}