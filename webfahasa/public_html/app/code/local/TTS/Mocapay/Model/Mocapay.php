<?php

require_once('ConfigMoca.php');

class TTS_Mocapay_Model_Mocapay extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'mocapay';
    protected $_formBlockType = 'mocapay/form';
    protected $_infoBlockType = 'mocapay/info';
    protected static $_CONFIG;

    public function getConfig()
    {
        if (!self::$_CONFIG)
        {
            self::$_CONFIG = new ConfigMoca();
        }

        return self::$_CONFIG;
    }

//    public function getBaseUrl()
//    {
//        return $this->getConfigData('mocapay_url');
//    }
//
//    public function getPartnerId()
//    {
//        return $this->getConfigData('partner_id');
//    }
//
//    public function getPartnerSecret()
//    {
//        return $this->getConfigData('partner_secret');
//    }
//
//    public function getClientId()
//    {
//        return $this->getConfigData('client_id');
//    }
//
//    public function getClientSecret()
//    {
//        return $this->getConfigData('client_secret');
//    }
//
//    public function getRedirectUrlAndroid()
//    {
//        return $this->getConfigData('redirect_url_android');
//    }
//
//    public function getRedirectUrliOS()
//    {
//        return $this->getConfigData('redirect_url_ios');
//    }
//
//    public function getMerchantId()
//    {
//        return $this->getConfigData('merchant_id');
//    }
//
//    public function getRedirectUrlFhs()
//    {
//        return $this->getConfigData('redirect_url_fhs');
//    }

    public function createRequestIdForOrder($orderId)
    {
        $tz = 'Asia/Ho_Chi_Minh';
        $timestamp = time();
        $dt = new DateTime("now", new DateTimeZone($tz));
        $dt->setTimestamp($timestamp);
        return $dt->format('ymdHis') . $orderId;
    }

    function insertOrderMocapay($order_id, $transaction_id, $amount, $channel, $state, $code_verifier)
    {
        try {
            $insert_sql = "insert into order_mocapay (order_id, transaction_id, created_at, updated_at, amount, channel, state, code_verifier, status) "
                    . "values (:order_id, :transaction_id, now(), now(), :amount, :channel, :state, :code_verifier, '-1000')";
            $amount = doubleval($amount);
            $bind_params = array(
                'order_id' => $order_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'channel' => $channel,
                'state' => $state,
                'code_verifier' => $code_verifier,
            );
            $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->query($insert_sql, $bind_params);
            Mage::helper('repayment')->addPaymentLog($order_id, $this->_code, $transaction_id);
            return true;
        } catch (Exception $ex) {
            Mage::log("Exception insert order mocapay " . $order_id . ", transaction " . $transaction_id . " -ex: " . $ex, null, "mocapay.log");
            return false;
        }
    }

    public function getUrlMocapay($order_id, $channel = 'web')
    {
        Mage::log("Get mocapay url begin order_id=" . $order_id, null, "mocapay.log");
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        if ($order->getId())
        {
            $transaction_id = self::createRequestIdForOrder($order_id);
            $amount = round(doubleval($order->getGrandTotal()));
            
            $helper = Mage::helper('mocapay');
            $state = $helper->generateRandomString(7);
            $code_verifier = $helper->generateCodeVerifier();

            if ($this->insertOrderMocapay($order_id, $transaction_id, $amount, $channel, $state, $code_verifier))
            {
                $order->setState("new", "pending_payment", 'Thanh toán qua Moca chuẩn bị xử lý.');
                $order->save();
                $endpoint = '/mocapay/partner/v2/charge/init';
                $url = self::getConfig()->getBaseUrl() . $endpoint;
                $data = array(
                    "partnerTxID" => $transaction_id,
                    "partnerGroupTxID" => $transaction_id,
                    "amount" => $amount,
                    "currency" => "VND",
                    "merchantID" => self::getConfig()->getMerchantId(),
                    "description" => "Thanh toan don hang " . $order_id,
                    "isSync" => false,
                );

                $partner_id = self::getConfig()->getPartnerId();
                $partner_secret = self::getConfig()->getPartnerSecret();
                $content_type = "application/json";

                $date = date(DATE_RFC7231);

                $hmac_signature = Mage::helper('mocapay')->generateHMACSignature($partner_id, $partner_secret, "POST", $endpoint, $content_type, json_encode($data), $date);
                $result_raw = $this->execPostRequestWithAuth($url, json_encode($data, JSON_UNESCAPED_UNICODE), $hmac_signature, $date);
                $result = (array) json_decode($result_raw);
                Mage::log("Get mocapay url: order_id = " . $order_id . ", transaction_id = " . $transaction_id . " - response = " . print_r($result, true), null, "mocapay.log");
                if ($result["partnerTxID"] == $transaction_id)
                {
                    $request_jwt = $result["request"];
                        $redirect_url = $this->generateWebUrl($request_jwt, $state, $code_verifier, $channel);
                        Mage::log("Generate url transaction_id=" . $transaction_id . ", url=" . $redirect_url, null, "mocapay.log");
                        return array(
                            'redirect_url' => $redirect_url,
                            'transaction_id' => $transaction_id,
                            // for mobile :
                            'paymentUrl' => $request_jwt,
                            'transactionId' => $transaction_id,
                        );
                    }
                else
                {
                    Mage::log("Wrong transaction id when get url init " . $order_id . ", transaction=" . $transaction_id, null, "mocapay.log");
                }
            }
            else
            {
                Mage::log("Error create transaction mocapay " . $order_id . ", transaction=" . $transaction_id, null, "mocapay.log");
            }
        }
        else
        {
            Mage::log("No order with order_id=" . $order_id, null, "mocapay.log");
        }
        return self::redirectFailureFromCreateOrderMocapay();
    }

    public function redirectFailureFromCreateOrderMocapay()
    {
            return array(
                'redirect_url' => Mage::getUrl("checkout/onepage/failure/"),
                'paymentUrl' => "",
            );
        }

    public function generateWebUrl($request_jwt, $state, $code_verifier, $channel)
    {
        $helper = Mage::helper('mocapay');

        $code_challenge = $helper->base64URLEncode(($code_verifier));
        $nonce = $helper->generateRandomString(16);
        $redirect_uri = self::getConfig()->getRedirectUrlFhs($channel);

        $data = array(
            'acr_values' => 'consent_ctx:countryCode=VN,currency=VND',
            'client_id' => self::getConfig()->getClientId($channel),
            'code_challenge' => $code_challenge,
            'code_challenge_method' => 'S256',
            'nonce' => $nonce,
            'redirect_uri' => $redirect_uri,
            'request' => $request_jwt,
            'response_type' => 'code',
            'scope' => 'payment.vn.one_time_charge',
            'state' => $state,
        );

        $params = http_build_query($data);
        $base_url = self::getConfig()->getBaseUrl();
        $end_point = "grabid/v1/oauth2/authorize";
        return $base_url . $end_point . "?" . $params;
    }

    public function getOAuthToken($code, $code_verifier, $channel)
    {
        $base_url = self::getConfig()->getBaseUrl();
        $endpoind = "grabid/v1/oauth2/token";
        $url = $base_url . $endpoind;
        $data = array(
            'code' => $code,
            'client_id' => self::getConfig()->getClientId($channel),
            'grant_type' => 'authorization_code',
            'redirect_uri' => self::getConfig()->getRedirectUrlFhs($channel),
            'code_verifier' => $code_verifier,
            'client_secret' => self::getConfig()->getClientSecret($channel),
        );

        $result = (array) json_decode($this->execPostWithFormUrlEncoded($url, $data));
        Mage::log("Get access_token: code=" . $code . ", code_verifier=" . $code_verifier . " - response = " . print_r($result, true), null, "mocapay.log");

        $access_token = $result['access_token'];

        return $access_token;
    }

    public function confirmPayment($order_mocapay, $access_token)
    {
        if ($order_mocapay)
        {
            $endpoint = "mocapay/partner/v2/charge/complete";
            $url = self::getConfig()->getBaseUrl() . $endpoint;
            $data = array(
                "partnerTxID" => $order_mocapay['transaction_id']
            );
            $helper = Mage::helper('mocapay');

            $date = date(DATE_RFC7231);
            $signature_pop = $helper->calculateHMACForPOP(self::getConfig()->getClientSecret($order_mocapay['channel']), $access_token);

            $moca_status = (array) json_decode($this->execPostWithPop($url, json_encode($data, JSON_UNESCAPED_UNICODE), $access_token, $date, $signature_pop));
            Mage::log("Confirm response: order_id " . $order_mocapay['order_id'] . ', transaction_id = ' . $order_mocapay['transaction_id'] . ' - reponse = ' . print_r($moca_status, true), null, 'mocapay.log');

            //status is status of request, not status of transaction. status transaction is txStatus
            if ($moca_status && $moca_status['status'] == "success")
            {
                //update order_mocapay + order_fahasa
                self::handleFahasaOrder($order_mocapay, $moca_status);

                //return result to redirect user in UI
                if ($moca_status['txStatus'] == "success" || $moca_status["txStatus"] == "processing")
                {
                    return true;
                }
            }
        }
        else
        {
            //$transaction_id is null
        }
        return false;
    }

    function handleFahasaOrder($order_mocapay, $moca_status)
    {
        $moca_status['transaction_id'] = $order_mocapay['transaction_id'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_mocapay['order_id']);
        if ($order->getId())
        {
            $is_current_transaction = Mage::helper('repayment')->checkTransIsCurPayment($order, $order_mocapay['transaction_id']);
            $is_in_repayment_time = Mage::helper('repayment')->checkOrderInPaymentTime($order);
            Mage::log("Handle fahasa order: is_in_repayment_time=" . $is_in_repayment_time . ", is_current_trans=" . $is_current_transaction . " - moca_status = " . $moca_status['txStatus'], null, "mocapay.log");
            
            //txStatus = Success => payment success
            if ($moca_status['txStatus'] == "success")
            {
                if ($is_in_repayment_time)
                {
                    if ($is_current_transaction)
                    {
                        self::handleOrderSuccess($order, $moca_status);
                    }
                    else
                    {
                        self::markOrderRefund($order, $moca_status);
                    }
                }
                else
                {
                    if ($is_current_transaction)
                    {
                        self::handleOrderSuccess($order, $moca_status);
                    }
                    else
                    {
                        self::markOrderRefund($order, $moca_status);
                    }
                }
            }
            else if ($moca_status['txStatus'] == "failed")
            {
                if ($is_in_repayment_time)
                {
                    if ($is_current_transaction)
                    {
                        //do nothing => for repayment 1 hour 
                        self::markOrderProccessed($order, $moca_status);
                    }
                    else
                    {
                        //do nothing => for repayment 1 hour 
                        self::markOrderProccessed($order, $moca_status);
                    }
                }
                else
                {
                    if ($is_current_transaction)
                    {
                        //transaction is current trans of fahasa order
                        //cancel order -> last transaction
                        //function cancel order
                        if (Mage::helper('repayment')->checkOrderHasTransRefund($order->getIncrementId(), $order_mocapay['transaction_id'])){
                            self::markOrderProccessed($order, $moca_status);
                        } else {
                            self::handleOrderFail($order, $moca_status);
                        }
                    }
                    else
                    {
                        //transaction is not current trans of fahasa order -> old transaction
                        //do nothing
                        self::markOrderProccessed($order, $moca_status);
                    }
                }
            }
            else
            {
                self::markOrderProccessed($order, $moca_status);
            }
        }
        else
        {
            Mage::log("Update order fahasa: order not found " . $order_mocapay['order_id'] . " - moca_data = " . print_r($moca_status, true), null, "mocapay.log");
        }

    }

    function handleOrderFail($order, $moca_status)
    {
        Mage::log("Handle fahasa order fail: order_id = " . $order->getIncrementId() . ", transaction_id = " . $moca_status['transaction_id']
                . " state=" . $order->getState() . ", status=" . $order->getStatus(), null, "mocapay.log");
        try {
            if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
            {
                $cancel_result = Mage::helper('cancelorder')->cancelOrderReturn($order, "mocapay.log");
                if ($cancel_result)
                {
                    //Step 1. Update order_payment
                    self::updateOrderMocapay($order->getIncrementId(), $moca_status);
                    Mage::dispatchEvent('payment_order_return', array('order_id' => $order->getEntityId(), 'increment_id' => $order->getIncrementId(), 'status' => 'success',
                        'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_PAYMENT_FAIL, 'cur_payment_method' => $this->_code,
                        'cur_payment_title' => $order->getPayment()->getMethodInstance()->getTitle(),
                        'customer_id' => $order->getCustomerId(), 'customer_email' => $order->getCustomerEmail()));
                }
                else
                {
                    //do not update order_airpay => for java proccess
                }
            }
            else if ($order->getState() == "canceled" && $order->getStatus() == "canceled")
            {
                //no need call canceled because order was canceled before
                //add log history + update order_mocapay
                self::addStatusHistoryComment($order, "Thanh toán qua Moca thất bại");
                self::updateOrderMocapay($order->getIncrementId(), $moca_status);
            }
            else
            {
                self::addStatusHistoryComment($order, "Thanh toán qua Moca thất bại");
                self::updateOrderMocapay($order->getIncrementId(), $moca_status);
            }
        } catch (Exception $ex) {
            Mage::log("Exception handle order faile order_id = " . $order->getIncrementId() . ", transaction_id = " . $moca_status['transaction_id']
                    . " state=" . $order->getState() . ", status=" . $order->getStatus() . " - ex = " . $ex, null, "mocapay.log");
        }
    }

    //done: update transaction status
    public function markOrderProccessed($order, $moca_status)
    {
         Mage::log("Mark order processed: order_id = " . $order->getIncrementId() . ", transaction_id = " . $moca_status['transaction_id'], null, "mocapay.log");
        //only update status in order_mocapay
        $this->updateOrderMocapay($order->getIncrementId(), $moca_status);
    }

    //mark order for java to refund 
    public function markOrderRefund($order, $moca_status)
    {
        try {
            Mage::log("Mark order refund: order_id=" . $order->getIncrementId() . ", transaction_id=" . $moca_status['transaction_id'], null, "mocapay.log");
            $order_id = $order->getIncrementId();
            Mage::log("Callback: mark order refund: order_id=" . $order_id . ", transaction_id=" . $moca_status['transaction_id'], null, 'mocapay.log');
            $this->updateOrderMocapay($order->getIncrementId(), $moca_status);
            $query = "update order_mocapay set refund_code = '-1000' where order_id = :order_id and transaction_id = :transaction_id ";
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $binds = array(
                "order_id" => $order_id,
                "transaction_id" => $moca_status['transaction_id']
            );
            $write->query($query, $binds);
        } catch (Exception $ex) {
            Mage::log("Mark order refund: exception " . $ex, null, "mocapay.log");
        }
    }

    function handleOrderSuccess($order, $moca_status)
    {
        Mage::log("Handle fahasa order success: state=" . $order->getState() . ", status=" . $order->getStatus(), null, "mocapay.log");
        //update order fahasa + order mocapay
        try {
            if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
            {
                $order->setState("new", "paid", 'Thanh toán qua Moca thành công');
                $order->save();
                self::updateOrderMocapay($order->getIncrementId(), $moca_status);

                //send order email
                Mage::dispatchEvent('payment_order_return', array('order_id' => $order->getEntityId(), 'increment_id' => $order->getIncrementId(), 'status' => 'success',
                    'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_PAYMENT_SUCCESS, 'cur_payment_method' => $this->_code,
                    'cur_payment_title' => $order->getPayment()->getMethodInstance()->getTitle(),
                    'customer_id' => $order->getCustomerId(), 'customer_email' => $order->getCustomerEmail()));
            }
            else if ($order->getState() == "canceled" && $order->getStatus() == "canceled")
            {
                self::addStatusHistoryComment($order, "Thanh toán qua Moca thành công");
                self::createRedmineIssue($order);
                self::updateOrderMocapay($order->getIncrementId(), $moca_status);
            }
            else
            {
                self::addStatusHistoryComment($order, "Thanh toán qua Moca thành công");
                self::updateOrderMocapay($order->getIncrementId(), $moca_status);
            }
        } catch (Exception $ex) {
            Mage::log("Handle fahasa order exception " . $ex, null, "mocapay.log");
        }
    }

    public function addStatusHistoryComment($order, $comment)
    {
        $history = $order->addStatusHistoryComment($comment, false);
        $history->setIsCustomerNotified(false);
        $order->save();
    }

    function updateOrderMocapay($order_id, $moca_status)
    {
        try {
            $moca_trans_id = $moca_status['txID'];
            $status = $moca_status['txStatus'];
            $description = $moca_status['description'];
            $transaction_id = $moca_status['transaction_id'];

            $query = "update order_mocapay set status = :status, moca_trans_id = :moca_trans_id, description = :description, callback = 1 "
                    . "where order_id = :order_id and transaction_id = :transaction_id ";
            $bind_params = array(
                'status' => $status,
                'moca_trans_id' => $moca_trans_id,
                'description' => $description,
                'order_id' => $order_id,
                'transaction_id' => $transaction_id,
            );
            Mage::log("Update order mocapay status: order_id=" . $order_id . ", transaction_id=" . $transaction_id . ", status=" . $status . ", moca_id=" . $moca_trans_id, null, "mocapay.log");
            $write = Mage::getSingleton('core/resource')->getConnection('core_wrire');
            $write->query($query, $bind_params);
            return true;
        } catch (Exception $ex) {
            Mage::log("Exception update order moca " . $order_id . ", ex = " . $ex, null, "mocapay.log");
            return false;
        }
    }

    function getMocaOrderByTransactionId($transaction_id)
    {
        $query = "select order_id, transaction_id, amount, state, channel, status from order_mocapay where transaction_id = '{$transaction_id}' ;";
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $result = $read->fetchAll($query);
        if ($result[0])
        {
            return $result[0];
        }
        return false;
    }

    function getMocaOrderByState($state)
    {
        $query = "select m.order_id, m.transaction_id, m.amount, m.state, m.code_verifier, m.callback, m.status, m.channel, t.access_token from order_mocapay m "
                . "left join order_mocapay_token t on t.transaction_id = m.transaction_id"
                . " where m.state = '{$state}' ;";
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $result = $read->fetchAll($query);
        if ($result[0])
        {
            return $result[0];
        }
        return false;
    }
    
    function insertTokenLog($order_mocapay, $code, $access_token)
    {
        $query = "insert into order_mocapay_token (transaction_id, code, access_token) values (:transaction_id, :code, :access_token) "
                . "  on duplicate key update access_token = values(access_token) ";
        $bind_params = array(
            'transaction_id' => $order_mocapay['transaction_id'],
            'code' => $code,
            'access_token' => $access_token,
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_wrire');
        $write->query($query, $bind_params);
        Mage::log("Insert order mocapay token: order_id=" . $order_mocapay['order_id'] . ", transaction_id=" . $order_mocapay['transaction_id'] . ", code=" . $code, null, "mocapay.log");
    }
    function updateCodeVerifierLog($order_mocapay, $code_verifier){
        try {
            $query = "update order_mocapay set code_verifier=:code_verifier where transaction_id=:transaction_id;";
        $bind_params = array(
            'transaction_id' => $order_mocapay['transaction_id'],
            'code_verifier' => $code_verifier,
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_wrire');
        $write->query($query, $bind_params);
        Mage::log("Update order mocapay (MOBILE) codeVerifier: order_id=" . $order_mocapay['order_id'] . ", transaction_id=" . $order_mocapay['transaction_id'] . ", codeVerifier=" . $code_verifier, null, "mocapay.log");
        } catch (Exception $exc) {
            Mage::log("Update order mocapay (MOBILE) codeVerifier: order_id=" . $order_mocapay['order_id'] . ", transaction_id=" . $order_mocapay['transaction_id'] . " codeVerifier ERROR =" + $exc->getTraceAsString(), null, "mocapay.log");
            //echo $exc->getTraceAsString();
        }
    }
    //important: transaction need to call confirm payment to set transaction is success in mocapay
    //handle redirect from mocapay return after customer pay by moca
    public function handleRedirectFromMocaAfterPayment($code, $state)
    {
        Mage::log("Handle redirect from moca code = " . $code . ", state = " . $state, null, "mocapay.log");
        $code = trim($code);
        $state = trim($state);
        
        if ($code && $state)
        {
            $order_mocapay = $this->getMocaOrderByState($state);
            if ($order_mocapay)
            {

                if ($order_mocapay['callback'] == 0)
                {
                    if (empty($order_mocapay['access_token'])){
                        $retry_number = 3;
                        do {
                            $access_token = $this->getOAuthToken($code, $order_mocapay['code_verifier'], $order_mocapay['channel']);
                            $retry_number--;
                        } while ($access_token == null && $retry_number > 0);

                        //insert access_token log for transaction
                        //if access_token is null -> it can not confirm payment, so payment will be failed
                        if ($access_token && !empty($access_token)) {

                            self::insertTokenLog($order_mocapay, $code, $access_token);
                            return $this->confirmPayment($order_mocapay, $access_token);
                        }
                    } else {
                        $access_token = $order_mocapay['access_token'];
                        return $this->confirmPayment($order_mocapay, $access_token);
                    }
                    
                } else {
                    if ($order_mocapay['status'] == 'success'){
                        return true;
                    }
                }
            }
            else
            {
                //no order matched state 
                Mage::log("Redirect from moca: not found or callback before " . $code . ", state = " . $state . " - order_moca callback "
                        . $order_mocapay['callback'], null, "mocapay.log");
            }
        }
        return false;
    }
    
    //important: transaction need to call confirm payment to set transaction is success in mocapay
    //handle redirect from mocapay return after customer pay by moca
    public function handleRedirectFromMocaAfterPaymentMobile($code, $code_verifier, $transactionId, $channel, $urlReturn)
    {
        Mage::log("MOBILE with channel".$channel." Handle redirect from moca code = " . $code . ", code_verifier = " . $code_verifier . ", transactionId = " . $transactionId . " and urlReturn : ". $urlReturn, null, "mocapay.log");
        if ($code && $code_verifier && $transactionId)
        {
            $order_mocapay = $this->getMocaOrderByTransactionId($transactionId);
            if ($order_mocapay)
            {
                $helper = Mage::helper('mocapay');
//                $code_verifier_format = $helper->generateCodeVerifierTest($code_verifier);
                if ($order_mocapay['callback'] == 0)
                {
                    //update code_verifier in db
                    self::updateCodeVerifierLog($order_mocapay,$code_verifier);
                    $retry_number = 3;
                    do
                    {
                        $access_token = $this->getOAuthToken($code, $code_verifier , $channel);
//                        $access_token = "eyJhbGciOiJSUzI1NiIsImtpZCI6Il9kZWZhdWx0IiwidHlwIjoiSldUIn0.eyJhdWQiOiI3NTQ0ZWVhYjA3ZWY0MWUxYmQ2M2MzOTYwZDY3YTkzMyIsImF1dGhfdGltZSI6MTYxMDUyOTcyMiwiZXhwIjoxNjQyMDY1Nzg0LCJpYXQiOjE2MTA1Mjk3ODQsImludF9zdmNfYXV0aHpfY3R4IjoiNTk3OTUyZWZjNjQ4NDMwNWIwMzhlZGU2NzJjOWViNzAiLCJpc3MiOiJodHRwczovL2lkcC5ncmFiLmNvbSIsImp0aSI6IlpZNUxYWlZUU1JXVUk3UFRieWNONEEiLCJuYmYiOjE2MTA1Mjk2MDQsInBpZCI6ImFlNmI5ZmMxLTRhYjAtNGY1My1hMGVhLTRkYTIzNWI2YjdlZCIsInNjcCI6IltcIjYwNmViOGIwOTg3ZjQyNDdhOGJiMmYxNDMwOTI5MmM2XCIsXCJvcGVuaWRcIl0iLCJzdWIiOiJkNzMzYzhiMy0zODM4LTQyNzMtYWViYS0zZjZlOTc1YTY0ZWMiLCJzdmMiOiJQQVNTRU5HRVIiLCJ0a190eXBlIjoiYWNjZXNzIn0.bQPY0yXSJml_xOPru1Q3kUTt0YKy5IUbfoEkfceFR-r9bM7Hb9txRJWKzVWhMNa6f6oYWjqJJIyLdUAOC8mWXw1XVFC_kSajjdcj9vIquLYnajJ_5Z0fFyLJ9wjRBYRpOT4TgT-a6o28LIfNdNNVxp9hudAqDGIigQ_CA3Kk7pAfwknjou8z60dIGp5ODyJA8st6VfN0U5D4qBzHZ_OMYn-UOcpBRhkG7ZNTXtFwe9xi1urR9ClZIBuGdAq53wqBMWd67e75WLk0aFnsTBL86OCJ8R6wN-i2Q-gxQYmN6jIAqAVf1f6A1yf0ap6YDhWKRd27HT28s_oTnP_5FL2eow";
                        $retry_number--;
                    }
                    while ($access_token == null && $retry_number > 0);

                    //insert access_token log for transaction
                    //if access_token is null -> it can not confirm payment, so payment will be failed
                    if ($access_token && !empty($access_token))
                    {
                        self::insertTokenLog($order_mocapay, $code, $access_token);
                        return $this->confirmPayment($order_mocapay, $access_token);
                    }
                } else {
                    if ($order_mocapay['status'] == 'success'){
                        return true;
                    }
                }
            }
            else
            {
                //no order matched state 
                Mage::log("MOBILE Redirect from moca: not found or callback before " . $code . ", code_verifier = " . $code_verifier . " - order_moca callback "
                        . $order_mocapay['callback'], null, "mocapay.log");
            }
        }
        return false;
    }

    public function execPostWithPop($url, $data, $authorization, $date, $pop)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $authorization,
            'X-GID-AUX-POP: ' . $pop,
            'Content-Type: application/json',
            'Date: ' . $date
                )
        );

        //execute post
        $result = curl_exec($ch);
        if (curl_errno($ch))
        {
            $error_msg = curl_error($ch);
            Mage::log("ERROR curl exec " . $url . ", " . print_r($data, true) . " - message: " . print_r($error_msg, true)
                    . " - response: " . print_r($result, true), null, 'mocapay.log');
        }

        //close connection
        curl_close($ch);

        return $result;
    }

    public function execPostRequest($url, $data)
    {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
                )
        );

        //execute post
        $result = curl_exec($ch);
        if (curl_errno($ch))
        {
            $error_msg = curl_error($ch);
            Mage::log("ERROR curl exec " . $url . ", " . print_r($data, true) . " - message: " . print_r($error_msg, true)
                    . " - response: " . print_r($result, true), null, 'mocapay.log');
        }

        //close connection
        curl_close($ch);
        return $result;
    }

    public function execPostWithFormUrlEncoded($url, $data)
    {
        $params = http_build_query($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
                )
        );

        //execute post
        $result = curl_exec($ch);
        if (curl_errno($ch))
        {
            $error_msg = curl_error($ch);
            Mage::log("ERROR curl exec " . $url . ", " . print_r($data, true) . " - message: " . print_r($error_msg, true)
                    . " - response: " . print_r($result, true), null, 'mocapay.log');
        }

        //close connection
        curl_close($ch);
        return $result;
    }

    public function execPostRequestWithAuth($url, $data, $authorization, $date)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $authorization,
            'Date: ' . $date
                )
        );

        //execute post
        $result = curl_exec($ch);
        if (curl_errno($ch))
        {
            $error_msg = curl_error($ch);
            Mage::log("ERROR curl exec " . $url . ", " . print_r($data, true) . " - message: " . print_r($error_msg, true)
                    . " - response: " . print_r($result, true), null, 'mocapay.log');
        }

        //close connection
        curl_close($ch);
        return $result;
    }

    public function createRedmineIssue($order)
    {
        $increment_id = $order->getIncrementId();
        Mage::log("*** Mocapay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ")", null, "mocapay.log");
        $helper = \Mage::helper('cancelorder');
        $subject = 'Hoàn tiền cho khách đơn hàng ' . $increment_id;
        $description = 'Đơn hàng đã được thanh toán nhưng khách hủy. Hoàn tiền lại cho khách.';
        $response = $helper->createRedmineIssue(1, $subject, $description, 12);
        Mage::log("*** Mocapay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ") - response: " . print_r($response, true), null, "mocapay.log");
    }
    
    public function updateCodeVerifiedForMobile($transactionId, $orderId, $codeVerifier)
    {
        if (!empty($codeVerifier) && $codeVerifier != "null")
        {
            Mage::log("Update code verified for mobile: order_id=" . $orderId . ", transaction_id=" . $transactionId . ", code_verifier=" . $codeVerifier, null, "mocapay.log");
            $query = "update order_mocapay set code_verifier = :code_verifier where order_id = :order_id and transaction_id = :transaction_id ";
            $bind_params = array(
                'code_verifier' => $codeVerifier,
                'order_id' => $orderId,
                'transaction_id' => $transactionId
            );
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->query($query, $bind_params);
        }
    }

}
