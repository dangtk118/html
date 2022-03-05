<?php

require_once('Config.php');

class TTS_Momopay_Model_Momopay extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'momopay';
    protected $_formBlockType = 'momopay/form';
    protected $_infoBlockType = 'momopay/info';
    
    const PARTNER_CODE = "partnerCode";
    const PARTNER_REF_ID = "partnerRefId";
    const PARTNER_TRANS_ID = "partnerTransId";
    const CUSTOMER_NUMBER = "customerNumber";
    const APP_DATA = "appData";
    const HASH = "hash";
    const VERSION = "version";
    const AMOUNT = "amount";
    const PAY_TYPE = "payType";
    const MOMO_TRANS_ID = "momoTransId";
    const REQUEST_ID = "requestId";
    const REQUEST_TYPE = "requestType";
    const SIGNATURE = "signature";
    const STATUS = "status";
    const MESSAGE = "message";
    const PAY_TYPE_MOBILE = 3; //fixed
    const VERSION_MOBILE_VALUE = 2.0; //fix

    protected static $_CONFIG;
    
    public function getConfig(){
        if (!self::$_CONFIG) {
            self::$_CONFIG = new Config(self::getPartnerCode(), self::getPublicKey(), self::getBaseUrl(), self::getSecretKey());
        }
        
        return self::$_CONFIG;
    }
    
     public function getBaseUrl(){
        return $this->getConfigData('momo_base_url');
    }
    
    public function getPublicKey(){
        return $this->getConfigData('momo_public_key');
    }
    
    public function getPartnerCode(){
        return $this->getConfigData('momo_Partner');
    }
    
    public function getSecretKey(){
        return $this->getConfigData('momo_Secret');
    }
    
    public function getTitle() {
        return $this->getConfigData('title');
    }

    public function get_icon() {
        return $this->getConfigData('icon');
    }

    public function getOrderPlaceRedirectUrl() {
        return \Mage::getUrl('momopay/standard/redirect', array('_secure' => true));
    }
    
    public function createRequestIdForOrder($orderId){
        $date = date("ymdhis");
        return $date.$orderId;
    }
    
    
    // buoc 1 : handle cho web
    public function getUrlMomopay($orderid)
    {
        // lay thong tin order ra :
        $order = \Mage::getModel('sales/order')->loadByIncrementId($orderid);
        if ($order->getId())
        {
            //config momo : 
            $endpoint = $this->getConfigData('momo_Url');
            $partnerCode = $this->getConfigData('momo_Partner');
            $accessKey = $this->getConfigData('hash_code');
            $serectkey = $this->getConfigData('momo_Secret');
            $orderInfo = "Thanh toán cho đơn hàng " . $orderid;

            //lay grand_total
            $amount = (string) round($order->getGrandTotal());

            //when call momopay, order_id = request_id, but in database order_id is increment_id of order_fahasa, request_id is transaction_id
            $orderId = self::createRequestIdForOrder($orderid);
            $requestId = $orderId;
            $requestType = "captureMoMoWallet";
            $extraData = "merchantName=FAHASA.COM";

            $returnUrl = \Mage::getUrl("momopay/standard/response/");

            /*
             * Lưu ý: link notifyUrl không phải là dạng localhost
             */
            $ipHost = $_SERVER['HTTP_HOST'];
            $notifyurl = $this->getConfigData('momo_Url2');
            //$notifyurl = "https://webhook.site/a4d2acd1-e355-4cfd-9040-1fdef16936ba";
            //before sign HMAC SHA256 signature
            $rawHash = "partnerCode=" . $partnerCode . "&accessKey=" . $accessKey . "&requestId=" . $requestId . "&amount=" . $amount . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&returnUrl=" . $returnUrl . "&notifyUrl=" . $notifyurl . "&extraData=" . $extraData;
            $signature = hash_hmac("sha256", $rawHash, $serectkey);
            $data = array('partnerCode' => $partnerCode,
                'accessKey' => $accessKey,
                'requestId' => $requestId,
                'amount' => $amount,
                'orderId' => $orderId,
                'orderInfo' => $orderInfo,
                'returnUrl' => $returnUrl,
                'notifyUrl' => $notifyurl,
                'extraData' => $extraData,
                'requestType' => $requestType,
                'signature' => $signature
            );

            // 1. get resultUrl;
            // 1.1 fail : cancelOrder;
            // 1.2 success : Url;
            // 2. save order Momo Db order_momopay and log Momo 
            try {
                // insert vo db order_momopay
                $paytype = "webmomo";
                self::insertOrderMomopay($orderid, $requestId, $amount, $paytype);

                // cap nhat state cho order 
                $order->setState("new", "pending_payment", 'Thanh toán qua Momopay chuẩn bị xử lý.');
                $order->save();

                $result = $this->execPostRequest($endpoint, json_encode($data));
                $jsonResult = json_decode($result, true);  // decode json
                // neu reponse tra ve ko loi => thanh cong 
                // tra ve url di toi momo de thanh toan.
                if ($jsonResult['errorCode'] === 0)
                {
                    //transaction_id = $requestId;
                    $jsonResult['transaction_id'] = $requestId;
                    \Mage::log("*** momopay - create order Momopay success #" . $orderid . "   requestId :" . $requestId . " Data Object: " . print_r(json_decode($result), true), null, "momopay.log");
//                $jsonResult = json_decode($result, true);  // decode json
                    return $jsonResult;
                }
                else
                {

//               return self::redirectFailureFromCreateOrderMomopay($order, $jsonResult);
                    return null;
                }
            } catch (Exception $ex) {
//            return self::redirectFailureFromCreateOrderMomopay($order, $ex);
                return null;
            }
        }
        return null;
    }

    // Buoc 2 : sau khi thanh toan tu momo xong => xu ly kq trang thai tra ve 
    // case 1 : == 0 => success 
    // case 2 : != 0 => fail
    // function dc su dung chung cho ca callback voi redirect (handle cho web)
    public function checkOrderStatusFromRedirectMomo($resultFromRedirect,$callback = 0) {
        
        // khai bao bien result co 2 attr : success va status
        $result = array();
        //Checksum (Kiem tra du lieu dung k)
        \Mage::log("*** momopay -checking data checkOrderStatusFromRedirectMomo of Momopay .........  # orderId" . $resultFromRedirect['orderId'] . " requestId " . $resultFromRedirect['requestId'], null, "momopay.log");

        $serectkey = self::getconfig()->getSecretKey();
        $rawHashCheck = "partnerCode=" . $resultFromRedirect['partnerCode'] . "&accessKey=" . $resultFromRedirect['accessKey'] . "&requestId=" . $resultFromRedirect['requestId'] . "&amount=" . $resultFromRedirect['amount'] .
                "&orderId=" . $resultFromRedirect['orderId'] . "&orderInfo=" . $resultFromRedirect['orderInfo'] .
                "&orderType=" . $resultFromRedirect['orderType'] . "&transId=" . $resultFromRedirect['transId'] . "&message=" . $resultFromRedirect['message'] .
                "&localMessage=" . $resultFromRedirect['localMessage'] . "&responseTime=" . $resultFromRedirect['responseTime'] . "&errorCode=" . $resultFromRedirect['errorCode'] . "&payType=" . $resultFromRedirect['payType'] . "&extraData=" . $resultFromRedirect['extraData'];
        //hash_hmac 
        $partnerSignatureCheck = hash_hmac("sha256", $rawHashCheck, $serectkey);
        if ($resultFromRedirect['signature'] == $partnerSignatureCheck) {
            \Mage::log("*** momopay " . $stringCallBack . " - Success. Checksum from resultUrl is vaild # orderId" . $resultFromRedirect['orderId'] . " requestId " . $resultFromRedirect['requestId'], null, "momopay.log");
            
            $orderMomo = $this->getOrderMomopayInfo($resultFromRedirect['orderId'], $resultFromRedirect['requestId']);
            // kiem tra order nay` co callback khong ?
            if ($callback == 1) {
                if ($orderMomo['callback'] != 0) {
                    $result['success'] = (int)$orderMomo['status'] === 0 ? true : false;
                    $result['status'] = $orderMomo['status'];
                    \Mage::log("*** momopay - order #" . $resultFromRedirect['orderId'] . " requestId #" . $resultFromRedirect['requestId'] . " has been call and handle", null, "momopay.log");
                    return $result;
                } else {
                    // co callback nhung chua thuc thi :
                    \Mage::log("*** momopay - order #" . $resultFromRedirect['orderId'] . " requestId #" . $resultFromRedirect['requestId'] . " start CALLBACK", null, "momopay.log");
                    $stringCallBack = "CALLBACK";
                }
            } else {
                // kiem tra neu calback da xu ly trc 
                if ($orderMomo['callback'] != 0) {
                    $result['success'] = (int)$orderMomo['status'] === 0 ? true : false;
                    $result['status'] = $orderMomo['status'];
                    \Mage::log("*** momopay - order #" . $resultFromRedirect['orderId'] . " requestId #" . $resultFromRedirect['requestId'] ." + ". $result['success']." has been call and handle", null, "momopay.log");
                    return $result;
                }

                \Mage::log("*** momopay - order #" . $resultFromRedirect['orderId'] . " requestId #" . $resultFromRedirect['requestId'] . " not run CALLBACK", null, "momopay.log");
                $stringCallBack = "";
            }

            // sau khi hash_hmac va so sanh thanh cong va kiem tra calback => kiem tra giao dich lai 1 lan nua
            //fixed repayment: only get status + do not update order_momopay status because it will be updated in belove code. transaction update order fahasa + order_momopay
            $orderResponse = $this->checkStatusOrderMomopay($resultFromRedirect);
            if ($orderResponse != null) {
                if ($orderResponse['errorCode'] === 0) {
                    \Mage::log("*** momopay " . $stringCallBack . " - responseorder have status Success requestId=" . $orderResponse['requestId'] . ", orderId=" . $orderResponse['orderId'] . ", status=" . $orderResponse['errorCode'] . ", describe = " . $orderResponse['localMessage'], null, "momopay.log");
                    $result['success'] = true;
                    $result['status'] = $orderResponse['errorCode'];

                    // callback 1 => update order fahasa
                    if ($callback == 1) {
                        $this->updateOrderFahasa($orderResponse, $orderMomo['order_id']);
                    }
                    
                } else if ($orderResponse['errorCode'] !== 7) { // errors loi khong phai pending. 7: pending
                    \Mage::log("*** momopay " . $stringCallBack . " - responseorder have status cancel requestId=" . $orderResponse['requestId'] . ", orderId=" . $orderResponse['orderId'] . ", status=" . $orderResponse['errorCode'] . ", describe = " . $orderResponse['localMessage'], null, "momopay.log");
                    $result['success'] = false;
                    $result['status'] = $orderResponse['errorCode'];
                    // callback 1 => update order fahasa
                    if ($callback == 1) {
                        $this->updateOrderFahasa($orderResponse, $orderMomo['order_id']);
                    }
                } else { // if orderResponse pedning : 
                    for ($i = 1; $i < 3; $i++) {
                        sleep($i);
                        $dem = $i+1;
                        $orderResult = $this->checkStatusOrderMomopay($orderResponse);
                        \Mage::log("*** momopay " . $stringCallBack . " - check time :".$dem." requestId=" . $orderResult['requestId'] . ", orderId=" . $orderResult['orderId'] ."******************", null, "momopay.log");

                        if ($orderResult['errorCode'] === 0) {
                            \Mage::log("*** momopay " . $stringCallBack . " - responseorder have status Success requestId=" . $orderResult['requestId'] . ", orderId=" . $orderResult['orderId'] . ", status=" . $orderResult['errorCode'] . ", describe = " . $orderResult['localMessage'], null, "momopay.log");
                            $result['success'] = true;
                            $result['status'] = $orderResult['errorCode'];
                            
                            // callback 1 => update order fahasa
                            if ($callback == 1) {
                                $this->updateOrderFahasa($orderResult, $orderMomo['order_id']);
                            }
                            break;
                        }
                    }
                    // sau 3 lan that bai :
                    if ($orderResult['errorCode'] !== 0) {
                        $result['success'] = false;
                        $result['status'] = $orderResult['errorCode'] ?? null;
                        if ($orderResponse['errorCode'] === 7) {
                            \Mage::log("*** momopay " . $stringCallBack . " - responseorder have status still pending requestId=" . $orderResult['requestId'] . ", orderId=" . $orderResult['orderId'] . ", status=" . $orderResult['errorCode'] . ", describe = " . $orderResult['localMessage'], null, "momopay.log");
                        } else {
                            \Mage::log("*** momopay " . $stringCallBack . " - responseorder have status Failed requestId=" . $orderResult['requestId'] . ", orderId=" . $orderResult['orderId'] . ", status=" . $orderResult['errorCode'] . ", describe = " . $orderResult['localMessage'], null, "momopay.log");
                        }
                        \Mage::log("*** momopay  step orderResponse pedning check callback : ". $callback , null, "momopay.log");
                        
                        // callback 1 => update order fahasa
                        if ($callback == 1) {
                            $this->updateOrderFahasa($orderResult, $orderMomo['order_id']);
                        }
                    } 
                }
            } else {
                    $result['success'] = false;
                    $result['status'] = null;
                    \Mage::log("*** momopay ".$stringCallBack." - Can not get data orderResponse data is null", null, "momopay.log");
                }
           
        } else { // fail : order null
            $result['success'] = false;
            $result['status'] = null;
            \Mage::log("*** momopay ".$stringCallBack." - Error. Checksum from resultUrl is not vaild, data = ".print_r($resultFromRedirect,true), null, "momopay.log");
        }

        return $result;
    }
    
    // GET check status order from momopay
    public function checkStatusOrderMomopay($resultFromRedirect) {
        //config 
        $requestType = "transactionStatus";
        $serectkey = self::getconfig()->getSecretKey();
        $endpoint = $this->getConfigData('momo_Url');
        
        //before sign HMAC SHA256 signature
        $rawHash = "partnerCode=".$resultFromRedirect['partnerCode']."&accessKey=".
                $resultFromRedirect['accessKey']."&requestId=".$resultFromRedirect['requestId']."&orderId=".$resultFromRedirect['orderId']."&requestType=".$requestType;
        
        $signature = hash_hmac("sha256", $rawHash, $serectkey);
        
        // set data chuan bi gui
        $data = array('partnerCode' => $resultFromRedirect['partnerCode'],
            'accessKey' => $resultFromRedirect['accessKey'],
            'requestId' => $resultFromRedirect['requestId'],
            'orderId' => $resultFromRedirect['orderId'],
            'requestType' => $requestType,
            'signature' => $signature
        );

        // gui len Momo  
        $resultRes = $this->execPostRequest($endpoint, json_encode($data));
        $result = json_decode($resultRes, true); 
       
        // check status
        if (!empty($result)) {
            //Checksum (Kiem tra du lieu dung k)
            \Mage::log("*** momopay -checking data reponse checkStatusOrder of Momopay .........  #" . $result['orderId'] . " requestId " . $result['requestId'], null, "momopay.log");
            
            $rawHashCheck = "partnerCode=" . $result['partnerCode'] . "&accessKey=" . $result['accessKey'] . "&requestId=" . $result['requestId'] . "&orderId=" . $result['orderId'] .
                    "&errorCode=" . $result['errorCode'] . "&transId=" . $result['transId'] .
                    "&amount=" . $result['amount'] . "&message=" . $result['message'] . "&localMessage=" . $result['localMessage'] .
                    "&requestType=" . $result['requestType'] . "&payType=" . $result['payType'] . "&extraData=" . $result['extraData'];
            
            //hash_hmac 
            $partnerSignatureCheck = hash_hmac("sha256", $rawHashCheck, $serectkey);
            
            if ($result['signature'] == $partnerSignatureCheck) {
                \Mage::log("*** momopay -checking data reponse checkStatusOrder success  #" . $result['orderId'] . " requestId " . $result['requestId'], null, "momopay.log");
                // do not update order_momopay. it is called in transaction order_fahasa
                return $result;
            } else {
                \Mage::log("*** momopay -checking data reponse checkStatusOrder failed  #" . $result['orderId'] . " requestId " . $result['requestId'], null, "momopay.log");
                return null;
            }
        } else {
            \Mage::log("*** momopay -data empty  #" . $result['orderId'] . " requestId " . $result['requestId'], null, "momopay.log");
            return null;
        }
    }
    
    // update order_fahasa
    public function updateOrderFahasa($orderResponse, $order_id) {
        if ($orderResponse != null) {
            //check order_Id && request_ID
            $orderId = $order_id;
            $order = \Mage::getModel('sales/order')->loadByIncrementId($orderId);
            
            //$orderResponse['orderId']: transaction_id
            $is_current_transaction = Mage::helper('repayment')->checkTransIsCurPayment($order, $orderResponse['orderId']);
            $is_in_repayment_time = Mage::helper('repayment')->checkOrderInPaymentTime($order);
            Mage::log("Callback: transaction_id=". $orderResponse['orderId'] . " is_in_repayment=" . $is_in_repayment_time . ", is_current_transaction=" . $is_current_transaction, null, "momopay.log");
            if ($orderResponse['errorCode'] === 0)
            {
                //payment success
                if ($is_in_repayment_time)
                {
                    if ($is_current_transaction)
                    {
                        self::handleOrderSuccess($order, $orderResponse);
                        
                    }
                    else
                    {
                        self::markRefundOrder($order, $orderResponse);
                    }
                }
                else
                {
                    if ($is_current_transaction)
                    {
                        self::handleOrderSuccess($order, $orderResponse);
                    }
                    else
                    {
                        self::markRefundOrder($order, $orderResponse);
                    }
                }
            }
            else if ($orderResponse['errorCode'] !== 7)
            {
                //payment fail
                if ($is_in_repayment_time)
                {
                    if ($is_current_transaction)
                    {
                        //do nothing -> for repayment in 1 hour
                         self::markOrderProccessed($orderResponse);
                    }
                    else
                    {
                        //do nothing -> for repayment in 1 hour
                        self::markOrderProccessed($orderResponse);
                    }
                }
                else
                {
                    if ($is_current_transaction)
                    {
                        //transaction is current trans of fahasa order
                        //cancel order -> last transaction
                        //function cancel order
                        if (Mage::helper('repayment')->checkOrderHasTransRefund($order->getIncrementId(), $orderResponse['orderId']))
                        {
                            self::markOrderProccessed($orderResponse);
                        }
                        else
                        {
                            self::handleOrderFail($order, $orderResponse);
                        }
                    }
                    else
                    {
                        //transaction is not current trans of fahasa order -> old transaction
                        //do nothing
                        self::markOrderProccessed($orderResponse);
                    }
                }
            }
            else
            {
                //pending => update status in order_mompay + do not update status order_fahasa to mark order pending => java will process 
            }

        } else {  // fail : response  null 
             \Mage::log("*** Momopay - can not update order Fahasa # ". print_r($orderResponse,true) , null, "Momopay.log");
        }
    }
    
    //done
    public function markOrderProccessed($orderResponse)
    {
        //only update status in order_momopay
        $this->updateOrderMomopay($orderResponse, 1);
    }

    
    //done
    public function handleOrderSuccess($order, $orderResponse)
    {
        Mage::log("Callback: handle order success " . $orderResponse['orderId'] . ", fahasa order: state = " . $order->getState() . ", status = " . $order->getStatus(), null, "momopay.log");
        //callback always = 1, used for update order_momopay, this function is called in callback function
        if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
        {
            $order->setState("new", "paid", 'Thanh toán qua Mompay thành công');
            $order->save();
            $this->updateOrderMomopay($orderResponse,1);
            
            //send order email
              Mage::dispatchEvent('payment_order_return', array('order_id'=>$order->getEntityId(), 'increment_id'=>$order->getIncrementId(), 'status'=>'success', 
                  'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_PAYMENT_SUCCESS, 'cur_payment_method' => $this->_code, 
                  'cur_payment_title' => $order->getPayment()->getMethodInstance()->getTitle(),
                  'customer_id' => $order->getCustomerId(), 'customer_email' => $order->getCustomerEmail()));
        }
        else if ($order->getState() == "canceled" && $order->getStatus() == "canceled")
        {
            self::addStatusHistoryComment($order, "Thanh toán qua Mompay thành công");
            self::createRedmineIssue($order);
            $this->updateOrderMomopay($orderResponse,1);
        }
        else
        {
            self::addStatusHistoryComment($order, "Thanh toán qua Mompay thành công");
            $this->updateOrderMomopay($orderResponse,1);
        }
    }
    
    //done
    public function handleOrderFail($order, $orderResponse)
    {
        Mage::log("Callback: handle order fail " . $orderResponse['orderId'] . ", fahasa order: state = " . $order->getState() . ", status = " . $order->getStatus(), null, "momopay.log");
        if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
        {
            //call cancel center
            //cancel order return 
            $cancel_result = Mage::helper('cancelorder')->cancelOrderReturn($order, "momopay.log");
            if ($cancel_result)
            {
                //update order_momopay
                $this->updateOrderMomopay($orderResponse, 1);
                Mage::dispatchEvent('payment_order_return', array('order_id' => $order->getEntityId(), 'increment_id' => $order->getIncrementId(), 'status' => 'success',
                    'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_PAYMENT_FAIL, 'cur_payment_method' => $this->_code, 
                    'cur_payment_title' => $order->getPayment()->getMethodInstance()->getTitle(),
                    'customer_id' => $order->getCustomerId(), 'customer_email' => $order->getCustomerEmail()));
            }
            else
            {
                //only add log history status + not update payment order -> for the next java process will check status
                self::addStatusHistoryComment($order, "Gọi hủy đơn toán qua Mompay thất bại");
            }
        }
        else if ($order->getState() == "canceled" && $order->getStatus() == "canceled")
        {
            //no need call canceled because order was canceled before
            //add log history + update payment_order
            $this->updateOrderMomopay($orderResponse, 1);
            self::addStatusHistoryComment($order, "Thanh toán qua Mompay thất bại");
        }
        else
        {
            //add log history + update payment_order
            $this->updateOrderMomopay($orderResponse,1);
            self::addStatusHistoryComment($order, "Thanh toán qua Mompay thất bại");
        }
    }

    // fail or errors
    function redirectFailureFromCreateOrderMomopay($order, $result) {
        //
        if($result['errorCode']){
            $descriptionError = $result['localMessage'];
        }else {
            $descriptionError = $result->getMessage() ?? "Error";
        }
        \Mage::log("*** momopay - Fail to request create order #" . $order->getIncrementId() . ".Description error :". $descriptionError .". Redirect to checkout/onepage/failure/. Data Object: " . print_r(json_decode($result), true), null, "momopay.log");
        $fail_url = \Mage::getUrl("checkout/onepage/failure/");

        $data['url'] = $fail_url;
        $data['message'] = "Redirect failure";
        return $data;
    }
    
    // insert order order_momopay
    function insertOrderMomopay($orderId,$requestId,$amount, $payType = null) {
        if (is_string($amount)) {
            $amount = (float) $amount;
        } else {
            $amount = $amount;
        }
        
        $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "insert into order_momopay (order_id, request_id, amount, paytype) values (:orderId, :requestId, :amount, :payType) ";
        $params = array(
            "orderId" => $orderId,
            "requestId" => $requestId, 
            "amount" => $amount,
            "payType" => $payType
        );
        $write->query($query, $params);
        
        //insert payment log to check current payment
        Mage::helper('repayment')->addPaymentLog($orderId, $this->_code, $requestId);
    }
    
    // update status order_Mompay
    function updateOrderMomopay($result, $callback = 0) {
        //set data 
        $requestId = $result['requestId'];
        $orderId = $result['orderId'];
        $status = $result['errorCode'];
        $transId = $result['transId'];
        $message = $result['localMessage'];
        $payType = $result['payType'];
                 
        $query = "update order_momopay set status = '{$status}'";
        
        if ($transId && $transId != 0){ // transId = 0 => status dang o -1
            $query .= ", mmtransid = '{$transId}' ";
        }
        if ($payType){
            $query .= ", paytype = '{$payType}' ";
        }
        if ($message){
            $query .= ", description='{$message}' ";
        }
        //called by callback
        if ($callback == 1){
            $query .= ", callback={$callback} ";
        }
        $query .= " where request_id='{$requestId}'";
        $query .= ";";
        if ($callback == 1) {
            \Mage::log("*** momopay CALLBACK - Updating order_Id=" . $orderId . " request_id=" . $requestId ." in db query: " . $query . "-- with data : " . print_r($result, true), null, "momopay.log");
        } else {
            \Mage::log("*** momopay - Updating order_Id=" . $orderId . " request_id=" . $requestId ." in db query: " . $query . "-- with data : " . print_r($result, true), null, "momopay.log");
        }
        try{
            $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->query($query);
            if ($callback == 1) {
                \Mage::log("*** momopay - CALLBACK - Update order_Id=" . $orderId . " request_id=" . $requestId ." in db query Success ***", null, "momopay.log");
            } else {
                \Mage::log("*** momopay - Update order_Id=" . $orderId . " request_id=" . $requestId ." in db query Success ***", null, "momopay.log");
            }
        } catch (Exception $ex) {
            \Mage::log("*** momopay: update order momopay failed. Exception=" . $ex->getMessage(), null, "momopay.log");
        }
    }
    
    // get info order_Mompay
    function getOrderMomopayInfo($orderId,$requestId) {
        $read = \Mage::getSingleton('core/resource')->getConnection('core_read');
        $query = "select * from order_momopay where request_id='{$requestId}';";
        $results = $read->fetchAll($query);

        if ($results[0]) {
            return $results[0];
        } else {
            return null;
        }
    }
    
   
    //mark order for java to refund 
    public function markRefundOrder($order, $orderResponse){
        Mage::log("Callback: mark order refund " . $orderResponse['orderId'], null, 'momopay.log');
        $this->updateOrderMomopay($orderResponse,1);
        $query = "update order_momopay set refund_code = -1000 where order_id = :order_id and request_id = :request_id";
        $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
        $binds = array(
            "order_id" => $order->getIncrementId(),
            "request_id" => $orderResponse['requestId']
        );
        $write->query($query, $binds);
    }

    public function pendingOrder($order) {
        $order->setState("new", "pending_payment", 'Thanh toán qua Momopay đang được xử lý.');
        $order->save();
    }
    // ---------End set status fhs_sales_flat_order-------
    
    public function addStatusHistoryComment($order, $comment){
        $history = $order->addStatusHistoryComment($comment, false);
        $history->setIsCustomerNotified(false);
        $order->save();
    }
    
    public function createRedmineIssue($order){
        $increment_id = $order->getIncrementId();
        \Mage::log("*** Momopay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ")", null, "momopay.log");
        $helper = \Mage::helper('cancelorder');
        $subject = 'Hoàn tiền cho khách đơn hàng ' . $increment_id;
        $description = 'Đơn hàng đã được thanh toán nhưng khách hủy. Hoàn tiền lại cho khách.';
        $response = $helper->createRedmineIssue(1, $subject, $description, 12);
        \Mage::log("*** Momopay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ") - response: " . print_r($response, true), null, "momopay.log");
    }
    
    public function execPostRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );
