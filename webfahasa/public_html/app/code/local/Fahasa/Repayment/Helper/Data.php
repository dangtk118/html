<?php 
class Fahasa_Repayment_Helper_Data_Error {


}

class Fahasa_Repayment_Helper_Data extends Mage_Core_Helper_Abstract {
    
    const NON_GATEWAY_METHODS = array(
        "banktransfer",
        "cashondelivery"
    );
    
    const ZALOPAY_CODES = array("zalopayapp", "zalopayatm", "zalopay", "zalopaycc");
    
    public function getOrderIdPaymentPending($customer_id){
	try{
	    $timeout = Mage::getStoreConfig('repayment_config/config/timeout');
	    if(!empty($timeout)){
		$timeout_datetime = date('Y-m-d H:i:s', strtotime("+7 hours "."-".$timeout." seconds"));
	    }
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select entity_id, increment_id from fhs_sales_flat_order where status = 'pending_payment' and customer_id = ".$customer_id. (!empty($timeout_datetime)?' and convert_tz(created_at, \'+0:00\', \'+7:00\') > \''.$timeout_datetime.'\'':'')." ;";
	    return $reader->fetchAll($sql);
	} catch (Exception $ex) {}
	return null;
    }
    
    public function getOrderDetail($orderId, $isCheckRepayment = false, $is_mobile = false, $turn_on_hidden = false){
        $orderItem = array();
        $orderDetail = \Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $customer = \Mage::getSingleton('customer/session')->getCustomer();
         
        if (!empty($orderDetail) && !empty($customer->getId()) && $customer->getId() == $orderDetail->getCustomerId()) {
            $items = $orderDetail->getAllVisibleItems();
            $orderItem['status'] = $orderDetail->getStatus();
            $orderItem['createdAt'] = date("Y-m-d H:i:s", strtotime('+7 hours', strtotime($orderDetail->getCreatedAt())));

            $payment = $orderDetail->getPayment();
            $orderItem['paymentMethod'] = $payment->getMethod();

            $orderItem['shippingMethod'] = $orderDetail->getShippingMethod();
            $orderItem['shippingDescription'] = $orderDetail->getShippingDescription();

            $orderItem['orderId'] = $orderId;
            $orderItem['orderColor'] = $this->getColorOfOrder($orderDetail->getStatus());

            // create info for VAT and note 
            $fieldsmn = \Mage::getModel('fieldsmanager/fieldsmanager');
            $_Write = \Mage::getSingleton('core/resource')->getConnection('core_read');
            $modelOrder = $orderDetail->getData();

            $getEntityId = $modelOrder['entity_id'];
            $vatData = $fieldsmn->GetFMDb($getEntityId, 'orders', $_Write);

            foreach ($vatData as $key) {
                if ($key['attribute_id'] == 172)
                    $orderItem['note'] = json_decode($key['value']);
                if ($key['attribute_id'] == 147)
                    $orderItem['VAT']['company'] = json_decode($key['value']);
                if ($key['attribute_id'] == 148)
                    $orderItem['VAT']['address'] = json_decode($key['value']);
                if ($key['attribute_id'] == 149)
                    $orderItem['VAT']['taxcode'] = json_decode($key['value']);
                if ($key['attribute_id'] == 219)
                    $orderItem['VAT']['name'] = json_decode($key['value']);
                if ($key['attribute_id'] == 220)
                    $orderItem['VAT']['email'] = json_decode($key['value']);
            }

            $billingAddress = $orderDetail->getBillingAddress();
            $orderItem['billingAddress']['firstName'] = $billingAddress->getFirstname();
            $orderItem['billingAddress']['lastName'] = $billingAddress->getLastname();
            $orderItem['billingAddress']['telephone'] = $billingAddress->getTelephone();
            $orderItem['billingAddress']['street'] = $billingAddress->getStreet()[0];
            $orderItem['billingAddress']['city'] = $billingAddress->getCity();
            $orderItem['billingAddress']['ward'] = $billingAddress->getWard();

            // get region_id if not vietnam
            if ($billingAddress->getRegion() == null) {
                $region = $billingAddress->getRegionId();
            } else {
                $region = $billingAddress->getRegion();
            }
            $orderItem['billingAddress']['region'] = $region;

            $orderItem['billingAddress']['countryId'] = $billingAddress->getCountryId();

            $shippingAddress = $orderDetail->getShippingAddress();
            $orderItem['shippingAddress']['firstName'] = $shippingAddress->getFirstname();
            $orderItem['shippingAddress']['lastName'] = $shippingAddress->getLastname();
            $orderItem['shippingAddress']['telephone'] = $shippingAddress->getTelephone();
            $orderItem['shippingAddress']['street'] = $shippingAddress->getStreet()[0];
            $orderItem['shippingAddress']['city'] = $shippingAddress->getCity();
            $orderItem['shippingAddress']['ward'] = $shippingAddress->getWard();

            if ($shippingAddress->getRegion() == null) {
                $regionsp = $shippingAddress->getRegionId();
            } else {
                $regionsp = $shippingAddress->getRegion();
            }
            $orderItem['shippingAddress']['region'] = $regionsp;

            $orderItem['shippingAddress']['countryId'] = $shippingAddress->getCountryId();
            $has_expected_date = [];
            $countItemsOfProducts = 0;
            
            // total = 0 VND :
            $dataHidden = null;
            $hideTotal = false;
            if($turn_on_hidden && $is_mobile){
                // handle check hidden price and fee :
                $helperSaleData = \Mage::helper("sales");
                $dataHidden = $helperSaleData->getOrdersOptionRule($orderId);
                if(!empty($dataHidden[$orderId])){
                  $hideTotal = $dataHidden[$orderId]['hide_total'];
                }
            }

            foreach ($items as $item) {
                $countItemsOfProducts += (int) $item->getQtyOrdered();
                $product = $item->getProduct();
                $expectedDateMsg = '';

                if ($orderItem['status'] != "complete" && $orderItem['status'] != "canceled") {
                    if ($product->getSoonRelease() == 1) {
                        // expectedDateMsg product for mobile 
                        if ($is_mobile) {
                            $msgArr = \Mage::helper('fahasa_catalog/product')->getProductExpectedMsg($product);
                            $arrayMsgProduct = array(
                                "0" => $msgArr[0],
                                "1" => $msgArr[1]
                            );
                            $expectedDateMsg = $arrayMsgProduct;
                        } else {
                            $msg = Mage::helper('fahasa_catalog/product')->getProductExpectedMsg($product)[0];
                            $expectedDateMsg = $msg;
                        }

                        if (empty($has_expected_date['expected_date'])) {
			    if (!empty($product->getExpectedDate()) && time() <= strtotime($product->getExpectedDate())) {
				$has_expected_date['expected_date'] = $product->getExpectedDate();
			    }
			} else {
			    if (!empty($product->getExpectedDate()) 
				&& strtotime($has_expected_date['expected_date']) < strtotime($product->getExpectedDate())
				&& time() <= strtotime($product->getExpectedDate())) {
				$has_expected_date['expected_date'] = $product->getExpectedDate();
			    }
			}

			if (empty($has_expected_date['book_release_date'])) {
			    if (!empty($product->getBookReleaseDate()) && time() <= strtotime($product->getBookReleaseDate())) {
				$has_expected_date['book_release_date'] = $product->getBookReleaseDate();
			    }
			} else {
			    if (!empty($product->getBookReleaseDate()) 
				&& strtotime($has_expected_date['book_release_date']) < strtotime($product->getBookReleaseDate())
				&& time() <= strtotime($product->getBookReleaseDate())) {
				$has_expected_date['book_release_date'] = $product->getBookReleaseDate();
			    }
			}

                        $has_expected_date['has_value'] = true;
                    }
                }
                $obj = (object) [
                            'productId' => (int) $product->getEntityId(),
                            'quantity' => (int) $item->getQtyOrdered(),
                            'name' => $item->getProduct()->getName(),
                            'price' => $hideTotal ? 0 : $item->getPriceInclTax(),
                            'originalPrice' => $hideTotal ? 0 : $product->getPrice(),
                            'image' => \Mage::helper('catalog/image')->init($product, 'image')->resize(265)->__toString(),
                            'quoteItemId' => $item->getId(),
                            'expectedDate' => $product->getExpectedDate() ? date("d/m/Y", strtotime($product->getExpectedDate())) : '',
                            'bookReleaseDate' => $product->getBookReleaseDate() ? date("d/m/Y", strtotime($product->getBookReleaseDate())) : '',
                            'soonRelease' => $product->getSoonRelease(),
                            'expectedDateMsg' => $expectedDateMsg,
                ];

                if ($product->type_id == 'bundle') {
                    $options = $item->getChildrenItems();
                    $bundleOptions = array();
                    foreach ($options as $option) {
                        $bundleOptions[] = array(
                            'productId' => (int) $option->getProductId(),
                            'quantity' => (int) $option->getQtyOrdered(),
                            'name' => $option->getName(),
                            'price' => $option->getBasePriceInclTax()
                        );
                    }
                    $obj->options = $bundleOptions;
                }
                $orderItem['countItemsProduct'] = $countItemsOfProducts;
                $orderItem['products'][] = $obj;
            }
            if (!empty($has_expected_date['has_value'])) {
                $msg = "*" . \Mage::helper('fahasa_customer')->__('Your order has product(s) be coming in stock');
                if (!empty($has_expected_date['expected_date']) || !empty($has_expected_date['book_release_date'])) {
                    $msg .= ". " . \Mage::helper('fahasa_catalog/product')->getProductExpectedMsg(null, 1, $has_expected_date['expected_date'], $has_expected_date['book_release_date'])[0];
                }
                // expectedDateMsg for mobile 
                if($is_mobile){
                    $msgEn = "";
                    $msgEn= \Mage::helper('fahasa_catalog/product')->getProductExpectedMsg(null, 1, $has_expected_date['expected_date'], $has_expected_date['book_release_date'])[1];
                    $arrayExpectedDateMsg = array(
                        "0" => $msg,
//                        "1" => "Your order has product(s) be coming in stock. ". $msgEn
                    );
                    $orderItem['expectedDateMsg'] = $arrayExpectedDateMsg;
                }else{
                    $orderItem['expectedDateMsg'] = $msg;
                }
                
		$msg = "*".\Mage::helper('fahasa_customer')->__("Soon release");
                if (!empty($has_expected_date['expected_date'])) {
                    $msg .= ". " . \Mage::helper('fahasa_customer')->__("The expected date %s.", date('d/m/Y', strtotime($has_expected_date['expected_date'])));
                }
                // expectedDateSoMsg for mobile 
                if ($is_mobile) {
                    $soMsgEn = "";
                    $soMsgEn = "The expected date" . date('d/m/Y', strtotime($has_expected_date['expected_date']));
                    $arrayExpectedDateSoMsg = array(
                        "0" => $msg,
                        "1" => "Soon release". $soMsgEn
                    );
                    $orderItem['expectedDateSoMsg'] = $arrayExpectedDateSoMsg;
                } else {
                    $orderItem['expectedDateSoMsg'] = $msg;
                }
            }
            if ($hideTotal) {
                $orderItem['total']['subtotal'] = 0;
                $orderItem['total']['shippingAmount'] = 0;
                $orderItem['total']['discountDescription'] = 0;
                $orderItem['total']['discountAmount'] = 0;
                $orderItem['total']['codfeeAmount'] = 0;
                $orderItem['total']['giftwrapAmount'] = 0;
                $orderItem['total']['tryoutDiscount'] = 0;
                $orderItem['total']['isFreeship'] = 0;
                $orderItem['total']['freeshipDiscount'] = 0;
                $orderItem['total']['grandTotal'] = "0";
            } else {
                $orderItem['total']['subtotal'] = $orderDetail->getSubtotalInclTax();
                $orderItem['total']['shippingAmount'] = $orderDetail->getOriginalShippingFee() ? $orderDetail->getOriginalShippingFee() : $orderDetail->getShippingInclTax();
                $orderItem['total']['discountDescription'] = $orderDetail->getDiscountDescription();
                $orderItem['total']['discountAmount'] = $orderDetail->getDiscountAmount();
                $orderItem['total']['codfeeAmount'] = $orderDetail->getcodfee();
                $orderItem['total']['giftwrapAmount'] = $orderDetail->getOnestepcheckoutGiftwrapAmount();
                $orderItem['total']['tryoutDiscount'] = $orderDetail->getTryoutDiscount();
                $orderItem['total']['isFreeship'] = $orderDetail->getIsFreeship();
                $orderItem['total']['freeshipDiscount'] = $orderDetail->getFreeshipAmount() > 0 ? 0 : $orderDetail->getFreeshipAmount();
                $orderItem['total']['grandTotal'] = $orderDetail->getGrandTotal();
            }
                $orderItem['bookstoreId'] = $orderDetail->getPickupLocation();

            // handle statusBar orderDetail :
            $orderItem['statusBar'] = [];
            $statusArray = ["pending", "pending_payment", "customer_confirmed", "paid"];
            $stepNumber = 3; // co 3 trang thai;
            $stepNameIcon = array(
                1 => "donhangmoi-created",
                2 => "dangxuly-processing",
                3 => "hoantat/huy-complete/canceled",
            );
            $dataStatusText = array();

            $helperData = \Mage::helper('sales/data')->getOrderLogOrderInfo($orderDetail) ?? null;

            switch ($orderDetail->getStatus()) {
                case "complete":
                    $stepNameIcon[3] = "hoantat-complete";
                    $dataStatusArr["color"] = "green";
                    $dataStatusArr["colorId"] = "#29a72a";
                    $dataStatusArr["step"] = 3;
                    $dataStatusArr["stepNumber"] = $stepNumber;
                    $orderItem['statusBar'] = $this->getDataStatusBar($stepNameIcon, $dataStatusArr, $helperData);
                    break;
                case "canceled":
                    $stepNameIcon[3] = "huy-canceled";
                    $dataStatusArr["color"] = "red";
                    $dataStatusArr["colorId"] = "#fa0001";
                    $dataStatusArr["step"] = 3;
                    $dataStatusArr["stepNumber"] = $stepNumber;
                    $orderItem['statusBar'] = $this->getDataStatusBar($stepNameIcon, $dataStatusArr, $helperData);
                    break;
                case "processing":
                    $dataStatusArr["color"] = "blue";
                    $dataStatusArr["colorId"] = "#2F80ED";
                    $dataStatusArr["step"] = 2;
                    $dataStatusArr["stepNumber"] = $stepNumber;
                    $orderItem['statusBar'] = $this->getDataStatusBar($stepNameIcon, $dataStatusArr, $helperData);
                    break;
                case "pending":
                case "pre_pending":
                case "pending_payment":
                case "customer_confirmed":
                case "paid":
                    $dataStatusArr["color"] = "orange";
                    $dataStatusArr["colorId"] = "#f7941f";
                    $dataStatusArr["step"] = 1;
                    $dataStatusArr["stepNumber"] = $stepNumber;
                    $orderItem['statusBar'] = $this->getDataStatusBar($stepNameIcon, $dataStatusArr, $helperData);
                    break;
                default :
                    $orderItem['statusBar'] = null;
                    break;
            }
            
            $payment_status = null;
            $data_trans_history = $this->getDataRepaymentMethodsLog($orderId);
            $orderItem['payment_status'] = $data_trans_history['payment_status'];
            $orderItem['historyPaymentMethod'] = $data_trans_history['trans_history'];
            if ($isCheckRepayment) {
                //check can repayment
                $orderItem['checkRePayment'] = $this->checkOrderInRePaymentTime($orderDetail);
                $orderItem['eventCartLog'] = array();
                $orderItem['methodsRePayment'] = array();
                $orderItem['timeOut'] = 0;
                //check can repayment
                if ($orderItem['checkRePayment']['canRepayment']) {
                    /// get eventCartLog : 
                    $dataEventCartLog = $this->getEventcartLog($orderId) ?? null;
                    if ($dataEventCartLog) {
                        $orderItem['eventCartLog'] = $dataEventCartLog;
                    } else {
                        $orderItem['eventCartLog'] = array();
                    }
                    $dateMethodsRePayment = $this->getPaymentMethodByOrderId($orderId, $is_mobile);
                    $orderItem['methodsRePayment'] = $dateMethodsRePayment;
                    $orderItem['timeOut'] = $this->getCountDownTimer($orderId) ?? 0;
                }
            }
            
            
            if($turn_on_hidden && $is_mobile && !empty($dataHidden)){
                // handle check hidden price and fee :
                //$helperSaleData = \Mage::helper("sales");
                //$dataHidden = $helperSaleData->getOrdersOptionRule($orderId);
                if(!empty($dataHidden[$orderId])){
                  $orderItem['hide_total'] = $dataHidden[$orderId]['hide_total'];
                  $orderItem['hide_shipping_fee'] = $dataHidden[$orderId]['hide_shipping_fee'];
                  $orderItem['hide_link_product'] = $dataHidden[$orderId]['hide_total'];
                  unset($dataHidden[$orderId]['hide_shipping_fee']);
                  unset($dataHidden[$orderId]['hide_total']);
                  $orderItem['subOrderCheck']['data'] = $dataHidden[$orderId];
                  $orderItem['subOrderCheck']['colorReplace'] = '{"color" : "green","number1" :"#B6F1B6","number2" : "#2ED62E"}';
                  
                }
            }
        }


        return $orderItem;
    }
    
