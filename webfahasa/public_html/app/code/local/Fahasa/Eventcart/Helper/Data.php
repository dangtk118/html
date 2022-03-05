<?php

class Fahasa_Eventcart_Helper_Data extends Mage_Core_Helper_Abstract {

    //Type filter
    const TYPE_APPLY_DISTRICT = "apply_district";
    const TYPE_SUB_TOTAL = "sub_total";
    const TYPE_ALL_PRODUCT = "all_product";
    const TYPE_ONE_OF_PRODUCT = "one_of_product";
    const TYPE_SUB_TOTAL_FOR_SUBSET = "sub_total_for_subset";
    const TYPE_CATEGORY_MID_ID = "category_mid_id";
    const TYPE_QTY_OF_PRODUCT = "qty_of_product"; //qty of one product, it is different from total items in cart
    const TYPE_SUPPLIER = "supplier";
    const TYPE_LOGIN = "login"; //because we don't want to insert 1 filter row for login => default it always request login member
    const TYPE_NUM_ITEMS_FOR_SUBSET = "num_items_for_subset";
    const TYPE_NUM_ITEMS = "num_items"; //num items for all products in cart (not in list product)
    const TYPE_STORE_ID = "store_id";
    const TYPE_PAYMENT_METHOD = "payment_method";
    const TYPE_APPLY_PROVINCE = "apply_province";
    
    //Error
    const TYPE_ERROR_NOT_MATCHED_PRODUCT = "not_matched_product";
    
    const ERROR_SUB_TOTAL = "Giá trị của sản phẩm không thỏa điều kiện";
    const ERROR_NOT_MATCHED_PRODUCT = "Giỏ hàng có sản phẩm không nằm trong danh mục ưu đãi";
    const ERROR_SUB_TOTAL_FOR_SUBSET = "Giá trị của sản phẩm trong danh mục ưu đãi không thỏa điều kiện";
    const ERROR_APPLY_DISTRICT = "Địa chỉ giao hàng không thỏa khu vực giao hàng";
    const ERROR_QTY_OF_PRODUCT = "Số lượng sản phẩm trong danh mục ưu đãi không thỏa điều kiện";
    const ERROR_LOGIN = "Áp dụng cho thành viên";
    const ERROR_NUM_ITEMS_FOR_SUBSET = "Số lượng sản phẩm không thỏa điều kiện";
    const ERROR_NUM_ITEMS = "Số lượng sản phẩm không thỏa điều kiện";
    const ERROR_STORE_ID_MOBILE = "Áp dụng cho app mobile";
    const ERROR_STORE_ID_WEB = "Áp dụng cho web";
    
    //Type action
    const ACTION_TYPE_ADD_GIFT = "add_gift";
    const ACTION_TYPE_NO_ACTION = "no_action";
    const ACTION_TYPE_SALESRULE = "salesrule";
    
    const NO_ACTION_ARRAY = array(
        self::ACTION_TYPE_SALESRULE
    );
    
    const PRODUCT_UNIT = " sản phẩm";
    const PRODUCT_UNIT_EN = " products";
    
    //self::TYPE_LOGIN => filter_type only affect 10% for reach_percent. It is calculated specially
    const RULES_AFFECT_PERCENT = array(
        self::TYPE_SUB_TOTAL,
        self::TYPE_SUB_TOTAL_FOR_SUBSET,
//        self::TYPE_QTY_OF_PRODUCT,
    );
    
    
    const TYPE_LOGIN_AFFECT_PERCENT = 10;
    
    const DISPLAY_TYPE_ERRORS = array(
      self::TYPE_LOGIN,
      self::TYPE_NUM_ITEMS,
      self::TYPE_NUM_ITEMS_FOR_SUBSET,
      self::TYPE_STORE_ID,
      self::TYPE_APPLY_DISTRICT,
      self::TYPE_PAYMENT_METHOD
    );
    
    const TYPE_EVENT_SALE = 0;
    const TYPE_EVENT_COUPON = 1;
    const TYPE_EVENT_GIFT = 2;
    const TYPE_EVENT_PAYMENT = 3;
    const TYPE_EVENT_FREESHIP = 4;
    const TYPE_EVENT_COUPON_PAYMENT = 5; //rule apply coupon in fahasa but condition is payment
    const TYPE_EVENT_FREESHIP_PAYMENT = 6; //rule apply freeship in fahasa but condition is payment
    
    const FILTER_OPERATOR_DIFRRERENT = "!=";

    const TYPE_AFFECT_PAYMENT_CHECKOUT = array (
        self::TYPE_EVENT_PAYMENT,
        self::TYPE_EVENT_COUPON_PAYMENT,
        self::TYPE_EVENT_FREESHIP_PAYMENT
    );
    
    public function checkEventCart($billing, $is_full_process = false, $separate_coupon = false)
    {
        $split_events = null;
        $matched_events = null;
        $quote = null; 
	
	//get cart
	static $_getQuoteCallCount = 0;
	if ($_getQuoteCallCount == 0)
	{
	    $_getQuoteCallCount++;
//	    $onePage = Mage::getSingleton('checkout/type_onepage');
//	    $quote = $onePage->getQuote();
            $quote = Mage::helper("rediscart/cart")->getStaticQuote();
	    $_getQuoteCallCount--;
	    try{
		//need to check quote total?
//		$quote->setTotalsCollectedFlag(false)->collectTotals();

		$cart = array(
		    "billing" => (array) $billing,
		    "quote" => $quote
		);
	    }catch(Exception $ex){}
	}
        
        if (Mage::getStoreConfig('eventcart_config/config/is_active')){
	    try {
		$matched_events = $this->validateEvent($cart);
		$split_events = $this->parseEventsInCart($matched_events, $is_full_process, $separate_coupon, $quote);
	    } catch (Exception $ex) {}
        }
	
	// $split_events = $this->addBuffetCouponInEvent($split_events);
        
	try{
	    $split_events = $this->addWalletVoucherInEvent($cart, $quote, $split_events);
	}catch (Exception $ex) {}
        
        $split_events = $this->addCartCouponInEvent($quote, $split_events);
	
        array_multisort(array_column($split_events['affect_coupons']['matched'], 'applied'), SORT_DESC, $split_events['affect_coupons']['matched']);

        return array(
            "success" => true,
            "events" => $split_events["non_auto_events"],
            "affect_items" => $split_events["affect_items"],
            "affect_carts" => $split_events["affect_carts"],
            "affect_coupons" => $split_events["affect_coupons"],
            "affect_payments" => $split_events['affect_payments'],
            "affect_freeships" => $split_events['affect_freeships'],
            "affect_payments_checkout" => $split_events['affect_payments_checkout'],
        );
    }
    
    public function addBuffetCouponInEvent($split_events)
    {
        if (Mage::getStoreConfig('eventcart_config/config/is_active_buffet_coupon'))
        {
            try {
                $buffet_coupon = Mage::helper("fpointstorev2/data")->getBuffetCoupon();
//                if ($is_full_process)
//                {
                    if (!empty($buffet_coupon))
                    {

                        //coupon code discount price
                        $coupon_matched = array_filter($buffet_coupon, function($e) {
                            return $e['event_type'] == self::TYPE_EVENT_COUPON && $e['matched'];
                        });

                        $coupon_not_matched = array_filter($buffet_coupon, function($e) {
                            return $e['event_type'] == self::TYPE_EVENT_COUPON && !$e['matched'];
                        });

                        $freeship_matched = array_filter($buffet_coupon, function($e) {
                            return $e['event_type'] == self::TYPE_EVENT_FREESHIP && $e['matched'];
                        });

                        $freeship_not_matched = array_filter($buffet_coupon, function($e) {
                            return $e['event_type'] == self::TYPE_EVENT_FREESHIP && !$e['matched'];
                        });

                        $split_events["affect_coupons"]['matched'] = array_merge($split_events["affect_coupons"]['matched'], $coupon_matched);
                        $split_events["affect_coupons"]['not_matched'] = array_merge($split_events["affect_coupons"]['not_matched'], $coupon_not_matched);

                        $split_events["affect_freeships"]['matched'] = array_merge($split_events["affect_freeships"]['matched'], $freeship_matched);
                        $split_events["affect_freeships"]['not_matched'] = array_merge($split_events["affect_freeships"]['not_matched'], $freeship_not_matched);
                    }
//                }
//                else
//                {
//                    if (!empty($buffet_coupon))
//                    {
//                        $matched = [];
//                        $not_matched = [];
//                        if (!empty($split_events["affect_carts"]))
//                        {
//                            $matched = $split_events["affect_carts"]['matched'];
//                            $not_matched = $split_events["affect_carts"]['not_matched'];
//                        }
//                        else
//                        {
//                            $split_events["affect_carts"] = [];
//                        }
//                        foreach ($buffet_coupon as $item)
//                        {
//                            if ($item['matched'])
//                            {
//                                array_push($matched, $item);
//                            }
//                            else
//                            {
//                                array_push($not_matched, $item);
//                            }
//                        }
//                        $split_events["affect_carts"]['matched'] = $matched;
//                        $split_events["affect_carts"]['not_matched'] = $not_matched;
//                    }
//                }
            } catch (Exception $ex) {
                
            }
        }
        return $split_events;
    }

