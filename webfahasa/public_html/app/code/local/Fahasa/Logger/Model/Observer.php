<?php

/**
 * Observer that will log customer behavior into database
 *
 * @author Thang Pham
 */
class Fahasa_Logger_Model_Observer {
    
    /**
     * Log when user checkout with coupon code. This event is invoke when we
     * are used that user checkout with coupon code.
     */
    public function logCouponUsedDuringCheckout($observer){
//        $order = $observer['order'];
//        $isLogin = $order->getCustomerId() ? 1 : 0;
//        $couponuse = Mage::getModel('logger/logcouponuse');
//        $couponuse->setOrderId($order->getIncrementId());
//        $couponuse->setCustomerEmail($order->getCustomerEmail());
//        $couponuse->setCouponCode($order->getCouponCode());
//        $couponuse->setCouponAmt($order->getDiscountAmount());
//        $couponuse->setTotalAmt($order->getGrandTotal());
//        $couponuse->setIsLogin($isLogin);
//        $couponuse->setCouponRule($order->getCouponRuleName());
//        $couponuse->setInsertTime(self::getCurrentTime());
//        $couponuse->save();
    }
    
    /**
     * Log information about when coupon code are being sent.     
     */
    public function logCouponCodeSentViaEmail($observer){
        $customerName = $observer['customername'];
        $couponCode = $observer['couponcode'];
        $customerEmail = $observer['customeremail'];
        //Obtain rule_id from store config, as this is set during campaign for CustomerRegister mod
        $rule_id = Mage::getStoreConfig('customerregister/general/ruleid');
        $logcouponsent = Mage::getModel('logger/logcouponsent');
        $logcouponsent->setCustomerName($customerName);
        $logcouponsent->setCustomerEmail($customerEmail);
        $logcouponsent->setCouponCode($couponCode);
        $logcouponsent->setRuleId($rule_id);
        $logcouponsent->setSentTime(self::getCurrentTime());
        $logcouponsent->save();
    }
    
    /**
     * Return the current time
     * @return type
     */
    public static function getCurrentTime(){
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone("Asia/Ho_Chi_Minh"));
        return $dt->format('Y-m-d H:i:s');        
    }
}