    public function checkOrderCanceledByPaymentFailure($order_id)
    {
        $query = "select action, timestamp from fahasa_bookstore_log where order_id = :order_id and action like '%cancel%' order by id desc limit 1 ";
        $read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $binds = array(
            'order_id' => $order_id
        );
        $rs = $read->fetchAll($query, $binds);
        if (count($rs) > 0)
        {
            if ($rs[0]['action'] == 'cancelOrderDueToPaymentFailure')
            {
                return array(
                    'payment_failure' => true,
                    'timestamp' => $rs[0]['timestamp']
                );
            }
            else
            {
                return array(
                    'payment_failure' => false,
                    'timestamp' => $rs[0]['timestamp']
                );
            }
        }
        
        //order is not canceled
        return array(
            'payment_failure' => false,
        );
    }

    public function getCountDownTimer($orderId) {
        $timeOut = 0;
        try {
            $orderDetail = \Mage::getModel('sales/order')->loadByIncrementId($orderId);
            $timeout = \Mage::getStoreConfig('repayment_config/config/timeout');
            if (!empty($timeout) && $orderDetail->getStatus() === 'pending_payment') {
                $timeout_datetime = date('Y-m-d H:i:s', strtotime("+7 hours " . "-" . $timeout . " seconds"));
                $created_at = date('Y-m-d H:i:s', strtotime('+7 hours', strtotime($orderDetail->getCreatedAt())));
                $timeOut = strtotime($created_at) - strtotime($timeout_datetime);
            }
        } catch (Exception $exc) {
            //return $timeOut;
        }
        return $timeOut;
    }

