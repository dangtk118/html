<?php

/*
  Pg123paymaster Payment Controller
  By: 123Pay
  123pay.vn
 */

class P123paymaster_Pg123paymaster_PaymentController extends Mage_Core_Controller_Front_Action {

    // The redirect action is triggered when someone places an order
    public function redirectAction() {

        $this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'pg123paymaster', array('template' => 'pg123paymaster/redirect.phtml'));
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    // The response action is triggered when your gateway sends back a response after processing the customer's payment
    public function responseAction() {
        include 'rest.client.class.php';
        include 'common.class.php';



        $transactionID = $_GET['transactionID'];
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $readresult = $write->query("select * from order_123pay where merchant_transactionID='" . $transactionID . "'");
        $orderId = 0;
        $statusOrder = -1;
        while ($row = $readresult->fetch()) {

            $orderId = $row['order_id'];
            $statusOrder = $row['status'];
        }


        $validated = true;
        $pg123pay = new P123paymaster_Pg123paymaster_Model_Standard();
        //sandbox
        $merchantCode = 'MICODE';
        $secretKey = 'MIKEY';
        $passCode = 'MIPASSCODE';
        $queryOrderUrl = 'https://sandbox.123pay.vn/miservice/queryOrder1';
        //production
        if ($pg123pay->getModeMaster() == 1) {
            $merchantCode = trim($pg123pay->getMerchantCodeMaster());
            $passCode = trim($pg123pay->getPasscodeMaster());
            $secretKey = trim($pg123pay->getSecretKeyMaster());
            $queryOrderUrl = trim($pg123pay->getQueryOrderUrlMaster());
        }
        $aConfig = array
            (
            'merchantCode' => $merchantCode,
            'url' => $queryOrderUrl,
            'key' => $secretKey,
            'passcode' => $passCode
        );


        $flag = false;
        $transactionID = $_GET['transactionID'];
        $time = $_GET['time'];
        $status = $_GET['status'];
        $ticket = $_GET['ticket'];

        $recalChecksum = md5($status . $time . $transactionID . $aConfig['key']);
        if ($recalChecksum != $ticket) {
            header('Location: ' . Mage::getBaseUrl());
            exit;
        }

        try {
            $entify_id = 0;
            if ($orderId > 0) {
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);
                $entify_id = $order->getId();
            }
            $cartURL = Mage::getBaseUrl(); //Mage::getUrl('sales/order/view/order_id', array('order_id' => $entify_id, '_secure' => true));

            $IP = $_SERVER['REMOTE_ADDR'];
            if ($IP == '::1')
                $IP = '127.0.0.1';
            $aData = array
                (
                'mTransactionID' => $transactionID,
                'merchantCode' => $merchantCode,
                'clientIP' => $IP,
                'passcode' => $passCode,
                'checksum' => '',
            );
            Mage::log("*** 123Pay Master - responseAction. Transaction Id: $transactionID, Order Id: $orderId, Order Status $statusOrder", null, "123pay.log");
            $data = Common::callRest($aConfig, $aData);
            if($orgin == "mobile") {
                $ipHost = $_SERVER['HTTP_HOST'];
                $aConfig["url"] = "http://$ipHost:88/broadcastMessage";
                $aData['transactionId'] = $transactionID;
                $aData['orderId'] = $orderId;
            }
            $msg = '';
            $result = $data->return;
            Mage::log("*** 123Pay Master - responseAction. Results Data: " . print_r($result, true), null, "123pay.log");
            if ($result['httpcode'] == 200) {
                if ($result[0] == '1') {
                    if ($result[2] == '1') {//success

                        //Do success call service
                        $msg = "Quý khách đã thanh toán thành công.";
                        if ($orderId > 0) {

                            if ($statusOrder != $result[2]) {


                                $write->query("update `order_123pay` set `123PayTransactionID`= '" . $result[1] . "',`status`=" . $result[2] . ",`discount_total`= " . $result[4] . " where `order_id`= " . (int) $orderId);

                                // Payment was successful, so update the order's state, send order email and move to the success page
                                $order = Mage::getModel('sales/order');
                                $order->loadByIncrementId($orderId);
                                //get state by status
                                $status_config = Mage::getStoreConfig('payment/pg123paymaster/order_status_succeed_master'); //value																		
                                $item = Mage::getResourceModel('sales/order_status_collection')
                                        ->joinStates()
                                        ->addFieldToFilter('main_table.status', $status_config)
                                        ->getFirstItem();
                                $state_custom = $item->getState();
                                $order->setState($state_custom, $status_config, 'Thanh toán qua 123Pay thành công');
                                $order->save();
                                Mage::log("*** 123Pay Master responseAction: Thanh toán qua 123Pay thành công.  Order id " . $order->getIncrementId() . " status: " . $order->getStatus(), null, "123pay.log");
                                try {
//                                    $order->sendNewOrderEmail();
//                                    $order->setEmailSent(true);
                                } catch (Exception $_e) {
                                    
                                }

                                // Mage::getSingleton('checkout/session')->unsQuoteId();
                                $this->redirectURL($orgin,"success?pt=123pay",$aConfig, $aData);
//                                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success?pt=123pay', array('_secure' => true));
                            } elseif ($statusOrder == 1)
                                $this->redirectURL($orgin,"success",$aConfig, $aData);
//                                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));
                        }
                        $flag = true;
                    }else {

                        $msg = "Thanh toán qua 123Pay không thành công, quý khách nhấn <a href='" . $cartURL . "'>vào đây</a>. để tiếp tục mua hàng";
                        if ($statusOrder != $result[2] && $orderId > 0) {
                            $order = Mage::getModel('sales/order');
                            $order->loadByIncrementId($orderId);
                            $this->cancelOrder($order);
                            $this->redirectURL($orgin,"failure",$aConfig, $aData);
//                            Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure/', array('_secure'=>true));
                        }
                    }
                } else {

                    $msg = "Thanh toán qua 123Pay không thành công, quý khách nhấn <a href='" . $cartURL . "'>vào đây</a> để tiếp tục mua hàng.";
                    if ($statusOrder != $result[2] && $orderId > 0) {
                        $order = Mage::getModel('sales/order');
                        $order->loadByIncrementId($orderId);
                        $this->cancelOrder($order);
                        $this->redirectURL($orgin,"failure",$aConfig, $aData);
//                        Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure/', array('_secure'=>true));
                    }
                }
            } else {
                $msg = "Thanh toán qua 123Pay không thành công, quý khách nhấn <a href='" . $cartURL . "'>vào đây</a> để tiếp tục mua hàng.";
                if ($statusOrder != $result[2] && $orderId > 0) {
                    $order = Mage::getModel('sales/order');
                    $order->loadByIncrementId($orderId);
                    $this->cancelOrder($order);
                    $this->redirectURL($orgin,"failure",$aConfig, $aData);
//                    Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure/', array('_secure'=>true));
                }
            }
        } catch (Exception $e) {
            $msg = "Thanh toán qua 123Pay không thành công, quý khách nhấn <a href='" . $cartURL . "'>vào đây</a> để tiếp tục mua hàng.";
            if ($statusOrder != $result[2] && $orderId > 0) {
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);
                $this->cancelOrder($order);
                Mage::log("*** 123Pay Master. Stacktrace is: " . $e->getTraceAsString(), null, "123pay.log");
                $this->redirectURL($orgin,"failure",$aConfig, $aData);
//                Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure/', array('_secure'=>true));
            }
        }
