<?php

require_once('AirpayConfig.php');

class TTS_Airpay_Model_Airpay extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'airpay';
    protected $_formBlockType = 'airpay/form';
    protected $_infoBlockType = 'airpay/info';
    protected static $_CONFIG;

    public function getConfig()
    {
        if (!self::$_CONFIG)
        {
            self::$_CONFIG = new AirpayConfig(self::getAppId(), self::getAppKey(), self::getBaseUrl());
        }

        return self::$_CONFIG;
    }

    public function getAppId()
    {
        return $this->getConfigData('app_id');
    }

    public function getAppKey()
    {
        return $this->getConfigData('app_key');
    }

    public function getBaseUrl()
    {
        return $this->getConfigData('base_url');
    }

    public function createRequestIdForOrder($orderId)
    {
        $tz = 'Asia/Ho_Chi_Minh';
        $timestamp = time();
        $dt = new DateTime("now", new DateTimeZone($tz));
        $dt->setTimestamp($timestamp);
        return $dt->format('ymdHis') . $orderId;
    }

    public function getUrlAirpay($order_id, $channel = 'web')
    {
        Mage::log("Get airpay_payment url: " . $order_id . ", channel = " . $channel, null, "airpay.log");
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

        if ($order->getId())
        {
            $transaction_id = $this->createRequestIdForOrder($order_id);
            $amount = $order->getGrandTotal();

            if ($this->insertOrderAirpay($order_id, $transaction_id, $amount, $channel))
            {
                $base_url = self::getConfig()->getBaseUrl();
                $url = $base_url . "/pay/get_landing_url";
                $app_id = self::getConfig()->getAppId();
                $app_key = self::getConfig()->getAppKey();
                $source = 'web';

                $redirect_param = "?transaction_id=" . $transaction_id;
		if($channel == 'web'){
		    $return_url = Mage::getUrl('airpay/standard/response') . $redirect_param;
		}else{
		    $return_url = "fahasaapp-airpay://backapp" . $redirect_param;
		}
                $data = array(
                    'app_id' => $app_id,
                    'app_key' => $app_key,
                    'order_id' => $transaction_id,
                    'source' => $source,
                    'return_url' => $return_url,
                );
                Mage::log("Air pay get url " . print_r($data, true), null, "airpay.log");
                $response = json_decode($this->execPostRequest($url, ($data)), true);
                Mage::log("Air pay get url execPostRequest " . print_r($response, true), null, "airpay.log");
                //return url for web
                if ($response && $response['url'])
                {
                    Mage::log("Get airpay_payment url: response " . $order_id . ", url = " . $response['url'], null, "airpay.log");
                    return array(
                        'redirect_url' => $response['url'],
                        'transaction_id' => $transaction_id,
                        'paymentUrl' => $response['url'], //for mobile app
			'transactionId' => $transaction_id //for mobile app
                    );
                }
            }
            else
            {
                Mage::log("No create transaction airpay " . $order_id . ", transaction=" . $transaction_id, null, "airpay.log");
            }
        }
        else
        {
            Mage::log("No order with order_id=" . $order_id, null, "airpay.log");
        }
        return self::redirectFailureFromCreateOrderAirpay();
    }

    //return true: transaction was paid. return false: transaction was failed
    public function checkStatusFromRedirect($transaction_id)
    {
        Mage::log("Airpay redirect url: transaction_id = " . $transaction_id, null, "airpay.log");
        if ($transaction_id)
        {
            $order_airpay = $this->getOrderByTransactionId($transaction_id);
            if ($order_airpay)
            {
                Mage::log("Airpay redirect transaction_id=" . $transaction_id . ", status (in db) = " . $order_airpay['status'], null, "airpay.log");
                $order_status = (int) $order_airpay['status'];
                //????need check callback field or not
                if ($order_status == -1000) {
                    //check airpay status
                    $status_response = self::getAirpayStatus($transaction_id);
                    if ($status_response)
                    {
                        $order_status = $status_response['order_status'];
                        if ($order_status == 200)
                        {
                            return true;
                        }
                    }
                }
                else if ($order_status == 200)
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        return false;
    }

    public function getAirpayStatus($transaction_id)
    {
        Mage::log("Get Airpay status begin: " . $transaction_id, null, "airpay.log");
        $app_id = self::getConfig()->getAppId();
        $app_key = self::getConfig()->getAppKey();
        $base_url = self::getConfig()->getBaseUrl();
        $url = $base_url . "/pay/get_order_status";
        $data = array(
            'app_id' => $app_id,
            'app_key' => $app_key,
            'order_id' => $transaction_id,
            'detailed' => 1,
        );
        Mage::log("Get Airpay status: data = " . print_r($data, true), null, "airpay.log");
        $response = json_decode($this->execPostRequest($url, $data), true);
        Mage::log("Get Airpay status end: response = " . $transaction_id . " - response = " . print_r($response, true), null, "airpay.log");
        return $response;
    }

    public function getOrderDetailForAirpay($app_key, $transaction_id)
    {
        Mage::log("Airpay get order_detail: app_key" . $app_key . ", transaction_id=" . $transaction_id, null, "airpay.log");
        $order_validity = 400;
        $order_data = null;
        $app_key_db = self::getConfig()->getAppKey();
        if ($app_key_db === $app_key)
        {
            $order_airpay = $this->getOrderByTransactionId($transaction_id);
            if ($order_airpay)
            {
                $order_id = $order_airpay['order_id'];
                $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
                $app_id = self::getConfig()->getAPPID();
                $amount = (int) $order->getGrandTotal();
                $expiry_time = $order_airpay['expiry_time'];
                $order_data = array(
                    'app_id' => $app_id,
                    'order_id' => $order_airpay['transaction_id'],
                    'currency' => 'VND',
                    'payable_amount' => $amount,
                    'expiry_time' => $expiry_time,
                    'item_name' => 'Đơn hàng Fahasa - ' . $order_id,
                );
                $order_validity = 200;
            }
            else
            {
                $order_validity = 400;
                Mage::log("Transaction_id is wrong - no order mapping" . $app_key . " - transaction_id " . $transaction_id, null, "airpay.log");
            }
        }
        else
        {
            $order_validity = 400;
            Mage::log("App key is wrong " . $app_key . " - transaction_id " . $transaction_id, null, "airpay.log");
        }

        Mage::log("Airpay get order_detail: response data " . print_r($order_data, true), null, "airpay.log");
        return array(
            'order_validity' => $order_validity,
            'order' => $order_data
        );
    }

    public function validateOrderForAirpay($app_key, $transaction_id)
    {
        Mage::log("Airpay validate order begin: app_key=" . $app_key . ", transaction_id=" . $transaction_id, null, "airpay.log");
        $order_validity = 400;
        $app_key_db = self::getConfig()->getAppKey();
        if ($app_key_db === $app_key)
        {
            $order_airpay = $this->getOrderByTransactionId($transaction_id);
            if ($order_airpay)
            {
                $order_id = $order_airpay['order_id'];
//                $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
                $expiry_time = $order_airpay['expiry_time'];
                $current_timestamp = time();
                if ($current_timestamp <= $expiry_time)
                {
                    //check whether order is payment success?? (read the red note in document)
                    //=> force return 200 to confirm transaction
                    $order_validity = 200;
                }
                else
                {
                    // expirty time for payment order
                    $order_validity = 200;
                }
            }
            else
            {
                Mage::log("Transaction_id is wrong - no order mapping" . $app_key . " - transaction_id " . $transaction_id, null, "airpay.log");
                $order_validity = 400;
            }
        }
        else
        {
            Mage::log("App key is wrong " . $app_key . " - transaction_id " . $transaction_id, null, "airpay.log");
            $order_validity = 400;
        }
        Mage::log("Airpay valide order end: return response order_validaity = " . $order_validity, null, "airpay.log");
        return array('order_validity' => $order_validity);
    }

    public function notifyOrderStatusAirpay($request)
    {
        $return_order_id = null;
        $return_message = null;

        $app_key = $request->getPost('app_key');
        $transaction_id = $request->getPost('order_id');
        $order_status_notify = $request->getPost('order_status');
        $payment_id = $request->getPost('payment_id');
        $event_id = $request->getPost('event_id');
        $coupon_id = $request->getPost('coupon_id');

        Mage::log("Airpay notify begin: data: app_key=" . $app_key . ", transaction_id=" . $transaction_id . ", order_status=" . $order_status_notify . ", payment_id=" . $payment_id
                . ", event_id=" . $event_id . ", coupon_id=" . $coupon_id, null, "airpay.log");

        $app_key_db = self::getConfig()->getAppKey();
        if ($app_key_db === $app_key)
        {
            $order_airpay = $this->getOrderByTransactionId($transaction_id);
            if ($order_airpay)
            {
                if ($order_airpay['callback'] == 0)
                {
                    $status_response = self::getAirpayStatus($transaction_id);
                    if (!empty($status_response) && $status_response['code'] == 0)
                    {
                        $bankcode = $status_response['payment_method'];
                        $bank_name = $status_response['payment_name'];
                        $order_status = $status_response['order_status'];
                        $order_id = $order_airpay['order_id'];
                        $update_order_airpay = array(
                            'transaction_id' => $transaction_id,
                            'order_status' => $order_status,
                            'payment_id' => $payment_id,
                            'event_id' => $event_id,
                            'coupon_id' => $coupon_id, 
                            'bankcode' => $bankcode,
                            'bank_name' => $bank_name,
                        );

                        $this->handleFahasaOrder($order_id, $update_order_airpay);
                    }
                    $return_order_id = $order_airpay['transaction_id'];
                    $return_message = "Thanh toán đơn hàng thành công";
                }
                else
                {
                    Mage::log("Airpay had notified before " . $app_key . " - transaction_id " . $transaction_id, null, "airpay.log");
                    $return_order_id = $order_airpay['transaction_id'];
                    $return_message = "Thanh toán đơn hàng thành công";
                }
            }
            else
            {
                Mage::log("Transaction_id is wrong - no order mapping" . $app_key . " - transaction_id " . $transaction_id, null, "airpay.log");
            }
        }
        else
        {
            Mage::log("App key is wrong " . $app_key . " - transaction_id " . $transaction_id, null, "airpay.log");
        }

        Mage::log("Airpay notify end: return response return_order_id = " . $return_order_id . ", message = " . $return_message, null, "airpay.log");
        return array(
            'order_id' => $return_order_id,
            'msg' => $return_message,
        );
    }

    function handleFahasaOrder($order_id, $airpay_data)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        if ($order->getId())
        {
            $is_current_transaction = Mage::helper('repayment')->checkTransIsCurPayment($order, $airpay_data['transaction_id']);
            $is_in_repayment_time = Mage::helper('repayment')->checkOrderInPaymentTime($order);
            Mage::log("Handle fahasa order: is_in_repayment_time=" . $is_in_repayment_time . ", is_current_trans=" . $is_current_transaction . " - airpay_status = " . $airpay_data['order_status'], null, "airpay.log");
            //status = 200 => success
            if ($airpay_data['order_status'] == 200)
            {
                if ($is_in_repayment_time)
                {
                    if ($is_current_transaction)
                    {
                        self::handleOrderSuccess($order, $airpay_data);
                    }
                    else
                    {
                        self::markOrderRefund($order, $airpay_data);
                    }
                }
                else
                {
                    if ($is_current_transaction)
                    {
                        self::handleOrderSuccess($order, $airpay_data);
                    }
                    else
                    {
                        self::markOrderRefund($order, $airpay_data);
                    }
                }
            }
            else
            {
                if ($is_in_repayment_time)
                {
                    if ($is_current_transaction)
                    {
                        //do nothing => for repayment 1 hour 
                        self::markOrderProccessed($order, $airpay_data);
                    }
                    else
                    {
                        //do nothing => for repayment 1 hour 
                        self::markOrderProccessed($order, $airpay_data);
                    }
                }
                else
                {
                    if ($is_current_transaction)
                    {
                        //transaction is current trans of fahasa order
                        //cancel order -> last transaction
                        //function cancel order
                        if (Mage::helper('repayment')->checkOrderHasTransRefund($order->getIncrementId(), $airpay_data['transaction_id']))
                        {
                            self::markOrderProccessed($order, $airpay_data);
                        }
                        else
                        {
                            self::handleOrderFail($order, $airpay_data);
                        }
                    }
                    else
                    {
                        //transaction is not current trans of fahasa order -> old transaction
                        //do nothing
                        self::markOrderProccessed($order, $airpay_data);
                    }
                }
            }
        }
        else
        {
            Mage::log("Update order fahasa: order not found " . $order_id . " - airpay_data = " . print_r($airpay_data, true), null, "airpay.log");
        }

        return true;
    }

    function handleOrderSuccess($order, $airpay_data)
    {
        Mage::log("Handle fahasa order success: state=" . $order->getState() . ", status=" . $order->getStatus(), null, "airpay.log");
        //update order fahasa + order airpay
        try {
            if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
            {
                $order->setState("new", "paid", 'Thanh toán qua Airpay thành công');
                $order->save();
                self::updateOrderAirpay($order->getIncrementId(), $airpay_data);

                //send order email
                Mage::dispatchEvent('payment_order_return', array('order_id'=>$order->getEntityId(), 'increment_id'=>$order->getIncrementId(), 'status'=>'success',
                    'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_PAYMENT_SUCCESS, 'cur_payment_method' => $this->_code, 
                    'cur_payment_title' => $order->getPayment()->getMethodInstance()->getTitle(),
                    'customer_id' => $order->getCustomerId(), 'customer_email' => $order->getCustomerEmail()));
            }
            else if ($order->getState() == "canceled" && $order->getStatus() == "canceled")
            {
                self::addStatusHistoryComment($order, "Thanh toán qua Airpay thành công");
                self::createRedmineIssue($order);
                self::updateOrderAirpay($order->getIncrementId(), $airpay_data);
            }
            else
            {
                self::addStatusHistoryComment($order, "Thanh toán qua Airpay thành công");
                self::updateOrderAirpay($order->getIncrementId(), $airpay_data);
            }
        } catch (Exception $ex) {
            Mage::log("Handle fahasa order exception " . $ex, null, "airpay.log");
        }
    }

    function handleOrderFail($order, $airpay_data)
    {
        Mage::log("Handle fahasa order fail: state=" . $order->getState() . ", status=" . $order->getStatus(), null, "airpay.log");
        try {
            if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
            {
                $cancel_result = Mage::helper('cancelorder')->cancelOrderReturn($order, "airpay.log");
                if ($cancel_result)
                {
                    //Step 1. Update order_payment
                    self::updateOrderAirpay($order->getIncrementId(), $airpay_data);
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
                //add log history + update order_airpay
                self::addStatusHistoryComment($order, "Thanh toán qua Airpay thất bại");
                self::updateOrderAirpay($order->getIncrementId(), $airpay_data);
            }
            else
            {
                self::addStatusHistoryComment($order, "Thanh toán qua Airpay thất bại");
                self::updateOrderAirpay($order->getIncrementId(), $airpay_data);
            }
        } catch (Exception $ex) {
            
        }
    }

    //done
    public function markOrderProccessed($order, $airpay_data)
    {
        //only update status in order_momopay
        $this->updateOrderAirpay($order->getIncrementId(), $airpay_data);
    }

    //mark order for java to refund 
    public function markOrderRefund($order, $airpay_data)
    {
        try {
            Mage::log("Mark order refund: order_id=" . $order->getIncrementId() . ", transaction_id=" . $airpay_data['transaction_id'], null, "airpay.log");
            $order_id = $order->getIncrementId();
            Mage::log("Callback: mark order refund: order_id=" . $order_id . ", transaction_id=" . $airpay_data['transaction_id'], null, 'airpay.log');
            $this->updateOrderAirpay($order->getIncrementId(), $airpay_data);
            $query = "update order_airpay set refund_code = -1000 where order_id = :order_id and transaction_id = :transaction_id ";
            $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
            $binds = array(
                "order_id" => $order->getIncrementId(),
                "transaction_id" => $airpay_data['transaction_id']
            );
            $write->query($query, $binds);
        } catch (Exception $ex) {
            Mage::log("Mark order refund: exception " . $ex, null, "airpay.log");
        }
    }

    function updateOrderAirpay($order_id, $data)
    {
        try {
            $transaction_id = $data['transaction_id'];
            $order_status = $data['order_status'];
            $airpay_id = $data['payment_id'];
            $event_id = $data['event_id'];
            $coupon_id = $data['coupon_id'];
            $bankcode = $data['bankcode'];
            $bank_name = $data['bank_name'];

            $query = "update order_airpay set status = :status, airpay_id = :airpay_id, event_id = :event_id, coupon_id = :coupon_id, "
                    . "bankcode = :bankcode, bank_name = :bank_name,"
                    . " callback = 1 "
                    . "where order_id = :order_id and transaction_id = :transaction_id ";
            $bind_params = array(
                'status' => $order_status,
                'airpay_id' => $airpay_id,
                'event_id' => $event_id,
                'coupon_id' => $coupon_id,
                'bankcode' => $bankcode,
                'bank_name' => $bank_name,
                'order_id' => $order_id,
                'transaction_id' => $transaction_id,
            );
            Mage::log("Update order airpay status: order_id=" . $order_id . ", transaction_id=" . $transaction_id . ", status=" . $order_status . ", airpay_id=" . $airpay_id
                    . ", event_id=" . $event_id . ", coupon_id=" . $coupon_id, null, "airpay.log");
            $write = Mage::getSingleton('core/resource')->getConnection('core_wrire');
            $write->query($query, $bind_params);
            return true;
        } catch (Exception $ex) {
            Mage::log("Exception update order airpay " . $order_id . ", ex = " . $ex, null, "airpay.log");
            return false;
        }
    }

    function getOrderByTransactionId($transaction_id)
    {
        $query = "select order_id, transaction_id, amount, expiry_time, status, callback from order_airpay where transaction_id = '{$transaction_id}' ;";
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $result = $read->fetchAll($query);
        if ($result[0])
        {
            return $result[0];
        }
        return false;
    }

    function insertOrderAirpay($order_id, $transaction_id, $amount, $channel)
    {
        try {
            $insert_sql = "insert into order_airpay (order_id, transaction_id, created_at, updated_at, amount, channel, expiry_time) "
                    . "values (:order_id, :transaction_id, now(), now(), :amount, :channel, :expiry_time)";
            $amount = (int) $amount;
            $bind_params = array(
                'order_id' => $order_id,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'channel' => $channel,
                'expiry_time' => strtotime('+ 10 minutes'),
            );
            $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->query($insert_sql, $bind_params);
            Mage::helper('repayment')->addPaymentLog($order_id, $this->_code, $transaction_id);
            return true;
        } catch (Exception $ex) {
            Mage::log("Exception insert order airpay " . $order_id . ", transaction " . $transaction_id . " -ex: " . $ex, null, "airpay.log");
            return false;
        }
    }

    public function execPostRequest($url, $data)
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
                    . " - response: " . print_r($result, true), null, 'airpay.log');
        }

        //close connection
        curl_close($ch);
        return $result;
    }

    public function redirectFailureFromCreateOrderAirpay()
    {
        $fail_url = \Mage::getUrl("checkout/onepage/failure/");
        // cancel order

        return array(
            'url' => $fail_url
        );
    }

    public function addStatusHistoryComment($order, $comment)
    {
        $history = $order->addStatusHistoryComment($comment, false);
        $history->setIsCustomerNotified(false);
        $order->save();
    }

    public function createRedmineIssue($order)
    {
        $increment_id = $order->getIncrementId();
        \Mage::log("*** Airpay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ")", null, "airpay.log");
        $helper = \Mage::helper('cancelorder');
        $subject = 'Hoàn tiền cho khách đơn hàng ' . $increment_id;
        $description = 'Đơn hàng đã được thanh toán nhưng khách hủy. Hoàn tiền lại cho khách.';
        $response = $helper->createRedmineIssue(1, $subject, $description, 12);
        \Mage::log("*** Momopay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ") - response: " . print_r($response, true), null, "airpay.log");
    }

}
