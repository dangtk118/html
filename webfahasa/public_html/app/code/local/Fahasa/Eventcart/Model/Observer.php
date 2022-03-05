<?php

class Fahasa_Eventcart_Model_Observer {

    //function was called by apply coupon code - NOT for freeship code
    public function setCouponLabelInSession(Varien_Event_Observer $observer)
    {
        /* @var $rule Mage_SalesRule_Model_Rule */
        $rule = $observer->getEvent()->getRule();
        if ($rule->getCouponType() != Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON)
        {
            $couponLabel = $rule->getStoreLabel();
            if (empty($couponLabel))
            {
                $couponLabel = $rule->getCode();
            }
            Mage::getSingleton('core/session')->setCouponCode($rule->getCode());
            Mage::getSingleton('core/session')->setCouponLabel($couponLabel);
        }
    }

    public function resetCouponLabelInSession()
    {
        Mage::getSingleton('core/session')->setCouponCode(null);
        Mage::getSingleton('core/session')->setCouponLabel(null);
    }

}
