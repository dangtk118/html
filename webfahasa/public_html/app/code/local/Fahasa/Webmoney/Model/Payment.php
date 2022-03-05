<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
//if (!@class_exists('SphinxClient')) {
//    include Mage::getBaseDir().DS.'lib'.DS.'Sphinx'.DS.'sphinxapi.php';
//}
/**
 * Description of Payment
 *
 * @author Thang Pham
 */
class Fahasa_Webmoney_Model_Payment extends Mage_Payment_Model_Method_Abstract{
    protected $_code = 'webmoney';
    const WM_PENDING_PAYMENT_STATUS = 'pending';
    const WM_PAYMENT_SUCCESS_STATUS = 'paid';
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('webmoney/payment/redirect', array('_secure' => true));
    }
    
    public function createWMOrder(){
        $path = Mage::getModuleDir('', 'Fahasa_Webmoney');
        include $path . '/lib/WMMerchant/WMService.php';        
        include $path . '/lib/WMMerchant/GlobalConfig.php';
        include $path . '/lib/WMMerchant/base/Curl.php';
        include $path . '/lib/WMMerchant/base/Model.php';
        include $path . '/lib/WMMerchant/base/NetHelper.php';
        include $path . '/lib/WMMerchant/base/RequestModel.php';
        include $path . '/lib/WMMerchant/base/ResponseModel.php';
        include $path . '/lib/WMMerchant/base/Security.php';
        include $path . '/lib/WMMerchant/models/CreateOrderRequest.php';
        include $path . '/lib/WMMerchant/models/CreateOrderResponse.php';
        include $path . '/lib/WMMerchant/models/ViewOrderRequest.php';
        include $path . '/lib/WMMerchant/models/ViewOrderResponse.php';                
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $table=" 
            CREATE TABLE IF NOT EXISTS `order_webmoney` (
                    `webmoney_order_id` int(11) NOT NULL AUTO_INCREMENT,
                    `order_id` int(11) NOT NULL,
                    `mTransactionID` varchar(255) NOT NULL,
                    `responseTransactionID` varchar(255) NOT NULL,
                    `status` int(11) NULL DEFAULT '-100',                    
                    `created` DATETIME NOT NULL,                    
                    `redirectUrl` varchar(512) NOT NULL,
                    `message` varchar(512) NOT NULL,
                    `uimessage` varchar(512) NOT NULL,
                    `total` DECIMAL(10) NOT NULL,
                    `invoice_id` varchar(64) NOT NULL,
                    PRIMARY KEY (`webmoney_order_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
        $write->query($table);
        $wmMerchant = new WMMerchant\WMService(GlobalConfig::$config);
        $orderRequest = new WMMerchant\models\CreateOrderRequest();
        $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();            
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $mtransactionId = $order->getIncrementId() . "-" . time();
        $phoneNum = trim(preg_replace("/[^0-9]/", "",$order->getBillingAddress()->getTelephone()));
        $customerName = trim(substr(Mage::helper('webmoney')->remove_sign($order->_data['customer_lastname']) . ' ' . Mage::helper('webmoney')->remove_sign($order->_data['customer_firstname']), 0, 63));
        $customerAddress = trim(substr($order->getBillingAddress()->getStreet(-1).' '.$order->getBillingAddress()->getCity(), 0, 255));
        $customerEmail = trim($order->_data['customer_email']);
        $totalDue = round($order->getTotalDue());
        $responseURL = Mage::getUrl('webmoney/payment/response', array('_secure' => true));   
        $orderRequest->mTransactionID = $mtransactionId;
        $orderRequest->custPhone = $phoneNum;
        $orderRequest->custName = $customerName;
        $orderRequest->custAddress = $customerAddress;
        $orderRequest->custEmail = $customerEmail;
        $orderRequest->custGender = '';
        $orderRequest->description = 'Thanh toán đơn hàng #' . $order_id . ' từ Fahasa.com.';
        $orderRequest->totalAmount = $totalDue;
        $orderRequest->resultURL = $responseURL;
        $orderRequest->cancelURL = $responseURL;
        $orderRequest->errorURL = $responseURL;
        $orderRequest->addInfo = '';
        Mage::log("*** WebMoney Data Object: " . print_r($orderRequest, true), null, "webmoney.log");
        try{
            $reponse = $wmMerchant->createOrder($orderRequest);
            Mage::log("*** WebMoney Response Data: " . print_r($reponse, true), null, "webmoney.log");
            if(!$reponse->isError()){
                $sqlQuery = "INSERT INTO `order_webmoney` (`order_id`, `mTransactionID`, `responseTransactionID`, `status`, `created`, `redirectUrl`, `message`, `uimessage`, `total`, `invoice_id`) "
                    . "  VALUES (".$order_id.",'".$mtransactionId."','".$reponse->object->transactionID."'," . $reponse->errorCode . ",'" . date('Y-m-d H:i:s')
                    . "','" . $reponse->object->redirectURL."','". $reponse->message . "','" . $reponse->uiMessage ."'," . $totalDue . ",'')";
                $write->query($sqlQuery); 
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($order_id);
                $order->setState('pending_payment', self::WM_PENDING_PAYMENT_STATUS);
                $order->save();
                echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$reponse->object->redirectURL."';</SCRIPT>");	
                exit();
            }else {
                //Handle fail request payment
                Mage::log("*** WebMoney. Fail to request webmoney order for order id: $order_id. Redirect to checkout/onepage/failure/'.", null, "webmoney.log");
                $fail_url = Mage::getUrl("checkout/onepage/failure/");
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($order_id);
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'canceled', 'Thanh toán qua WebMoney không thành công. Happen prior redirect to WM. Response Error.');
                $order->save();
                echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$fail_url."';</SCRIPT>");	
            }
        } catch (Exception $ex) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($order_id);
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'canceled', 'Thanh toán qua WebMoney không thành công. Happen prior redirect to WM. Exception catch.');
            $order->save();
            Mage::log("*** WebMoney. Exception when create webmoney order for order id: $order_id. Redirect to checkout/onepage/failure/'.", null, "webmoney.log");
            Mage::log("*** WebMoney. Stacktrace is: " . $ex->getTraceAsString(), null, "webmoney.log");
            Mage::logException($ex);
            $fail_url = Mage::getUrl("checkout/onepage/failure/");
            echo ("<SCRIPT LANGUAGE='JavaScript'>window.location.href='".$fail_url."';</SCRIPT>");	
        }        
    }
}