    public function getActiveMethodCodes()
    {
        $result = array();
        $payments = \Mage::getSingleton('payment/config')->getActiveMethods();
        foreach ($payments as $paymentCode => $paymentModel)
        {
            if ($paymentModel->canUseCheckout())
            {
                $result[] = $paymentCode;
            }
        }
        return $result;
    }

    public function rePaymentOrder($order_id, $payment_method, $is_mobile = false, $platform)
    {
	
        $success = false;
        $redirect_url = null;
        $message = null;
        $payment_result = null;
        $transaction_id = null;
        $request_id = null;
        $shipping = null;
        
        $lastOrderId = \Mage::getSingleton('checkout/session')->getLastRealOrderId();
	if(!empty($lastOrderId)){
	    \Mage::getSingleton('checkout/session')->setLastOrderIdForPayment($lastOrderId);
	}
	$order_id = strval($order_id);
        $order = Mage::getModel("sales/order")->loadByIncrementId($order_id);
        $amount = 0;
        $check_valid = $this->checkOrderValidToRepayment($order);
        if ($check_valid['success'])
        {

            $amount = round($order->getGrandTotal());
            $payments_codes = $this->getActiveMethodCodes();
            if (in_array($payment_method, $payments_codes))
            {
                //???? check success = true when get url payment gateway succees or fail
                $session = Mage::getSingleton('checkout/session');
                $session->setLastQuoteId($order->getQuoteId());
                $session->setLastOrderId($order->getId());
                $session->setLastOrderIdForPayment($order->getIncrementId());
                $session->setLastSuccessQuoteId($order->getQuoteId());
                $session->setLastRealOrderId($order->getIncrementId());
                
                $success = true;

                if (in_array($payment_method, self::ZALOPAY_CODES))
                {
                    $zaloPay = new Fahasa_Zalopay_Model_Payment();
                    Fahasa_Zalopay_Model_Payment::$mobileChannel = true;
                    Fahasa_Zalopay_Model_Payment::$realOrderId = $order->getIncrementId();
                    $payment_result = $zaloPay->createZalopayOrder($payment_method);
                    $redirect_url = $payment_result['paymentUrl'] ?? $payment_result['url'];
                    $transaction_id = $payment_result['transaction_id'];
                    Fahasa_Zalopay_Model_Payment::$mobileChannel = false;
                }
                else if ($payment_method === "momopay")
                {
                    $momoPay = new TTS_Momopay_Model_Momopay();
                    if ($is_mobile)
                    {
                        //??????????
                        \Mage::log("*** BEGIN - momopay create order (Mobile) with orderFHS = " . $order->getIncrementId(), null, "payment.log");
                        $request_id = $momoPay->createMomoOrder($order);
                        $transaction_id = $request_id;
                    }
                    else
                    {
                        \Mage::log("*** BEGIN - momopay create order (Website) with orderFHS = " . $order->getIncrementId(), null, "payment.log");
                        $payment_result = $momoPay->getUrlMomopay($order->getIncrementId());
                        $redirect_url = $payment_result['payUrl'] ?? $payment_result['url'];
                        $transaction_id = $payment_result['transaction_id'];
                    }
                }
                else if ($payment_method === "vnpay")
                {
                    $vnPay = new TTS_Vnpay_Model_Vnpay();
                    if ($is_mobile)
                    {
                        $paymentResultMobile = $vnPay->getUrlVnpay($order->getIncrementId(), $platform);
                        $redirect_url = $paymentResultMobile['redirect_url'];
                        $transaction_id = $paymentResultMobile['transaction_id'];
                        $request_id = $transaction_id;
                    }
                    else
                    {
                        //???? check url return data
                        $payment_result = $vnPay->getUrlVnpay($order->getIncrementId(), "website");
                        $redirect_url = $payment_result['redirect_url'];
                        $transaction_id = $payment_result['transaction_id'];
                    }
                }
                else if ($payment_method === "airpay")
                {
                    $airPay = new TTS_Airpay_Model_Airpay();
                    if ($is_mobile)
                    {
			\Mage::log("*** BEGIN - airpay create order (Mobile) with orderFHS = " . $order->getIncrementId() , null, "payment.log");
			$payment_result = $airPay->getUrlAirpay($order->getIncrementId(), 'app');
                        $redirect_url = $payment_result['redirect_url'];
                        $transaction_id = $payment_result['transaction_id'];
                    }
                    else
                    {
                        \Mage::log("*** BEGIN - airpay create order (Website) with orderFHS = " . $order->getIncrementId(), null, "payment.log");
                        $paymentResult = $airPay->getUrlAirpay($order->getIncrementId());
                        $redirect_url = $paymentResult['redirect_url'];
                        $transaction_id = $paymentResult['transaction_id'];
                    }
                }
                else if ($payment_method == 'mocapay')
                {
                    $mocaPay = new TTS_Mocapay_Model_Mocapay();
                    $payment_result = $mocaPay->getUrlMocapay($order->getIncrementId(), $platform);
                    $redirect_url = $payment_result['redirect_url'];
                    $transaction_id = $payment_result['transaction_id'];
                }
                else
                {            
                    $redirect_url = Mage::getUrl('checkout/onepage/success');
                }

                //transaction_id for payment gateway
                //banktranfer + cashondelivery: transaction_id = null;
                $this->setPaymentMethodForOrder($order, $payment_method, $transaction_id);
            }
            else
            {
                $message = "INVALID_PAYMENT_METHOD";
            }
            
            if (empty($order->getCustomerId())) {
                $shipping_address = $order->getShippingAddress();
                $shipping = array(
                    "firstName" => $shipping_address->getFirstname(),
                    "lastName" => $shipping_address->getLastname(),
                    "country_id" => $shipping_address->getCountryId(),
                    "region" => $shipping_address->getRegion(),
                    "city" => $shipping_address->getCity(),
                    "ward" => $shipping_address->getWard(),
                    "street" => $shipping_address->getStreet()[0],
                    "telephone" => $shipping_address->getTelephone(),
                );
            }
        }
        else
        {
            //order is not enough condition to repayment
            $success = false;
            $message = $check_valid['message'];
        }

     
        
        //request_id = transaction_id: used for return mobile to identify current transaction
        return array(
            "success" => $success,
            "paymentMethod" => $payment_method,
            "redirectUrl" => $redirect_url,
            "redirect_url" => $redirect_url,
            "websocket" => $payment_result,
            "message" => $message,
            "requestId" => $request_id,
            //add more for mobile
            "amount" => $amount,
            "orderId" => $order_id,
            "shipping" => $shipping
        );
    }

 
   public function addPaymentLog($order_id, $payment_method, $transaction_id)
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $update = "update fhs_order_payment_log set is_current = 0 where order_id = :order_id ";
        $bind_update = array(
            'order_id' => $order_id
        );
        
