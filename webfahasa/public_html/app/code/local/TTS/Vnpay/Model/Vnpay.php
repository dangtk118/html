<?php

require_once('ConfigVnPay.php');

class TTS_Vnpay_Model_Vnpay extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'vnpay';
    protected $_formBlockType = 'vnpay/form';
    protected $_infoBlockType = 'vnpay/info';
    

    protected static $_CONFIG;
    protected static $_is_mobile = false;
    protected static $_platform = null;
    
    public function getConfig(){
        if (!self::$_CONFIG) {
            self::$_CONFIG = new ConfigVnPay(self::getVnpTerminal(), self::getSecretKey(), self::getBaseUrl());
        }
        
        return self::$_CONFIG;
    }

    public function getTitle() {
        return $this->getConfigData('title');
    }

    public function get_icon() {
        return $this->getConfigData('icon');
    }
    
    public function getVnpTerminal(){
        if (self::$_is_mobile){
            if (self::$_platform == "mobile_ios"){
                return $this->getConfigData('vnp_Terminal_Mobile_ios');    
            } else if (self::$_platform == "mobile_android"){
                return $this->getConfigData('vnp_Terminal_Mobile_android');    
            }
            
        }
        return $this->getConfigData('vnp_Terminal');
        
    }
        
    public function getBaseUrl(){
        return $this->getConfigData('vnp_UrlBase');
    }
    
    public function getSecretKey(){
        if (self::$_is_mobile){
            if (self::$_platform == "mobile_ios")
            {
                return $this->getConfigData('hash_code_mobile_ios');
            }
            else if (self::$_platform == "mobile_android")
            {
                return $this->getConfigData('hash_code_mobile_android');
            }
        }
        return $this->getConfigData('hash_code');
    }
    
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('vnpay/standard/redirect', array('_secure' => true));
    }
    
    public function setIsMobilePlatform($platform){
        if ($platform){
            self::$_is_mobile = true;
            self::$_platform = $platform;
        }
        
    }

    public function getUrlVnpay($orderid, $vnp_paytype = 'unknown') {
        Mage::log('*** vnpay - Begin set param with orderId : ' . $orderid . ' -------', null, 'vnpay.log', TRUE);
        $_order = Mage::getModel('sales/order')->loadByIncrementId($orderid);
        //$_order->sendNewOrderEmail();
        
        // Số tiền không mang các ký tự phân tách thập phân, phần nghìn, ký tự tiền tệ. 
        // Để gửi số tiền thanh toán là 10,000 VND (mười nghìn VNĐ) 
        // thì merchant cần nhân thêm 100 lần (khử phần thập phân), 
        // sau đó gửi sang VNPAY là: 1000000
        $getGrandTotal = $_order->getGrandTotal();
        $amount_total  = round($getGrandTotal);
        $vnp_Amount = $amount_total * 100;
        
        //------ END -----
        $vnp_Returnurl = null;
        $vnp_BankCode = null;
        if ($vnp_paytype == "mobile_ios"){
            self::setIsMobilePlatform($vnp_paytype);
            $vnp_Returnurl = "fahasaapp-vnpay://backapp";
            $vnp_BankCode = "MBAPP";
        } else if ($vnp_paytype == "mobile_android"){
            self::setIsMobilePlatform($vnp_paytype);
            $vnp_Returnurl = "fahasaapp-vnpay://backapp";
            $vnp_BankCode = "MBAPP";
        }else {
            $vnp_BankCode = "VNPAYQR";
            $vnp_Returnurl = Mage::getUrl('vnpay/standard/success');
        }
        
        $date = new DateTime(); //this returns the current date time
        $result = $date->format('Y-m-d-H-i-s');
        $krr = explode('-', $result);
        $result1 = implode("", $krr);
        $vnp_Url = $this->getConfigData('vnp_Url');
       
        $hashSecret = self::getConfig()->getSecretKey();
        $vnp_Locale = $this->getConfigData('vnp_Locale');
        $vnp_OrderInfo = 'Thanh toan voi ma don hang ' . $orderid;
        $vnp_OrderType = 'other';
        $vnp_CurrCode = $this->getConfigData('vnp_Currency');
//        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        $vnp_IpAddr = "https://www.fahasa.com/";
        $terminalCode=  self::getConfig()->getVnpTerminal();
        
        //param tự tạo : lưu với dạng id + time như momo
        $create_date = Mage::helper('vnpay')->getTimeStamp();
        $vnp_requestId = $create_date. $orderid;
        
        
        //vnp_txnref: transaction of fahasa pass to vnpay (each order has multiple transaction_id)
        $Odarray = array(
            "vnp_TmnCode" => $terminalCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $result1,
            "vnp_CurrCode" => $vnp_CurrCode,
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_requestId,
            "vnp_Version" => "2.0.0",
        );
        if ($vnp_BankCode){
            $Odarray["vnp_BankCode"] = $vnp_BankCode;
        }
        ksort($Odarray);
        
        \Mage::log("*** vnpay - Start create order vnpay with  order #" . $orderid . " Data Odarray: " . print_r($Odarray, true), null, "vnpay.log");
        
        // insert vo db order_vnpay
        // success : order_vnpay tao thanh cong ---- responseCode : -99 ( mac dinh )
        // fail : huy order 
        
            /// Tao URL cho chuyen huong toi VNPAY : 
            $query = "";
            $i = 0;
            $data = "";
            foreach ($Odarray as $key => $value) {
                if ($i == 1) {
                    $data .= '&' . $key . "=" . $value;
                } else {
                    $data .= $key . "=" . $value;
                    $i = 1;
                }

                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }
            $vnp_Url .= '?';
            $vnp_Url .= $query;
            if (isset($hashSecret)) {
            $vnpSecureHash = hash('sha256', $hashSecret . $data);
            $vnp_Url .= 'vnp_SecureHashType=SHA256&vnp_SecureHash=' . $vnpSecureHash;
            
            \Mage::log('*** vnpay - with  order : #' . $orderid . ' requestId : ' . $vnp_requestId . ' has create Url Payment = ' . $vnp_Url, null, 'vnpay.log', TRUE);

            try {
                // insert vo db order_vnpay
                self::insertOrderVnpay($orderid, $vnp_requestId, $vnpSecureHash, $amount_total, $Odarray , $vnp_paytype);
            } catch (Exception $ex) {
                return self::redirectFailureFromCreateOrderVnpay($_order, $ex);
            }
            \Mage::log("*** vnpay - Insert order Vnpay success #" . $orderid . "   requestId :" . $vnp_requestId . " Data Object: " . print_r($Odarray, true), null, "vnpay.log");

            \Mage::log("*** vnpay - start set state of order : #" . $orderid . "   requestId :" . $vnp_requestId , null, "vnpay.log");
            
            // cap nhat state cho order 
            $_order->setState("new", "pending_payment", 'Thanh toán qua Vnpay chuẩn bị xử lý.');
            $_order->save();
            
             \Mage::log("*** vnpay - set state of order Success : #" . $orderid . "   requestId :" . $vnp_requestId, null, "vnpay.log");
            return array(
                "redirect_url" => $vnp_Url,
                "transaction_id" => $vnp_requestId,
            );            
        } else {
            return self::redirectFailureFromCreateOrderVnpay($_order);
        }
    }
    
    public function getResponseDescription($responseCode) {

        switch ($responseCode) {
            case "00" :
                $result = "Giao dịch thành công - Approved";
                break;
            case "05" :
                $result = "Giao dịch không thành công do: Quý khách nhập sai mật khẩu thanh toán quá số lần quy định. Xin quý khách vui lòng thực hiện lại giao dịch ";
                break;
            case "06" :
                $result = "Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP). Xin quý khách vui lòng thực hiện lại giao dịch. ";
                break;
            case "07" :
                $result = "Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường). Đối với giao dịch này cần merchant xác nhận thông qua merchant admin: Từ chối/Đồng ý giao dịch ";
                break;
            case "12" :
                $result = "Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa. ";
                break;
            case "09" :
                $result = "Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng.";
                break;
            case "10" :
                $result = "Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần ";
                break;
            case "11" :
                $result = "Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch. ";
                break;
            case "24" :
                $result = "Giao dịch không thành công do: Khách hàng hủy giao dịch ";
                break;
            case "51" :
                $result = "Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch";
                break;
            case "65" :
                $result = "Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày. ";
                break;
            case "75" :
                $result = "Ngân hàng thanh toán đang bảo trì ";
                break;
            case "99" :
                $result = "Các lỗi khác (lỗi còn lại, không có trong danh sách mã lỗi đã liệt kê) ";
                break;
            default :
                $result = "Failured";
        }
        return $result;
    }

    public function transStatus($txnResponseCode) {
        $transStatus = "";
        if ($txnResponseCode == "00") {
            $transStatus = "Transaction Success";
        } else {
            $transStatus = "Transaction Fail </br>" . $this->getResponseDescription($txnResponseCode);
        }
        return $transStatus;
    }
    
    // fail or errors
    function redirectFailureFromCreateOrderVnpay($order , $result = null ) {
        //
        if(is_object($result)){
            $descriptionError = $result->getMessage() ?? "Error";
        }else { 
            $descriptionError = "Lỗi";
        }
        \Mage::log("*** vnpay - Fail to request create order #" . $order->getIncrementId() . ".Description error : ". $descriptionError .". Redirect to checkout/onepage/failure/. ", null, "vnpay.log");
        $fail_url = \Mage::getUrl("checkout/onepage/failure/");

        //do not cancel order => allow repayment
        
        $data['url'] = $fail_url;
//      $data['message'] = "Redirect failure";
        return $data['url'];
    }
    
    // insert order order vn pay
    function insertOrderVnpay($orderid,$vnp_requestId,$vnp_securehash,$getGrandTotal,$Odarray,$vnp_paytype) {
        
        $vnp_Amount = 0;
        if ($getGrandTotal) {
            $vnp_Amount = $getGrandTotal;
        }
        $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "insert into order_vnpay (order_id, vnp_txnref, vnp_responsecode, vnp_tmncode, vnp_orderinfo, vnp_securehash, vnp_amount, callback, vnp_paytype ,vnp_transdate ) "
                . "values (:order_id, :vnp_txnref, :vnp_responsecode, :vnp_tmncode, :vnp_orderinfo, :vnp_securehash, :vnp_amount, :callback, :vnp_paytype, :vnp_transdate) ";
        
        $params = array(
            "order_id" => $orderid,
            "vnp_txnref" => $vnp_requestId,
            "vnp_responsecode" => "-99",
            "vnp_tmncode" => $Odarray['vnp_TmnCode'],
            "vnp_orderinfo" => $Odarray['vnp_OrderInfo'],
            "vnp_securehash" => $vnp_securehash,
            "vnp_amount" => $vnp_Amount,
            "callback" => 0,
            "vnp_paytype" => $vnp_paytype,
            "vnp_transdate" => $Odarray['vnp_CreateDate']
        );
        \Mage::log('*** vnpay - with  order : #' . $orderid . ' requestId : ' . $vnp_requestId . ' start insert DB  table order_vnpay ***'. print_r($params, true), null, 'vnpay.log', TRUE);
        $write->query($query, $params);
         
        //insert payment log to check current payment
        Mage::helper('repayment')->addPaymentLog($orderid, $this->_code, $vnp_requestId);
    }
    
    // update status order_Vnpay
    function updateOrderVnpay($ipn, $callback = 0) {
        $vnp_ResponseCode = $ipn['vnp_ResponseCode'];
        $vnp_TransactionNo = $ipn['vnp_TransactionNo'];
        $vnp_TxnRef = $ipn['vnp_TxnRef'];
        $vnp_Description = $this->getResponseDescription($vnp_ResponseCode);
        
        $query = "update order_vnpay set vnp_responsecode=:vnp_responsecode, vnp_transactionno=:vnp_transactionno ,vnp_description=:vnp_description, callback=:callback "
                . "WHERE order_id = :order_id and vnp_txnref=:vnp_txnref;";
        
        $params = array(
            "vnp_responsecode" => $vnp_ResponseCode,
            "vnp_transactionno" => $vnp_TransactionNo,
            "vnp_description" => $vnp_Description,
            "order_id" => $ipn['order_id'],
            "vnp_txnref" => $vnp_TxnRef,
            "callback" => $callback
        );
        
        \Mage::log("*** vnpay - Updating order_vnpay order_id=" . $ipn['order_id'] . " vnp_txnref=" . $vnp_TxnRef ." ----- with data : " . print_r($params, true), null, "vnpay.log");
        
        try {
            $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->query($query, $params);
            Mage::log("*** vnpay - Update order_vnpay order_id= " . $ipn['order_id'] . " vnp_txnref=" . $vnp_TxnRef ." in db query Success ***", null, "vnpay.log");
            
            return true;
        } catch (Exception $ex) {
            Mage::log("*** vnpay - update order_vnpay failed. order_id" . $vnp_TxnRef . ", Exception=" . $ex->getMessage(), null, "vnpay.log");
            
            return false;
        }
    }
    
    // get info order_Mompay: check by request_id
    function getOrderVnpayInfo($orderId) {
        $read = \Mage::getSingleton('core/resource')->getConnection('core_read');
        $query = "select * from order_vnpay where vnp_txnref='{$orderId}';";
        try{
            $results = $read->fetchAll($query);
            if ($results[0]) {
                return $results[0];
            } else {
                \Mage::log("*** vnpay - get info order_vnpay order: ".$orderId. " is EMPTY ***", null, "vnpay.log");
                return null;
            }
        } catch ( Exception $ex){
            \Mage::log("*** vnpay - get info order_vnpay failed. Exception=" . $ex->getMessage(), null, "vnpay.log");
            return null;
        }
    }
    
    //comment cancel order for re-payment: do not update status order
    // cancelOrderReturn
    public function cancelOrderReturn($order) {
        $status = $order->getStatus();
        $helper = \Mage::helper('cancelorder');
        $increment_id = $order->getIncrementId();
        $result = $helper->thirdPartyPaymentFailRestRerturnCode($order, $increment_id);
        \Mage::log("*** Vnpay cancelOrderReturn  method: order id " . $order->getIncrementId() . " status: " . $status . "and param from thirdPartyPaymentFail (true = 1, false = ''/empty) :" . print_r($result,true), null, "vnpay.log");
        if($result['success']){
            return TRUE; 
        }else{
            return FALSE;
        }
        return TRUE; 
    }
    
    public function generateSecureHash($inputData, $hash_secret)
    {
        ksort($inputData);

        $query = "";
        $i = 0;
        $data = "";
        foreach ($inputData as $key => $value)
        {
            if ($i == 1)
            {
                $data .= '&' . $key . "=" . $value;
            }
            else
            {
                $data .= $key . "=" . $value;
                $i = 1;
            }

            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        $vnp_Url .= '?';
        $vnp_Url .= $query;
        $vnpSecureHash = hash('sha256', $hash_secret . $data);
        return $vnpSecureHash;
    }

    public function pendingOrder($order) {
        $order->setState("new", "pending_payment", 'Thanh toán qua Vnpay đang được xử lý.');
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
        \Mage::log("*** Vnpay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ")", null, "vnpay.log");
        $helper = \Mage::helper('cancelorder');
        $subject = 'Hoàn tiền cho khách đơn hàng ' . $increment_id;
        $description = 'Đơn hàng đã được thanh toán nhưng khách hủy. Hoàn tiền lại cho khách.';
        $response = $helper->createRedmineIssue(1, $subject, $description, 12);
        \Mage::log("*** Vnpay create redmine issue: order id " . $increment_id . " (state=" . $order->getState() . ", status=" . $order->getStatus() . ") - response: " . print_r($response, true), null, "vnpay.log");
    }
    
    public function execGetRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json')
        );

        //execute get
        $result = curl_exec($ch);
        if (curl_errno($ch)){
            $error_msg = curl_error($ch);
            Mage::log("ERROR curl exec " . $url . ", " . print_r($data, true) . " - message: " . print_r($error_msg, true)
                    . " - response: " . print_r($result, true), null, 'vnpay.log');
        }

        //close connection
        curl_close($ch);
        return $result;
    }
    
    // ket qua VNPAY tra ve de xu ly don hang order_vnpay va order_fahasa :
    // success : == 00 => cap nhat order 
    // fail : !== 0 => thong bao that bai 
    // => tra json cho VNPAY
    public function ipnReturn($get) {
        /// kiểm tra $get
        if (isset($get['vnp_TxnRef'])) {
            $order_id = $get['vnp_TxnRef'];
        } else {
            $returnData = array();
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
            \Mage::log("*** vnpay - FINAL - GET params is not isset ======== return json to VNPAY : rspcode: " . $returnData['RspCode'] . " Message: " . $returnData['Message'], null, "vnpay.log");
            //Trả lại VNPAY theo định dạng JSON
            echo json_encode($returnData);
            exit();
        }
        \Mage::log("*** vnpay - Start handle with  :  vnp_TxnRef: " . $order_id, null, "vnpay.log");
        $returnData = array();
        // get info vnpay get requestId;
        //$dataOrderVnPay = \Mage::getModel('vnpay/vnpay')->getOrderVnpayInfo($order_id);
        $dataOrderVnPay = $this->getOrderVnpayInfo($order_id);
        if ($dataOrderVnPay) {
            \Mage::log("*** vnpay - get Info Order_Vnpay success  with data array :". print_r($dataOrderVnPay,TRUE), null, "vnpay.log");
            $vnp_requestId = $dataOrderVnPay['vnp_txnref']; //vnp_txnref: transaction_id
            $vnp_callback = $dataOrderVnPay['callback'];
            $vnp_AmountDB = $dataOrderVnPay['vnp_amount'] ? $dataOrderVnPay['vnp_amount'] * 100 : null;
        //------ END ----- 
        } else {
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
            \Mage::log("*** vnpay - FINAL - get Info Order_Vnpay failed  with order : ". $order_id ." *** return json to VNPAY : rspcode: " . $returnData['RspCode'] . " Message: " . $returnData['Message'], null, "vnpay.log");
            //Trả lại VNPAY theo định dạng JSON
            echo json_encode($returnData);
            exit();
        }
        
        $SECURE_SECRET = null;
        $tmnCode_mobile_ios = $this->getConfigData('vnp_Terminal_Mobile_ios');
        $tmnCode_mobile_android = $this->getConfigData('vnp_Terminal_Mobile_android');
        if ($get["vnp_TmnCode"] == $tmnCode_mobile_ios)
        {
            $SECURE_SECRET = Mage::getStoreConfig('payment/vnpay/hash_code_mobile_ios');
        }
        else if ($get["vnp_TmnCode"] == $tmnCode_mobile_android)
        {
            $SECURE_SECRET = Mage::getStoreConfig('payment/vnpay/hash_code_mobile_android');
        }
        else
        {
            $SECURE_SECRET = Mage::getStoreConfig('payment/vnpay/hash_code');
        }


        $vnp_SecureHash = $get['vnp_SecureHash'];
        $hashSecret = $SECURE_SECRET;
        
        // data array GET
        $ipn = array();
        $ipn['vnp_OrderInfo'] = $get["vnp_OrderInfo"];
        $ipn['vnp_SecureHash'] = $hashSecret;
        $ipn['vnp_TmnCode'] = $get["vnp_TmnCode"];
        $ipn['vnp_ResponseCode'] = $get["vnp_ResponseCode"];
        $ipn['vnp_TransactionNo'] = $get["vnp_TransactionNo"];
        $ipn['vnp_TxnRef'] = $get["vnp_TxnRef"];
        $ipn['vnp_Amount'] =  $get["vnp_Amount"];
        $ipn['vnp_requestid'] = $vnp_requestId;
        $ipn['vnp_callback'] = $vnp_callback;
        $ipn['order_id'] = $dataOrderVnPay['order_id'];
        
        \Mage::log("*** vnpay - data Array ipn with order ". $order_id . ": ". print_r($ipn,true), null, "vnpay.log");


        //$get = $_GET;
        $data = array();
        foreach ($get as $key => $value) {
            $data[$key] = $value;
        }
        unset($data["vnp_SecureHashType"]);
        unset($data["vnp_SecureHash"]);
        ksort($data);
        $i = 0;
        $data2 = "";
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $data2 .= '&' . $key . "=" . $value;
            } else {
                $data2 .= $key . "=" . $value;
                $i = 1;
            }
        }
        
        $secureHash = hash('sha256', $hashSecret . $data2);
        $callBack = 1;
        
        
        // Check secureHash : 
        // - success : update orderVNPay --- calback = 1;
        // - Faill : return Error;
        if ($secureHash == $vnp_SecureHash) {
            \Mage::log("*** vnpay - Check secureHash Success  : ". $order_id ." and request_id : ".$vnp_requestId, null, "vnpay.log");
            // check amount : 
            // equal => countinue;
            // not equal => fail;
            if ($ipn['vnp_Amount'] != $vnp_AmountDB) {
                $returnData['RspCode'] = '04';
                $returnData['Message'] = 'Invalid amount';
                \Mage::log("*** vnpay - FINALLY - Invalid amount ---- ipn(dataRertun) = ".$ipn['vnp_Amount']." and amountDB(order_vnpay) = ".$vnp_AmountDB."  with order : " . $order_id . " and request_id : " . $vnp_requestId . " *** return json to VNPAY : rspcode: " . $returnData['RspCode'] . " Message: " . $returnData['Message'], null, "vnpay.log");
                //Trả lại VNPAY theo định dạng JSON
                echo json_encode($returnData);
                exit();
            }
            // check callback : 
            // == 0 => update order;
            // != 0 => already confirmed;
            if ($vnp_callback == 0) {
                \Mage::log("*** vnpay - Check vnp_callback == 0 Success  : ". $order_id ." and request_id : ".$vnp_requestId . "callback : " . $vnp_callback, null, "vnpay.log");
                // TH 2.1 $ipn['vnp_ResponseCode'] == 0 => handle binh thuong
                // TH 2.2 $ipn['vnp_ResponseCode'] != 0 => call CancleOrder truoc => TRUE : UpdateOrder VNpay , FALSE : khong update de cho java xu ly
                
                $orderId = $dataOrderVnPay['order_id'];
                $order = \Mage::getModel('sales/order')->loadByIncrementId($orderId);
                if ($order->getId() != null)
                {
                    $is_current_transaction = Mage::helper('repayment')->checkTransIsCurPayment($order, $vnp_requestId);
                    $is_in_repayment_time = Mage::helper('repayment')->checkOrderInPaymentTime($order);
                    Mage::log("Callback: transaction_id=". $dataOrderVnPay['vnp_txnref'] . " is_in_repayment=" . $is_in_repayment_time . ", is_current_transaction=" . $is_current_transaction, null, "vnpay.log");
                    if ($ipn['vnp_ResponseCode'] == "00")
                    {
                        if ($is_in_repayment_time)
                        {
                            if ($is_current_transaction)
                            {
                                $returnData = self::handleOrderSuccess($order, $ipn, $callBack);
                            }
                            else
                            {
                                $returnData = self::markRefundOrder($ipn, $callBack);
                            }
                        }
                        else
                        {
                            if ($is_current_transaction)
                            {
                                $returnData = self::handleOrderSuccess($order, $ipn, $callBack);
                            }
                            else
                            {
                                $returnData = self::markRefundOrder($ipn, $callBack);
                            }
                        }
                    }
                    else
                    {
                        if ($is_in_repayment_time)
                        {
                            if ($is_current_transaction)
                            {
                                //do nothing -> for repayment in 1 hour
                                $returnData = self::markOrderProccessed($ipn, $callBack);
                            }
                            else
                            {
                                //do nothing -> for repayment in 1 hour
                                $returnData = self::markOrderProccessed($ipn, $callBack);
                            }
                        }
                        else
                        {
                            if ($is_current_transaction)
                            {
                                //transaction is current trans of fahasa order
                                //cancel order -> last transaction
                                //function cancel order
                                if (Mage::helper('repayment')->checkOrderHasTransRefund($order->getIncrementId(), $vnp_requestId))
                                {
                                    $returnData = self::markOrderProccessed($ipn, $callBack);
                                }
                                else
                                {
                                    $returnData = self::handleOrderFail($order, $ipn, $callBack);
                                }
                            }
                            else
                            {
                                //transaction is not current trans of fahasa order -> old transaction
                                //do nothing
                                $returnData = self::markOrderProccessed($ipn, $callBack);
                            }
                        }
                    }
                }
                else
                {
                    $returnData['RspCode'] = "01";
                    $returnData['Message'] = "Order not found";
                }

                echo json_encode($returnData);
            } else {
                $returnData['RspCode'] = '02';
                $returnData['Message'] = 'Order already confirmed';
                $returnData['Signature'] = $secureHash;
                \Mage::log("*** vnpay - FINALLY - Already Confirmed with callbackk: ". $vnp_callback ." order : ". $order_id ." and request_id : ".$vnp_requestId."  *** return json to VNPAY : rspcode: " . $returnData['RspCode'] . " Message: " . $returnData['Message'], null, "vnpay.log");
                //Trả lại VNPAY theo định dạng JSON
                echo json_encode($returnData);
            }
        } else {
            $returnData['RspCode'] = '97';
            $returnData['Message'] = 'Invalid signature';
            $returnData['Signature'] = $secureHash;
            \Mage::log("*** vnpay - FINALLY - : Signature Failed with order : ". $order_id ." and request_id : ".$vnp_requestId." *** return json to VNPAY : rspcode: " . $returnData['RspCode'] . " Message: " . $returnData['Message'], null, "vnpay.log");
            //Trả lại VNPAY theo định dạng JSON
            echo json_encode($returnData);
        }
    }
    
    
    //done
    public function markRefundOrder($ipn, $callBack)
    {
        Mage::log("Callback: mark refund order " . $ipn['vnp_TxnRef'] , null, "vnpay.log");
        //update status of order_vnpay
        $this->updateOrderVnpay($ipn, $callBack);
        $query = "update order_vnpay set refund_code = -1000 where order_id = :order_id and vnp_txnref = :transaction_id";
        $write = \Mage::getSingleton('core/resource')->getConnection('core_write');
        $binds = array(
            "order_id" => $ipn['order_id'],
            "transaction_id" => $ipn['vnp_TxnRef']
        );
        $write->query($query, $binds);

        return array(
            'RspCode' => '00',
            'Message' => 'Confirm Success'
        );
    }
    
    
    //done
    public function markOrderProccessed($ipn, $callBack)
    {
        $result = array();
        //update status of order_vnpay
        $resultUpdateOrderVnPay = $this->updateOrderVnpay($ipn, $callBack);
        if ($resultUpdateOrderVnPay == true)
        {
            $result['RspCode'] = "00";
            $result['Message'] = "Confirm Success";
        }
        else
        {
            $result['RspCode'] = "-99";
            $result['Message'] = "Unknow error";
        }
        return $result;
    }

    
    //done
    public function handleOrderSuccess($order, $ipn, $callBack)
    {
        Mage::log("Callback: handle order success " . $ipn['vnp_TxnRef'] . ", fahasa order: state = " . $order->getState() . ", status = " . $order->getStatus(), null, "vnpay.log");
        $result = null;

        $resultUpdateOrderVnPay = $this->updateOrderVnpay($ipn, $callBack);
        if ($resultUpdateOrderVnPay == true)
        {
            try {
                if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
                {
                    $order->setState("new", "paid", 'Thanh toán qua Vnpay thành công');
                    $order->save();
                    
                    //send order email
                    Mage::dispatchEvent('payment_order_return', array('order_id'=>$order->getEntityId(), 'increment_id'=>$order->getIncrementId(), 'status'=>'success',
                        'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_PAYMENT_SUCCESS, 'cur_payment_method' => $this->_code,
                        'cur_payment_title' => $order->getPayment()->getMethodInstance()->getTitle(),
                        'customer_id' => $order->getCustomerId(), 'customer_email' => $order->getCustomerEmail()));
                }
                else if ($order->getState() == "canceled" && $order->getStatus() == "canceled")
                {
                    self::addStatusHistoryComment($order, "Thanh toán qua Vnpay thành công");
                    self::createRedmineIssue($order);
                }
                else
                {
                    self::addStatusHistoryComment($order, "Thanh toán qua Vnpay thành công");
                }

                $result['RspCode'] = "00";
                $result['Message'] = "Confirm Success";
            } catch (Exception $ex) {
                $result['RspCode'] = "-99";
                $result['Message'] = "Unknow error";
            }
        }
        else
        {
            $result['RspCode'] = '99';
            $result['Message'] = 'Unknow error';
        }
        return $result;
    }

    //done
    public function handleOrderFail($order, $ipn, $callback)
    {
        Mage::log("Callback: handle order fail " . $ipn['vnp_TxnRef'] . ", fahasa order: state = " . $order->getState() . ", status = " . $order->getStatus(), null, "vnpay.log");
        $result = null;
        try {
            if ($order->getState() == "new" && $order->getStatus() == "pending_payment")
            {
                $cancel_result = Mage::helper('cancelorder')->cancelOrderReturn($order, "vnpay.log");
                if ($cancel_result)
                {
                    //Step 1. update order_payment (order_zalopay, order_momopay...)
                    $this->updateOrderVnpay($ipn, $callback);
                    $result['RspCode'] = "00";
                    $result['Message'] = "Confirm Success";
                    Mage::dispatchEvent('payment_order_return', array('order_id' => $order->getEntityId(), 'increment_id' => $order->getIncrementId(), 'status' => 'success', 
                        'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_PAYMENT_FAIL, 'cur_payment_method' => $this->_code,
                        'cur_payment_title' => $order->getPayment()->getMethodInstance()->getTitle(),
                        'customer_id' => $order->getCustomerId(), 'customer_email' => $order->getCustomerEmail()));
                }
                else
                {
                    //only add log history status + not update payment order -> for the next java process will check status
                    self::addStatusHistoryComment($order, "Gọi hủy đơn toán qua Vnpay thất bại");
                    $result['RspCode'] = '99';
                    $result['Message'] = 'Unknow error';
                }
            }
            else if ($order->getState() == "canceled" && $order->getStatus() == "canceled")
            {
                //no need call canceled because order was canceled before
                //add log history + update payment_order
                self::addStatusHistoryComment($order, "Thanh toán qua Vnpay thất bại");
                $this->updateOrderVnpay($ipn, $callback);

                $result['RspCode'] = "00";
                $result['Message'] = "Confirm Success";
            }
            else
            {
                //add log history + update payment_order
                self::addStatusHistoryComment($order, "Thanh toán qua Vnpay thất bại");
                $this->updateOrderVnpay($ipn, $callback);
                $result['RspCode'] = "00";
                $result['Message'] = "Confirm Success";
            }
        } catch (Exception $ex) {
            $result['RspCode'] = '99';
            $result['Message'] = 'Unknow error';
        }

        return $result;
    }

    //order_id: is request_id
    public function checkVnpayOrderStatusForMobile($order_id){
         $dataOrderVnPay = $this->getOrderVnpayInfo($order_id);
         $success = false;
         $is_processed = false;
         $is_paid = false;
         $message = null;
         $status = -1000;
        if ($dataOrderVnPay)
        {
            $success = true;
            $status = $dataOrderVnPay['vnp_responsecode'];
            if ($dataOrderVnPay["callback"] == 1)
            {
                
                $is_processed = true;
                if ($status == "00")
                {
                    $is_paid = true;
                }
            }
        } else{
            $message = "ORDER_NOT_FOUND";
        }
        
        return array(
            "success" => $success,
            "is_processed" => $is_processed,
            "status" => $status,
            "message" => $message
        );
    }
}
