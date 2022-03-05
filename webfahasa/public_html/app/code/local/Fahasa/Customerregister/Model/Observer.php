<?php
/**
 * Description of Observer
 *
 * @author Thang Pham
 */
class Fahasa_Customerregister_Model_Observer {
    
    const SENDER_EMAIL_ADDRESS = 'info@fahasa.com';
    const SENDER_EMAIL_NAME = 'FAHASA';
    /**
     * Template name for the email template that will send out coupon once the 
     * user successfully create account in fahasa.com
     */
    const TEMPLATE_ID = 'coupon code thank you';
    
    /**
     * Event method invoke when customer successfully create account, whether 
     * via normal website customer/account/create or via facebook
     */
    //Chương trình đã bỏ, chương trình hiện tại, phải cập nhật tài khoản thì mới được cộng fpoint
    public function fhs_success_register_after($observer) {
        //Look for the coupons code
//        $campaign_enabled = Mage::getStoreConfig("customerregister/general/enable");
//        if ($campaign_enabled == "1") {
//            $type = Mage::getStoreConfig("customerregister/general/type");
//    
//            switch ($type) {
//                // use coupon code
//                case 0:
//                    $rule_Id = Mage::getStoreConfig("customerregister/general/ruleid");
//                    if ($rule_Id) {
//                        $core_helper = Mage::helper('coreextended');
//                        $coupon_code = $core_helper->getAvailableCouponCode($rule_Id);
//                        if ($coupon_code) {
//                            //Code is good. Sending out email
//                            $customer = $observer->getCustomer();
//                            $this->sendCouponEmail($coupon_code, $customer->getEmail(), $customer->getFirstname() . ' ' . $customer->getLastname());
//                        }
//                    }
//                    break;
//  
//                // use fpoint
//                case 1:
//                    // new customer
//                    $customer = $observer->getCustomer();
//                    $customerEmail = $customer->getEmail();
//                    $customerId = $customer->getId();
//                    $fpointVal = preg_replace("/[^0-9\.]/", '', Mage::getStoreConfig("customerregister/general/fpoint"));
//
//                    $write = Mage::getSingleton("core/resource")->getConnection("core_write");
//                    $sql1 = "insert into fhs_purchase_action_log 
//                        (account , customer_id , action , value , amountAfter , updateBy ,lastUpdated, description , type) 
//                        values ('" . $customerEmail . "', $customerId, 'top up', $fpointVal, $fpointVal, 'admin', now(), 'top up for register customer', 'fpoint');";
//                    $sql2 = "update fhs_customer_entity set fpoint = '" . $fpointVal . "' where email='" . $customerEmail . "';";
//
//                    Mage::log("**Sql1 : $sql1", null, 'register.log');
//                    Mage::log("**Sql2 : $sql2", null, 'register.log');
//                    $write->query($sql1 . $sql2);
//                    break;
//            }
//        }
    }

    /**
     * Given the coupon code, and target email, send this coupon code to the
     * target email. This should be sent after the customer successfully register
     * on fahasa.com
     * @param type $couponCode 
     * @param type $receiveEmail
     * @param type $receiveName
     */
    protected function sendCouponEmail($couponCode, $receiveEmail, $receiveName){
        $templateId = self::TEMPLATE_ID;
        $emailTemplate = Mage::getModel('core/email_template')->loadByCode($templateId);
        $rule = Mage::getModel('salesrule/rule')->load(Mage::getStoreConfig("customerregister/general/ruleid"));
        //set up variable coupon code for email template
        $vars = array(
            'couponcode'    => $couponCode, 
            'customername'  => $receiveName,
            'datestart'     =>date("d-m-Y", strtotime($rule->getFromDate())),
            'dateend'       =>date("d-m-Y", strtotime($rule->getToDate()))
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
            //dispatch event to log code that are sent out to customer via email
            Mage::dispatchEvent('sent_coupon_code_via_email_after', 
                    array('couponcode' => $couponCode,
                        'customername' => $receiveName,
                        'customeremail' => $receiveEmail));
        }catch (Exception $e){
            Mage::logException($e);
        }
    }
    
    public function fhs_success_register_after_new($observer) {
        $customer = $observer->getCustomer();
        //customer does not have email -> need to update
        if (empty($customer->getRealEmail())){
            Mage::helper("fahasa_customer")->sendNotiToUpdateEmail($customer);
        }
    }

}
