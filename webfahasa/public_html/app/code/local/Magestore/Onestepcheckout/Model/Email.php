<?php

class Magestore_Onestepcheckout_Model_Email extends Mage_Core_Model_Abstract
{
    const TYPE_NO_GATEWAY = 1;
    const TYPE_PAYMENT_SUCCESS = 2;
    const TYPE_PAYMENT_FAIL = 3;

    public function send_email_pending($observer)
    {
	//Mage::dispatchEvent('payment_order_return', array('order_id'=>$order->getEntityId(), 'status'=>'complete'));
	$order_id = $observer->getOrderId();
	$increment_id = $observer->getIncrementId();
	$status = $observer->getStatus();
        $type_payment = $observer->getTypePayment();
        $cur_payment_method = $observer->getCurPaymentMethod();
        $cur_payment_title = $observer->getCurPaymentTitle();
        $customer_email = $observer->getCustomerEmail();
        $customer_id = $observer->getCustomerId();
        
	if(empty($order_id) || empty($status) || empty($increment_id)){
	    Mage::log("**send_email_pending fail! order_id=".$order_id.", increment_id=".$increment_id.", status=".$status, null, 'order_email.log');
	    return;
	}
	$email_data = $this->getEmailQueue($order_id);
	if(!empty($email_data)){
	    if(!empty($email_data['message_parameters'])){
		$message_parameters = unserialize($email_data['message_parameters']);
		if(!empty($message_parameters['payment_status'])){
		    if($message_parameters['payment_status'] == 'success'){
			return;
		    }
		}
		if(!empty($message_parameters['subject'])){
		    $subject = $message_parameters['subject'];
		}
	    }
	    if(!empty($subject)){
                if ($type_payment == self::TYPE_PAYMENT_SUCCESS){
                    $subject = "Đơn hàng thanh toán thành công # ".$increment_id;
		    $message_parameters['subject'] = $subject;
		    $message_parameters['payment_status'] = $status;
                    $message_payment = '<span style="color: #F7941E;">Đơn hàng đã được thanh toán thành công</span><br/>Cảm ơn bạn đã mua hàng tại <strong>Fahasa</strong>!</span>';
                    $this->setEmailQueue($order_id, $message_parameters, $message_payment, $cur_payment_method, $cur_payment_title);
                    if ($customer_id)
                    {
                        $title = "Đơn hàng thanh toán thành công #" . $increment_id;
                        $message = "Đơn hàng đã được thanh toán thành công. Cảm ơn bạn đã mua hàng tại Fahasa.com";
                        $this->pushNotification($customer_email, $title, $message, "order", $increment_id);
                    }
                } else if($type_payment == self::TYPE_PAYMENT_FAIL){
                    $subject = "Đơn hàng bị hủy do quá thời hạn thanh toán # ".$increment_id;
		    $message_parameters['subject'] = $subject;
		    $message_parameters['payment_status'] = $status;
                    $message_payment = '<span style="color: #CC0000;">Đơn hàng của bạn đã bị hủy do quá thời hạn thanh toán</span>';
                    $this->setEmailQueue($order_id, $message_parameters, $message_payment, $cur_payment_method, $cur_payment_title);
                    
                    if ($customer_id)
                    {
                        $title = "Đơn hàng bị hủy do quá thời hạn thanh toán #" . $increment_id;
                        $message = "Đơn hàng của bạn đã bị hủy do quá thời hạn thanh toán";
                        $this->pushNotification($customer_email, $title, $message, "order", $increment_id);
                    }
                } 
	    }
	}

        return;
    }
    
    private function getEmailQueue($order_id){
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select entity_id, message_body, message_parameters, is_pending, processed_at from fhs_core_email_queue where message_body_hash = 'flashsale' and entity_id = ".$order_id.";";
	    $rs = $reader->fetchRow($sql);
	    return $rs;
	}catch(Exception $ex) {
	    Mage::log("**getEmailQueue fail! order_id=".$order_id.", ex=".$ex->getMessage(), null, 'order_email.log');
	}
	return null;
    }
    
    private function setEmailQueue($order_id, $message_parameters, $message_payment, $cur_payment_method, $cur_payment_title){
	try{
            if ($cur_payment_method == "banktransfer"){
                $cur_payment_title += $this->getBankTransferInfo();
            }
	    $message_parameters = serialize($message_parameters);
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $sql = "update fhs_core_email_queue set message_parameters = '".$message_parameters."', processed_at = null, is_pending = 0,"
                    . "message_body = replace(replace(message_body,'[%payment_status_noti%]', '" . $message_payment . "'), '[%payment_method%]', '" 
                    . $cur_payment_title ."') where message_body_hash = 'flashsale' and entity_id = ".$order_id.";";
	    $writer->query($sql);
	}catch(Exception $ex) {
	    Mage::log("**setEmailQueue fail! order_id=".$order_id.", ex=".$ex->getMessage(), null, 'order_email.log');
	}
	return null;
    }
    
    
    public function getBankTransferInfo()
    {
        return "<table>\n"
                . "<tbody>\n"
                . "<tr>\n"
                . "<td><strong><u>Hướng dẫn:</u></strong><br />\n"
                . "Sau khi hoàn tất mua hàng trên website, Quý khách vui lòng chuyển tiền vào tài khoản của chúng tôi theo thông tin sau:<br />\n"
                . "<br />\n"
                . "<strong>Ngân hàng Vietinbank - CN1 Thành phố Hồ Chí Minh</strong><br />\n"
                . "Tên tài khoản: Công Ty Cổ Phần Phát Hành Sách TP.Hồ Chí Minh - FAHASA<br />\n"
                . "Số tài khoản: 112000005263<br />\n"
                . "<br />\n"
                . "<strong><u>Chú ý:</u></strong><br />\n"
                . "- Khi chuyển khoản, vui lòng ghi lại Mã số Đơn hàng được thanh toán vào phần ghi chú của lệnh chuyển khoản.<br />\n"
                . "- Quý khách vui lòng thanh toán phí chuyển khoản.</td>\n"
                . "</tr>\n"
                . "</tbody>\n"
                . "</table>";
    }

    public function pushNotification($email, $title, $message, $pageType, $pageValue)
    {
        $urlServer = "https://fahasa.com:88/pushNotificationMobile";
        $hashKey = "824b35b38e2e4fc0f9e88070cbcecd64ccaa592c8e11f9f80413aea36ea6ab84";
        $postHelper = Mage::helper('cancelorder');
        $json = array(
            "email" => $email,
            "hashKey" => $hashKey,
            "title" => $title,
            "message" => $message,
            "pageType" => $pageType,
            "pageValue" => $pageValue,
        );
        $postHelper->httpPost($urlServer, json_encode($json));
    }

}