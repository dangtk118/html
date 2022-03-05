<?php

class Fahasa_Zalopay_Model_Payment extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'zalopay';
    public static $mobileChannel = false;
    public static $realOrderId = null;

    protected $_canUseInternal = true;
    protected $_canUseCheckout = false;
    
    public function getZalopayCode(){
        return $this->_code;
    }
    
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('zalopay/payment/redirect', array('_secure' => true, "zalopayCode" => self::getZalopayCode()));
    }

    public function getMode() {
        return Mage::getStoreConfig('payment/zalopay/mode');
    }

    public function getAppId() {
        return Mage::getStoreConfig('payment/zalopay/app_id');
    }

    public function getKey1() {
        return Mage::getStoreConfig('payment/zalopay/key_1');
    }

    public function getKey2() {
        return Mage::getStoreConfig('payment/zalopay/key_2');
    }

    public function getZalopayBaseApi() {
        // check live/sanbox mode
        if (self::getMode() == 1) {
            return Mage::getStoreConfig('payment/zalopay/zalopay_base_api');
        } else {
            return Mage::getStoreConfig('payment/zalopay/zalopay_base_api_sanbox');
        }
    }

    public function getZalopayGatewayApi() {
        return Mage::getStoreConfig('payment/zalopay/zalopay_gateway_api');
    }
    
     public function getRefundUrl() {
        return Mage::getStoreConfig('payment/zalopay/zalopay_refund_api');
    }

    public function getBankCode() {
        return isset($_REQUEST['bankcode']) ? $_REQUEST['bankcode'] : '';
    }

    public function getType() {
        return isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
    }

    public function loadConfig() {
        $path = Mage::getModuleDir('', 'Fahasa_Zalopay');
        require_once $path . '/lib/zalopay/BaseEntity.php';
        require_once $path . '/lib/zalopay/Api.php';
        require_once $path . '/lib/zalopay/AppIdToken.php';
        require_once $path . '/lib/zalopay/CallbackData.php';
        require_once $path . '/lib/zalopay/CallbackPayment.php';
        require_once $path . '/lib/zalopay/CallbackRequest.php';
        require_once $path . '/lib/zalopay/Config.php';
        require_once $path . '/lib/zalopay/DateUtil.php';
        require_once $path . '/lib/zalopay/DefaultResponse.php';
        require_once $path . '/lib/zalopay/EmbedData.php';
        require_once $path . '/lib/zalopay/GatewayPayment.php';
        require_once $path . '/lib/zalopay/Item.php';
        require_once $path . '/lib/zalopay/JsonUtil.php';
        require_once $path . '/lib/zalopay/Order.php';
        require_once $path . '/lib/zalopay/OrderBuilder.php';
        require_once $path . '/lib/zalopay/OrderGateway.php';
        require_once $path . '/lib/zalopay/OrderResponse.php';
        require_once $path . '/lib/zalopay/ProductInfo.php';
        require_once $path . '/lib/zalopay/PromotionInfo.php';
        require_once $path . '/lib/zalopay/QrPayment.php';

        require_once $path . '/lib/phpqrcode/phpqrcode.php';

        // add by theanh
        require_once $path . '/lib/zalopay/OrderStatusResponse.php';

        $config = new ZalopayConfig(
                self::getAppId(), self::getKey1(), self::getKey2(), self::getZalopayBaseApi(), self::getZalopayGatewayApi()
        );
        return $config;
    }

    // build all item in order
    function getItems($order) {
        $allItem = $order->getAllItems();
        $items = array();
        foreach ($allItem as $pro) {
            // get only parent item
            if ($pro->getParentItemId() == null) {
                $item = new Item();
                $item->setItemId($pro->getId());
                $item->setItemName($pro->getName());
                $item->setItemQuantity(round($pro->getQtyOrdered()));
                $item->setItemPrice(round($pro->getBaseOriginalPrice()));
                $items[] = $item;
            }
        }
        return $items;
    }

    // create redirect order in zalopay server
    public static function createZalopayOrder($zalopayCode) {
     
        $helper = Mage::helper('zalopay');

        if (self::$mobileChannel == true)
        {
            $orderId = self::$realOrderId;
        }
        else
        {
            $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        // order info
        $phoneNum = trim(preg_replace("/[^0-9]/", "", $order->getBillingAddress()->getTelephone()));
        $customerName = trim(substr($helper->remove_sign($order->_data['customer_lastname']) . ' ' . Mage::helper('webmoney')->remove_sign($order->_data['customer_firstname']), 0, 63));
        $customerAddress = trim(substr($order->getBillingAddress()->getStreet(-1) . ' ' . $order->getBillingAddress()->getCity() . ', ' . $order->getBillingAddress()->getRegion() . ' ' . $order->getBillingAddress()->getCountryId(), 0, 255));
        $customerEmail = trim($order->_data['customer_email']);
        $totals = round($order->getTotalDue());

        // config
        $config = self::loadConfig();

        // order builder
        $orderBuilder = new OrderBuilder();
        $orderBuilder->setAppId($config->getAPPID());
        $orderBuilder->setAppUser('pmqc');
        $orderBuilder->setAppTime(time() * 1000);

        $appTransId = DateUtil::getAppTransIdPrefix() . $orderId;
        $orderBuilder->setAppTransId($appTransId);

        // If you running campaign, please input info;
        //create param for zalopay promotion
        $is_promotion = self::checkOrderHasPromotion($order);
        if ($is_promotion){
            $promotionInfo = new PromotionInfo($is_promotion, array());
        }
        else{
            $promotionInfo = new PromotionInfo('', array());
        }

        // Embeddata will submit back throw callback api
        $bankCode = "";
        $bankGroup = "";
        
        if (!empty($zalopayCode)){
            if ($zalopayCode == "zalopayatm"){
                $bankGroup = "ATM";
            }
            else if ($zalopayCode == "zalopaycc"){
                $bankCode = "CC";
            }
            else if ($zalopayCode == "zalopayapp"){
                $bankCode = "zalopayapp";
            }
        }
        
        $embedData = new EmbedData($promotionInfo->toJson(), $orderId, $bankGroup);
        $orderBuilder->setEmbedData($embedData);
        
        $orderBuilder->setBankcode($bankCode);

        // build list item
        $items = self::getItems($order);

        $orderBuilder->setItems("");
        $orderBuilder->setAmount($totals);

        $orderBuilder->setDescription('Payment for order Fahasa.com No#' . $orderId);
        $orderBuilder->setKey1($config->getKEY1());

        $orderBuilder->setPhone($phoneNum);
        $orderBuilder->setEmail($customerEmail);
        $orderBuilder->setAddress($customerAddress);
        
        $orderParams = $orderBuilder->createOrder();

        try {
            self::insertOrderZalopay($orderId, $appTransId, $totals, $zalopayCode);
            $order->setState("new", "pending_payment", 'Thanh toán qua Zalopay chuẩn bị xử lý.');
            $order->save();

            Mage::log("*** zalopay - create order #" . $orderId . " Data Object: " . print_r($orderParams->toArray(), true), null, "zalopay.log");

            $orderResponse = Api::createOrder($config->getZALOPAY_BASE_API() . '/v001/tpe/createorder', $orderParams);
            Mage::log("*** order response - order id: " . $orderId . " - " . print_r($orderResponse, true), null, "zalopay.log");
            if ($orderResponse->getReturnCode() == 1) {
                Mage::log("*** zalopay - create order zalopay success #" . $orderId . " Data Object: " . print_r($orderParams->toArray(), true), null, "zalopay.log");
                $zpTransToken = $orderResponse->getZpTransToken();
                $appIdToken = new AppIdToken($config->getAPPID(), $zpTransToken);
                $redirectUrl = GatewayPayment::generateRedirectUrlByToken($appIdToken, $config->getZALOPAY_GATEWAY_API());
                if(self::$mobileChannel == true){
                    $data['paymentUrl'] = $redirectUrl;
                    $data['transactionId'] = $appTransId;
                    $data['zpTransToken'] = $zpTransToken;
                    return $data;
                } else {
                    header('Location: ' . $redirectUrl);
                    return;
                }
            } else {
                self::redirectFailureFromCreateOrderZalopay($order, $orderParams);
            }
        } catch (Exception $ex) {
            self::redirectFailureFromCreateOrderZalopay($order, $orderParams);
        }
    }

    function redirectFailureFromCreateOrderZalopay($order, $orderParams) {
        Mage::log("*** zalopay - Fail to request create order #" . $order->getIncrementId() . ". Redirect to checkout/onepage/failure/. Data Object: " . print_r($orderParams->toArray(), true), null, "zalopay.log");
        $fail_url = \Mage::getUrl("checkout/onepage/failure/");

        //do not canceled order for repayment

        if (self::$mobileChannel == true) {
            $data['url'] = $fail_url;
            $data['message'] = "Redirect failure";
            return $data;
        } else {
            header('Location: ' . $fail_url);
            return;
        }
    }

    // insert order order_zalopay
    function insertOrderZalopay($orderId, $appTransId, $amount, $zalopayCode) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "INSERT INTO `order_zalopay` "
                . "(`order_id`, `apptransid`, `amount`, `payment_code`) "
                . "  VALUES "
                . "(" . $orderId . ", '" . $appTransId . "', '" . $amount . "', '". $zalopayCode ."')";
        Mage::log("*** zalopay - insert order #" . $orderId . " - appTransId=" . $appTransId . " in db query: " . $query, null, "zalopay.log");
        $write->query($query);
        
        //insert payment log to check current payment
        Mage::helper('repayment')->addPaymentLog($orderId, $zalopayCode, $appTransId);
        
    }

    // update status order_zalopay
    function updateOrderZalopay($orderId, $appTransId, $zpTransId, $status, $bankCode, $zpSystem, $channel, $callback = 0, $amountDiscount = 0) {
        $query = "update order_zalopay set status = '{$status}'";
        
        if ($zpTransId){
            $query .= ", zptransid = '{$zpTransId}' ";
        }
        if ($bankCode){
            $query .= ", bankcode = '{$bankCode}' ";
        }
        if ($zpSystem){
            $query .= ", zpSystem = {$zpSystem} ";
        }
        if ($channel){
            $query .= ", channel={$channel} ";
        }
        if ($amountDiscount && $amountDiscount != 0 && $amountDiscount != "0"){
             $query .= ", discount={$amountDiscount} ";
        }
        //called by callback
        if ($callback == 1){
            $query .= ", callback={$callback} ";
        }
        
        $query .= " where apptransid='{$appTransId}' ";
        if ($orderId == ""){
            $query .= " and order_id='{$orderId}'";
        }
        
        $query .= ";";
        
        Mage::log("*** zalopay - update appTransId=" . $appTransId . " in db query: " . $query, null, "zalopay.log");
        try{
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->query($query);
        } catch (Exception $ex) {
            Mage::log("*** zalopay: update order zalopay failed. Exception=" . $ex->getMessage(), null, "zalopay.log");
        }
    }

    // get info order from order_zalopay
    function getOrderInfo($appTransId) {
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query = "select * from order_zalopay where apptransid = '" . $appTransId . "';";
        $results = $read->fetchAll($query);

        if ($results[0]) {
            return $results[0];
        } else {
            return null;
        }
    }

    // GET check status order from zalopay
    public function checkStatusOrderZalopay($orderId, $config, $appId, $appTransId, $bankCode, $zpSystem, $channel, $callback = 0) {

        $hmacInput = sprintf("%s|%s|%s", $appId, $appTransId, $config->getKEY1());
        $mac = hash_hmac("sha256", $hmacInput, $config->getKEY1());
        // build params check status
        $orderParams = array(
            'appid' => $appId,
            'apptransid' => $appTransId,
            'mac' => $mac
        );

        // check status
        $url = $config->getZALOPAY_BASE_API() . '/v001/tpe/getstatusbyapptransid?' . http_build_query($orderParams);
        Mage::log("*** zalopay -check status zalopay server Order #" . $orderId . " appTransId " . $appTransId . "Url =" . $url, null, "zalopay.log");
        $orderResponse = Api::statusOrder($url);
        Mage::log("*** zalopay - response status order #" . $orderId . " appTransId " . $appTransId . "Data Object: " . print_r($orderResponse, true), null, "zalopay.log");
        if ($callback){
            self::updateOrderZalopay($orderId, $appTransId, $orderResponse->getZpTransId(), $orderResponse->getReturnCode(), $bankCode, $zpSystem, $channel, $callback,0);
        }
        return $orderResponse;
    }
    
    public function refundOrder($zalopay_order){
        //do not run in callback, only mark for next java to process
        self::updateZalopayOrderRefund($zalopay_order['order_id'], $zalopay_order['apptransid'], -1000, "Call refund after");
    }
    
    public function updateZalopayOrderRefund($order_id, $transaction_id, $refund_code, $refund_message, $refund_id){
        $update = "update order_zalopay set refund_code = :refund_code, refund_message = :refund_message, refund_id = :refund_id "
                . "where order_id = :order_id and apptransid = :apptransid ";
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $binds = array(
            'refund_code' => $refund_code,
            'refund_message' => $refund_message,
            'refund_id' => $refund_id,
            'order_id' => $order_id,
            'apptransid' => $transaction_id,
        );
        $write->query($update, $binds);
    }
    
    public function handleOrderSuccess($order)
    {
        if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
        {
            $order->setState("new", "paid", 'Thanh toán qua Zalopay thành công');
            $order->save();
            //send order email
            Mage::dispatchEvent('payment_order_return', array('order_id'=>$order->getEntityId(), 'increment_id'=>$order->getIncrementId(), 'status'=>'success', 
                'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_PAYMENT_SUCCESS, 
                'cur_payment_method' => $order->getPayment()->getMethod(),
                'cur_payment_title' => $order->getPayment()->getMethodInstance()->getTitle(), 
                'customer_id' => $order->getCustomerId(), 'customer_email' => $order->getCustomerEmail()));
        }
        else if ($order->getState() == "canceled" && $order->getStatus() == "canceled")
        {
            self::addStatusHistoryComment($order, "Thanh toán qua Zalopay thành công");
            self::createRedmineIssue($order);
        }
        else
        {
            self::addStatusHistoryComment($order, "Thanh toán qua Zalopay thành công");
        }
    }

    // set status fhs_sales_flat_order
    public function pendingOrder($order) {
        $order->setState("new", "pending_payment", 'Thanh toán qua Zalopay đang được xử lý.');
        $order->save();
    }

    public function addStatusHistoryComment($order, $comment){
        $history = $order->addStatusHistoryComment($comment, false);
        $history->setIsCustomerNotified(false);
        $order->save();
    }
    
    // callback action when zalopay server return callbackURL
    public function callback($data) {
        Mage::log("*** zalopay - response POST callback action", null, "zalopay.log");
        // call back from REST can't get orderId
        $orderId = "";
        $requestBody = json_encode(isset($data) ? $data : "");
        Mage::log("*** zalopay - response POST callback action :response data =" . $requestBody, null, "zalopay.log");

        // config
        $config = self::loadConfig();

        //return response to zalopay
        $returnCode = null;
        $returnMessage = null;

        if (!empty($requestBody)) {
            $json = json_decode($requestBody, true);
            $callbackRequest = new CallbackRequest($json['data'], $json['mac']);
            Mage::log("*** zalopay - response POST callback action: data =" . $json['data'], null, "zalopay.log");
            Mage::log("*** zalopay - response POST callback action: mac =" . $json['mac'], null, "zalopay.log");
            if (CallbackPayment::isValid($callbackRequest, $config->getKEY2())) {
                $callbackData = CallbackPayment::getCallbackData($callbackRequest);
                Mage::log("*** zalopay - response POST callback begin check order status GET from zalopay - " . $requestBody, null, "zalopay.log");

                //get order status in fhs_sales_flat_order
                $orderInfo = self::getOrderInfo($callbackData->getAppTransId());
                if ($orderInfo != null) {
                    //zalopay has never callback for this order
                    if (!$orderInfo['callback']) {
                        try {
                            // check status from zalopay + update status fahasa db
                            $orderId = $orderInfo['order_id'];
                            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

                            // add amount discount
                            $amountDiscount = $callbackData->getDiscountAmount();
                            $channel = $callbackData->getChannel();
                            $bankCode = $callbackData->getBankcode();
                            if ($channel == "36"){
                                $bankCode = $callbackData->getCcbankcode();
                            }
                            
//                            $orderResponse = self::checkStatusOrderZalopay($orderId, $config, $callbackData->getAppId(), 
//                                    $callbackData->getAppTransId(), $bankCode, $callbackData->getZpSystem(), $callbackData->getChannel(), 1);
                            
                            $this->updateOrderZalopay($orderId, $callbackData->getAppTransId(), $callbackData->getZptransid(), 1, $callbackData->getBankcode(), $callbackData->getZpSystem(),
                                    $callbackData->getChannel(), 1, $amountDiscount);
                            //get zpTransId, amount for check security
                            if ($callbackData->getAppTransId() == $orderInfo['apptransid']) {
                                $is_current_transaction = Mage::helper('repayment')->checkTransIsCurPayment($order, $orderInfo['apptransid']);
                                //check order in 1 hour time to repayment
                                if (Mage::helper('repayment')->checkOrderInPaymentTime($order))
                                {
                                    if ($is_current_transaction)
                                    {
                                        //set success
                                        self::handleOrderSuccess($order);
                                    }
                                    else
                                    {
                                        //refund
                                        self::refundOrder($orderInfo);
                                    }
                                }
                                else
                                {
                                    if ($is_current_transaction)
                                    {
                                        //set success
                                        self::handleOrderSuccess($order);
                                    }
                                    else
                                    {
                                        //refund
                                         self::refundOrder($orderInfo);
                                    }
                                }

                                $returnCode = 1;
                                $returnMessage = "SUCCESS";
                            } else {
                                Mage::log("*** zalopay - response POST callback: zpTransid and amount are different - orderId = #" 
                                        . $orderId . ", appTransId=" . $callbackData->getAppTransId() . ", zpTransId(callback)="
                                        . $callbackData->getZptransid() . "- zpTransId(queryStatus)=" . $orderResponse->getZpTransId() .
                                        ", amount(callback)=" . $callbackData->getAmount() . " - amount(queryStatus)=" . $orderResponse->getAmount(), null, "zalopay.log");
                                $returnCode = -3;
                                $returnMessage = "ZP_TRANID_INVALID";
                            }
                        } catch (Exception $ex) {
                            Mage::log("Exception update order when callback " . $ex, null, "zalopay.log");
                            $returnCode = 0;
                            $returnMessage = "ERR_UPDATE_STATUS";
                        }
                    } else {
                        //zalopay has callback for this order before
                        $returnCode = 2;
                        $returnMessage = "CALLBACK_BEFORE";
                    }
                } else {
                    $returnCode = -1;
                    $returnMessage = "INVALID_ORDER";
                }
            } else {
                Mage::log("*** zalopay - response POST callback: Invalid mac - " . $requestBody, null, "zalopay.log");
                $returnCode = -2;
                $returnMessage = "INVALID_MAC";
            }
        }
        Mage::log("*** zalopay - return response POST callback: returnCode = " . $returnCode . ", returnMessage = " . $returnMessage, null, "zalopay.log");
        return array(
            "returnCode" => $returnCode,
            "returnMessage" => $returnMessage
        );
    }
    
    public function checkStatusFromRedirect($app_id, $app_trans_id, $pmcid, $bankcode, $amount, $discountamount, $status, $checksum)
    {
        $config = self::loadConfig();
        $hmacInput = sprintf("%s|%s|%s|%s|%s|%s|%s", $app_id, $app_trans_id, $pmcid, $bankcode, $amount, $discountamount, $status);
        $checksum_generate = hash_hmac("sha256", $hmacInput, $config->getKEY2());

        if ($checksum_generate == $checksum)
        {
            $orderInfo = self::getOrderInfo($app_trans_id);
            if ($orderInfo != null && $status == 1)
            {
                return true;
            }
        }
        return false;
    }

    public function checkOrderStatusFromRedirect($appTransId, $status, $bankCode) {
        $config = self::loadConfig();
        $appId = $config->getAPPID();
        Mage::log("*** zalopay - response GET redirect action appTransId=" . $appTransId . ", status=" . $status, null, "zalopay.log");
        $orderInfo = self::getOrderInfo($appTransId);
        $result = array();
        if ($orderInfo != null) {
            $orderId = $orderInfo['order_id'];
            $result['success'] = true;
                // retry 3 times if is processing payment
            for ($i = 0; $i < 3; $i++) {
                sleep($i);
                $orderResponse = self::checkStatusOrderZalopay($orderId, $config, $appId, $appTransId);
                Mage::log("*** zalopay - response checkStatusOrderZalopay appTransId=" . $appTransId . ", status=" . $orderResponse->getReturnCode(), null, "zalopay.log");
                $status = $orderResponse->getReturnCode();
                if ($orderResponse->getIsProcessing() == FALSE && $status != -49 && $status != -117) {
                    Mage::log("*** zalopay - response checkStatusOrderZalopay appTransId=" . $appTransId . ", status=" . $orderResponse->getReturnCode() . ", IsProcessing = " . $orderResponse->getIsProcessing(), null, "zalopay.log");
                    break;
                }
                Mage::log("*** zalopay - retry GET redirect action appTransId=" . $appTransId . " orderId =" . $orderId, null, "zalopay.log");
            }
            $result['status'] = $status;

        } else {
            $result['success'] = false;
            Mage::log("*** zalopay - Error. No orderInfo for appTransId=" . $appTransId . " is null", null, "zalopay.log");
        }

        return $result;
    }
    
    public function createRedmineIssue($order){
        $increment_id = $order->getIncrementId();
        Mage::log("*** zalopay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ")", null, "zalopay.log");
        $helper = Mage::helper('cancelorder');
        $subject = 'Hoàn tiền cho khách đơn hàng ' . $increment_id;
        $description = 'Đơn hàng đã được thanh toán nhưng khách hủy. Hoàn tiền lại cho khách.';
        $response = $helper->createRedmineIssue(1, $subject, $description, 12);
        Mage::log("*** zalopay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ") - response: " . print_r($response, true), null, "zalopay.log");
    }
    
    public function getActivePaymentEvent($payment_method, $rule_ids){
        $query = "select * from fhs_payment_event "
                . "where active = 1 and now() between from_date and to_date "
                . "and (day_of_week is null or find_in_set(dayofweek(now()), day_of_week)) "
                . "and payment = :payment ";

        if (!empty($rule_ids)) {
            $query .= "and fhs_rule_id in (" . $rule_ids . ") ";
        }
        
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $params = array(
            "payment" => $payment_method
        );
        $result = $read->fetchAll($query, $params);
        return $result;
    }
    
    function checkOrderHasPromotion($order){
        $applied_rule_ids = $order->getAppliedRuleIds();
        if (!$applied_rule_ids){
            return false;
        }
        
        $events = self::getActivePaymentEvent($order->getPayment()->getMethod(), $applied_rule_ids);
        if (count($events) > 0 && !empty($events[0]['partner_promotion'])){
            return $events[0]['partner_promotion'];
        }
        
        return false;
    }

    public static function postCurl($url, $params, $second = 30)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $data = curl_exec($ch);
        if ($data)
        {
            curl_close($ch);
            return $data;
        }
        else
        {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error, 0);
        }
    }

}
