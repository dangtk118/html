<?php
/**
 * Description of Observer
 *
 * @author Thang Pham
 */
class Fahasa_Reviewcouponcode_Model_Observer extends Fahasa_Customerregister_Model_Observer{
    
    const SENDER_EMAIL_ADDRESS = 'info@fahasa.com';
    const SENDER_EMAIL_NAME = 'FAHASA';
    /**
     * Template name for the email template that will send out coupon once the 
     * user successfully create account in fahasa.com
     */
    const TEMPLATE_ID = 'coupon code thank you review';
    /**
     *
     */
    public function fhsReviewSaveAfter($observer){
        $campaign_enabled = Mage::getStoreConfig("reviewcouponcode/general/enable");
        if($campaign_enabled == "1"){
            $rule_Id = Mage::getStoreConfig("reviewcouponcode/general/ruleid");
            if($rule_Id){
                Mage::log("**Rule Id: $rule_Id");
                $core_helper = Mage::helper('coreextended');
                $coupon_code = $core_helper->getAvailableCouponCode($rule_Id);
                Mage::log("**Coupon Code: $coupon_code");
                if($coupon_code){
                    //Code is good. Sending out email
                    $customer = $observer->getCustomer();
                    $this->sendReviewCouponCode($coupon_code, $observer->getObject(), $rule_Id);
                }
            }
        }
    }

    public function sendReviewCouponCode($coupon_code, $review, $rule_Id){
        
        $status = $review->getStatusId();
        if($status == 1){
            /**
             * Approved status. To avoid issue where a status can be approved
             * multiple times. We will save status_id to table to avoid duplicated code 
             * sending out
             */ 
            $reviewId = $review->getReviewId();
            $reviewCoupon = Mage::getModel("reviewcouponcode/reviewcouponcode")->load($reviewId);
            if($reviewCoupon->getReviewId() != $reviewId){
                //Have not approved this review yet, send coupon to this customer
                $reviewC = Mage::getModel("reviewcouponcode/reviewcouponcode");
                $reviewC->setReviewId($reviewId);
                $customer_email = Mage::getModel("customer/customer")->load($review->getCustomerId())->getEmail();
                $reviewC->setCustomerEmail($customer_email);
                $reviewC->setCouponCode($coupon_code);
                $reviewC->setCouponRule($rule_Id);                
                $reviewC->setInsertTime($this->getCurrentTime());
                $reviewC->setApproveBy($this->getCurAdminLoggedInUser());
                $reviewC->save();
                $this->sendReviewCouponEmail($coupon_code,$customer_email,$review);
            }
        }
    }
    
    protected function sendReviewCouponEmail($couponCode, $receiveEmail, $review){
        
        $_product = Mage::getModel('catalog/product')->load($review->getEntityPkValue());
        
        
        
        $templateId = self::TEMPLATE_ID;
        $emailTemplate = Mage::getModel('core/email_template')->loadByCode($templateId);
        //set up variable coupon code for email template
        $receiveName = $review->getNickname();
        $rule = Mage::getModel('salesrule/rule')->load(Mage::getStoreConfig("reviewcouponcode/general/ruleid"));
        $vars = array(
            'couponcode'            => $couponCode, 
            'customername'          => $receiveName,
            'bookname'              =>$_product->getName(),
            'url'                   =>Mage::getBaseUrl() .$_product->getUrlPath() .'#customer-reviews',
            'datestart'             =>date("d-m-Y", strtotime($rule->getFromDate())),
            'dateend'               =>date("d-m-Y", strtotime($rule->getToDate()))
                );
        //View the processed templated before sending out email
        //$content_preview = $emailTemplate->getProcessedTemplate($vars);
        //Set sender information
        $emailTemplate->setSenderEmail(self::SENDER_EMAIL_ADDRESS);
        $emailTemplate->setSenderName(self::SENDER_EMAIL_NAME);
        try{
            $emailTemplate->send($receiveEmail,$receiveName, $vars);
            $core_helper = Mage::helper('coreextended');
            //Mark this coupon code as sent so it will not be used again
            $core_helper->markCouponCodeAsSent($couponCode);
        }catch (Exception $e){
            Mage::logException($e);
        }
    }
    
    function getCurAdminLoggedInUser(){
        return Mage::getSingleton('admin/session')->getUser()->getUsername();
    }

    /**
     *  Return the current time
     *  @return type
     **/
    function getCurrentTime(){
        $dt = new DateTime();
        $dt->setTimezone(new DateTimeZone("Asia/Ho_Chi_Minh"));
        return $dt->format('Y-m-d H:i:s');
    }
}