//        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        //execute post
        $result = curl_exec($ch);
        if (curl_errno($ch)){
            $error_msg = curl_error($ch);
            Mage::log("ERROR curl exec " . $url . ", " . print_r($data, true) . " - message: " . print_r($error_msg, true)
                    . " - response: " . print_r($result, true), null, 'momopay.log');
        }

        //close connection
        curl_close($ch);
        return $result;
    }
    
     public function createMomoOrder($order){
            //used for mobile 
            $orderId = $order->getIncrementId() ? $order->getIncrementId() : null;
            \Mage::log("*** momopay (Mobile): insert order momopay with orderId = " . $orderId , null, "momopay.log");
            $request_id = self::createRequestIdForOrder($order->getIncrementId());
            self::insertOrderMomopay($order->getIncrementId(), $request_id, round($order->getGrandTotal()), self::PAY_TYPE_MOBILE);
            // cap nhat state cho order 
            $order->setState("new", "pending_payment", 'Thanh toán qua Momopay chuẩn bị xử lý.');
            $order->save();
            \Mage::log("*** momopay (Mobile): insert order SUCCESS momopay with orderId = " . $orderId , null, "momopay.log");
            return $request_id;
        }

    //request is called by mobile 1 time
    public function requestMomoOrderForMobile($orderId, $requestId, $request, $data_request) {
        Mage::log("request - mombile: request data order_id=" . $orderId . ", request_id=" . $requestId . ", data=" . print_r($request, true) . "data_request=" . print_r($data_request, true), null, "momopay.log");

        $result = false;
        $status = -1000;
        
        $config = self::getConfig();
        $secret_key = $config->getSecretKey();
        
        //get momo_order_infor in order_momopay
        $order_momopay_infor = self::getOrderMomopayInfo($orderId, $requestId);
        if (!$order_momopay_infor) {
            return array(
                "success" => false,
                "status" => $status,
            );
        }

        $appPayRequest = self::createAppPayRequest($orderId, $order_momopay_infor, $request);

        $endpoint = $config->getBaseUrl() . '/pay/app'; 
        $appPayResponse = $this->execPostRequest($endpoint, json_encode($appPayRequest));
        Mage::log("request - mobile: response pay app: order_id=" .$orderId . ", request_id=" . $requestId .", response= " . print_r($appPayResponse, true), null, 'momopay.log'); 
        $payResponse = json_decode($appPayResponse, true);
        
        $signature_params = "status=" . $payResponse["status"] . "&message=" . $payResponse["message"] . "&amount=" . $payResponse["amount"]
                . "&transid=" . $payResponse["transid"];
        $signature_check = hash_hmac("sha256", $signature_params, $secret_key);

        if ($signature_check === $payResponse["signature"]) {
            //$payResponse['status'] = 0: customer has enough conditions to pay transaction
            if ($payResponse['status'] === 0){
                //check transaction status from momo
                $check_status_response = self::checkMomoOrderStatusForMobile($requestId, $payResponse["transid"]);
                if ($check_status_response) {
                    $status = $check_status_response['status'];
                    $message = $check_status_response['message'];
                    $momo_trans_id = $payResponse['transid'];
                    
                    if ($check_status_response['status'] === 0) {
                        //success
                        $result = true;
                    } else if ($check_status_response['status'] === 9000){
                        for ($i = 1; $i <= 3; $i++){
                            sleep($i);
                            $check_status_response = self::checkMomoOrderStatusForMobile($requestId, $payResponse["transid"]);
                            if ($check_status_response['status'] !== 9000){
                                $status = $check_status_response['status'];
                                $message = $check_status_response['message'];
                                if ($status == 0){
                                    $result = true;
                                }
                                break;
                            } else {
                                $status = $check_status_response['status'];
                                $result = true;
                            }
                        }
                    }
                    
                    $update_data = array(
                        "orderId" => $orderId,
                        "errorCode" => $status,
                        "transId" => $momo_trans_id,
                        "message" => $message,
                        "payType" => self::PAY_TYPE_MOBILE,
                        "requestId" => $order_momopay_infor["request_id"],
                        "localMessage" => $message,
                    );
                    self::updateOrderMomopay($update_data, 0);
                }else{
                    Mage::log("request - mobile: check status fail order_id=" . $orderId . ", request_id=" . $requestId . print_r($payResponse, true), null, "momopay.log");
                }
            }
            else{
                Mage::log("request - mobile: pay_response fail order_id=" . $orderId . ", request_id=" . $requestId . print_r($payResponse, true), null, "momopay.log");
            }
        } else {
            Mage::log("request - mobile: pay_response wrong signature  order_id=" . $orderId . ", request_id=" . $requestId, null, "momopay.log");
        }
        Mage::log("request - mobile: response to mobile sucess=" . $result . ", status=" . $status, null, "momopay.log");
        return array(
            "success" => $result,
            "status" => $status,
        );
    }

    public function createAppPayRequest($orderId, $order_momopay_infor, $request){
        $momo_helper = Mage::helper('momopay');
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $amount = round($order->getGrandTotal());

        $partner_ref_id = $order_momopay_infor['request_id'];
        $hashJson = array(
            self::AMOUNT => $amount,
            self::PARTNER_REF_ID => $partner_ref_id,
            self::PARTNER_CODE => self::getConfig()->getPartnerCode(),
            self::PARTNER_TRANS_ID => $orderId
        );

        $public_key = self::getConfig()->getPublicKey();
        $hash = $momo_helper::encryptRSA($hashJson, $public_key);

        $requestArr = array(
            self::PARTNER_CODE => self::getConfig()->getPartnerCode(),
            self::PARTNER_REF_ID => $partner_ref_id,
            self::CUSTOMER_NUMBER => $request['phonenumber'],
            self::APP_DATA => $request['data'],
            self::HASH => $hash,
            self::VERSION => self::VERSION_MOBILE_VALUE,
            self::PAY_TYPE => self::PAY_TYPE_MOBILE 
        );

        return $requestArr;
    }
    
    public function callbackForMobile($data){
        Mage::log("callback - mobile: " . print_r($data, true), null, "momopay.log");
        $request = (array) $data;
        $secret_key = self::getConfig()->getSecretKey();
        
        $signature_param = "accessKey=" . $request['accessKey'] . "&amount=" . $request['amount'] . "&message=" . $request['message']
                . "&momoTransId=" . $request['momoTransId'] . "&partnerCode=" . $request['partnerCode'] 
                . "&partnerRefId=" . $request["partnerRefId"] . "&partnerTransId=" . $request['partnerTransId']
                . "&responseTime=" . $request['responseTime'] . "&status=" . $request['status'] . "&storeId=" . $request["storeId"]
                . "&transType=" . $request["transType"];
        
        $return_message = "";
        $return_status = -1000;
        
        $signature_check = hash_hmac("sha256", $signature_param, $secret_key);
        if ($signature_check === $request['signature']){
            //get momo_order_infor in order_momopay
            $order_id = $request["partnerTransId"]; //partnerTransId: orderId (different partnerRefId)
            $order_momopay_infor = self::getOrderMomopayInfo($order_id, $request['partnerRefId']);
            if ($order_momopay_infor){
                $amount_order = doubleval($order_momopay_infor['amount']);
                if ($order_momopay_infor['request_id'] == $request['partnerRefId'] && $amount_order == $request['amount'])
                {
                    $order_id = $order_momopay_infor['order_id'];

                    $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

                    $status = $request['status'];
                    if (self::confirmMomoOrderForMobile($request))
                    {
                        $return_message = "Thành công";
                        $return_status = 0;

                        $update_data = array(
                            "orderId" => $order_momopay_infor['request_id'],
                            "requestId" => $order_momopay_infor['request_id'],
                            "errorCode" => $request['status'],
                            "transId" => $request['momoTransId'],
                            "localMessage" => $request['message'],
                            "payType" => self::PAY_TYPE_MOBILE,
                        );
                        self::updateOrderFahasa($update_data, $order_id);
                    }
                    else
                    {
                        Mage::log("Callback - mobile: confirm fail ", null, "momopay.log");
                    }
                } else{
                    Mage::log("Callback - mobile: order exist but not equal amount and request _id " . $order_id . ", request_id=" . $request['partnerRefId'], null, "momopay.log");
                }
            } else{
                Mage::log("Callback - mobile: order not exist order_id" . $order_id . ", request_id=" . $request['partnerRefId'], null, "momopay.log");
            }
        }else{
           Mage::log("Callback - mobile: wrong signature order_id" . $order_id . ", request_id=" . $request['partnerRefId'], null, "momopay.log");
        }
        
        $return_signature_param = "amount=". $request['amount'] . "&message=" . $return_message . "&momoTransId=" . $request['momoTransId'] . "&partnerRefId=" 
                . $request['partnerRefId'] . "&status=" . $status;
        
        $return_signature = hash_hmac("sha256", $return_signature_param, $secret_key);

        return array(
            self::STATUS => $return_status,
            self::MESSAGE => $return_message,
            self::PARTNER_REF_ID => $request['partnerRefId'],
            self::MOMO_TRANS_ID => $request["momoTransId"],
            self::AMOUNT => $request['amount'],
            self::SIGNATURE => $return_signature,
        );
    }
    
    public function confirmMomoOrderForMobile($data) {
        $secret_key = self::getConfig()->getSecretKey();
        $endpoint = self::getConfig()->getBaseUrl() . "/pay/confirm";

        $signature_param = "partnerCode=" . $data['partnerCode'] . "&partnerRefId=" . $data['partnerRefId'] . "&requestType=capture"  
                . "&requestId=" . $data['partnerRefId'] . "&momoTransId=" . $data['momoTransId'];
        $signature = hash_hmac("sha256", $signature_param, $secret_key);

        $request_param = array(
            self::PARTNER_CODE => self::getConfig()->getPartnerCode(),
            self::PARTNER_REF_ID => $data["partnerRefId"],
            self::REQUEST_TYPE => "capture",
            self::REQUEST_ID => $data["partnerRefId"],
            self::MOMO_TRANS_ID => $data["momoTransId"],
            self::SIGNATURE => $signature,
            self::CUSTOMER_NUMBER => $data["customerNumber"],
        );
        Mage::log("confirm - mobile: confirm order with momo request " . print_r($request_param, true), null, "momopay.log");
        $response_json = $this->execPostRequest($endpoint, json_encode($request_param));
        Mage::log("confirm - mobile: confirm order with momo response " . print_r($response_json, true), null, "momopay.log");
        $response = json_decode($response_json, true);
        
        if ($response && $response['status'] ===0){
            return true;
        }
        
        return false;
    }

    public function checkMomoOrderStatusForMobile($partnerRefId, $momoTransId){
        $config = self::getConfig();
        $endpoint = $config->getBaseUrl() . "/pay/query-status";
        $public_key = self::getConfig()->getPublicKey();
        $momo_helper = Mage::helper('momopay');

        $hash_json = array(
            self::REQUEST_ID => $partnerRefId,
            self::PARTNER_CODE => $config->getPartnerCode(),
            self::PARTNER_REF_ID => $partnerRefId,
            self::MOMO_TRANS_ID => $momoTransId,
        );
        
        $hash = $momo_helper::encryptRSA($hash_json, $public_key);
        
        $request_param = array(
            self::PARTNER_CODE => $config->getPartnerCode(),
            self::PARTNER_REF_ID => $partnerRefId,
            self::HASH => $hash,
            self::VERSION => self::VERSION_MOBILE_VALUE,
            self::MOMO_TRANS_ID => $momoTransId
        );
        
        $response_json = $this->execPostRequest($endpoint, json_encode($request_param));
        $response_data = json_decode($response_json, true);
        $response = null;
        
        if ($response_data['status'] === 0 && $response_data['data']['status'] !== null){
            $response = $response_data['data'];
        }
        return $response;
    }

}
