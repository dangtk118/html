<?php

class Fahasa_Almostcart_Helper_Data extends Mage_Core_Helper_Abstract {    
    public function getCartTotal(){
        $session = Mage::getSingleton('checkout/session');
        
        $quote = $session->getQuote();

        $totals = $quote->getTotals();       
        foreach ($totals as $k => $v) {
            if ($k == "subtotal") {                
                $cartTotal = $v->getValue();
                return $cartTotal;
            }
        }
    }
    
     public function getListGiftAlmostCart(){
        $cartTotal = $this->getCartTotal();

        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $query = "select g.id as rank_id, g.alternate_name, g.min_cart_value, g.max_cart_value, g.item_order, g.type, g.gift_image, "
                . "gi.coupon_code, gi.name as name_gift, gi.image_url as image_gift, gi.item_order as item_order_gift, "
                . "gi.quantity, co.usage_limit as times_limit, sum(if(cu.customer_id is not null, 1, 0)) as times_used, "
                . "case (g.min_cart_value <= (CAST('{$cartTotal}' as DECIMAL(10,2))) and ((g.max_cart_value >= (CAST('{$cartTotal}' as DECIMAL(10,2))) "
                . "or g.max_cart_value is null) or (g.allow_above = 1)   )) when 1 then 1 else 0 end as can_choose, gi.description, gi.rule_id, stock.qty as stock_quantity "
                . "from fhs_almostcart_gift g "
                . "join fhs_almostcart_gift_item gi on (g.id = gi.rank_id and gi.status = 1) "
                . "left join fhs_salesrule_coupon co on (co.code = gi.coupon_code) left join fhs_salesrule_coupon_usage cu on co.coupon_id = cu.coupon_id "
                . " left join fhs_salesrule rule on rule.rule_id = gi.rule_id
                left join fhs_catalog_product_entity pe on pe.sku = rule.gift_sku
                left join fhs_cataloginventory_stock_item stock on stock.product_id = pe.entity_id "
                . "where g.status = 1 "
                . "and g.start_date <= now() "
                . "and g.end_date >= now() "
                . "group by gi.rule_id "
                . "order by g.item_order asc, g.id asc, item_order_gift asc";
        $rsGift = $readConnection->fetchAll($query);
        
        $listRank = array();
        
        if (count($rsGift) > 0) {
            $type = $rsGift[0]['type'];

            foreach ($rsGift as $gift) {
                if ($listRank[$gift['rank_id']]['rankId'] == null) {
                    $listRank[$gift['rank_id']]['rankId'] = $gift['rank_id'];
                    $listRank[$gift['rank_id']]['minCartValue'] = (float) $gift['min_cart_value'];
                    $listRank[$gift['rank_id']]['maxCartValue'] = (float) $gift['max_cart_value'];
                    $listRank[$gift['rank_id']]['alternateName'] = $gift['alternate_name'];
                    $listRank[$gift['rank_id']]['giftImage'] = Mage::getBaseUrl('media') . $gift['gift_image'];
                    $listRank[$gift['rank_id']]['canChoose'] = $gift['can_choose'] == true ? true : false;                    
                }
                
                $isInStock = false;
                if ($gift['stock_quantity'] && $gift['stock_quantity'] > 0){
                    $isInStock = true;
                }

                $item = array(
                    "name" => $gift['name_gift'],
                    "image" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $gift['image_gift'],
                    "couponCode" => $gift['coupon_code'],
                    "canChoose" => $gift["can_choose"] == true ? true : false, 
                    "quantity" => $gift["quantity"],
                    "timesLimit" => $gift["times_limit"],
                    "timesUsed" => $gift["times_used"],
                    "description" => $gift["description"],
                    "ruleId" => $gift["rule_id"],
                    "isInStock" => $isInStock,
                );
                
                $listRank[$gift['rank_id']]['data'][] = $item;
            }
            
            $listAllGift = array_merge($listRank);
            
            if ($type == "random"){
                $randomGift = null;
               
                $activeRank = -1;
                $activeGiftsData = array();
                for ($i = 0; i < count($listAllGift); $i++){
                    $activeGiftsData = $listAllGift[$i]["data"];
                    $idx = array_search(true, array_column($activeGiftsData, "canChoose"));
                    if ($idx !== false){
                        $activeRank = $i;
                        break;
                    }
                }
                if ($activeRank !== -1){
                    $randomGift = $this->createRandomGift($activeGiftsData, "quantity");
                    
                    if ($randomGift){
                        $randomGiftData = $activeGiftsData[array_search($randomGift, array_column($activeGiftsData, "couponCode"))];

                        $timesLimit = $randomGiftData["timesLimit"];
                        
                        //usage_limit of coupon is limited
                        if ($timesLimit){
                            $timesRest = $timesLimit - $randomGiftData["timesUsed"];

                            if ($timesRest <= 0){
                               $randomGift  = $this->createRandomGift($activeGiftsData, "timesLimit");
                            }
                        }
                    }
                    else{
                        $randomGift  = $this->createRandomGift($activeGiftsData, "timesLimit");
                    }
                    
                    if ($randomGift){
                        $giftKey = array_search($randomGift, array_column($activeGiftsData, 'couponCode'));
                        $randomImage = $activeGiftsData[$giftKey]["image"];
                    }
                }
            }
            
            
            $listLimitGift = $this->getListActiveGift($rsGift);
        }
        
        $quote = $this->getQuote();
        $applied_rules_gift = $quote->getAppliedRulesGift();
        
        return array(
            "cartTotal" => $cartTotal,
            "listAllGift" => (!empty($listAllGift)?$listAllGift:''),
            "listLimitGift" => (!empty($listLimitGift)?$listLimitGift:''),
            "randomGift" => array (
                "couponCode" => (!empty($randomGift)?$randomGift:''),
                "image" => (!empty($randomImage)?$randomImage:'')
            ),
            "type" => (!empty($type)?$type:''),
            "appliedRulesGift" => $applied_rules_gift,
        );
    }
    
    
    function createRandomGift($activeGiftsData, $limitType) {
        $listRand = array();
        foreach ($activeGiftsData as $activeGift) {
            $timesUsed = $activeGift["timesUsed"];

            $timesRate = $activeGift[$limitType];
            
            //if usage_limit is limited
            if ($timesRate){
                $restRate = $timesRate - $timesUsed;

                if ($restRate > 0) {
                    $gift_array = array_fill(0, $restRate, $activeGift["couponCode"]);
                    $listRand = array_merge($listRand, $gift_array);
                }
            }
            else{
                $listRand = array_merge($listRand, array($activeGift["couponCode"]));
            }
        }

        $randomGift = $this->array_random($listRand);
        if (count($randomGift)  > 0){
            return $randomGift[0];
        }
        return false;
    }