    //the same code as addBuffetCouponInCart
    public function addWalletVoucherInEvent($cart, $quote, $split_events)
    {
        $customer = \Mage::getSingleton('customer/session')->getCustomer();
        $customer_id = $customer->getId();
        if (\Mage::getStoreConfig('fpointstorev2_config/wallet_voucher/is_active') && $customer_id){
            $helper = Mage::helper("fpointstorev2/data");
            $wallet_voucher_list = $helper->getVoucherHistoryList($customer_id, true, true, true);
	    
	    $wallet_voucher = array();
	    $rule_ids = array();
	    $rule_ids_expired = array();
	    foreach($wallet_voucher_list as $item){
		if(!$item['is_expired'] && empty($rule_ids[$item['rule_id']])){
		    $rule_ids[$item['rule_id']] = $item['rule_id'];
		}elseif($item['is_expired'] && empty($rule_ids_expired[$item['rule_id']])){
		    $rule_ids_expired[$item['rule_id']] = $item['rule_id'];
		}
	    }
	    
	    if(!empty($rule_ids)){
		$event_cart = $this->validateEvent($cart, $rule_ids, 0);
		$event_cart = $this->parseEventsInCart($event_cart, true, true, $quote);
		
		//replace and use not matched item
		if(!empty($event_cart['affect_coupons']['not_matched'])){
		    $event_cart_list = array();
		    foreach($event_cart['affect_coupons']['not_matched'] as $item){
			if(!empty($item['options'][0])){
			    $opt = $item['options'][0];
			    $event_cart_list[$opt['option_value']] = $item;
			}
		    }
		    foreach($wallet_voucher_list as $key=>$item){
			if(!$item['is_expired']){
			    if(!empty($event_cart_list[$item['rule_id']])){
				$event_cart_item = $event_cart_list[$item['rule_id']];

				$item['error'] = $event_cart_item['error'];
				$item['min_total'] = $event_cart_item['min_total'];
				$item['need_total'] = $event_cart_item['need_total'];
				$item['sub_total'] = $event_cart_item['sub_total'];
				$item['max_total'] = $event_cart_item['max_total'];
				$item['reach_percent'] = $event_cart_item['reach_percent'];
    //			    $item['applied'] = $event_cart_item['applied'];
				$item['matched'] = false;

				$wallet_voucher[$key] = $item;
			    }
			}else{
			    $wallet_voucher[$key] = $item;
			}
		    }
		}
		
		//replace and use matched item
		if(!empty($event_cart['affect_coupons']['matched'])){
		    $event_cart_list = array();
		    foreach($event_cart['affect_coupons']['matched'] as $item){
			if(!empty($item['options'][0])){
			    $opt = $item['options'][0];
			    $event_cart_list[$opt['option_value']] = $item;
			}
		    }
		    foreach($wallet_voucher_list as $key=>$item){
			if(!$item['is_expired']){
			    if(!empty($event_cart_list[$item['rule_id']])){
				$event_cart_item = $event_cart_list[$item['rule_id']];

				$item['error'] = $event_cart_item['error'];
				$item['min_total'] = $event_cart_item['min_total'];
				$item['need_total'] = $event_cart_item['need_total'];
				$item['sub_total'] = $event_cart_item['sub_total'];
				$item['max_total'] = $event_cart_item['max_total'];
				$item['reach_percent'] = $event_cart_item['reach_percent'];
//				$item['applied'] = $event_cart_item['applied'];
				$item['matched'] = true;

				$wallet_voucher[$key] = $item;
			    }
			}else{
			    $wallet_voucher[$key] = $item;
			}
		    }
		}
	    }elseif(!empty($rule_ids_expired)){
		$wallet_voucher = $wallet_voucher_list;
	    }
         
             //coupon code discount price
            $coupon_matched = array_filter($wallet_voucher, function($e) {
                return $e['event_type'] == self::TYPE_EVENT_COUPON && $e['matched'];
            });

            $coupon_not_matched = array_filter($wallet_voucher, function($e) {
                return $e['event_type'] == self::TYPE_EVENT_COUPON && !$e['matched'];
            });

            $freeship_matched = array_filter($wallet_voucher, function($e) {
                return $e['event_type'] == self::TYPE_EVENT_FREESHIP && $e['matched'];
            });

            $freeship_not_matched = array_filter($wallet_voucher, function($e) {
                return $e['event_type'] == self::TYPE_EVENT_FREESHIP && !$e['matched'];
            });

            $split_events["affect_coupons"]['matched'] = array_merge($split_events["affect_coupons"]['matched'], $coupon_matched);
            $split_events["affect_coupons"]['not_matched'] = array_merge($split_events["affect_coupons"]['not_matched'], $coupon_not_matched);

            $split_events["affect_freeships"]['matched'] = array_merge($split_events["affect_freeships"]['matched'], $freeship_matched);
            $split_events["affect_freeships"]['not_matched'] = array_merge($split_events["affect_freeships"]['not_matched'], $freeship_not_matched);
        }
        
        return $split_events;
    }

    public function addCartCouponInEvent($quote, $split_events){
        $coupon_code = strtoupper($quote->getCouponCode());
	
	try{
	    if(!empty($split_events['affect_coupons']['matched']) && !empty($coupon_code)){
		foreach($split_events['affect_coupons']['matched'] as $item){
		    if($item['applied'] && !empty($item['coupon_code'])){
			if(strtoupper(trim($item['coupon_code'])) == trim($coupon_code)){return $split_events;}
		    }
		}
	    }
	}catch(Exception $ex){}
	
        //if coupon_code is null, check coin code ty show UI for cancel coupon code
        if (empty($coupon_code)){
            $coinObj = Mage::getSingleton('core/session')->getFhsCoin();
            if ($coinObj != null){
                $coupon_code = $coinObj['code'];
            }
        }
        $freeship_coupon_code = strtoupper($quote->getFreeshipCode());
        
        $code_rules = $this->getRuleInfoByCouponCode(array($coupon_code, $freeship_coupon_code));
        
        if (!empty($coupon_code) && !in_array($coupon_code, array_column($split_events['affect_coupons']['matched'], 'coupon_code'))
                && !in_array($coupon_code, array_column($split_events['affect_coupons']['not_matched'], 'coupon_code'))
                ){
            array_unshift($split_events['affect_coupons']['matched'],$this->createEventFromCoupon($coupon_code, $code_rules, self::TYPE_EVENT_COUPON));
        }
        
        if (!empty($freeship_coupon_code) && !in_array($freeship_coupon_code, array_column($split_events['affect_freeships']['matched'], 'coupon_code'))
                && !in_array($freeship_coupon_code, array_column($split_events['affect_freeships']['not_matched'], 'coupon_code')
                ))
        {
            array_unshift($split_events['affect_freeships']['matched'], $this->createEventFromCoupon($freeship_coupon_code, $code_rules, self::TYPE_EVENT_FREESHIP));
        }
        return $split_events;
        
    }
    
    public function getRuleInfoByCouponCode($codes){
        $codes = array_filter($codes, function($e){
            return empty($e) == false;
        });
        if (count($codes) > 0)
        {
            $codes_str = implode("','", $codes);
            $query = "select r.rule_id, UPPER(sc.code) as code, rl.label from fhs_salesrule r join fhs_salesrule_coupon sc on sc.rule_id = r.rule_id "
                    . " join fhs_salesrule_label rl on rl.rule_id = r.rule_id and rl.store_id = 0 "
                    . "where sc.code in ('" . $codes_str . "')"
                    . "UNION "
                    . "select c.campaign_id as rule_id, UPPER(c.code) as code , cc.campaign_name as label from fhs_coin c "
                    . "join fhs_coin_campaign cc on cc.campaign_id  = c.campaign_id "
                    . "where c.code  in ('" . $codes_str . "') ";
            $read = Mage::getSingleton("core/resource")->getConnection("core_read");
            $rs = $read->fetchAll($query);
            return $rs;
        }
        return null;
    }
    
    public function createEventFromCoupon($coupon_code, $code_rules, $event_type){
        $cur_rule = array_filter($code_rules, function($e) use ($coupon_code){
            return $e['code'] == $coupon_code;
        });
        $label = count($cur_rule) > 0 ? $cur_rule[0]['label'] : null;
        return array(
            "action_type" => "salesrule",
            "title" => $coupon_code,
            "is_auto" => true,
            "matched" => true,
            "coupon_code" => $coupon_code,
            "rule_content" => $this->createDynamicRuleContent(null, $coupon_code, $label),
            "reach_percent" => 100,
            "sub_total" => "",
            "min_total" => "0d",
            "max_total" => "",
            "event_type" => $event_type,
            "almost_run_out" => false,
            "applied" => true,
            "title_2" => $label,
        );
    }
    
    public function checkEventCartWithQuote($quote, $billing){
        $cart = array(
            "billing" => $billing,
            "quote" => $quote
        );
        
        return $this->validateEvent($cart);
    }

    public function validateEvent($cart, $rule_ids = null, $is_show = 1) {
        $result = array();
        $events = $this->getActiveEvents($rule_ids, $is_show);

        $needRedis = $this->checkEventNeedRedis($events);
        if ($needRedis) {
            $helper_redis = Mage::helper("flashsale/redis");
            $redis_client = $helper_redis->createRedisClientEventCart();

            if (!$redis_client->isConnected())
            {
                return null;
            }
        }

        foreach ($events as $event) {
            $is_matched = false;
            $error = null;
            $rank = array();
            $filters = $event['filters'];
            
            $is_in_stock = false; //check if event has any option which is in stock
            $almost_run_out = false; //option of action is fully (not run out of)
            foreach ($filters as $filter) {
                //option active: customer can choose it (has times_used)
                //option in_active: option is out of stock times_used or cart is not enough to get promotion
                //- at this time: active flag is only for checking option is out of stock times_used 
                //  because the second case (cart is not enough to get promotion) is setted in below code
                $in_stock_options = array_filter($filter["action"]["options"], function($opt){
                   if ($opt["active"]){
                       return $opt;
                   }
                });
                $almost_run_out_options = array_filter($filter["action"]["options"], function($opt){
                   if ($opt["almost_run_out"]){
                       return $opt;
                   }
                });
                //if there are no options left, go to next otion of event
                if (count($in_stock_options) == 0 && $is_show == 1){
                    continue;
                }
                if (count($almost_run_out_options) >= 1){
                    $almost_run_out = true;
                }
                $is_in_stock = true;
                $validateRs = $this->validateRule($filter["conditions"], $cart, $redis_client, $event['event_type']);
		$filterTemp = $filter["action"];
                
                if (!$validateRs['matched']) {
                    $filterTemp = $filter["action"];
                    $filterTemp["options"] = array_map(function($arr){
                        if (!$arr["default"]){
                            $arr["active"] = false;
                        }
                        return $arr;
                    }, $filterTemp["options"]);
                } 
              
                $matched = $validateRs['matched'];
                $is_matched = $matched || $is_matched;
                $rank[] = array_merge($filterTemp, array("matched_items" => $validateRs["matched_items"],
                    "reach_percent" => $validateRs["reach_percent"],
                    "matched" => $matched,
                    "sub_total" => $validateRs["sub_total"],
                    "min_total" => $validateRs["min_total"],
                    "max_total" => $validateRs["max_total"],
                    "need_total" => $validateRs["need_total"],
                    "error" => $validateRs["messages"],
                    "payment_method" => $validateRs["payment_method"]));
            }
            if (!$is_matched && count($rank) > 0){
                $error = $rank[0]["error"];
            }
            
            //only return event if event has in_stock options
            if ($is_in_stock){
                //is_matched: with the event, whether cart matches any rule in event
                $result[] = array(
                    "event_id" => $event["event_id"],
                    "form_ui" => $event["form_ui"],
                    "is_affect_item" => $event["is_affect_item"],
                    "event_type" => $event["event_type"],
                    "order_index" => (int) $event["order_index"],
                    "rank" => $rank,
                    "error" => $error,
                    "almost_run_out" => $almost_run_out,
                    'to_date' => $event['to_date'],
                );
            }
        }
        
        
        if ($needRedis) {
            $redis_client->close();
        }

        return $result;
    }