        //log payment method for tracking
        $insert = "insert fhs_order_payment_log (order_id, payment_method, transaction_id, created_at, is_current) values "
                . "(:order_id, :payment_method, :transaction_id, now(), 1) ";
        $bind_log = array(
            'order_id' => $order_id,
            'payment_method' => $payment_method,
            'transaction_id' => $transaction_id,
        );
        $write->query($update, $bind_update);
        $write->query($insert, $bind_log);
    }

    public function setPaymentMethodForOrder($order, $payment_method, $transaction_id)
    {
        try {
            if (in_array($payment_method, self::NON_GATEWAY_METHODS))
            {
                //set status order: new - pending
                $order->setState("new", "pre_pending", 'Chuyển hình thức thanh toán sang ' . $payment_method);
                $order->save();
//                Mage::dispatchEvent('payment_order_return', array('order_id' => $order->getEntityId(), 'increment_id' => $order->getIncrementId(), 'status' => 'success', 
//                    'type_payment' => Magestore_Onestepcheckout_Model_Email::TYPE_NO_GATEWAY, 'cur_payment_method' => $payment_method
//                        ));
            }
            else
            {
                //in flow: order is new - pending_payment
                //set status order: new - pending
                $order->setState("new", "pending_payment", 'Chuyển hình thức thanh toán sang ' . $payment_method);
                $order->save();
            }
            //update payment_method for order
            $query = "update fhs_sales_flat_order_payment set method = :method where parent_id = :order_id";
            $bind = array(
                "method" => $payment_method,
                "order_id" => $order->getId()
            );

            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->query($query, $bind);

            if (in_array($payment_method, self::NON_GATEWAY_METHODS))
            {
                $this->addPaymentLog($order->getIncrementId(), $payment_method, $transaction_id);
            }
        } catch (Exception $ex) {
            Mage::log("Exception set payment order log " . $order->getIncrementId() . ", payment = " . $payment_method . ", transaction_id " . $transaction_id . " - " . $ex, null, "repayment.log");
        }
    }
    
    public function checkOrderWasProcessedFpoint($order_id){
        $query = "select * from fhs_flashsale_queue where order_id = :order_id";
        $connection_read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $select_params = array(
            "order_id" => $order_id,
        );
        try {
            $rs = $connection_read->fetchAll($query, $select_params);
            if (count($rs) > 0){
                $is_fpoint_processed = $rs[0]['fpoint_processed'];
                if ($is_fpoint_processed == "1"){
                    return true;
                }
            }
        }catch(Exception $ex){
            
        }
        return false;
        
    }

    public function checkOrderInRePaymentTime($orderDetail,$resetlastOrder = true) {
        $orderItem = array();
        $isLogin = \Mage::getSingleton('customer/session')->isLoggedIn();
        if ($isLogin) {
            $customer = \Mage::getSingleton('customer/session')->getCustomer();
        }
//        $orderDetail = \Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $lastOrderId = \Mage::getSingleton('checkout/session')->getLastOrderIdForPayment();
        if($resetlastOrder === true){
            \Mage::getSingleton('checkout/session')->setLastOrderIdForPayment(null);
        }
        $orderItem['canRepayment'] = false;
        $orderItem['isOwnerOrder'] = false;
        $orderItem['canReorder'] = false;
        
        //check order has been processed fpoint yet
        
        
        if (!empty($orderDetail) && $this->checkOrderWasProcessedFpoint($orderDetail->getId())) {
            $orderItem['orderEntityId'] = $orderDetail->getId();
            
            $timeout = \Mage::getStoreConfig('repayment_config/config/timeout');
            if (!empty($timeout)) {
                $timeout_datetime = date('Y-m-d H:i:s', strtotime("+7 hours " . "-" . $timeout . " seconds"));
            }
            
            $timeout_cod = Mage::getStoreConfig('repayment_config/config/timeout_cod');
             if (!empty($timeout_cod)) {
                $timeout_cod_datetime = date('Y-m-d H:i:s', strtotime("+7 hours " . "-" . $timeout . " seconds"));
            }
            
            $created_at = date('Y-m-d H:i:s', strtotime('+7 hours', strtotime($orderDetail->getCreatedAt())));

            $payment_method = $orderDetail->getPayment()->getMethod();
            
            if ((!in_array($payment_method, self::NON_GATEWAY_METHODS) && strtotime($created_at) > strtotime($timeout_datetime) && ($orderDetail->getStatus() == 'pending_payment')) 
                    || (in_array($payment_method, self::NON_GATEWAY_METHODS) && strtotime($created_at) > strtotime($timeout_cod_datetime) && $orderDetail->getStatus() == "pre_pending"))
            {
                if ($isLogin) {
                    if ($orderDetail->getCustomerId() == $customer->getEntityId()) {
                        $orderItem['canRepayment'] = true;
                    }
                } else {
                    if (!empty($lastOrderId)) {
                        if ($orderDetail->getIncrementId() == $lastOrderId) {
                            $orderItem['canRepayment'] = true;
                        }
                    }
                }
            } else {
                if ($isLogin) {
                    if ($orderDetail->getCustomerId() == $customer->getEntityId()) {
                        $orderItem['isOwnerOrder'] = true;
                    }
                    $orderItem['canReorder'] = \Mage::helper('sales/reorder')->canReorder($orderDetail);
                } else {
                    if (!empty($lastOrderId)) {
                        if ($orderDetail->getIncrementId() == $lastOrderId) {
                            $orderItem['isOwnerOrder'] = true;
                        }
                    }
                }
            }
        }

        return $orderItem;
    }
    
    public function getRepaymentOrderLog($order_id, $is_canceld_by_customer, $canceled_payment_fail)
    {
        $dataStatusColor = array('refund_processing' => '{"color":"blue","number1":"#E0ECFD","number2":"#2F80ED"}'
            , 'refund_success' => '{"color":"green","number1":"#B6F1B6","number2":"#2ED62E"}'
            , 'refund_success_1' => '{"color":"orange","number1":"#FCDAB0","number2":"#F7941E"}'
            , 'refund_failed' => '{"color":"red","number1":"#F3B4AF","number2":"#A90000"}'
            , 'processing' => '{"color":"blue","number1" : "#E0ECFD","number2":"#2F80ED"}'
            , 'success' => '{"color" : "green","number1" :"#B6F1B6","number2" : "#2ED62E"}'
            , 'failed' => '{"color":"red","number1" :"#F3B4AF","number2" :"#A90000"}'
        );
        
        $result = array();
        $rs = array();
        $query = 'select pl.id, o.increment_id, pl.payment_method, pl.created_at as trans_created_at, pl.transaction_id, '
                . 'case when oz.id is not null then if(oz.status = 1, "success", if(oz.status > 1 or oz.status = -1000, "processing", "failed")) '
                . 'when om.id is not null then  if(om.status = 0, "success", if(om.status = -1000 or om.status = 7, "processing", "failed")) '
                . 'when ov.vnp_id is not null then if(ov.vnp_responsecode = "00" , "success", if(ov.vnp_responsecode  = "-99", "processing", "failed")) '
                . 'when oa.id is not null then if(oa.status = 200, "success", if(oa.status = -1000, "processing", "failed")) '
                . 'when mc.id is not null then if(mc.status = "success", "success", if(mc.status = "-1000" or mc.status = "processing", "processing", "failed")) '
                . 'else "" end as payment_status, '
                . 'case when oz.id is not null then if(oz.refund_code is null, "no_refund", '
                . 'if(oz.refund_code = -1000, "refund_processing", if(oz.refund_code = 1, "refund_success", if (oz.refund_code > 1, "refund_processing", "refund_failed")))) '
                . 'when om.id is not null then if(om.refund_code is null, "no_refund", '
                . 'if(om.refund_code = -1000, "refund_processing", if(om.refund_code = 0, "refund_success", "refund_failed"))) '
                . 'when ov.vnp_id is not null then if(ov.refund_code is null, "no_refund", '
                . 'if(ov.refund_code = -1000, "refund_processing", if(ov.refund_code = "00", "refund_success", "refund_failed"))) '
                . 'when oa.id is not null then  if(oa.refund_code is null, "no_refund", '
                . 'if(oa.refund_code = -1000, "refund_processing", if(oa.refund_code = 0, "refund_success", "refund_failed"))) '
                . 'when mc.id is not null then  if(mc.refund_code is null, "no_refund", '
                . 'if(mc.refund_code = -1000, "refund_processing", if(mc.refund_code = "success", "refund_success", "refund_failed"))) '
                . 'else "" end as refund_status,'
                . 'pl.is_current as is_current_trans '
                . 'from fhs_sales_flat_order o '
                . 'join fhs_order_payment_log pl on pl.order_id  = o.increment_id '
                . 'left join order_zalopay oz on oz.order_id = pl.order_id and oz.apptransid  = pl.transaction_id '
                . 'left join order_momopay om on om.order_id = pl.order_id and om.request_id  = pl.transaction_id '
                . 'left join order_vnpay ov on ov.order_id = pl.order_id and ov.vnp_txnref  = pl.transaction_id '
                . 'left join order_airpay oa on oa.order_id = pl.order_id and oa.transaction_id = pl.transaction_id '
                . 'left join order_mocapay mc on mc.order_id = pl.order_id and mc.transaction_id = pl.transaction_id '
                . 'where o.increment_id = :order_id '
                . 'order by pl.id DESC; ';

        $connection_read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $select_params = array(
            "order_id" => $order_id,
        );
        try {
            $rs = $connection_read->fetchAll($query, $select_params);
        } catch (Exception $exc) {
            $exc->getTraceAsString();
        }

        if (count($rs) > 0)
 {
            foreach ($rs as $trans) {
                $item = array();
//                $item['transaction_id'] = $trans['transaction_id'];
                $item['created_at'] = date("d/m/Y H:i:s",strtotime($trans['trans_created_at']));
                $item['payment_method_text'] = Mage::getStoreConfig('payment/' . $trans['payment_method'] . '/title');
                $item['payment_method'] = $trans['payment_method'];
                $item['refund_status'] = $trans['refund_status'];
                if (in_array($trans['payment_method'], self::NON_GATEWAY_METHODS)) {
                    $item['status'] = "success";
                    $item['color'] = $dataStatusColor['success'];
                } else if (empty($trans['payment_status'])) {
                    $item['status'] = "empty";
                    $item['color'] = null;
                } else {
                    if ($trans['refund_status'] == 'no_refund') {
                        $item['status'] = $trans['payment_status'];
                        $item['color'] = $dataStatusColor[$item['status']];
                    } else {
                        $item['status'] = $trans['refund_status'];
                        $item['color'] = $dataStatusColor[$item['status']];
                    }
                    
                    //if trans is current trans of order
                    if ($is_canceld_by_customer && $trans['is_current_trans'] == 1 && $trans['payment_status'] == 'success')
                    {
                        if ($canceled_payment_fail['timestamp'] && strtotime($canceled_payment_fail['timestamp']) < strtotime('-30 days'))
                        {
                            $item['status'] = 'refund_success';
                        }
                        else
                        {
                            $item['status'] = 'refund_processing';
                        }
                    }
                }
                $result[] = $item;
            }
        }
        return $result;
    }
    
   
    public function checkOrderInPaymentTime($order)
    {
        $timeout = \Mage::getStoreConfig('repayment_config/config/timeout');
        if (!empty($timeout))
        {
            $timeout_datetime = date('Y-m-d H:i:s', strtotime("+7 hours " . "-" . $timeout . " seconds"));
        }
        $created_at = date('Y-m-d H:i:s', strtotime('+7 hours', strtotime($order->getCreatedAt())));
        if (strtotime($created_at) > strtotime($timeout_datetime))
        {
            return true;
        }
        return false;
    }

    public function checkOrderValidToRepayment($order)
    {
        $success = false;
        $message = null;

        if ($order->getId())
        {
            //only change when order is payment is gateway
            $check_repayment = $this->checkOrderInRePaymentTime($order,false);
            if ($check_repayment['canRepayment']){
                $success = true;
            } else {
                if ($check_repayment['isOwnerOrder']) {
                    ///Đơn hàng đã hết hạn thời gian hiệu lực có thể thanh toán lại	     
                    $message = "EXPIRY_TIME";
                    if ($check_repayment['canReorder']) {
                        $message = "RE_ORDER"; //Quý khách vui lòng đặt hàng lại
                    }
                } else {
                    $message = "INVALID_ORDER";  // Đơn hàng không hợp lệ
                }
            }
        }
        else
        {
            $message = "INVALID_ORDER";
        }

        return array(
            "success" => $success,
            "message" => $message
        );
    }
    
    public function getTransactionOrder($order_id){
        $query = "select order_id, payment_method, transaction_id, created_at from fhs_order_payment_log where order_id = '" . $order_id . "' and is_current = 1 ";
        $read_connection = Mage::getSingleton('core/resource')->getConnection('core_read');
       
        $rs = $read_connection->fetchAll($query);
        if (count($rs) > 0){
            return $rs[0];
        }
        return false;
    }
    
    //check transaction_id is current transaction_id of order (transanactin_id is the newest)
    public function checkTransIsCurPayment($order, $transaction_id)
    {
        if ($order->getId())
        {
            $order_id = $order->getIncrementId();
            $newest_transaction = $this->getTransactionOrder($order_id);
            $cur_transaction_id = $newest_transaction['transaction_id'];
            $cur_payment_method = $newest_transaction['payment_method'];
            $payment_method = $order->getPayment()->getMethod();

            if ($transaction_id === $cur_transaction_id && $cur_payment_method === $payment_method)
            {
                return true;
            }
        }

        return false;
    }
    
    public function checkOrderHasTransRefund($order_id, $transaction_id)
    {

        try {
            if ($order_id && $transaction_id)
            {
                $query = "select p1.transaction_id, p1.payment_method,\n"
                        . "case\n"
                        . "when oz.id is not null then oz.status\n"
                        . "when mo.id is not null then mo.status\n"
                        . "when ov.vnp_id is not null then ov.vnp_responsecode\n"
                        . "when oa.id is not null then oa.status\n"
                        . "when mc.id is not null then mc.status\n"
                        . "else '' end as transaction_status,\n"
                        . "case\n"
                        . "when oz.id is not null then oz.refund_code\n"
                        . "when mo.id is not null then mo.refund_code\n"
                        . "when ov.vnp_id is not null then ov.refund_code\n"
                        . "when oa.id is not null then oa.refund_code\n"
                        . "when mc.id is not null then mc.refund_code\n"
                        . "else '' end as refund_code\n"
                        . " from fhs_order_payment_log p1\n"
                        . "left join order_zalopay oz on oz.order_id  = p1.order_id and oz.apptransid  = p1.transaction_id\n"
                        . "left join order_momopay mo on mo.order_id  = p1.order_id and mo.request_id  = p1.transaction_id\n"
                        . "left join order_vnpay ov on ov.order_id = p1.order_id and ov.vnp_txnref  = p1.transaction_id\n"
                        . "left join order_airpay oa on oa.order_id  = p1.order_id and oa.transaction_id  = p1.transaction_id\n"
                        . "left join order_mocapay mc on mc.order_id = p1.order_id and mc.transaction_id  = p1.transaction_id\n"
                        . "where p1.order_id = :order_id and p1.transaction_id != :transaction_id \n"
                        . "and ((oz.status = -1000 or oz.status > 1 or mo.status = -1000 or mo.status = 7 or ov.vnp_responsecode = '-99' or "
                        . "oa.status = -1000 or mc.status = -1000) "
                        . "or (oz.refund_code = -1000 or mo.refund_code = -1000 or ov.refund_code = -1000 or oa.refund_code = -1000 or mc.refund_code = '-1000'))";
                $read_connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $bind = array(
                    'order_id' => $order_id,
                    'transaction_id' => $transaction_id
                );
                $rs = $read_connection->fetchAll($query, $bind);
                if (count($rs) > 0)
                {
                    return true;
                }
            }
        } catch (Exception $ex) {
            Mage::log("Exception check order has refund " . $order_id . " - ex " . $ex, null, "repayment.log");
        }
        return false;
    }

    //common
    public  function getColorOfOrder($staus){
         $dataStatusColor = array('primary' => '{"color":"blue","number1" : "#E0ECFD","number2":"#2F80ED"}'
            , 'success' => '{"color" : "green","number1" :"#B6F1B6","number2" : "#2ED62E"}'
            , 'danger' => '{"color":"red","number1" :"#F3B4AF","number2" :"#A90000"}'
            , 'warning' => '{"color":"red","number1":"#F3B4AF","number2":"#A90000"}'
            , 'pending' => '{"color":"orange","number1":"#FCDAB0","number2":"#F7941E"}'
            , 'pre_pending' => '{"color":"orange","number1":"#FCDAB0","number2":"#F7941E"}'
            , 'processing' => '{"color":"blue","number1" : "#E0ECFD","number2":"#2F80ED"}'
            , 'complete' => '{"color" : "green","number1" :"#B6F1B6","number2" : "#2ED62E"}'
            , 'canceled' => '{"color":"red","number1" :"#F3B4AF","number2" :"#A90000"}'
            , 'pending_payment' => '{"color":"orange","number1":"#FCDAB0","number2":"#F7941E"}'
            , 'customer_confirmed' => '{"color":"orange","number1":"#FCDAB0","number2":"#F7941E"}'
            , 'paid' => '{"color":"orange","number1":"#FCDAB0","number2":"#F7941E"}'
        );
        $divtitle = '';
        switch ($staus) {
            case "complete": $divtitle = "success";
                break;
            case "canceled": $divtitle = "danger";
                break;
            case "delivery_failed":
            case "delivery_returned":
            case "ebiz_returned":
            case "permanent_no_stock":
            case "returning":
            case "returned":
                $divtitle = "warning";
                break;
            case "pending":
            case "pending_payment":
            case "customer_confirmed":
            case "paid":
            case "pre_pending":
                $divtitle = "pending";
                break;
            case "delivering":
            case "packed":
            case "processing":
                $divtitle = "primary";
                break;
            default :
                $divtitle = "primary";
                break;
        }
        $color = $dataStatusColor[$divtitle] ? $dataStatusColor[$divtitle] : null; 
        return $color;
    }
    public function getDataStatusBar($stepNameIcon,$dataStatusArr,$dataStausText){
        $returnData = [];
        for($i = 1;$i <= $dataStatusArr["stepNumber"]; $i++){
            $name = "step".$i;
            $jsonData = '{';
            if($i <= $dataStatusArr['step']){
                $split = explode("-",$stepNameIcon[$i]);
                $time = $dataStausText[$split[1]]->date ?? "";
                $text = $dataStausText[$split[1]]->status ?? "";
                $jsonData.='"color":"'.$dataStatusArr['color'].'","nameIcon":"'.$split[0].'","colorId":"'.$dataStatusArr['colorId'].'"';
                $jsonData.=',"time":"'.$time.'"';
                $jsonData.=',"text":"'.$text.'"';
                if($i < $dataStatusArr['step'] ){
                    $jsonData.= ',"bottomColor":"true"';
                }else{
                    $jsonData.= ',"bottomColor":"false"';
                }
                 // neu giai doan (step) dang o vi tri  3 (cuoi cung)  thi khong co thanh bottom o duoi
                if($dataStatusArr['step'] == 3 && $i == $dataStatusArr['step'] ){
                    $jsonData.= ',"showBottom":"false"' ;
                }else{
                    $jsonData.= ',"showBottom":"true"' ;
                }
                
            }else{
                $split = explode("-",$stepNameIcon[$i]);
                $jsonData.= '"color":"sliver","nameIcon":"'.$split[0].'","colorId":"#E0E0E0"'.',"time":" "'.',"text":" ","bottomColor":"false"';
                if($i == 3 ){
                    $jsonData.= ',"showBottom":"false"' ;
                }else{
                    $jsonData.= ',"showBottom":"true"' ;
                }
            }
             $jsonData.= '}';
             $returnData[]= $jsonData;
        }
        return $returnData;
    }
    public function getFullImageURL($partImgUrl) {
        $fullUrl = \Mage::getBaseUrl('media') . 'catalog/product/' . $partImgUrl;
        return $fullUrl;
    }
    
    public function getPaymentMethodByOrderId($order_id, $is_mobile) {
        $order_details = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $couponCode = $order_details->coupon_code; //Coupon code
//        $couponCode = 'VNPAY500';
        $oCoupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
        $oRule = Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());
        $conditions = $oRule->getConditions()->asArray();

        //$_conditions (VNPAY500) 
        /* TH1: co 1 condition =>
         * *array(5){
         * *   [...]
         * *   [attribute] => payment_method
         * *   [value] => 'vnpay'
          } */
        /* TH 2 : co 2 condition tro len =>
         * *array(5){
         * *   [...]
         * *   [conditions] => array()
         * *   [...]
          } */
        $dataCondition = null;
        $resultMethods = array();
        foreach ($conditions['conditions'] as $_conditions) {
            if ($_conditions['attribute'] == "payment_method"){
                $dataCondition[] = $_conditions;
            }
        }
        $blockRepayMent = Mage::app()->getLayout()->createBlock('repayment/repayment');
        $methods = $blockRepayMent->getPaymentMethods($is_mobile);
        $eventCart = \Mage::helper("eventcart")->checkEventCart(null, true, true);
        
        // --- check dataCondition has data :
        if (count($dataCondition) > 0){
            $resultMethods = $methods;
            foreach($dataCondition as $condition){
                $resultMethods = $this->getMethodsAfterCheckRule($resultMethods, $condition);
            }
           // handle add event_cart into payment_method;
            for ($i = 0; $i < count($resultMethods); $i++) {
                if (count($eventCart['affect_payments']) > 0 && $eventCart['success'] && $eventCart['affect_payments'][$resultMethods[$i]['value']]) {
                    $resultMethods[$i]['title'] = $eventCart['affect_payments'][$resultMethods[$i]['value']]['title'];
                    $resultMethods[$i]['rule_content'] = $eventCart['affect_payments'][$resultMethods[$i]['value']]['rule_content'];
                }
            }  
        } else {
            for ($i = 0; $i < count($methods); $i++) {
                $itemMethod['label'] = $methods[$i]['label'];
                $itemMethod['value'] = $methods[$i]['value'];
                // handle add event_cart into payment_method;
                if (count($eventCart['affect_payments']) > 0 && $eventCart['success']) {
                    $itemMethod['title'] = $eventCart['affect_payments'][$methods[$i]['value']]['title'];
                    $itemMethod['rule_content'] = $eventCart['affect_payments'][$methods[$i]['value']]['rule_content'];
                }
                $resultMethods[] = $itemMethod;
            }
        }
        return $resultMethods;
    }
    
    public function getMethodsAfterCheckRule($methods, $dataCondition) {
        // TH 1.1 : neu operator != => value di phai remove no ra => get het list con lai.
        // TH 1.2 : neu operator == => chi lay phuong thuc do.
        // -- TH 1.1:
        if ($dataCondition['operator'] == "!=") {
            $arrayHandleMethods = $methods;
            $count = count($arrayHandleMethods);
            $resultMethods = array();
            for ($i = 0; $i < $count; $i++) {
                if ($arrayHandleMethods[$i]['value'] == $dataCondition['value']) {
                    continue;
                }
                $resultMethods[] = $arrayHandleMethods[$i];
            }
        } else { //-- TH 1.2:
            foreach ($methods as $method) {
                if ($method['value'] == $dataCondition['value']) {
                    $resultMethods[0]['label'] = $method['label'];
                    $resultMethods[0]['value'] = $method['value'];
                    break;
                }
            }
        }
        return $resultMethods;
    }

    public function getEventcartLog($orderId) {
        $connection_read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $select_query = "select *, 1 as matched, 1 as applied  from fhs_event_cart_order_log where order_id =:orderId ;";
        $select_params = array(
            "orderId" => $orderId,
        );
        $result = $connection_read->fetchAll($select_query, $select_params);
        if (count($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }
    
    public function getDataRepaymentMethodsLog($orderId) {
        $arrayData = array();
        $payment_status = null;
        $orderDetail = \Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $canceled_payment_fail = $this->checkOrderCanceledByPaymentFailure($orderId);
        $is_canceld_by_customer = false;
        if ($orderDetail->getStatus() == 'canceled' && !$canceled_payment_fail['payment_failure']){
            $is_canceld_by_customer = true;
        }

        $trans_history = $this->getRepaymentOrderLog($orderId, $is_canceld_by_customer, $canceled_payment_fail);
        
        $num_trans_refund_success = array_filter($trans_history, function($e) {
            return $e['status'] == 'refund_success';
        });

        $num_trans_refund_processing = array_filter($trans_history, function($e) {
            return $e['status'] == 'refund_processing' || $e['refund_status'] == 'refund_failed';
        });

        if (count($num_trans_refund_processing) > 0) {
            $payment_status = "refund_processing";
        } else if (count($num_trans_refund_success) > 0) {
            $payment_status = "refund_success";
        } else {
            if ($orderDetail->getStatus() == "canceled") {
                //check whether order was be canceled by customer or payment failure
                if ($canceled_payment_fail['payment_failure']) {
                    $payment_status = "payment_failure";
                }
            }
        }
        $arrayData['payment_status'] = $payment_status;
        $arrayData['trans_history'] = $trans_history;
        return $arrayData;
    }

}