    function array_random($arr, $num = 1) {
        if (count($arr) > 0) {
            shuffle($arr);

            $r = array();
            for ($i = 0; $i < $num; $i++) {
                $r[] = $arr[$i];
            }
            return $r;
        } else {
            return NULL;
        }
    }
    
    public function getListActiveGift($listGift){
        $chooseFirstIndex = array_search(true, array_column($listGift, "can_choose"));

        if ($chooseFirstIndex !== false){
            $minCartSort = SORT_DESC;
        }
        else{
            $minCartSort = SORT_ASC;
        }
        array_multisort(array_column($listGift, "can_choose"), SORT_DESC,
                array_column($listGift, "min_cart_value"), $minCartSort, 
                array_column($listGift, "item_order_gift"), SORT_ASC,        
                $listGift);
        
        $result = array();
        $count = 0;
        foreach($listGift as $gift){
            if ($chooseFirstIndex !== false){
                if ($count < 3){
                    if ($gift['can_choose'] == true){
                        $item = array(
                            "name" => $gift['name_gift'],
                            "image" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $gift['image_gift'],
                            "couponCode" => $gift['coupon_code'],
                            "canChoose" => true
                        );
                        $result[] = $item;
                        $count++;
                        continue;
                    }
                    break;
                }
                break;
            }
            else{
                if ($count < 3){
                    $item = array(
                        "name" => $gift['name_gift'],
                        "image" => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $gift['image_gift'],
                        "couponCode" => $gift['coupon_code'],
                        "canChoose" => false
                    );
                    $result[] = $item;
                    $count++;
                    continue;
                }
                break;
            }
        }
        return $result;
        
    }
    
    
    public function chooseFreeGift($ruleId, $apply) {
        $success = true;
        $message = null;

        try {
            if ($apply){
                if (!$ruleId){
                    return array(
                        "success" => false,
                        "message" => "INVALID_RULE"
                    );
                }

                $rule = Mage::getModel('salesrule/rule')->load($ruleId);
                if ($rule){
                    $quote = $this->getQuote();
                    if ($quote->getItemsCount()){
                        $quote->setAppliedRulesGift($ruleId);
                        $quote->collectTotals()->save();
                    }
                } else {    
                    $success = false;
                    $message = "INVALID_RULE";
                }
            } else {
                $quote = $this->getQuote();
                $quote->setAppliedRulesGift(null);
                $quote->collectTotals()->save();
            }
        } catch (Exception $e) {
            Mage::log("Exception choose free gift " . $e->getMessage(), null, "freegift.log");
            $success = false;
            $message = "EXCEPTION_CHOOSE_GIFT";
        }

        return array(
            "success" => $success,
            "message" => $message
        );
    }
    
    public function getQuote() {
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0)
        {
            $_getQuoteCallCount++;

            $onePage = Mage::getSingleton('checkout/type_onepage');
            /* @var $quote Mage_Sales_Model_Quote */
            $quote = $onePage->getQuote();
            $_getQuoteCallCount--;
            return $quote;
        }
        return null;
    }

}
