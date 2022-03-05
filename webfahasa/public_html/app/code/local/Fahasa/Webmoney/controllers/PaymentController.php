<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PaymentController
 *
 * @author phamtn8
 */
class Fahasa_Webmoney_PaymentController extends Mage_Core_Controller_Front_Action {

    //put your code here
    public function redirectAction() {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'webmoney', array('template' => 'webmoney/redirect.phtml'));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }
    
    public function responseAction(){
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
        $wmMerchant = new WMMerchant\WMService(GlobalConfig::$config);
        $success = $wmMerchant->validateSuccessURL();
        $fail = $wmMerchant->validateFailedURL();
        $cancel = $wmMerchant->validateCanceledURL();
        $transactionId = $_GET['transaction_id'];                
        Mage::log("*** WebMoney Redirect Back: TransactionId: $transactionId, success status: $success; fail status: $fail; cancel status: $cancel", null, "webmoney.log");
        if($transactionId === null){
            Mage::log("*** WebMoney transaction id is null. Redirect to failure page", null, "webmoney.log");
            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure/', array('_secure'=>true));
            return;
        }
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $readresult = $write->query("select * from order_webmoney where mTransactionID='".$transactionId."'");
        $orderId = -1;
        $statusOrder = -1;
        $wmOrderInvoiceId = '';
        while ($row = $readresult->fetch())
        {

            $orderId = $row['order_id'];
            $statusOrder = $row['status'];
            $wmOrderInvoiceId = $row['invoice_id'];
            $dbTransId = $row['responseTransactionID'];
        }
        $orgin = $_GET['orgin'];
        $aData = array();
        if($orgin == "mobile/") {
            $ipHost = $_SERVER['HTTP_HOST'];
            $url = "http://$ipHost:88/broadcastMessage";
            $aData['transactionId'] = $transactionId;
            $aData['orderId'] = $orderId;
        } else {
            $aData['transactionId'] = $transactionId;
        }
        if($success === true){
            //Check trang thai cua order tren WM
            $request = new WMMerchant\models\ViewOrderRequest();
            $request->mTransactionID = $transactionId;
            $response = $wmMerchant->viewOrder($request);
            Mage::log("*** WebMoney Check Status with TransactionId: $transactionId. Response is:", null, "webmoney.log");
            Mage::log(print_r($response, true), null, "webmoney.log");
            $invoiceId = $response->object->invoiceID;
            $resTransactionId = $response->object->transactionID;
            $resStatus = $response->errorCode;            
            if($invoiceId != null){                
                if(empty($wmOrderInvoiceId)){
                    if($statusOrder == $resStatus && $statusOrder == 0 && $dbTransId == $resTransactionId){  //no error. should not
                        $write->query("update `order_webmoney` set `invoice_id`= '".$invoiceId."' where `order_id`= ".(int)$orderId); 
                        //Redirect to success checkout page
                        $order = Mage::getModel( 'sales/order' );
                        $order->loadByIncrementId( $orderId );
                        $order->setState( Mage_Sales_Model_Order::STATE_NEW, Fahasa_Webmoney_Model_Payment::WM_PAYMENT_SUCCESS_STATUS, 'Thanh toan webmoney thanh cong' );
//                        $order->sendNewOrderEmail();
//                        $order->setEmailSent( true );
                        $order->save();     
                        $this->redirectURL($orgin, 'success?pt=webmoney', $url, $aData);
//                        $this->_redirect( 'checkout/onepage/success?pt=webmoney', array('_secure'=>true));
                    }
                }else{
                    //Already see $wmOrderInvoiceId, this is a dup request, redirect to successful checkout page
                    Mage::log("*** WebMoney. Already see invoice Id: $wmOrderInvoiceId. This is likely a duplicated request. Order Id: $orderId. Redirect to success checkout.", null, "webmoney.log");
                    $this->redirectURL($orgin, 'success', $url, $aData);
//                    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));
                }
            }else{
                Mage::log("*** WebMoney. Invoice Id is null for transaction id $transactionId. The response object is: " . print_r($response, true), null, "webmoney.log");
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);
                $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'canceled', 'Thanh toán qua WebMoney không thành công. No invoice id');
                $order->save();
                $this->redirectURL($orgin, 'failure', $url, $aData);
//                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure/', array('_secure'=>true));
            }
        }else{
            //handle case for both fail or cancel
            Mage::log("*** WebMoney. Fail or cancel with transaction id: $transactionId. Redirect to checkout/onepage/failure/'.", null, "webmoney.log");
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($orderId);
            $msg = "Thanh toán qua WebMoney không thành công";
            if($fail === true){
                $msg .= ". Fail On WM side.";
            }else if($cancel === true){
                 $msg .= ". Khach Hang Cancel tren WebMoney.";
            }
            $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, 'canceled', $msg);
            $order->save();
            $this->redirectURL($orgin, 'failure', $url, $aData);
//            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure/', array('_secure'=>true));
        }        
    }

    public function redirectURL($orgin, $action, $url, $aData) {
        if ($orgin == "mobile/") {
            $messContent = array();
            $mess = array();
            if ($action == "failure") {
                $mess["message"] = "Thanh toán qua webmoney không thành công";
                $mess["paymentStatus"] = 0;
                $messContent["msgContent"] = $mess;
                $this->cancelPaymentStatus($aData['transactionId'], -100);
            } else {
                $mess["message"] = "Thanh toán qua webmoney thành công";
                $mess["paymentStatus"] = 1;
                $mess["orderId"] = $aData["orderId"];
                $mess["method"] = "123paymaster";
//                $mess["chanelId"] = $aData["mTransactionID"] .  $aData["orderId"];
                $messContent["msgContent"] = $mess;
                $messContent["transactionId"] = $aData["mTransactionID"];
                $messContent["orderId"] = $aData["orderId"];
            }
            $wmMerchant = new WMMerchant\WMService(GlobalConfig::$config);
            $wmMerchant->callRestFahasa($url,$messContent);
        } else {
            if($action == "failure"){
                $this->cancelPaymentStatus($aData['transactionId'], -100);
            }
            $this->_redirect( "checkout/onepage/$action?pt=webmoney", array('_secure'=>true));
//            Mage_Core_Controller_Varien_Action::_redirect("checkout/onepage/$action", array('_secure' => true));
        }
    }
    
    public function cancelPaymentStatus($mTransactionID,$status) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sqlQuery = "UPDATE order_webmoney set status = ".$status." where mTransactionID = '".$mTransactionID."'";
        $write->query($sqlQuery);
        Mage::log("*** webmoney cancelAction set status: cancel TransactionID = " . $mTransactionID . " status: " . $status, null, "webmoney.log");
    }
}