    public function checkEventNeedRedis($events) {
        foreach ($events as $event){
            $filters = $event["filters"];
            foreach ($filters as $filter){
                foreach($filter['conditions'] as $condition){
                    if ($value["min_value"] == null && $value["max_value"] == null){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function parsePaymentEvent($events){
        $result = null;
        $payments = \Mage::getSingleton('payment/config')->getActiveMethods();
        $payment_arr = array();
        foreach ($payments as $paymentCode => $paymentModel)
        {
            if ($paymentModel->canUseCheckout())
            {
                $payment_arr[] = $paymentCode;
            }
        }
        
        array_multisort(array_column($events, 'order_index'), SORT_ASC, $events);
        foreach($events as $event){
            foreach ($event['rank'] as $rank){
                if (in_array($rank["payment_method"], $payment_arr) && !$result[$rank["payment_method"]]){
                    $temp = array();
                    $title_arr = explode(";", $rank["title"]);
                    if (count($title_arr) == 2)
                    {
                        $temp["title"] = trim($title_arr[1]);
                    }

                    $temp["rule_content"] = $rank["rule_content"];
                    $result[$rank["payment_method"]] = $temp;
                }
            }
            
        }
        return $result;
    }
    
    public function createTitleEvent($title)
    {
        $title_arr = explode(";", $title);
        $rs = array();
        if (count($title_arr) == 2)
        {
            $title_1 = $title_arr[0];
            $title_2 = $title_arr[1];
            $rs["title"] = trim($title_1);
            $rs["title_2"] = trim($title_2);
        }
        return $rs;
    }

    public function parseEventsInCart($events, $is_full_process, $separate_coupon, $quote)
    {
        $affect_payments_checkout = null;
        if ($is_full_process)
        {
            $affect_payments_checkout = array_filter($events, function($e) {
                if (in_array($e['event_type'], self::TYPE_AFFECT_PAYMENT_CHECKOUT))
                {
                    return $e;
                }
            });

            $affect_payments_checkout = $this->parsePaymentEvent($affect_payments_checkout);

        }
        
        //get payment for cart
        $affect_payments = array();

        $affect_payment_events = array_filter($events, function($e) {
            if ($e['event_type'] == self::TYPE_EVENT_PAYMENT)
            {
                return $e;
            }
        });

        $affect_payments_temp = $this->parseAffectEventCart($affect_payment_events, $quote);
        $affect_payments_temp = array_map(function($e) {
            $title = $e["title"];
            $title_arr = explode(";", $title);
            if (count($title_arr) == 2)
            {
                $title_1 = $title_arr[0];
                $title_2 = $title_arr[1];
                $e["title"] = trim($title_1);
                $e["title_2"] = trim($title_2);
            }
            return $e;
        }, $affect_payments_temp);

        $affect_payments["matched"] = array_values(array_filter($affect_payments_temp, function($e) {
                    return $e["matched"];
                }));

        $affect_payments["not_matched"] = array_values(array_filter($affect_payments_temp, function($e) {
                    return !$e["matched"];
                }));

        $affect_item_events = array_filter($events, function ($e){
            //get event_cart which is not coupon
            if ($e['is_affect_item'] && $e['event_type'] != self::TYPE_EVENT_COUPON){
                return $e;
            }
        });
        
        $affect_items = array();
        foreach ($affect_item_events as $event){
            foreach ($event['rank'] as $rank){
                if (count($rank['matched_items'])){
                    $items = array_map(function($e) use ($rank, $event) {
                        $e["promo_message"] = $rank['title'];
                        $e["reach_percent"] = $rank["reach_percent"];
                        $e["matched"] = $rank["matched"];
                        return $e;
                    }, $rank['matched_items']);

                    $affect_items = array_merge($affect_items, $items);
                    if ($is_full_process){
                        $affect_items = array_filter($affect_items, function($item){
                            if ($item["matched"]){
                                return $item;
                            }
                        });
                    }
                }
            }
        }
        
        if ($separate_coupon)
        {
            $affect_cart_events = array_filter($events, function ($e) {
                if (!$e['is_affect_item'] && ($e['event_type'] == self::TYPE_EVENT_SALE || $e['event_type'] == self::TYPE_EVENT_GIFT))
                {
                    return $e;
                }
            });
        } else {
            $affect_cart_events = array_filter($events, function ($e) {
                if (!$e['is_affect_item'])
                {
                    return $e;
                }
            });
        }

        $affect_carts_temp = $this->parseAffectEventCart($affect_cart_events, $quote);
        $affect_carts_temp = array_map(function($e) {
            $title = $e["title"];
            $title_arr = explode(";", $title);
            if (count($title_arr) == 2)
            {
                $title_1 = $title_arr[0];
                $title_2 = $title_arr[1];
                $e["title"] = trim($title_1);
                $e["title_2"] = trim($title_2);
            }
            return $e;
        }, $affect_carts_temp);

        $affect_carts["matched"] = array_values(array_filter($affect_carts_temp, function($e) {
                    return $e["matched"];
                }));

        $affect_carts["not_matched"] = array_values(array_filter($affect_carts_temp, function($e) {
            return !$e["matched"];
        }));


        $affect_coupons = array();
        if ($separate_coupon)
        {
            $affect_coupon_events = array_filter($events, function ($e) {
                if ($e['event_type'] == self::TYPE_EVENT_COUPON || $e['event_type'] == self::TYPE_EVENT_COUPON_PAYMENT)
                {
                    return $e;
                }
            });

            $affect_coupons_temp = $this->parseAffectEventCart($affect_coupon_events, $quote);
            $affect_coupons_temp = array_map(function($e) {
                $title = $e["title"];
                $title_arr = explode(";", $title);
                if (count($title_arr) == 2)
                {
                    $title_1 = $title_arr[0];
                    $title_2 = $title_arr[1];
                    $e["title"] = trim($title_1);
                    $e["title_2"] = trim($title_2);
                }
                return $e;
            }, $affect_coupons_temp);

            $affect_coupons["matched"] = array_values(array_filter($affect_coupons_temp, function($e) {
                        return $e["matched"];
                    }));

            $affect_coupons["not_matched"] = array_values(array_filter($affect_coupons_temp, function($e) {
                return !$e["matched"];
            }));
        }

        $affect_freeships = array();
        if ($separate_coupon)
        {
            $affect_freeship_events = array_filter($events, function ($e) {
                if ($e['event_type'] == self::TYPE_EVENT_FREESHIP || $e['event_type'] == self::TYPE_EVENT_FREESHIP_PAYMENT)
                {
                    return $e;
                }
            });

            $affect_freeships_temp = $this->parseAffectEventCart($affect_freeship_events, $quote);
            $affect_freeships_temp = array_map(function($e) {
                $title = $e["title"];
                $title_arr = explode(";", $title);
                if (count($title_arr) == 2)
                {
                    $title_1 = $title_arr[0];
                    $title_2 = $title_arr[1];
                    $e["title"] = trim($title_1);
                    $e["title_2"] = trim($title_2);
                }
                return $e;
            }, $affect_freeships_temp);

            $affect_freeships["matched"] = array_values(array_filter($affect_freeships_temp, function($e) {
                        return $e["matched"];
                    }));

            $affect_freeships["not_matched"] = array_values(array_filter($affect_freeships_temp, function($e) {
                        return !$e["matched"];
                    }));
        }


        $non_auto_events = array();
        foreach($events as $event){
            $non_auto_ranks = array_filter($event["rank"], function ($rank){
                if (!$rank["is_auto"]){
                    return $rank;
                }
            });
            if (count($non_auto_ranks) > 0){
                $event["rank"] = $non_auto_ranks;
                $non_auto_events[] = $event;
            }
        }
        
        
        return array(
            "affect_items" => $affect_items,
            "affect_carts" => $affect_carts,
            "affect_coupons" => $affect_coupons,
            "non_auto_events" => $non_auto_events,
            "affect_payments" => $affect_payments,
            "affect_freeships" => $affect_freeships,
            "affect_payments_checkout" => $affect_payments_checkout
        );
    }
    
    public function createDynamicRuleContent($title, $title1, $title2, $expired_date)
    {
        $title1_temp = $title2_temp = null;
        if($title){
            $titles = $this->createTitleEvent($title);
            if (count($titles) == 0)
            {
                return null;
            }
            
            $title1_temp = $titles['title'];
            $title2_temp = $titles['title_2'];
        }  else {
            $title1_temp = $title1;
            $title2_temp = $title2;
        }
        if (!$title1_temp && !$title2_temp){
            return null;
        }
        $style = '<style>.fhs-container {padding: 3px 0;}.bold-text {font-weight: bold;}</style>';
        $content = '<div class="fhs-container bold-text">' . $title1_temp . '</div>';
        if ($title2_temp)
        {
            $content .= '<div class="fhs-container">' . $title2_temp . '</div>';
        }
        if($expired_date){
            if (strtotime(($expired_date))){
                $expired_date = date('d/m/Y', strtotime($expired_date));
            }
            $content .= '<div class="fhs-container">Hạn sử dụng: ' . $expired_date . '</div>';
        }

        return $style . $content;
    }

    public function parseAffectEventCart($affect_cart_events, $quote)
    {
        $affect_carts = array();
        $applied_coupon_code = $quote->getCouponCode();
        $applied_freeship_coupon_code = $quote->getFreeshipCode();
        
        
        foreach ($affect_cart_events as $event)
        {
            //get nearer rank with almost rank
            //event has 2 rank: 100K-200K, 200K-500K, subtotal is 150 => show rank 1 + rank 2
            foreach ($event['rank'] as $rank)
            {
                $affect_cart_temp = $rank;
                //add coupon in action
                $affect_cart_temp["coupon_code"] = $rank["coupon_code"];
                
                if ($rank["rule_content"]){
                    $affect_cart_temp["rule_content"] = $rank["rule_content"];
                } else {
                    $affect_cart_temp['rule_content'] = $this->createDynamicRuleContent($rank['title'], null, null, $event['to_date']);
                }
                $affect_cart_temp["event_type"] = $event["event_type"];
                $affect_cart_temp["almost_run_out"] = $event["almost_run_out"];
                $affect_cart_temp["order_index"] = $event["order_index"];
                $affect_cart_temp["event_id"] = $event["event_id"];
                
                //check whether action was applied or not
                if ($affect_cart_temp["event_type"] == self::TYPE_EVENT_COUPON || $affect_cart_temp['event_type'] == self::TYPE_EVENT_COUPON_PAYMENT)
                {
                    //check if quote is applied by coupon code of rule in event cart
                    //check if coupon_code is contained in affect_freeship in case we set rule wrongly
                    if (!empty($applied_coupon_code) && (strtoupper($applied_coupon_code) == strtoupper($affect_cart_temp["coupon_code"]) 
                            || (!empty($applied_freeship_coupon_code) && strtoupper($applied_freeship_coupon_code) == strtoupper($affect_cart_temp["coupon_code"]))
                            ))
                    {
                        $affect_cart_temp["applied"] = true;
                        $affect_cart_temp["matched"] = true;
                    }
                    else
                    {
                        $affect_cart_temp["applied"] = false;
                    }
                } else if ($affect_cart_temp["event_type"] == self::TYPE_EVENT_FREESHIP || $affect_cart_temp['event_type'] == self::TYPE_EVENT_FREESHIP_PAYMENT){
                     //check if quote is applied by coupon code of rule in event cart
                    if (!empty($applied_freeship_coupon_code) && (strtoupper($applied_freeship_coupon_code) == strtoupper($affect_cart_temp["coupon_code"])
                            || (!empty($applied_coupon_code) && strtoupper($applied_coupon_code) == strtoupper($affect_cart_temp["coupon_code"]))
                            ))
                    {
                        $affect_cart_temp["applied"] = true;
                        $affect_cart_temp["matched"] = true;
                    }
                    else
                    {
                        $affect_cart_temp["applied"] = false;
                    }
                }
                else if ($affect_cart_temp["matched"])
                {
                    $affect_cart_temp["applied"] = true;
                }


                if ($event["form_ui"])
                {
                    $form_ui = (array) ($event["form_ui"]);
                    $affect_cart_temp["page_detail"] = $form_ui["page_detail"];
                }

                //remove some error because we don't need show it in UI
                //errors displayed in UI
                $errors = array();
                foreach ($affect_cart_temp["error"] as $error)
                {
                    if (in_array($error["type"], self::DISPLAY_TYPE_ERRORS))
                    {
                        $errors[] = $error;
                    }
                }

                $affect_cart_temp["error"] = $errors;

                if ($is_full_process)
                {
                    if ($affect_cart_temp["matched"])
                    {
                        $affect_carts[] = $affect_cart_temp;
                    }
                }
                else
                {
                    $min_reach_percent = Mage::getStoreConfig('eventcart_config/config/min_reach_percent');
                    //get nearer rank with almost rank
                    //event has 2 rank: 100K-200K, 200K-500K, subtotal is 250
                    //not show rank 1
                    //reach_percent >= 100: sub_total is matched but there are some rule not matched (ex: num items)
                    //ex: event has 1 rank => show
                    //event has 3 rank => show rank mmatched min_reach_percent
                    if (($affect_cart_temp["reach_percent"] >= $min_reach_percent) && ($affect_cart_temp["matched"] || count($event['rank']) == 1 || $affect_cart_temp["reach_percent"] < 100
                            ))
                    {
                        $affect_carts[] = $affect_cart_temp;
                    }
                }
            }
        }

        if (count($affect_carts) > 0)
        {
            array_multisort(array_column($affect_carts, 'reach_percent'), SORT_DESC, $affect_carts);
        }

        $full_affect_carts = array_filter($affect_carts, function($e) {
            if ($e['reach_percent'] == 100)
            {
                return $e;
            }
        });

        $affect_carts = array_filter($affect_carts, function($e) {
            if ($e['reach_percent'] < 100)
            {
                return $e;
            }
        });
        $affect_carts = array_merge($affect_carts, $full_affect_carts);
        array_multisort(array_column($affect_carts, 'order_index'), SORT_ASC, $affect_carts);

        return $affect_carts;
    }

    public function getAlmostRankFromEvent($event){
        if (count($event["rank"]) == 0){
            return null;
        }
        
        //default is first rank
        $reach_percent_arr = array_column($event["rank"], "reach_percent");
        $max_index = array_keys($reach_percent_arr, max($reach_percent_arr));
        $result = $event["rank"][$max_index[0]];
        return $result;
    }


    public function validateRule($conditions, $cart, $redis_client, $event_type){
        //in default, all the rules are connected with & operator
        $isAndRule = true; 
        
        $matched = true; //defautl is true
        $messages = array();
        $matched_items = null; //list products are matched with rule (type one_of_product - affect_item = true)
        $reach_percent = 0;
        
        //set 4 value based on sub_total or sub_total_for_subset. There is no case 2 conditions occur in a rule
        $sub_total = 0; //total is displayed in progress circle, it is total for subset or all items
        $min_total = 0; //used in UI
        $max_total = 0; //used in UI
        $need_total = null; //used in UI
        
        $payment_method = null; //used for calculated promotion payment in checkout view
     
        $isAffectCart = $this->checkFilterAffectCart($conditions);
//        if ($isAffectCart){
        $afterConditions = array();
        $matchedCartItems = null;
//        }
        
        $num_rules_percent = $this->calculateRuleAffectPercent($conditions);
        //check filtes which is NOT be affected by the sibling filters
        foreach ($conditions as $condition){
            switch ($condition["filter_type"])
            {
                case self::TYPE_SUB_TOTAL:
                    $validate_rs = $this->validateCartTotal($condition, $cart);
                    if (!$validate_rs["matched"]){
                        $matched &= false;
                        $messages[] = array(
                            "type" => self::TYPE_SUB_TOTAL,
                            "message" => $this->getErrorPriceRange($condition, self::ERROR_SUB_TOTAL)
                        );
                    }
                    $reach_percent = $validate_rs["reach_percent"];
                    $sub_total = Mage::helper('core')->formatPrice($validate_rs["sub_total"], false);
                    $max_total = Mage::helper('core')->formatPrice($validate_rs["max_total"], false);
                    $min_total = Mage::helper('core')->formatPrice($min_total, false);
                    $need_total_temp = $validate_rs["max_total"] - $validate_rs["sub_total"];
                    if ($need_total_temp > 0){
                        $need_total = Mage::helper('core')->formatPrice($need_total_temp, false);
                    }
                    
                    break;
                case self::TYPE_ALL_PRODUCT:
                    $cartItems = $this->getCartItems($cart);
                    $classifyProducts = $this->classifyAllProductWithRule($condition, $cartItems, $redis_client);
                    $matchedCartItems = $classifyProducts["matched_products"];
                    //if carts has any product which is not matched list product marketing supply
                    if (count($classifyProducts["matched_products"]) != count($cartItems)) {
                        $matched &= false;
                        $messages[] = array(
                            "type" => self::TYPE_ERROR_NOT_MATCHED_PRODUCT,
                            "message" => self::ERROR_NOT_MATCHED_PRODUCT,
                            "products" => $classifyProducts["not_matched_products"]
                        );
                    }
                    break;
                case self::TYPE_ONE_OF_PRODUCT:
                    //condition: one product which is matched a below rule
                    //now, not yet handle
                    $cartItems = $this->getCartItems($cart);
                    $classifyProducts = $this->classifyProductsWithOneOfProduct($condition, $cartItems, $redis_client);
                    $matchedCartItems = $classifyProducts["matched_products"];
                    //no need check matched: because check some products in cart 
                    $messages[] = array(
                        "type" => self::TYPE_ERROR_NOT_MATCHED_PRODUCT,
                        "message" => self::ERROR_NOT_MATCHED_PRODUCT,
                        "products" => $classifyProducts["not_matched_products"]
                    );
                    $matched_items = $matchedCartItems;
                    break;
                case self::TYPE_CATEGORY_MID_ID:
                    $cartItems = $this->getCartItems($cart);
                    $classifyProducts = $this->classifyProductsWithCategoryId($condition, $cartItems, $redis_client);
                    $matchedCartItems = $classifyProducts["matched_products"];
                    //no need check matched: because check some products in cart 
                    $messages[] = array(
                        "type" => self::TYPE_ERROR_NOT_MATCHED_PRODUCT,
                        "message" => self::ERROR_NOT_MATCHED_PRODUCT,
                        "products" => $classifyProducts["not_matched_products"]
                    );
                    break;
                case self::TYPE_SUPPLIER:
                    $cartItems = $this->getCartItems($cart);
                    $classifyProducts = $this->classifyProductsWithAttribute($condition, $cartItems, $redis_client, self::TYPE_SUPPLIER);
                    $matchedCartItems = $classifyProducts["matched_products"];
                    //no need check matched: because check some products in cart 
                    $messages[] = array(
                        "type" => self::TYPE_ERROR_NOT_MATCHED_PRODUCT,
                        "message" => self::ERROR_NOT_MATCHED_PRODUCT,
                        "products" => $classifyProducts["not_matched_products"]
                    );
                    break;
                case self::TYPE_APPLY_DISTRICT:
                    if (!$this->validateApplyDistrict($condition, $cart, $redis_client)) {
                        $matched &= false;
                        $messages[] = array(
                            "type" => self::TYPE_APPLY_DISTRICT,
                            "message" => self::ERROR_APPLY_DISTRICT
                        );
                    }
                    break;
                case self::TYPE_APPLY_PROVINCE:
                    $validate_rs = $this->validateApplyProvince($condition, $cart, $redis_client);
                    if (!$validate_rs["matched"])
                    {
                        $matched &= false;
                        
                        $messages[] = array(
                            "type" => self::TYPE_APPLY_DISTRICT,
                            "message" => $validate_rs['message']
                        );
                    }
                    break;
                case self::TYPE_SUB_TOTAL_FOR_SUBSET:
                    $afterConditions[] = $condition;
                    break;
                case self::TYPE_QTY_OF_PRODUCT:
                    $afterConditions[] = $condition;
                    break;
                case self::TYPE_LOGIN:
                    if (!$cart["quote"]->getCustomerId()){
                        $matched &= false;
                        $messages[] = array(
                            "type" => self::TYPE_LOGIN,
                            "message" => self::ERROR_LOGIN
                        );
                    }
                    break;
                case self::TYPE_NUM_ITEMS_FOR_SUBSET:
                    $afterConditions[] = $condition;
                    break;
                case self::TYPE_NUM_ITEMS:
                    $cartItem = $cart["quote"]->getShippingAddress()->getAllVisibleItems();
                    $validate_rs = $this->validateNumItemsForSubset($condition, $cartItem);
                    if (!$validate_rs["matched"])
                    {
                        $matched &= false;
                        $messages[] = array(
                            "type" => self::TYPE_NUM_ITEMS,
                            "message" => $this->getErrorPriceRange($condition, self::ERROR_NUM_ITEMS, false)
                        );
                    }

                    //if there is sub_total condition, it is high priority
                    if (!$isAffectCart)
                    {
                        $reach_percent = $validate_rs["reach_percent"];
                        $sub_total = $validate_rs["sub_total"] . self::PRODUCT_UNIT;
                        $max_total = $validate_rs["max_total"] . self::PRODUCT_UNIT;
                        $min_total = $min_total . self::PRODUCT_UNIT;
                        $need_total_temp = $validate_rs["max_total"] - $validate_rs["sub_total"];
                        if ($need_total_temp > 0)
                        {
                            $need_total = $need_total_temp . self::PRODUCT_UNIT;
                        }
                    }
                    break;
                   case self::TYPE_STORE_ID:
                    $validate_rs = $this->validateStoreId($condition, $cart, $redis_client);
                    if (!$validate_rs["matched"])
                    {
                        $matched &= false;
                        
                        $messages[] = array(
                            "type" => self::TYPE_STORE_ID,
                            "message" => $validate_rs['message']
                        );
                    }

                    break;
                case self::TYPE_PAYMENT_METHOD:
                    $validate_rs = $this->validatePaymentMethod($condition, $cart, $redis_client);
                    $payment_method = $validate_rs["payment_method"];
                    if (!$validate_rs["matched"])
                    {
                        if ($event_type != self::TYPE_EVENT_PAYMENT){
                            $matched &= false;
                            $messages[] = array(
                                "type" => self::TYPE_PAYMENT_METHOD,
                                "message" => $validate_rs['message']
                            );
                        }
                    }
                    break;
                default:
            }
        }
        
        //check filtes which is be affected by the sibling filters
        foreach ($afterConditions as $condition2) {
            switch ($condition2["filter_type"]){
                case self::TYPE_SUB_TOTAL_FOR_SUBSET:
                    $validate_rs = $this->validateCartTotalForSubset($condition2, $matchedCartItems);
                    if (!$validate_rs["matched"]) {
                        $matched &= false;
                        $messages[] = array(
                            "type" => self::TYPE_SUB_TOTAL_FOR_SUBSET,
                            "message" => $this->getErrorPriceRange($condition2, self::ERROR_SUB_TOTAL_FOR_SUBSET)
                        );
                    }
                    $reach_percent = $validate_rs["reach_percent"];
                    $sub_total = Mage::helper('core')->formatPrice($validate_rs["sub_total"], false);
                    $max_total = Mage::helper('core')->formatPrice($validate_rs["max_total"], false);
                    $min_total = Mage::helper('core')->formatPrice($min_total, false);
                    $need_total_temp = $validate_rs["max_total"] - $validate_rs["sub_total"];
                    if ($need_total_temp > 0){
                        $need_total = Mage::helper('core')->formatPrice($need_total_temp, false);
                    }
                    
                    break;
                case self::TYPE_QTY_OF_PRODUCT:
                    $validate_rs = $this->validateQtyOfProduct($condition2, $matchedCartItems);
                     if (!$validate_rs["matched"]) {
                        $matched &= false;
                        $messages[] = array(
                            "type" => self::TYPE_QTY_OF_PRODUCT,
                            "message" => $this->getErrorPriceRange($condition2, self::ERROR_QTY_OF_PRODUCT, false)
                        );
                    }
                    
                    $reach_percent = $validate_rs["reach_percent"];
                case self::TYPE_NUM_ITEMS_FOR_SUBSET:
                    $validate_rs = $this->validateNumItemsForSubset($condition2, $matchedCartItems);
                    if (!$validate_rs["matched"]) {
                        $matched &= false;
                        $messages[] = array(
                            "type" => self::ERROR_NUM_ITEMS_FOR_SUBSET,
                            "message" => $this->getErrorPriceRange($condition2, self::ERROR_NUM_ITEMS_FOR_SUBSET)
                        );
                    }
                    //if there is sub_total condition, it is high priority
                    if (!$isAffectCart)
                    {
                        $reach_percent = $validate_rs["reach_percent"];
                        $sub_total = $validate_rs["sub_total"] . self::PRODUCT_UNIT;
                        $max_total = $validate_rs["max_total"] . self::PRODUCT_UNIT;
                        $min_total = $min_total . self::PRODUCT_UNIT;
                        $need_total_temp = $validate_rs["max_total"] - $validate_rs["sub_total"];
                        if ($need_total_temps > 0)
                        {
                            $need_total = $need_total_temp . self::PRODUCT_UNIT;
                        }
                    }

                    break;
                default:
            }
        }
        
        //rule is not matched, reset matched_items
        //do not need reset -> need show items if cart almost reach
//        if (!$matched){
//            $matched_items = null;
//        }

        return array(
            "matched" => $matched,
            "messages" => $messages,
            "matched_items" => $matched_items,
            "reach_percent" => $reach_percent,
            "sub_total" => $sub_total,
            "min_total" => $min_total,
            "max_total" => $max_total,
            "need_total" => $need_total,
            "payment_method" => $payment_method,
        );
    }
    
    public function validateStoreId($condition, $cart, $redis_client)
    {
        $store_id = $cart['quote']->getStore()->getId();
        $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $condition["filter_id"];
        $value = $redis_client->get($filterKey);
        
        $matched = false;
        $message = null;
        if ($value)
        {
            $value = (array) json_decode($value);
            if (in_array($store_id, $value["values"])){
                $matched = true;
            }
        }
        if (!$matched){
           if (empty($value["error_message"]))
            {
                if ($store_id == 4)
                {
                    $message = self::ERROR_STORE_ID_WEB;
                }
                else
                {
                    $message = self::ERROR_STORE_ID_MOBILE;
                }
            }
            else
            {
                $message = $value["error_message"];
            }
        }
        return array(
            "matched" => $matched,
            "message" => $message
        );
    }
    
    public function validatePaymentMethod($condition, $cart, $redis_client){
        $payment_method =$cart["quote"]->getPayment()->getMethod();
        
        $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $condition["filter_id"];
        $value = $redis_client->get($filterKey);
        
        $matched = false;
        $message = null;
        $payment_rule = null;
        if ($value)
        {
            $value = (array) json_decode($value);
            if (count($value["values"]) > 0){
                $payment_rule = $value["values"][0];
            }
            //in default, values has only one payment_method
            if (in_array($payment_method, $value["values"])){
                $matched = true;
            }
        }
        if (!$matched){
            if (empty($value["error_message"]))
            {
                $all_payment = "";
                foreach ($value["values"] as $key => $method)
                {
                    if ($key > 0)
                    {
                        $all_payment .= ", ";
                    }
                    $all_payment .= Mage::getStoreConfig('payment/' . $method . '/title');
                }

                $message = "Áp dụng cho " . $all_payment;
            }
            else
            {
                $message = $value["error_message"];
            }
        }
        return array(
            "matched" => $matched,
            "message" => $message,
            "payment_method" => $payment_rule,
        );
    }

    public function calculateRuleAffectPercent($conditions){
        $count = 0;
        $has_login_type = false;
        
        $affect_percent = 0;
        foreach($conditions as $condition){
            if (in_array($condition["filter_type"], self::RULES_AFFECT_PERCENT)){
                $count++;
            }
            
            if ($condition["filter_type"] == self::TYPE_LOGIN){
                $has_login_type = true;
            }
        }
        
        //if count = 0 => set count = 1 to division
        if ($count == 0){
            $count = 1;
        }
        
        if ($has_login_type){
            //set type_login affect 10%
            $affect_percent = (100 - self::TYPE_LOGIN_AFFECT_PERCENT)/$count;
        } else {
            $affect_percent = 100/$count;
        }
        
        //return 0.1 -> 1
        return $affect_percent/100;
    }

    public function checkFilterAffectCart($conditions)
    {
        //filter affect cart: cart_total_for_subset, sub_total
        $filter_types = array_column($conditions, "filter_type");
        foreach ($filter_types as $filter_type)
        {
            if (in_array($filter_type, self::RULES_AFFECT_PERCENT))
            {
                return true;
            }
        }

        return false;
    }

    public function validateCartTotal($condition, $cart)
    {
        $shippingAddress = $cart["quote"]->getShippingAddress();
        $subTotal = $shippingAddress->getSubtotalInclTax();
        $minValue = (float) $condition["min_value"];
        $maxValue = (float) $condition["max_value"];
        if ($subTotal > 0 && (!$minValue || $subTotal >= $minValue) && (!$maxValue || $subTotal <= $maxValue))
        {
            return array(
                "matched" => true,
                "reach_percent" => 100,
                "sub_total" => $subTotal,
                "max_total" => $minValue
            );
        }
        
        $reach_percent = round(($subTotal * 100) / $minValue);
        if ($reach_percent > 100){
            $reach_percent = 100;
        }
        return array(
            "matched" => false,
            "reach_percent" => $reach_percent,
            "sub_total" => $subTotal,
            "max_total" => $minValue
        );
    }
    

    public function getErrorPriceRange($condition, $message, $formatPrice = true){
        $minValue = (float) $condition["min_value"];
        $maxValue = (float) $condition["max_value"];
        if ($formatPrice){
            $formattedMinValue = Mage::helper('core')->formatPrice($minValue, false);
            $formattedMaxValue = Mage::helper('core')->formatPrice($maxValue, false);
        } else {
            $formattedMinValue = $minValue;
            $formattedMaxValue = $maxValue;
        }

        if ($minValue && !$maxValue){
            $message .= " >= $formattedMinValue";
        } else if (!$minValue && $maxValue){
            $message .= " <= $formattedMaxValue";
        } else if ($minValue && $maxValue){
            $message .= " " . $formattedMinValue . " - " . $formattedMaxValue;
        }
        return $message;
    }
    
    public function validateCartTotalForSubset($condition, $cartItems) {
        $subTotal = 0;
        foreach ($cartItems as $cartItem) {
            $subTotal += $cartItem["price"] * $cartItem["qty"];
        }

        $minValue = (float) $condition["min_value"];
        $maxValue = (float) $condition["max_value"];

        if ($subTotal > 0 && (!$minValue || $subTotal >= $minValue) && (!$maxValue || $subTotal <= $maxValue)) {
            return array(
                "matched" => true,
                "reach_percent" => 100,
                "sub_total" => $subTotal,
                "max_total" => $minValue
            );
        }

        $reach_percent = round(($subTotal * 100) / $minValue);
        //for case subtotal > min_value
        if ($reach_percent > 100){
            $reach_percent = 100;
        }
        return array(
            "matched" => false,
            "reach_percent" => $reach_percent,
            "sub_total" => $subTotal,
            "max_total" => $minValue
        );
    }
    
    public function validateNumItemsForSubset($condition, $cartItems){
        //return sub_total: the same sub total for subset to show UI
        $numItems = 0;
        foreach ($cartItems as $cartItem) {
            $numItems +=  $cartItem["qty"];
        }

        $minValue = (float) $condition["min_value"];
        $maxValue = (float) $condition["max_value"];
        
        if ($numItems > 0 && (!$minValue || $numItems >= $minValue) && (!$maxValue || $numItems <= $maxValue))
        {
            return array(
                "matched" => true,
                "reach_percent" => 100,
                "sub_total" => $numItems,
                "max_total" => $minValue
            );
        }
        
        $reach_percent = round(($numItems * 100) / $minValue);
        //for case subtotal > min_value
        if ($reach_percent > 100){
            $reach_percent = 100;
        }
        return array(
            "matched" => false,
            "reach_percent" => $reach_percent,
            "sub_total" => $numItems,
            "max_total" => $minValue
        );
    }
    
    public function validateQtyOfProduct($condition, $cartItems){
        if (count($cartItems) == 0)
        {
            return array(
                "matched" => false,
                "reach_percent" => 0
            );
        }

        $minValue = (float) $condition["min_value"];
        $maxValue = (float) $condition["max_value"];
        
        foreach ($cartItems as $item)
        {
            if (($minValue && $item['qty'] < $minValue) || ($maxValue && $item['qty'] > $maxValue))
            {
                $reach_percent = round(($item['qty'] * 100) / $minValue);
                return array(
                    "matched" => false,
                    "reach_percent" => $reach_percent
                );
            }
        }

        return array(
            "matched" => true,
            "reach_percent" => 100
        );
    }

    public function getCartItems($cart) {
        $items = $cart["quote"]->getAllVisibleItems();
        $cartItems = array();
        foreach ($items as $product_item) {
	    if (!$product_item["is_free_product"]){
		$cartItems[] = array(
		    "product_id" => $product_item["product_id"],
		    "name" => $product_item["name"],
		    "price" => $product_item["price_incl_tax"],
		    "qty" => $product_item["qty"],
		    "image" => Mage::helper('catalog/image')->init($product_item->getProduct(), 'thumbnail')->resize(270, 364)->__toString(),
                    "category_mid_id" => $product_item->getProduct()["category_mid_id"],
                    "category_main_id" => $product_item->getProduct()["category_main_id"],
                    "category_3_id" => $product_item->getProduct()["category_1_id"],
                    "supplier" => $product_item->getProduct()["supplier"],
		);
	    }
        }

        return $cartItems;
    }

    
    
    //return array: matched_product + NOT matched_product
    public function classifyAllProductWithRule($condition, $cartItems, $redis_client){
        $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $condition["filter_id"];
        $value = $redis_client->get($filterKey);
        
        $matched_products = array();
        $not_matched_products = array();
        if ($value){
            $value = (array) json_decode($value);
            
            foreach($cartItems as $item){
                if (in_array($item["product_id"], $value["values"])){
                    $matched_products[] = $item;
                } else {
                    $not_matched_products[] = array(
                        "product_id" => $item["product_id"],
                        "name" => $item["name"],
                        "image" => $item["image"],
                        "price" => $item["price"],
                        "qty" => $item["qty"],
                    );
                }
            }
        }
        
        return array(
            "matched_products" => $matched_products,
            "not_matched_products" => $not_matched_products
        );
    }

    //return array: matched_product + NOT matched_product in category_mid rule
    public function classifyProductsWithAttribute($condition, $cartItems, $redis_client, $attribute){
        $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $condition["filter_id"];
        $value = $redis_client->get($filterKey);
        
        $matched_products = array();
        $not_matched_products = array();
        if ($value){
            $value = (array) json_decode($value);
            
            foreach($cartItems as $item){
                if (in_array(strtoupper($item[$attribute]), $value["values"])){
                    $matched_products[] = $item;
                } else {
                    $not_matched_products[] = array(
                        "product_id" => $item["product_id"],
                        "name" => $item["name"],
                        "image" => $item["image"],
                        "price" => $item["price"],
                        "qty" => $item["qty"],
                    );
                }
            }
        }
        
        return array(
            "matched_products" => $matched_products,
            "not_matched_products" => $not_matched_products
        );
    }
    
     //return array: matched_product + NOT matched_product in category_mid rule
    public function classifyProductsWithCategoryId($condition, $cartItems, $redis_client){
        $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $condition["filter_id"];
        $value = $redis_client->get($filterKey);
        
        $matched_products = array();
        $not_matched_products = array();
        
        $include = true;
        $operator = $condition['filter_operator'];
        if ($operator && $operator == self::FILTER_OPERATOR_DIFRRERENT){
            $include = false;
        }
        
        if ($value){
            $value = (array) json_decode($value);
            
            foreach ($cartItems as $item)
            {
                if ($include)
                {
                    if (in_array(($item["category_main_id"]), $value["values"]) 
                            || in_array(($item["category_mid_id"]), $value["values"]) 
                            || in_array(($item["category_3_id"]), $value["values"]))
                    {
                        $matched_products[] = $item;
                    }
                    else
                    {
                        $not_matched_products[] = array(
                            "product_id" => $item["product_id"],
                            "name" => $item["name"],
                            "image" => $item["image"],
                            "price" => $item["price"],
                            "qty" => $item["qty"],
                        );
                    }
                }
                else
                {
                    if (!in_array(($item["category_main_id"]), $value["values"]) 
                            && !in_array(($item["category_mid_id"]), $value["values"]) 
                            && !in_array(($item["category_3_id"]), $value["values"]))
                    {
                        $matched_products[] = $item;
                    }
                    else
                    {
                        $not_matched_products[] = array(
                            "product_id" => $item["product_id"],
                            "name" => $item["name"],
                            "image" => $item["image"],
                            "price" => $item["price"],
                            "qty" => $item["qty"],
                        );
                    }
                }
            }
        }
        
        return array(
            "matched_products" => $matched_products,
            "not_matched_products" => $not_matched_products
        );
    }
     
    public function classifyProductsWithOneOfProduct($condition, $cartItems, $redis_client){
        $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $condition["filter_id"];
        $value = $redis_client->get($filterKey);
        
        $matched_products = array();
        $not_matched_products = array();
        if ($value){
            $value = (array) json_decode($value);
            
            foreach($cartItems as $item){
                if (in_array($item["product_id"], $value["values"])){
                    $matched_products[] = $item;
                } else {
                    $not_matched_products[] = array(
                        "product_id" => $item["product_id"],
                        "name" => $item["name"],
                        "image" => $item["image"],
                        "price" => $item["price"],
                        "qty" => $item["qty"],
                    );
                }
            }
        }
        
        return array(
            "matched_products" => $matched_products,
            "not_matched_products" => $not_matched_products
        );
    }
    
    public function validateApplyDistrict($condition, $cart, $redis_client){
        $shippingAddress = $cart["quote"]->getShippingAddress();
        
        $helperCity = Mage::helper('vietnamshipping');
        $province_id = ($shippingAddress->getRegionId());
        $cities =  ($helperCity->getCitiesByRegion($province_id));
        $district_name = $shippingAddress->getCity();
        $index_district = array_search($district_name, array_column($cities, "district_name"));
        
        //need to check with false value, because $index_district may be zezo
        if ($index_district === false){
            return false;
        }
        
        $curDistrict = $cities[$index_district];
                
        $district_id = $curDistrict["district_id"];
        $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $condition["filter_id"];
        $value = $redis_client->get($filterKey);
        if ($value){
            $value = (array) json_decode($value);
            if (in_array($district_id, $value["values"])){
                return true;
            }
        }
        
        return false;
    }
    
    public function validateApplyProvince($condition, $cart, $redis_client){
        $shippingAddress = $cart["quote"]->getShippingAddress();
        
        $province_id = ($shippingAddress->getRegionId());
        $filterKey = Fahasa_Eventcart_Helper_Redis::EVENTCART_FILTER . Fahasa_Eventcart_Helper_Redis::SEPERATOR . $condition["filter_id"];
        $value = $redis_client->get($filterKey);
        
        $matched = false;
        $message = null;
        if ($value){
            $value = (array) json_decode($value);
            if (in_array($province_id, $value["values"])){
                $matched = true;
            }
        }
        
       if (!$matched)
        {
            
            if (empty($value["error_message"]))
            {
                $all_province_matched = "";
                foreach ($value["values"] as $key => $province_id_matched)
                {
                    if ($key > 0)
                    {
                        $all_province_matched .= ", ";
                    }
                    $all_province_matched .= $province_id_matched;
                }

                if (!empty($all_province_matched))
                {
                    $query = "select default_name as province_name from fhs_directory_country_region where region_id in (" . $all_province_matched . ") ";
                    $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
                    $data = $readConnection->fetchAll($query);
                    if (count($data) > 0)
                    {
                        $message = "Áp dụng cho ";
                        foreach ($data as $key => $province)
                        {
                            if ($key > 0)
                            {
                                $message .= ", ";
                            }
                            $message .= $province['province_name'];
                        }
                    }
                }
            }
            else
            {
                $message = $value["error_message"];
            }
        }
        return array(
            "matched" => $matched,
            "message" => $message
        );
    }

    public function getActiveEvents($rule_ids = null, $is_show = 1)
    {
	$rule_ids_str = "";
	if(!empty($rule_ids)){
	    $rule_ids_str = implode(",",$rule_ids);
	    if(!empty($rule_ids_str)){
		$rule_ids_str = " and cao.value_int in (".$rule_ids_str.") ";
	    }
	}
	
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        
        //set times_used in buffetcombo = 0 because total_combo in fhs_event_buffetcombo is qty left (in_stock)
        $eventQuery = "select e.id as event_id, e.name, e.form_ui, e.affect_item, e.type as event_type, if(e.order_index is null, 999, e.order_index) as order_index, "
                . "cr.action_id as action_id, "
                . "ca.type as action_type, ca.title, ca.is_auto, cr.filter_id, cf.type as filter_type, cf.operator as filter_operator, cf.min_value, cf.max_value, "
                . "cao.id as option_id, cao.value as option_value, cao.name as option_name, cao.image as option_image, cao.qty as option_qty, "
                . "if (ca.type = 'salesrule', sr.uses_per_coupon, if(ca.type = 'buffetcombo', eb.total_combo, cao.times_limit)) as option_times_limit, "
                . "if (ca.type = 'salesrule', src.times_used, if (ca.type = 'buffetcombo', 0, cao.times_used)) as option_times_used, "
                . "stock.qty as stock_qty, stock.is_in_stock as stock_is_in_stock, "
                . "if (ca.type = 'salesrule', UPPER(src.code), if(e.type = 3 and cao.value = '1', UPPER(cao.name), '')) as coupon_code,"
                . "bl.content as rule_content, "
                . "e.to_date "
                . "from fhs_event_cart e "
                . "join fhs_event_cart_rule cr on e.id = cr.event_id "
                . "join fhs_event_cart_action ca on ca.id = cr.action_id "
                . "join fhs_event_cart_action_option cao on ca.id = cao.action_id ".$rule_ids_str
                . "join fhs_event_cart_filter cf on cr.filter_id = cf.id "
                . "left join fhs_cataloginventory_stock_item stock on ca.type = 'add_gift' and stock.product_id = cao.value_int "
                . "left join fhs_salesrule sr on ca.type = 'salesrule' and sr.rule_id = cao.value_int and sr.coupon_type = 2 and sr.use_auto_generation = 0 "
                . "left join fhs_salesrule_coupon src on sr.rule_id = src.rule_id "
                . "left join fhs_event_buffetcombo eb on ca.type = 'buffetcombo' and eb.id = cao.value_int "
                . "left join fhs_cms_block bl on bl.identifier = ca.block_detail_id "
                . "where now() between e.from_date and e.to_date and e.active = 1 and e.is_show = ".$is_show." and (e.day_of_week is null or find_in_set(dayofweek(now()), e.day_of_week)) "
                . "group by e.id, cr.action_id, cr.filter_id, cao.id "
                . "order by e.id, cr.action_id, cr.filter_id, cao.id";


        $eventRs = $readConnection->fetchAll($eventQuery);
        $data = array();
        if (count($eventRs) > 0)
        {
            foreach ($eventRs as $item)
            {
                if ($data[$item['event_id']]['event_id'] == null)
                {
                    $data[$item['event_id']]['event_id'] = $item['event_id'];
                    $data[$item['event_id']]['name'] = $item['name'];
                    $data[$item['event_id']]['form_ui'] = json_decode($item['form_ui']);
                    $data[$item['event_id']]['is_affect_item'] = $item['affect_item'] == "1" ? true : false;
                    $data[$item['event_id']]['event_type'] = (int) $item['event_type'];
                    $data[$item['event_id']]['order_index'] = (int) $item['order_index'];
                    $data[$item['event_id']]['to_date'] = $item['to_date'];
                }

                if ($data[$item['event_id']]['actions'][$item['action_id']] == null)
                {
                    $data[$item['event_id']]['actions'][$item['action_id']]['action_id'] = $item['action_id'];
                    $data[$item['event_id']]['actions'][$item['action_id']]['action_type'] = $item['action_type'];
//                    $data[$item['event_id']]['actions'][$item['action_id']]['form_ui'] = json_decode($item['form_ui']);
                    $data[$item['event_id']]['actions'][$item['action_id']]['title'] = $item['title'];
                    $data[$item['event_id']]['actions'][$item['action_id']]['is_auto'] = $item['is_auto'] == "1" ? true : false;
                    $data[$item['event_id']]['actions'][$item['action_id']]['coupon_code'] = $item['coupon_code'];
                    $data[$item['event_id']]['actions'][$item['action_id']]['rule_content'] = $item['rule_content'];
                }

                if ($data[$item['event_id']]['actions'][$item['action_id']]['filters'][$item['filter_id']] == null)
                {
                    $data[$item['event_id']]['actions'][$item['action_id']]['filters'][$item['filter_id']]['filter_id'] = $item['filter_id'];
                    $data[$item['event_id']]['actions'][$item['action_id']]['filters'][$item['filter_id']]['filter_type'] = $item['filter_type'];
                    $data[$item['event_id']]['actions'][$item['action_id']]['filters'][$item['filter_id']]['filter_operator'] = $item['filter_operator'];
                    $data[$item['event_id']]['actions'][$item['action_id']]['filters'][$item['filter_id']]['min_value'] = $item['min_value'];
                    $data[$item['event_id']]['actions'][$item['action_id']]['filters'][$item['filter_id']]['max_value'] = $item['max_value'];
                }


                $curOptions = $data[$item['event_id']]['actions'][$item['action_id']]['options'];
                
                if ($curOptions){
                    $existedOption = in_array($item["option_id"], array_column($curOptions, "option_id"));
                    if (!$existedOption) {
                        $data[$item['event_id']]['actions'][$item['action_id']]['options'][] = $this->parseOptionItemFromEvent($item);
                    }
                } else {
                    $data[$item['event_id']]['actions'][$item['action_id']]['options'][] =  $this->parseOptionItemFromEvent($item);
                }
            }

            $data_filters = array();
            foreach ($data as $event)
            {
                $event2 = array(
                    "event_id" => $event["event_id"],
                    "name" => $event["name"],
                    "form_ui" => $event["form_ui"],
                    "is_affect_item" => $event["is_affect_item"],
                    "event_type" => $event["event_type"],
                    "order_index" => (int) $event["order_index"],
                    "to_date" => $event["to_date"],
                );
                $actions = $event['actions'];
                $filters = array();
                foreach ($actions as $action)
                {
                    $filter = array();
                    $filter["conditions"] = $action['filters'];
//                    $action["options"] = $this->setDefaultOption($action["options"]);
                    $action["options"] = $action["options"];
                    
                    $filter["action"] = ($action);
                    
                    unset($filter["action"]["filters"]);

                    $filters[] = $filter;
                }
                $event2['filters'] = $filters;
                $data_filters[] = $event2;
            }
            return $data_filters;
        }
    }
    
    public function parseOptionItemFromEvent($item)
    {
        $option_times_limit = $item["option_times_limit"];
        $option_times_used = $item["option_times_used"];
        $active = !$option_times_limit || $option_times_used >= $option_times_limit ? false : true;
        
        switch ($item["action_type"])
        {
            case self::ACTION_TYPE_ADD_GIFT:
                if ($item["stock_qty"] > 0 && $item["stock_is_in_stock"])
                {
                    $active = true;
                }
                else
                {
                    $active = false;
                }
                break;
            case self::ACTION_TYPE_NO_ACTION:
                $active = true;
                break;
            default:
        }
        $almost_run_out = false;
        if ($active && $option_times_limit && $option_times_limit - $option_times_used <= 50){
            $almost_run_out = true;
        }

        //check if event's to_date almost reach, set almost_run_out = true
        $now = time();
        $to_date = strtotime($item['to_date']);
        $date_diff = round(($to_date - $now)/ (86400));
        if ($date_diff <= 5){
            $almost_run_out = true;
        }
        
        
        $option = array(
            "option_id" => $item["option_id"],
            "option_value" => $item["option_value"],
            "option_name" => $item["option_name"],
            "option_qty" => $item["option_qty"],
            "active" => $active,
            "almost_run_out" => $almost_run_out,
        );
        return $option;
    }

    public function setDefaultOption($options){
        $options = array_map(function($item){
            $item["default"] = false;
            return $item;
        }, $options);
        $no_value = array_search(null, array_column($options, "option_value"));
        if ($no_value != -1){
            $options[$no_value]["option_id"] = 0;
            $options[$no_value]["default"] = true;
            $options[$no_value]["active"] = true;
            $defaultOption = $options[$no_value];
            unset($options[$no_value]);
            array_unshift($options, $defaultOption);
            
        }
        return $options;
    }
    
    public function processRuleToCreateOrder($quote, $option_id){
        $events = Mage::helper("eventcart")->checkEventCartWithQuote($quote);
        
        //get all events which cart matches one rule
        $matched_events = array_filter($events, function($event){
            $matched_ranks = array_filter($event["rank"], function($rank){
               if ($rank["matched"]){
                   return $rank;
               }
            });
            
            if (count($matched_ranks) > 0){
                return $event;
            }
        });
        
        //process auto rule
        $auto_actions = array();
        foreach ($matched_events as $event){
            foreach ($event["rank"] as $rank)
            {
                if ($rank["matched"])
                {
                    if ($rank["is_auto"])
                    {
                        //get active option
                        foreach ($rank["options"] as $option)
                        {
                            //option is active: option matches cart
                            if ($option ["active"])
                            {
                                //check action is acted when create order (insert into fhs_event_cart_order)
                                if (!in_array($rank['action_type'], self::NO_ACTION_ARRAY)){
                                    //set action type for option
                                    $option["action_type"] = $rank["action_type"];
                                    $option["event_id"] = $event["event_id"];
                                    $auto_actions[] = $option;
                                    
                                    //comment to add multiple gift for 1 rule
                                    //break;
                                }
                            }
                        }
                    }
                    else if ($option_id)
                    {
                        //process for option_id which customer choose
                        foreach ($rank["options"] as $option)
                        {
                            if ($option["option_id"] == $option_id && $option ["active"])
                            {
                                //set action type for option
                                $option["action_type"] = $rank["action_type"];
                                $option["event_id"] = $event["event_id"];
                                $auto_actions[] = $option;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        foreach ($auto_actions as $action){
            switch ($action["action_type"]){
                case self::ACTION_TYPE_ADD_GIFT:
                    $this->addGiftOption($quote, $action);
                    break;
                case self::ACTION_TYPE_SALESRULE:
                    //not to do
                    break;
                default:
            }
        }
        
        return $auto_actions;
    }
    
      public function addGiftOption($quote, $option){
        //do not need to remove item because _resetFreeItems in FreeProduct/Model/Observer always remove all free item before
       
        //product_id = option['option_value']
        $product = Mage::getModel('catalog/product')->load($option['option_value']);
        //only handle gift is simple (there is an issue in case gift is bundle)
        //product is not null -> it is checked in query which checking stock
        if ($product->getId() && $product->getTypeId() == "simple")
        {
            $qty = 1;
            if ($option["option_qty"]){
                $qty = $option["option_qty"];
            }
            $freeItem = $this->_getFreeQuoteItem($quote, $product, $qty);
            $quote->addItem($freeItem);

            //set quote change to insert item in fhs_sales_flat_quote_item
            $quote->setDataChanges(true);
            $quote->save();
        }
    }
    
    public function _getFreeQuoteItem($quote, $product, $qty)
    {
        //copy code from add a free gift
        $stock = Mage::getModel('cataloginventory/stock_item')->assignProduct($product);
        //default qty of gift is 1
        if ($stock->getQty() < $qty)
        {
            return false;
        }

        $quoteItem = Mage::getModel('sales/quote_item')->setProduct($product);
        $quoteItem->setQuote($quote)
                ->setQty($qty)
                ->setCustomPrice(0.0)
                ->setOriginalCustomPrice($product->getPrice())
                ->setIsFreeProduct(true)
                ->setWeeeTaxApplied('a:0:{}') // Set WeeTaxApplied Value by default so there are no "warnings" later on during invoice creation
                ->setStoreId($quote->getStoreId());

        $quoteItem->addOption(new Varien_Object(array(
            'product' => $product,
            'code' => 'info_buyRequest',
            'value' => serialize(array('qty' => $qty, 'is_free_product' => true))
        )));
        // With the freeproduct_uniqid option, items of the same free product won't get combined.
        $quoteItem->addOption(new Varien_Object(array(
            'product' => $product,
            'code' => 'freeproduct_uniqid',
            'value' => uniqid(null, true)
        )));

        return $quoteItem;
    }
    
    public function getProductPromotion($product_id)
    {
        \Mage::log(' - BEGIN FUNCTION getProductPromotion IN REST ' . round(microtime(true) * 1000) , null, "redis_product_debug.log" );
        $events = array();
        $query = "select fp.id, fp.message, ca.block_detail_id, bl.content, e.form_ui, e.type as event_type, ca.title "
                . "from fhs_product_promotion fp left join "
                . "fhs_event_cart_action ca on ca.id = fp.action_id "
                . " left join fhs_event_cart_rule r on r.action_id = ca.id "
                . " left join fhs_event_cart e on e.id = r.event_id "
                . "left join fhs_cms_block bl on bl.identifier = ca.block_detail_id "
                . "where fp.product_id = {$product_id} and now() between fp.from_date and fp.to_date "
                . " group by ca.id "
                . "order by if(isnull(e.order_index), 99, e.order_index ), fp.id ";
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        \Mage::log(' - BEGIN fetchAll ' . round(microtime(true) * 1000) , null, "redis_product_debug.log" );
        $promotions = $readConnection->fetchAll($query);
        \Mage::log(' - END fetchAllT ' . round(microtime(true) * 1000) , null, "redis_product_debug.log" );
        foreach ($promotions as $promo){
            $form_ui = (array) json_decode($promo['form_ui']);
            $btn_link = null;
            $btn_title = null;
            if ($form_ui){
                $btn_link = !empty($form_ui["popup_btn_link"])?$form_ui["popup_btn_link"]:null;
                $btn_title = !empty($form_ui["popup_btn_title"])?$form_ui["popup_btn_title"]:null;
            }
            $title_arr = explode(";", $promo['title']);
            $title_1 = !empty($title_arr[0])?trim($title_arr[0]):'';
            $title_2 = !empty($title_arr[1])?trim($title_arr[1]):'';
            $events[] = array(
                "id" => (int) $promo['id'],
                "block_detail_id" => $promo["block_detail_id"],
                "rule_content" => $promo["content"],
                "btn_link" => $btn_link,
                "btn_title" => $btn_title,
                "title" => $title_1,
                "title_2" => $title_2,
                "event_type" => (int) $promo["event_type"],
                "matched" => true
            );
        }
        
        $affect_coupons = array_values(array_filter($events, function($e) {
            return $e['event_type'] == self::TYPE_EVENT_COUPON || $e['event_type'] == self::TYPE_EVENT_COUPON_PAYMENT;
        }));
        
        $affect_freeships = array_values(array_filter($events, function($e) {
            return $e['event_type'] == self::TYPE_EVENT_FREESHIP|| $e['event_type'] == self::TYPE_EVENT_FREESHIP_PAYMENT;
        }));
        
        $affect_payments = array_values(array_filter($events, function($e) {
            return $e['event_type'] == self::TYPE_EVENT_PAYMENT;
        }));

        $affect_carts = array_values(array_filter($events, function($e) {
            return $e['event_type'] == self::TYPE_EVENT_SALE || $e['event_type'] == self::TYPE_EVENT_GIFT;
        }));

        $affect_all = array();
        
        $index = 0;
        $affect_coupons_length = count($affect_coupons);

        $affect_freeships_length = count($affect_freeships);
        $affect_payments_length = count($affect_payments);
        $affect_carts_length = count($affect_carts);
        $max_count = max(array($affect_coupons_length, $affect_freeships_length, $affect_payments_length, $affect_carts_length));

        while (count($affect_all) < 3 && $index < $max_count)
        {
            if ($index < $affect_coupons_length)
            {
                $affect_coupons[$index]['priority'] = 1;
                $affect_all[] = $affect_coupons[$index];
            }
            if ($index < $affect_freeships_length)
            {
                $affect_freeships[$index]['priority'] = 2;
                $affect_all[] = $affect_freeships[$index];
            }
            if ($index < $affect_payments_length)
            {
                $affect_payments[$index]['priority'] = 3;
                $affect_all[] = $affect_payments[$index];
            }
            if (count($affect_all) < 3)
            {
                if ($index < $affect_carts_length)
                {
                    $affect_carts[$index]['priority'] = 4;
                    $affect_all[] = $affect_carts[$index];
                }
            }

            $index++;
            
        }
        array_multisort(array_column($affect_all, 'priority'), SORT_ASC, $affect_all);

        $result = array(
            'affect_all' => $affect_all,
            'affect_coupons' => $affect_coupons,
            'affect_freeships' => $affect_freeships,
            'affect_payments' => $affect_payments,
            'affect_carts' => $affect_carts
        );
        $result = array_filter($result, function($e){
            return count($e) > 0;
        });
//        $result = array();
//        if (count($affect_all) > 0)
//        {
//            $result[] = array(
//                "type" => "all",
//                "events" => $affect_all
//            );
//                    
//        }
//         if ($affect_coupons_length > 0)
//        {
//            $result[] = array(
//                "type" => "affect_coupons",
//                "events" => $affect_coupons
//            );
//                    
//        }
//         if ($affect_freeships_length > 0)
//        {
//            $result[] = array(
//                "type" => "affect_freeships",
//                "events" => $affect_freeships
//            );
//                    
//        }
//         if ($affect_payments_length > 0)
//        {
//            $result[] = array(
//                "type" => "affect_payments",
//                "events" => $affect_payments
//            );
//                    
//        }
//        
//        if ($affect_carts_length > 0)
//        {
//            $result[] = array(
//                "type" => "affect_carts",
//                "events" => $affect_carts
//            );
//                    
//        }
         \Mage::log(' - END FUNCTION getProductPromotion IN REST ' . round(microtime(true) * 1000) , null, "redis_product_debug.log" );
        return $result;
    }
    
    public function getNameOfPromotion($name) {
        switch ($name) {
            case "affect_all":
                return "Tất cả";
            case "affect_coupons":
                return "Mã giảm giá";
            case "affect_freeships":
                return "Mã vận chuyển";
            case "affect_payments":
                return "Mã thanh toán";
            case "affect_carts":
                return "Ưu đãi khác";
            default:
                return "Khác ";
        }
    }
    
    
    public function getColorAndIcon() {
        $dataIconAndColor = array('0' => '{"icon":"ico_promotion","number1":"#6B4EFF"}'
            , '1' => '{"icon":"ico_promotion","number1":"#FFB323"}'
            , '2' => '{"icon":"ico_gift","number1" : "#6B4EFF"}'
            , '3' => '{"icon" : "ico_ewallet","number1" :"#48A7F8"}'
            , '4' => '{"icon":"ico_freeship","number1" :"#23C16B"}'
            , '5' => '{"icon":"ico_ewallet","number1":"#FFB323"}'
            , '6' => '{"icon":"ico_ewallet","number1":"#23C16B"}'
        );
        
        return $dataIconAndColor;
    }
    
}
