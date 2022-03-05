<?php

class Magestore_Onestepcheckout_Helper_Queue extends Mage_Core_Helper_Abstract {
    const ERR_INVALID_OPTION_CART = "Giỏ hàng không thỏa điều kiện chương trình";
    const ERR_INVALID_PAYMENT_METHOD = "Phương thức thanh toán không hợp lệ";
    const ERR_MINIMUM_CART_TOTAL = "Thành tiền không đủ số tiền tối thiểu";
    const ERR_GTGT_INVALID = "Thông tin xuất hóa đơn GTGT không đúng";
    
    public function addOrderToQueue($request, $quote_id) {
	$result = [];
	$result['result'] = false;
	$result['message'] = '';
	
        Mage::log("add order to queue ", null, "event.log");
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0) {
            $_getQuoteCallCount++;
            $onePage = Mage::getSingleton('checkout/type_onepage');
            $quote = $onePage->getQuote();
            $_getQuoteCallCount--;
            Mage::log("*** BEGIN  addOrderToQueue with quote_id= " .$quote_id , null, "payment.log");
            try{
                $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
                
                
                $event_cart_log_array = array();
                $event_cart_log = \Mage::helper("eventcart")->checkEventCart(null, true, true);
                if ($event_cart_log['success']) {
                    /// add gift :
                    if($event_cart_log['affect_carts'] &&  $event_cart_log['affect_carts']['matched'] && !empty($event_cart_log['affect_carts']['matched'])){
                        foreach ($event_cart_log['affect_carts']['matched'] as $value) {
                            if ($value['applied']) {
                                $item_cart_log = array(
                                    "title" => $value['title'],
                                    "title_2" => $value['title_2'],
                                    "event_id" => $value["event_id"],
                                    "action_id" =>  $value["action_id"],
                                    "event_type" => $value['event_type'],

                                );
                                $event_cart_log_array[] = $item_cart_log;
                            }
                        }
                    }
                }
                
                //check event_cart before: whether option_id is existed in db
                //return all event_option to calculate times_limit of option
                $option_id = $request["event_cart_option"];
                $event_cart_options = Mage::helper("eventcart")->processRuleToCreateOrder($quote, $option_id);

                $event_cart_data = null;
                if (count($event_cart_options) > 0){
                    $event_cart_data = array_map(function($option){
                        return array(
                            "event_id" => $option["event_id"],
                            "option_id" => $option["option_id"],
                            "option_value" => $option["option_value"],
                            "option_type" => $option["action_type"],
                        );
                    }, $event_cart_options);
                }
                
                $billingData = $request['billing'];
                $billing = $shipping = array();
                $billing_address_id = $request["billing_address_id"] ?? false;
                $shipping_address_id = $request["shipping_address_id"] ?? false;

                //1. customer login: email get from quote
                //2. guest: email get from post data
                //email_shipping will be set the same billing email
                
                //case: customer login and use address in address book
                if (isset($billing_address_id) && trim($billing_address_id) !== '') {
                    $billing = Mage::getModel('customer/address')->load($billing_address_id);
                    if ($billing) {
                        $billing = $this->getAddressJson($billing, true);
                        $billing["email"] = $quote->getCustomerEmail();
                    } else {
                        Mage::log("Error no billing address for address_id=" . $billing_address_id . " - quote_id=" . $quote->getId(), null, "queue.log");
                    }
                } else {
                    //customer do not login => guest or customer login and use different address
                    $billing_region_id = $billingData['region_id'];
                    //has issue in region_name => should be use region_id for get region_name
                    $region_name = Mage::getModel('directory/region')->load($billing_region_id)->getName();
                    //should use region name if region name based on region_id is empty
                    //in case: address is abroad => region_id is text currently
                    if (empty($region_name)){
                        $billingData['region'] = $billingData['region'];
                    } else {
                        $billingData['region'] = $region_name;
                    }
                    
                    $billing = $this->getAddressJson($billingData);
//                    $billing["email"] = $billingData["email"]; // data cua mobile
                    //case: customer login => but enter another address for submit order => email is empty => use email in quote
                    if (!isset($billing['email']) || trim($billing['email']) === '') {
                        $billing["email"] = $quote->getCustomerEmail();
                    }
                }
                $billing["customer_address_id"] = $billing_address_id;

                if ($billingData['use_for_shipping']) {
                    $shipping = $billing;
                    $billing['use_for_shipping'] = true;
                } else {
                    if (isset($shipping_address_id) && trim($shipping_address_id) !== '') {
                        $shipping = Mage::getModel('customer/address')->load($shipping_address_id);
                        if ($shipping) {
                            $shipping = $this->getAddressJson($shipping, true);
                        }
                    } else {
                        $shipping = $request['shipping'];
                        $shippingRegionId = $shipping['region_id'];
                        $region_name = Mage::getModel('directory/region')->load($shippingRegionId)->getName();
                        //should use region name if region name based on region_id is empty
                        //in case: address is abroad => region_id is text currently
                        if (empty($region_name)){
                            $shipping['region'] = $shipping['region'];
                        } else {
                            $shipping['region'] = $region_name;
                        }
                        $shipping = $this->getAddressJson($shipping);
                    }

                    //set shipping_email the same billing_email
                    $shipping['email'] = $billing['email'];
                    $shipping["customer_address_id"] = $shipping_address_id;
                }

                /// 2.a) get request data
                /// Params
                /// isAjax, create_account_checkbox, billing_address_id
                /// shipping_address_id, shipping_method


                $shippingAddress = $quote->getShippingAddress();
                $payableAmount = $shippingAddress->getSubtotalInclTax() + $shippingAddress->getBaseShippingInclTax();
                if (Mage::helper('core')->isModuleEnabled('Fahasa_Codfee')){
                    $codAmount = Mage::helper('codfee')->calculateCodFee($shippingAddress, $payableAmount);
                }
                else{
                    $codAmount = 0;
                }
                
                  //event cart log
                //$event_cart_log = getFull100Eventcart(); => return array;
                if ($shippingAddress->getDiscountDescription()) {
                    $event_cart_coupon = "";
                    $event_cart_description = $shippingAddress->getDiscountDescription();
                    if ($request['coupon_code']){
                        $event_cart_coupon .= strtoupper($request['coupon_code']);
                    }
                    $coupon = array(
                        "title" => $event_cart_coupon,
                        "title_2" => $event_cart_description,
                        "event_id" => null,
                        "action_id" => null,
                        "event_type" => "1",
                        
                    );
                    // add coupon of order
                    $event_cart_log_array[] = $coupon;
                }
                

                $session = Mage::getSingleton('checkout/session');
                $core_session = Mage::getSingleton('core/session');
                
                $redis_cart_id = $session->getRedisCartId();
                
                if (!empty($quote->getFreeshipCode())){
                     $freeship_coupon = array(
                        "title" => $quote->getFreeshipCode(),
                        "title_2" => $session->getFreeshipLabel(),
                        "event_id" => null,
                        "action_id" => null,
                        "event_type" => 4,
                        
                    );
                    // add freeship code of order
                    $event_cart_log_array[] = $freeship_coupon;
                }

//                $check_freeship = $request['onestepcheckout_freeship_checkbox'] ?? $request['freeship'];
                $check_freeship = $request['onestepcheckout_freeship_checkbox'] == "1" 
                        || $request['onestepcheckout_freeship_checkbox'] === true ? true : false;

                if ($shippingAddress->getFreeShipping()) {
                    $check_freeship = false;
                }
                //get shipping_fee when shipping_method = vietnamshippingnormal -> to check if customer use freeship
                $shippingFeeNormal = Mage::getModel('vietnamshipping/carrier_vietnamshippingNormal')->getShippingFeeNormal($quote);
                
                $fm_fields = $request["fm_fields"];
		if(!empty($fm_fields)){
		    if(!empty($fm_fields->fm_vat_taxcode) 
			    || !empty($fm_fields->fm_vat_name)
			    || !empty($fm_fields->fm_vat_email)
			    || !empty($fm_fields->fm_vat_company)
			    || !empty($fm_fields->fm_vat_address) 
			){
			if(!Mage::helper('onestepcheckout')->validateTaxcode($fm_fields->fm_vat_taxcode) || strlen($fm_fields->fm_vat_address) < 10){
			    $result['message'] = self::ERR_GTGT_INVALID;
			    return $result;
			}
		    }
		}
		
                $paymentMethods = $request["payment"];
                if (count($paymentMethods) == 0){
                    Mage::getSingleton('checkout/session')->addError(self::ERR_INVALID_PAYMENT_METHOD);
                    
		    $result['message'] = self::ERR_INVALID_PAYMENT_METHOD;
		    return $result;
                }
                $paymentMethod = $paymentMethods['method'];
                
                $quoteItem = $quote->getAllItems();
                $cartItems = array();
                foreach ($quoteItem as $product_item) {
                    $options = $product_item->getProductOrderOptions();
                    if (!$options) {
                        $options = $product_item->getProduct()->getTypeInstance(true)->getOrderOptions($product_item->getProduct());
                    }

                    if ($attributes = $product_item->getProduct()->getCustomOption('bundle_selection_attributes')) {
                        $options['bundle_selection_attributes'] = $attributes->getValue();
                    }

                    $qty_ordered = $product_item["qty"];
                    //qty_bundle_ordered = qty_in_bundle * qty_ordered of parent
                    if ($product_item->getParentItem()){
                        $qty_ordered = $qty_ordered * $product_item->getParentItem()->getQty();
                    }
                    
                    $product = array(
                        "quote_item_id" => $product_item["item_id"],
                        "product_id" => $product_item["product_id"],
                        "sku" => $product_item["sku"],
                        "name" => $product_item['name'],
                        "qty" => $qty_ordered,
                        "price_incl_tax" => $product_item["price_incl_tax"],
                        "discount_percent" => $product_item["discount_percent"],
                        "discount_amount" => $product_item["discount_amount"],
                        "product_type" => $product_item["product_type"],
                        "parent_item_id" => $product_item["parent_item_id"],
                        "price" => $product_item["price"],
                        "tax_amount" => $product_item["tax_amount"],
                        "row_total" => $product_item["row_total"],
                        "row_total_incl_tax" => $product_item["row_total_incl_tax"],
                        "tax_percent" => $product_item["tax_percent"],
                        "product_options" => serialize($options),
                        "soon_release" => $product_item["soon_release"],
                        "is_free_product" => $product_item["is_free_product"] ? 1 : 0,
                    );
                    $cartItems[] = $product;
                }
                
                $buffetcomboTemp = $session->getBuffetcombo();
                $buffetcombo = null;
                if ($buffetcomboTemp){
                    $buffetcombo["product_ids"] = $buffetcomboTemp["ids"];
                    $buffetcombo["price"] = $buffetcomboTemp["price"];
                    $buffetcombo["count"] = $buffetcomboTemp["count"];
                    $buffetcombo["gift_id"] = $buffetcomboTemp["gift_id"];
                }
                
                //get fhs_coin 
                $fhsCoinObj = $core_session->getFhsCoin();
                $coinCode = null;
                $coinAmount = 0;
                $isCoin = false;
                if ($fhsCoinObj['code']){
                    $coinCode = $fhsCoinObj['code'];
                    $coinAmount = doubleval($shippingAddress->getCoinAmount());
                    $isCoin = true;
                }

                $freeship_code = $quote->getFreeshipCode();
                $freship_amount = $shippingAddress->getFreeshipAmount();
                
                //check quote has any item matched rule: choose event source id for saving event_source_id in database
                $event_source_id = $core_session->getEventSourceId();
                $event_source = null;
                $event_source_helper = Mage::helper("event/eventsource");
                if ($check_event_source = $event_source_helper->checkCartHasSourceOptionId($quoteItem))
                {
                    if (!$event_source_helper->checkCartHasExcludedCatIdForTracking($quoteItem))
                    {
                        //reason to get event_source_id from session: in step 1 for mobile, old version still use session way. 
                        //We want tracking order when customer go to sgk page
                        if ($check_event_source['matched'])
                        {
                            if ($event_source_id)
                            {
                                $event_source = array(
                                    "option_id" => $event_source_id,
                                    "event_id" => $check_event_source['event_id'],
                                    "affId" => $core_session->getAffId(),
                                );
                            }
                            else if ($request['affId'])
                            {
                                //customer does not choose school, cookie still store the affId
                                $event_source = array(
                                    "option_id" => $event_source_id,
                                    "event_id" => $check_event_source['event_id'],
                                    "affId" => $request["affId"]
                                );
                            }
                        }
                        else
                        {
                            //if cart does not matched category, cookie still store the affId, order is still recored for bookstore
                            if ($request['affId'])
                            {
                                $bookstoreAffId = Mage::helper("event/eventsource")->getBookstoreAffIdBySourceAffId($request['affId']);
                                if ($bookstoreAffId)
                                {
                                    //customer does not choose school, cookie still store the affId
                                    $event_source = array(
                                        "option_id" => $event_source_id,
                                        "event_id" => $check_event_source['event_id'],
                                        "affId" => $bookstoreAffId
                                    );
                                }
                                else
                                {
                                    Mage::log("No found bookstore affId " . $request['affId'], null, "aff_source.log");
                                }
                            }
                        }
                    }
                }

                //Date giao hang cua DH : 
                $deliveriDate = "";
                if($request['deliveriDate']){
                    $deliveriDate = $request['deliveriDate'];
                }

                /// 2.b) insert into queue
                $request_data = array(
                    'billing' => $billing,
                    'shipping' => $shipping,
                    'payment_method' => $paymentMethod,
                    'coupon_code' => $request['coupon_code'] ?? false,
                    'vipCode' => $request['vipCode'] ?? '',
                    'giftwrap' => $request['giftwrap'] ?? '',
                    'localeStoreId' => $request['localeStoreId'] ?? '',
                    'check_freeship' => $check_freeship,
                    "shipping_fee_normal" => $shippingFeeNormal,
                    'tryout' => $request['tryout'] ?? '',
                    'pickupLocation' => $request['pickupLocation'] ?? '',
                    'codfee' => $codAmount,
                    'cartItems' => $cartItems,
                    "fm_fields" => $fm_fields,
                    "store_id" => $quote->getStoreId(),
                    "customer_id" => $quote->getCustomerId(),
                    "customer_email" => $quote->getCustomerEmail(),
                    "customer_is_guest" => $quote->getCustomerIsGuest(),
                    "customer_group_id" => $quote->getCustomerGroupId(),
                    "grand_total" => $shippingAddress->getGrandTotal(),
                    "discount_amount" => $shippingAddress->getDiscountAmount(),
                    "subtotal" => $shippingAddress->getSubtotal(),
                    "subtotal_incl_tax" => $shippingAddress->getSubtotalInclTax(),
                    "base_subtotal" => $shippingAddress->getBaseSubtotal(),
                    "base_tax_amount" => $shippingAddress->getBaseTaxAmount(),
                    "tryout_discount" => $shippingAddress->getTryoutDiscount(),
                    "shipping_method" => $shippingAddress->getShippingMethod(),
                    "shipping_description" => $shippingAddress->getShippingDescription(),
                    "shipping_date" => $deliveriDate,
                    "shipping_incl_tax" => $shippingAddress->getShippingInclTax(),
                    "shipping_tax_amount" => $shippingAddress->getShippingTaxAmount(),
                    "base_shipping_amount" => $shippingAddress->getBaseShippingAmount(),
                    "applied_rule_ids" => $shippingAddress->getAppliedRuleIds(),
                    "discount_description_arr" => $shippingAddress->getDiscountDescriptionArray(),
                    "discount_description" => $shippingAddress->getDiscountDescription(),
                    "weight" => $shippingAddress->getWeight(),
                    "vip_discount" => $quote->getVipDiscount(),
                    "event_cart" => $event_cart_data,
                    "buffet_combo" => $buffetcombo,
                    "is_coin" => $isCoin,
                    "coin_code" => $coinCode,
                    "coin_amount" => $coinAmount,
                    "event_delivery" => $request['event_delivery_option'],
                    "event_cart_log" => $event_cart_log_array,
                    "freeship_code" => $freeship_code,
                    "freeship_amount" => $freship_amount,
                    "event_source" => $event_source
                );
                
                $customer_id = $quote->getCustomerId();
                //return $request_data;
                /// insert sql
                $connection_write = Mage::getSingleton("core/resource")->getConnection("core_write");
                $insert_query = "INSERT INTO fhs_flashsale_queue(created_at, request_data, customer_id, quote_id, is_processed, processed_at, "
                        . "processed_status, cart_id)"
                        . " values(NOW(), :request_data, :customer_id, :quote_id, :is_processed, NULL, :processed_status, :redis_cart_id);";
                $insert_params = array(
                    "request_data" => json_encode($request_data),
                    "customer_id" => $customer_id,
                    "quote_id" => $quote_id,
                    "is_processed" => false,
                    "processed_status" => "new",
                    "redis_cart_id" => $redis_cart_id,
                );

		
		//validateMinimumAmount cart
		if (!$quote->validateMinimumAmount()) {
                    Mage::getSingleton('checkout/session')->addError(self::ERR_INVALID_PAYMENT_METHOD);
                    
		    $result['message'] = self::ERR_INVALID_PAYMENT_METHOD;
		    return $result;
		}
                $connection_write->query($insert_query, $insert_params);

                $queueId = $connection_write->lastInsertId();

                $defaultExpiredTime = Mage::getStoreConfig('flashsale_config/config/expired_time');
                if ($defaultExpiredTime == null) {
                    $defaultExpiredTime = 300;
                }

                $flashsale_queue_expired = time() + $defaultExpiredTime;

                $session->setFlashsaleQueueId($queueId);
                $session->setFlashsaleQueueExpired($flashsale_queue_expired);
                Mage::log("*** addOrderToQueue has quote_id = ". $quote_id ." and queueId = ". $queueId, null, "payment.log");
		
                Mage::log("*** addOrderToQueue has quote_id = ". $quote_id ." and queueId = ". $queueId . ", optionId = " . $core_session->getEventSourceId() 
                        . ", cookie_affId = " . $request['affId'], null, "aff_source.log");
		$result['result'] = true;
		return $result;
            }
            catch(Exception $ex){
                Mage::log("*** Add cart to queue exception " . $quote_id . $ex, null, "queue.log");
                return $result;
            }

        } else {
            Mage::log("*** FAIL addOrderToQueue: recursively loop when getQuote(). Exit", null, "queue.log");
            return $result;
        }
    }
    
    public function checkCartIsAppliedFreeshipRule($quote){
        $appliedRuleIds = $quote->getAppliedRuleIds();
        $appliedRuleIds = explode(',', $appliedRuleIds);
        $rules =  Mage::getModel('salesrule/rule')->getCollection()->addFieldToFilter('rule_id' , array('in' => $appliedRuleIds));
        foreach ($rules as $rule){
            switch ($rule->getSimpleFreeShipping()){
                case Mage_SalesRule_Model_Rule::FREE_SHIPPING_ITEM:
                    return $rule->getDiscountQty() ? $rule->getDiscountQty() : true;
                case Mage_SalesRule_Model_Rule::FREE_SHIPPING_ADDRESS:
                    return true;
            }
        }
        return false;

    }

    public function getOrderStatus($customer_id, $quote_id){
        /// 1. Check if quote_id, session_id -> order status
        
        /// 2. return resul
        /// no) -> keep waiting
        /// yes) -> redirect order to Summary page
        
        $connection_read = Mage::getSingleton("core/resource")->getConnection("core_read");
        $insert_query = "SELECT * FROM fhs_flashsale_queue WHERE customer_id=:customer_id AND quote_id=:quote_id;";
        
        $insert_params = array(
            "customer_id" => $customer_id,
            "quote_id" => $quote_id
        );
        
        $result = $connection_read->query($insert_query, $insert_params);
        
        return $result;
    }
 
    public function checkOrderIsProcessed($isMobile = false, $platform) {
        $session = Mage::getSingleton('checkout/session');
        // get SessionID customer : 
        $customerSession = Mage::getSingleton('core/session');
        $SID = $customerSession->getEncryptedSessionId();
        $queueId = $session->getFlashsaleQueueId();
        \Mage::log("*** START - checkOrderIsProcessed with queue_id = " . $queueId . " customerSessionId = ". $SID , null, "payment.log");
        $connection_read = Mage::getSingleton("core/resource")->getConnection("core_read");
        
        $query = "select q.*, so.id as source_order_id, so.affId_record from fhs_flashsale_queue q "
                . "left join fhs_sales_flat_order o on q.order_id = o.entity_id "
                . "left join fhs_event_source_order so on so.order_id = o.increment_id "
                . "where q.id = :queue_id;";
        $params = array(
            "queue_id" => $queueId
        );
        $rs = $connection_read->fetchAll($query, $params);
        $isProcessed = false;
        $orderId = null;
        $quoteId = null;
        $incrementId = null;
        $payment_method_code = null;
        $success = true;
        $message_code = null;
        if (count($rs) > 0) {
            $isProcessed = $rs[0]['is_processed'] == 1 && $rs[0]['fpoint_processed'] == 1 ? true : false;
            \Mage::log("*** START - isProcessed with queue_id = " . $queueId . " and  isProcessed =" . $isProcessed, null, "payment.log");
            if ($isProcessed) { /// bi sa4564564 ;;;;;
                $orderId = $rs[0]['order_id'] ? $rs[0]['order_id'] : null;
                $quoteId = $rs[0]['quote_id'] ? $rs[0]['quote_id'] : null;
                $session->unsetData("flashsale_queue_expired");
                $session->unsetData("flashsale_queue_id");
                
                //refresh redis in cart mini in redis to get UI
                Mage::helper('rediscart/cart')->refreshCopyRedisCart();
                
                \Mage::log("*** START - Has orderId with orderId = " . $orderId . "and  queue_id =" . $queueId, null, "payment.log");
                $request_result = $rs[0]['request_result'] ? $rs[0]['request_result'] : null;
                if ($orderId && $request_result == "order_success") {
		    if(Mage::getStoreConfig("customer/register_guest_order/is_active") == 1 && !Mage::getSingleton('customer/session')->isLoggedIn()){
			$last_guest_orders = Mage::getSingleton('customer/session')->getLastGuestOrders();
			if(empty($last_guest_orders)){
			    $last_guest_orders = [];
			}
			array_push($last_guest_orders, $orderId);
			Mage::getSingleton('customer/session')->setLastGuestOrders($last_guest_orders);
		    }
                    
                    //unset event source id for aff_id tracking order
                    $customerSession->unsAffId();
                    $customerSession->unsEventSourceId();
                    $customerSession->unsEventSourceTimestamp();
                    //log in web.log for tracking
                    $quote = Mage::getModel('sales/quote')->load($quoteId);
                    
                    $affId = "";
                    if ($rs[0]['source_order_id'])
                    {
                        $affId = $rs[0]['affId_record'] ? $rs[0]['affId_record'] : "" ;
                    }

                    Mage::helper('weblog')->FinishCheckout($quote, $isMobile, $affId);
                    
                    //if payment = zalopay => get redirect url
                    $order = Mage::getModel("sales/order")->load($orderId);
                    
                    if (!$isMobile && Mage::getStoreConfig('accesstrade/general/enable') == 1){
                        //post api for access trade
                        Mage::helper('fhsmarketing')->postSuccessAccessTradePurchase($order->getIncrementId());
                    }
                    
                    
                    $session = Mage::getSingleton('checkout/session');
                    $session->setLastQuoteId($quoteId);
                    $session->setLastOrderId($orderId);
                    $session->setLastOrderIdForPayment($order->getIncrementId());
                    $session->setLastSuccessQuoteId($quoteId);
                    $session->setLastRealOrderId($order->getIncrementId());
                    $incrementId = $order->getIncrementId();
                    
                    $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
                    $payment_list = array("zalopayapp", "zalopayatm", "zalopay", "zalopaycc");
                    if (in_array($payment_method_code, $payment_list)) {
                        \Mage::log("*** BEGIN - zalopay create order with orderFHS = " . $order->getIncrementId() , null, "payment.log");
                        $zaloPay = new Fahasa_Zalopay_Model_Payment();
                        Fahasa_Zalopay_Model_Payment::$mobileChannel = true;
                        Fahasa_Zalopay_Model_Payment::$realOrderId = $order->getIncrementId();
                        $paymentResult = $zaloPay->createZalopayOrder($payment_method_code);
                        $redirectUrl = $paymentResult['paymentUrl'] ?? $paymentResult['url'];
                        Fahasa_Zalopay_Model_Payment::$mobileChannel = false;
                    } else if($payment_method_code == "momopay"){
                        $momoPay = new TTS_Momopay_Model_Momopay();
                        if ($isMobile){
                            \Mage::log("*** BEGIN - momopay create order (Mobile) with orderFHS = " . $order->getIncrementId() , null, "payment.log");
                            $momo_request_id = $momoPay->createMomoOrder($order);
                        }else{
                            \Mage::log("*** BEGIN - momopay create order (Website) with orderFHS = " . $order->getIncrementId() , null, "payment.log");
                            $paymentResult = $momoPay->getUrlMomopay($order->getIncrementId());
                            $redirectUrl =  $paymentResult['payUrl'] ?? $paymentResult['url'];
                        }
                    }else if($payment_method_code == "vnpay"){
                        $vnPay = new TTS_Vnpay_Model_Vnpay();
                        if ($isMobile){
                            \Mage::log("*** BEGIN - vnpay create order (Mobile) with orderFHS = " . $order->getIncrementId() , null, "payment.log");
                            $paymentResultMobile = $vnPay->getUrlVnpay($order->getIncrementId(), $platform);
                            $redirectUrl =  $paymentResultMobile['redirect_url'];
                            $transaction_id = $paymentResultMobile['transaction_id'];
                            $momo_request_id = $transaction_id;
                        }else{
                            \Mage::log("*** BEGIN - vnpay create order (Website) with orderFHS = " . $order->getIncrementId() , null, "payment.log");
                            $paymentResult = $vnPay->getUrlVnpay($order->getIncrementId(),"website");
                            $redirectUrl =  $paymentResult['redirect_url'];
                            $transaction_id = $paymentResult['transaction_id'];
                        }
                    } else if ($payment_method_code == 'mocapay'){
                        $mocaPay = new TTS_Mocapay_Model_Mocapay();
                        if ($isMobile && ($platform == "mobile_ios" || $platform == "mobile_android")) {
                            $paymentResult = $mocaPay->getUrlMocapay($order->getIncrementId(), $platform);
                            $redirectUrl = $paymentResult['redirect_url'];
                            $transaction_id = $paymentResult['transaction_id'];
                        } else {
                            $payment_result = $mocaPay->getUrlMocapay($order->getIncrementId(), 'web');
                            $redirectUrl = $payment_result['redirect_url'];
                            $transaction_id = $payment_result['transaction_id'];
                        }
                    } else if($payment_method_code == "airpay"){
                        $airPay = new TTS_Airpay_Model_Airpay();
                        if ($isMobile){
			    Mage::log("*** BEGIN - airpay create order (mobile) with orderFHS = " . $order->getIncrementId() , null, "payment.log");
                            $paymentResult = $airPay->getUrlAirpay($order->getIncrementId(), 'app');
                            $redirectUrl = $paymentResult['redirect_url'];
                            $transaction_id = $paymentResult['transaction_id'];
                        }else{
                            \Mage::log("*** BEGIN - airpay create order (Website) with orderFHS = " . $order->getIncrementId() , null, "payment.log");
                            $paymentResult = $airPay->getUrlAirpay($order->getIncrementId());
                            $redirectUrl = $paymentResult['redirect_url'];
                            $transaction_id = $paymentResult['transaction_id'];
                        }
                    }
                    else {
                        $redirectUrl = Mage::getUrl('checkout/onepage/success');
                    }
                } else {
		    $orderId = null;
                    $message_coce = $request_result;
		    switch ($request_result){
			case "rule_deactive":
			    $message = $this->__('Your coupon code is no longer valid.');                            
			    break;
			case "rule_less_to_from_date":
			    $message = $this->__('Your coupon code is not valid yet.');
			    break;
			case "rule_expired":
			    $message = $this->__('Your coupon code has Expired.');
			    break;
			case "rule_coupon_over_limit":
			case "rule_customer_over_limit":
			case "rule_over_limit":
			case "coupon_no_available":
			case "rule_coupon_over_mkt_fee":
			    $message = $this->__('Your coupon code has expired.');
			    break;
			case "not_enough_fpoint":
			    $message = $this->__('F-Point in your account is not enough.');
			    break;
			case "not_enough_free_ship":
			    $message = $this->__('Free Ship in your account is not enough.');
			    break;
                        case "no_quantity":
                            $message = $this->__('Flash Sale price has changed for product');
                            $message_coce = "flashsale_price_changed";
			    break;
                        case "no_fpoint_freeship":
                            $message = $this->__('F-Point and Freeship in your account are not enough');
                            $message_coce = "no_fpoint_freeship";
			    break;
                        case "not_enough_coin": 
                            $message = $this->__('Coin code is not enough');
                            $message_coce = "not_enough_coin";
			    break;
                        case "unsupported_address":
                            $message = $this->__('Products do not support to your address');
                            $message_coce = $this->__('Products do not support to your address');
			    break;
			default :
			    $message = "Flash Sale price has changed for product";
		    }
                  
		    Mage::getSingleton('checkout/session')->addError($message);
                }
            }
        } else {
            //no order in fhs_flashsale_queue => redirect to cart
            $isProcessed = true;
        }

        $queueExpired = $session->getFlashsaleQueueExpired();
        if ($queueExpired <= time()) {
            $session->unsetData("flashsale_queue_expired");
            $session->unsetData("flashsale_queue_id");
        }
        
        $result = array(
            "isProcessed" => $isProcessed,
            "orderId" => $orderId,
            "incrementId" => $incrementId,
            "quoteId" => $quoteId,
            "success" => $success,
            "redirectUrl" => $redirectUrl, 
            "websocket" => $paymentResult,
            "paymentMethod" => $payment_method_code,
            "requestId" => $momo_request_id,
            "message" => $message_coce
        );
        \Mage::log("*** END - checkOrderIsProcessed with queue_id = " . $queueId . " result =" . print_r($result,true), null, "payment.log");
        return $result;
    }
    
    public function getAddressJson($address, $isCustomerAddress){
        return array(
            "firstname" => $address["firstname"],
            "lastname" => $address["lastname"],
            "telephone" => $address["telephone"],
            "country_id" => $address["country_id"],
            "region_id" => $address["region_id"],
            "region" => $address["region"],
            "city" => $address["city"],
            "ward" => $address["ward"],
            "postcode" => $address["postcode"],
            "street" => $isCustomerAddress ? $address["street"] : $address["street"][0],
            "email" => $address["email"]
        );
    }
    
}

