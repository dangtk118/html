<?php

class Fahasa_YKienKhachHang_Helper_Data extends Mage_Core_Helper_Abstract {

    function checkExistOrder($orderId, $customer_id) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select status from fhs_sales_flat_order where increment_id = ".$orderId." and customer_id = ".$customer_id.";";
        $result = $readConnection->fetchRow($query);
        return $result;
    }
    function checkExistSurvey($orderId) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select * from y_kien_khach_hang where "
                . "order_id = ".$orderId.";";
        $result = $readConnection->fetchRow($query);
        return $result;
    }
    
    function checkSurveyComplete($orderId) {
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select * from y_kien_khach_hang where "
                . "order_id = :orderId and "
                . "status = 'complete'";
        $vars = array(
            "orderId" => $orderId
        );
        $results = $readConnection->query($query,$vars);
        return $results->rowCount();
    }

    const SENDER_EMAIL_ADDRESS = 'services@fahasa.com.vn';
    const SENDER_EMAIL_NAME = 'FAHASA';
    const TEMPLATE_ID = 'coupon code thank you survey order';

    function couponCodeSurvey($email,$order_id) {
        $core_helper = Mage::helper('coreextended');
        $campaign_enabled = Mage::getStoreConfig("ykienkhachhang/general/enable");
        if ($campaign_enabled == "1") {
            $rule_Id = Mage::getStoreConfig("ykienkhachhang/general/ruleid");
            if ($rule_Id) {
                Mage::log("**Survey from Email: $email, order_id: $order_id", null, 'survey.log');
                Mage::log("**Rule Id Survey: $rule_Id", null, 'survey.log');
                $couponCode = $core_helper->getAvailableCouponCode($rule_Id);
                Mage::log("**Coupon Code Survey: $couponCode", null, 'survey.log');
                Mage::log("------------------------------------", null, 'survey.log');
                if ($couponCode) {
                    //Code is good. Sending out email
                    return $couponCode;
                }
            }
        }
        return false;
    }

    function sendSurveyCouponEmail($couponCode, $customerEmail, $orderId) {
        $core_helper = Mage::helper('coreextended');
        $templateId = self::TEMPLATE_ID;
        $emailTemplate = Mage::getModel('core/email_template')->loadByCode($templateId);
        $customer = Mage::getModel("customer/customer")
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($customerEmail);
        $customername = $customer->getFirstname() . " " . $customer->getLastname();
        //set up variable coupon code for email template
        $rule = Mage::getModel('salesrule/rule')->load(Mage::getStoreConfig("ykienkhachhang/general/ruleid"));
        $vars = array(
            'couponcode' => $couponCode,
            'customername' => $customername,
            'orderid' => $orderId,
            'discount' => Mage::helper('core')->formatPrice(round($rule->getDiscountAmount())),
            'datestart' => date("d-m-Y", strtotime($rule->getFromDate())),
            'dateend' => date("d-m-Y", strtotime($rule->getToDate()))
        );
        //Set sender information
        $emailTemplate->setSenderEmail(self::SENDER_EMAIL_ADDRESS);
        $emailTemplate->setSenderName(self::SENDER_EMAIL_NAME);
        try {
            $emailTemplate->send($customerEmail, $customername, $vars);
            //Mark this coupon code as sent so it will not be used again
            $core_helper->markCouponCodeAsSent($couponCode);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
    
    function sendCouponToWalletVoucher($couponCode, $customerId, $orderId){
	Mage::helper('coreextended')->markCouponCodeAsSent($couponCode);
	$write = Mage::getSingleton("core/resource")->getConnection("core_write");
	$sql = "CALL insertWalletVoucherSurveyCode('".$couponCode."', ".$customerId.", ".$orderId.");";
	$write->query($sql);
    }
}
