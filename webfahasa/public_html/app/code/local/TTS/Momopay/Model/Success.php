<?php

class TTS_Momopay_Model_Success extends Mage_Core_Model_Abstract
{
 public function _construct()
    {
        parent::_construct();
        $this->_init('momopay/momopay');
		
		
    }
 public function loadByIncrementId($incrementId)
    /**
     * refId
     *
     * Mã giao dịch đối tác (refId/orderId) là mã duy nhất và định danh cho giao dịch của đối tác khi gửi qua MoMo để yêu cầu thanh toán. 
     * Một TID sẽ ứng với một refId.
     */
    {
        return $this->loadByAttribute('refId', $incrementId);
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