//        $this->loadLayout();
//        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'pg123pay', array('template' => 'pg123pay/response.phtml'));
//        $this->getLayout()->getBlock('content')->append($block);
//        $block->assign('msg', $msg);
//        $this->renderLayout();
    }
    
    public function redirectURL($orgin, $action, $aConfig, $aData) {
        if ($orgin == "mobile") {
            $messContent = array();
            $mess = array();
            if ($action == "failure") {
                $mess["message"] = "Thanh toán qua 123Paymaster không thành công";
                $mess["paymentStatus"] = 0;
                $messContent["msgContent"] = $mess;
                $this->cancelPaymentStatus($aData['mTransactionID'], -100);
            } else {
                $mess["message"] = "Thanh toán qua 123Paymaster thành công";
                $mess["paymentStatus"] = 1;
                $mess["orderId"] = $aData["orderId"];
                $mess["method"] = "123paymaster";
                $messContent["msgContent"] = $mess;
                $messContent["transactionId"] = $aData["mTransactionID"];
                $messContent["orderId"] = $aData["orderId"];
            }

            Common::callRest($aConfig, $messContent);
        } else {
            if($action == "failure"){
                $this->cancelPaymentStatus($aData['mTransactionID'], -100);
            }
            Mage_Core_Controller_Varien_Action::_redirect("checkout/onepage/$action", array('_secure' => true));
        }
    }

    // The cancel action is triggered when an order is to be cancelled
    public function cancelAction() {
        Mage::log("*** 123Pay Master cancelAction: is trigger.", null, "123pay.log");
        if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if ($order->getId()) {
                Mage::log("*** 123Pay Master cancelAction: cancel order id " . $order->getLastRealOrderId(), null, "123pay.log");
                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
            }
        }
    }

    /**
     * Cancel given order because unsuccessful payment. However, only cancel if 
     * order status is not "paid".
     * @param type $order
     */
    public function cancelOrder($order){
        $status = $order->getStatus();
        Mage::log("*** 123Pay Master cancelOrder method: order id " . $order->getIncrementId() . " status: " . $status, null, "123pay.log");
        if($status != "paid"){
            Mage::log("*** 123Pay Master cancelAction: cancel order id " . $order->getIncrementId() . " status: " . $order->getStatus(), null, "123pay.log");
            
            $helper = Mage::helper('cancelorder');
            $increment_id = $order->getIncrementId();
            $helper->thirdPartyPaymentFailRest($order, $increment_id);
//            $order->setState('new', 'canceled', 'Thanh toán qua 123Pay không thành công');
//            $order->save();
        }
    }
    
    public function cancelPaymentStatus($mTransactionID, $status) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sqlQuery = "UPDATE order_123pay set status = " . $status . " where merchant_transactionID = '" . $mTransactionID . "'";
        $write->query($sqlQuery);
        Mage::log("*** 123Paymaster cancelAction set status: cancel transactionID = " . $mTransactionID . " status: " . $status, null, "123pay.log");
    }

    public function notifyAction() {
        include 'common.class.php';

        $model = Mage::getModel('pg123pay/standard');

        $mTransactionID = $_REQUEST['mTransactionID'];
        $bankCode = $_REQUEST['bankCode'];
        $transactionStatus = $_REQUEST['transactionStatus'];
        $description = $_REQUEST['description'];
        $ts = $_REQUEST['ts'];
        $checksum = $_REQUEST['checksum'];
        Mage::log("*** 123Pay Master notifyAction: Request " . print_r($_REQUEST, true), null, "123pay.log");

        $sMySecretkey = trim($model->getSecretKey()); //key use to hash checksum that will be provided by 123Pay
        $sRawMyCheckSum = $mTransactionID . $bankCode . $transactionStatus . $ts . $sMySecretkey;
        $sMyCheckSum = sha1($sRawMyCheckSum);
        Mage::log("*** 123Pay Master notifyAction: My checksum " . $sMyCheckSum, null, "123pay.log");
        Mage::log("*** 123Pay Master notifyAction: Request checksum " . $checksum, null, "123pay.log");
        if ($sMyCheckSum != $checksum) {
            $this->response($mTransactionID, '-1', $sMySecretkey);
        }
        $iCurrentTS = time();
        $iTotalSecond = $iCurrentTS - $ts;

        $iLimitSecond = 300; //5 min = 5*60 = 300                
        $processResult = $this->process($mTransactionID, $bankCode, $transactionStatus);
        $this->response($mTransactionID, $processResult, $sMySecretkey);


        /* ===============================Function region======================================= */
    }

    public function process($mTransactionID, $bankCode, $transactionStatus) {
        try {
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $readresult = $write->query("select * from order_123pay where merchant_transactionID='" . $mTransactionID . "'");
            $orderId = 0;
            $statusOrder = -1;
            while ($row = $readresult->fetch()) {

                $orderId = $row['order_id'];
                $statusOrder = $row['status'];
            }
            Mage::log("*** 123Pay Master process method - order Id:" . $orderId . " statusOrder: " . $statusOrder , null, "123pay.log");
            if ($statusOrder == 1)
                return 2;
            $write->query("update `order_123pay` set `status`=" . $transactionStatus . " where `order_id`= " . (int) $orderId);
            
            // Payment was successful, so update the order's state, send order email and move to the success page
            if ($transactionStatus == 1) {
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);
                //get state by status
                $status_config = Mage::getStoreConfig('payment/pg123paymaster/order_status_succeed_master'); //value																		
                $item = Mage::getResourceModel('sales/order_status_collection')
                        ->joinStates()
                        ->addFieldToFilter('main_table.status', $status_config)
                        ->getFirstItem();
                $state_custom = $item->getState();
                $order->setState($state_custom, $status_config, 'Thanh toán qua 123Pay thành công');                
                $order->save();
                Mage::log("*** 123Pay Master process method: Thanh toán qua 123Pay thành công. Order: " . $order->getIncrementId() . " status: " . $order->getStatus(), null, "123pay.log");
                try {
//                    $order->sendNewOrderEmail();
//                    $order->setEmailSent(true);
                } catch (Exception $_e) {
                    return 1;
                }
            } else {
                $order = Mage::getModel('sales/order');
                $order->loadByIncrementId($orderId);
                $this->cancelOrder($order);
                Mage::log("*** 123Pay Master process method: status order cancelled. Order: " . $order->getIncrementId(), null, "123pay.log");
                $order->save();
            }



            return 1; //if process successfully
        } catch (Exception $_e) {
            return -3;
        }
    }

    public function response($mTransactionID, $returnCode, $key) {
        $ts = time();
        $sRawMyCheckSum = $mTransactionID . $returnCode . $ts . $key;
        $checksum = sha1($sRawMyCheckSum);
        $aData = array(
            'mTransactionID' => $mTransactionID,
            'returnCode' => $returnCode,
            'ts' => time(),
            'checksum' => $checksum
        );
        Mage::log("*** 123Pay Master response method: " . print_r($aData, true), null, "123pay.log");
        echo json_encode($aData);
        exit;
    }

}
