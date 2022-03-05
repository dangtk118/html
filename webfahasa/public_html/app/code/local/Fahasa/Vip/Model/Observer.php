<?php

/**
 * Handle discount for vip
 * @author Thang Pham
 */
class Fahasa_Vip_Model_Observer {
    
    const STUDENT_GROUP = "student";
    const STUDENT_LEVEL = 1;
    const INIT_GROUP = "";
    const INIT_LEVEL = -1;
    const SSC_REQUESTPARAMS = "RequestParams";
    const SSC_ACCESSKEY = "AccessKey";
    const SSC_ACCESSKEYVALUE = "59328ad64be319c0335af32f9e5653671d1d5f99";
    const SSC_URL_GETSTUDENT_BY_ID = "http://api.thessc.com.vn/api/School/GetStudentBySSCId/";
    static $vipOrgRunning = 0;
    
    public function vipDiscountApply($observer) {
        if(Fahasa_Vip_Model_Observer::$vipOrgRunning == 1){
            return;
        }
        Fahasa_Vip_Model_Observer::$vipOrgRunning = 1;
        try{
             
            //handle coupon code freeship 
            $this->setDiscountFreeshipCode($observer);
        
            
	    //15/10/2018: VIP To Chuc va student vip se chuyen sang hinh thuc accure fpoint, khong giam truc tiep gio hang nhu hien nay
            //Fahasa_Vip_Model_Member
            $member = Mage::helper('vip')->determineVipMember();         
            //init filter var
            $groupId = self::INIT_GROUP;
            $level = self::INIT_LEVEL;

            $coinObj = Mage::getSingleton('core/session')->getFhsCoin();
            $results = null;
            if ($coinObj){
                 $results = Mage::helper('tryout')->checkCoin($coinObj['code']);
            }

            // set is_vip use for create order
            $_POST['is_vip'] = FALSE;
            $_POST['vip_id'] = "";

            // Coin code can not apply with pickup location
            $shippingMethod = $observer->getQuote()->getShippingAddress()->getShippingMethod();
            if ($results['currentAmount'] > 0 && $shippingMethod !== "freeshipping_freeshipping") { 
                // check if have fhs_coin
                $this->applyDiscountFhsCoin($results);
            }else{ // if not fhs_coin
                Mage::getSingleton('core/session')->setFhsCoin(NULL);
                $buffet_combo = $this->applyBuffetCombo();
                if(!$buffet_combo && $member){
                    $_POST['is_vip'] = true;
                    $_POST['vip_id'] = $member->vipId;
                    Mage::log("*** determineVipMember: Model:  ", null, "vip_id.log");
                    Mage::log(print_r($member, true), null, "vip_id.log");
                    if($member->isSSC){
                        /**
                         * SSC member will use the same membership level as student
                         * For now, ssc only use level 1. This need to change in the long run.
                         * If we implements score base on how much customer buy, then old 
                         * ssc customer will be maintain in a separate table
                         */
                        $groupId = self::STUDENT_GROUP;
                        $level = self::STUDENT_LEVEL;
                    }else {
                        $groupId = $member->groupId;
                        $level = $member->level;
                    }
                    if($groupId != self::INIT_GROUP && $level != self::INIT_LEVEL){
                        $vip_level_collection = Mage::getModel('vip/viplevel')->getCollection()
                                    ->addFieldToFilter('group_id', $groupId)
                                    ->addFieldToFilter('level', $level);                                
                        $vip_level = $vip_level_collection->getFirstItem();
                        if($vip_level->getId() != null){
                            $discountAmt = $this->getDiscountAmt($vip_level);
                            if($discountAmt > 0){
                                Mage::log("*** VIP apply discountAmt " . $discountAmt, null, "vip_id.log");
                                $this->applyDiscount($discountAmt, $vip_level->getGroupLabel());
                            }                    
                        }else{
                            $custEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
                            Mage::log("*** Cannot find level for group_id: " . $groupId . ", and level: " . $level . ", customer email: " . $custEmail, null, "vip_id.log");
                        }            
                    }        
                }
            }
            $is_tryout = Mage::getSingleton('checkout/session')->getData('onestepcheckout_tryout');

            // handle list sku not apply with FPoint 
            $productNotApplyFpoint = Mage::helper('tryout')->getProductNotApplyFpoint();
            if ($is_tryout == 1 && $productNotApplyFpoint !== FALSE) {
                // user f12 and edit html when admin hidding checkbox use fpoint
                $custEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
                Mage::log("Warning: " . $custEmail . ". user f12 and edit html when admin hidding checkbox use fpoint.", null, "fpoint.log");
            }

            if($is_tryout == 1 && $productNotApplyFpoint == FALSE){
                $this->setDiscountTryout();
            }else{
                $this->unsetDiscountTryout();
            }
            
        } catch (Exception $ex) {
            Mage::logException($ex);
        }
        
       
        
        Fahasa_Vip_Model_Observer::$vipOrgRunning = 0;
        $this->logQuoteItemUpdateCart();
    }
    
    public function applyBuffetCombo(){
        //Mage::log("Apply Buffet Combo", null, "buffet.log");
        $buffet_combo = false;
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0) {
            $_getQuoteCallCount++;
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $_getQuoteCallCount--;
            $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');
            
            foreach ($quote->getAllAddresses() as $address) {
                if ($address->getAddressType() == $canAddItems) {
                    /// Previous we used $address->getAllNonNominalItems(), it has error
                    /// Because getAllNonNominalItems() will include child products in a bundle
                    /// getAllVisibleItems() will not
                    $items = $address->getAllVisibleItems();
                    if (!$items) {
                        return $this;
                    }
                    $buffet_helper = Mage::helper("event/buffetcombo");
                    $buffet_combo = $buffet_helper->pickBuffetcombo($items);
                    if($buffet_combo && !$buffet_combo['is_out_of_stock']){
                        $_buffet_real_price = 0;
                        foreach($buffet_combo['ids'] as $id){
                            foreach ($items as $item){
                                if($id == $item->getProductId()){
                                    $_buffet_real_price += $item->getPriceInclTax();
                                }
                            }
                        }

                        $buffet_discount = $_buffet_real_price - $buffet_combo['price'];
                        $this->applyDiscount($buffet_discount, "Buffet Combo", false);
                        ///Mage::log("after apply discount: " . $buffet_discount, null, "buffet.log");
                    }
                }
            }
        }
        return $buffet_combo;
    }
    
    /*
     * apply fhs_coin
     */

    public function applyDiscountFhsCoin($results) {
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0) {
            $_getQuoteCallCount++;
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $_getQuoteCallCount--;
            $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');
            foreach ($quote->getAllAddresses() as $address) {
                if ($address->getAddressType() == $canAddItems) {
                    // get grand total with discount from other rule
                    $grand_total = $address->getGrandTotal();
                    if (!$quote->getCouponCode()) {
                        if ($results['currentAmount'] >= $grand_total) {
                            $codfee = $address->getCodfee() == null ? 0 : $address->getCodfee();
                            $grand_total = $grand_total - $codfee;
                            Mage::log("*** Grand Total should be 0", null, "fhs_coin.log");
                            $address->setGrandTotal(0);
                            $address->setDiscountAmount(-($grand_total));
                            $address->setDiscountDescription($results['code']);
                            // remove COD
                            $address->setCodfee(0);
                            
                        } else {
                            $nTotal = $grand_total - $results['currentAmount'];
                            Mage::log("*** Grand Total should be $nTotal", null, "fhs_coin.log");
                            $address->setGrandTotal($nTotal);
                            $address->setDiscountAmount(-($results['currentAmount']));
                            $address->setDiscountDescription($results['code']);
                            
                        }
                        $address->setCoinAmount($results['currentAmount']);
                    }
                    $address->save();
                }
            } 
        } else {
            Mage::log("*** FAIL applyDiscountFhsCoin: " . $results['code'], null, "fhs_coin.log");
        }
    }

    /*
     * set discount tryout
     */
    public function setDiscountTryout() {
        $tryoutMoney = Mage::helper('tryout')->determinetryout();
        if ($tryoutMoney > 0) {
            $tryout_money = $tryoutMoney;
            $tryout_email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
            Mage::log("*** Tryout: Processing tryout Email account " . $tryout_email . " with balance " . $tryout_money, null, "vip_id.log");
            static $_getQuoteCallCount = 0;
            if ($_getQuoteCallCount == 0) {
                $_getQuoteCallCount++;
                $quote = Mage::getSingleton('checkout/session')->getQuote();
                $_getQuoteCallCount--;
                $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');
                foreach ($quote->getAllAddresses() as $address) {
                    if ($address->getAddressType() == $canAddItems) {
                        // get grand total with discount from other rule
                        $grand_total = $address->getGrandTotal();
                        Mage::log("*** Tryout Balance: " . $tryout_money . ", Grand Total: " . $grand_total, null, "vip_id.log");
                        if ($tryout_money >= $grand_total) { // tien vip nhiu hon OR =
                            $codfee = $address->getCodfee() == null ? 0 : $address->getCodfee();
                            $grand_total = $grand_total - $codfee;
                            Mage::log("*** Grand Total should be 0", null, "vip_id.log");
                            $address->setGrandTotal(0);
                            $address->setTryoutDiscount(-($grand_total));
                            $address->setBaseTryoutDiscount(-($grand_total));
                            $address->setCodfee(0);
                        } elseif ($tryout_money < $grand_total) { // tien vip nho hon
                            $nTotal = $grand_total - $tryout_money;
                            Mage::log("*** Grand Total should be $nTotal", null, "vip_id.log");
                            $address->setGrandTotal($nTotal);
                            $address->setTryoutDiscount(-($tryout_money));
                            $address->setBaseTryoutDiscount(-($tryout_money));
                        }
                    }
                    $address->save();
                }
            } else {
                Mage::log("*** FAIL Tryout: recursively loop when getQuote() for customer email " . $tryout_email . " and balance " . $tryout_money, null, "vip_id.log");
            }
        } else {
            $this->unsetDiscountTryout();
        }
    }
    
    public function unsetDiscountTryout() {
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0) {
            $_getQuoteCallCount++;
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $_getQuoteCallCount--;
            $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');
            foreach ($quote->getAllAddresses() as $address) {
                if ($address->getAddressType() == $canAddItems) {
                    $address->setTryoutDiscount(0);
                    $address->setBaseTryoutDiscount(0);
                }
                $address->save();
            }
        }
    }

    /**
     * Return the discount amount given the vip level
     * @return int
     */
    public function getDiscountAmt($member_vip_level){        
        $primary_discount = $member_vip_level->getDiscountPrimary();
        $increment_discount = $member_vip_level->getDiscountIncrement();
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0) {
            $_getQuoteCallCount++;
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $_getQuoteCallCount--;
            $discountAmt = 0;
            $arr_bundle_qty = array();
            
            $shippingAddress = $quote->getShippingAddress();
            $subTotal = $shippingAddress->getSubtotalInclTax();
            $couponDiscount = $shippingAddress->getDiscountAmount();
            
            foreach ($quote->getAllItems() as $item) {
                //We apply discount amount based on the ratio between the GrandTotal and the RowTotal            
                $product = $item->getProduct();
                
                // Ignore list sku no apply vip
                $arr_no_apply_vip = explode(",", Mage::getStoreConfig('vip_input/general/no_apply_vip'));
                if (in_array($product->getSku(), $arr_no_apply_vip)) {
                    continue;
                }
                
                if ($product->getTypeId() !== "bundle" || $product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                    if (array_key_exists($item->getParentItemId(), $arr_bundle_qty)) {
                        // product child bundle dynamic
                        // get qty from bundle parent
                        $qty = $arr_bundle_qty[$item->getParentItemId()]["qty"];
                    } else {
                        // product child bundle fix + simple
                        $qty = $item->getQty();
                    }
                    
                    $price = $product->getPrice();
                    $finalPrice = $item->getOriginalPrice();
                    
                    $finalPrice = $this->calculateFinalPriceAfterDiscount($finalPrice, $subTotal, $couponDiscount);

                    if ((float) $price === (float) $finalPrice) {
                        //discount with $primary_discount
                        $discountAmt += ($finalPrice * $primary_discount / 100) * $qty;
                    } else {
                        //discount with increment discount
                        $discountAmt += ($finalPrice * $increment_discount / 100) * $qty;
                    }
                } else {
                    // map bundle_id:qty 
                    // use handle discount item simple in bundle
                    $arr_bundle_qty[$item->getItemId()]["qty"] = $item->getQty();
                }
            }
            return $discountAmt;
        } else {
            Mage::log("*** FAIL getDiscountAmt: recursively loop when getQuote(). Exit", null, "vip_id.log");
            return 0;
        }
    }
    
    /**
     * Apply the given discount to quote, which will display in checkout cart
     * 
     */
    public function applyDiscount($discountAmount, $groupLabel, $isLabelIncludeVIP = true) {
        static $_getQuoteCallCount = 0;        
        if ($_getQuoteCallCount == 0) {
            $_getQuoteCallCount++;
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $_getQuoteCallCount--;
            //The above code is to avoid getQuote to recursively loop
            
            //This below comment code is used to apply vip + couponcode for vip customer            
//            $coupon_code_val = $quote->getCouponCode();
//            if($coupon_code_val){
//                //If use coupon code, dont do have use vip discount
//                $discountAmount = 0;
//            }
           
            $quoteid = $quote->getId();
            if($isLabelIncludeVIP){
                $member_vip_group_label = Mage::helper('vip')->__('VIP') . ' ' . $groupLabel;
            }else{
                $member_vip_group_label = $groupLabel;
            }
            
            //check condition here if need to apply Discount                
            if ($quoteid) {
                if ($discountAmount > 0) {
                    $quote->setVipDiscount($discountAmount);
                    
                    $quote->setSubtotal(0);
                    $quote->setBaseSubtotal(0);

                    $quote->setSubtotalWithDiscount(0);
                    $quote->setBaseSubtotalWithDiscount(0);

                    $quote->setGrandTotal(0);
                    $quote->setBaseGrandTotal(0);


                    $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');
                    foreach ($quote->getAllAddresses() as $address) {
                        
                        $address->setSubtotal(0);
                        $address->setBaseSubtotal(0);

                        $address->setGrandTotal(0);
                        $address->setBaseGrandTotal(0);

                        $address->collectTotals();

                        $quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
                        $quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

                        $quote->setSubtotalWithDiscount(
                                (float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount()
                        );
                        $quote->setBaseSubtotalWithDiscount(
                                (float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount()
                        );

                        $quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
                        $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());
//                        $quote->setVipDiscountAmount($discountAmount);
                        $quote->save();

                        $quote->setGrandTotal($quote->getBaseSubtotal() - $discountAmount)
                                ->setBaseGrandTotal($quote->getBaseSubtotal() - $discountAmount)
                                ->setSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)
                                ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal() - $discountAmount)
                                ->save();

                        if ($address->getAddressType() == $canAddItems) {
                            //if(abs($address->getDiscountAmount()) >= abs($discountAmount)){
                                // gia chiet khau cua rule lon hon gia vip lay gia rule
    //                            $address->save();
                            //    return;
                            //}  else {
                                $new_discount = $discountAmount + abs($address->getCustomDiscountAmount());
                                $new_discount_desc = $member_vip_group_label ;
                                if($address->getCustomDiscountDesc()){
                                    $new_discount_desc = $address->getCustomDiscountDesc() . ',' . $member_vip_group_label;
                                }
                                
                                $address->setSubtotalWithDiscount((float) $address->getSubtotalWithDiscount() - $new_discount);
                                $address->setGrandTotal((float) $address->getGrandTotal() - $new_discount);
                                $address->setBaseSubtotalWithDiscount((float) $address->getBaseSubtotalWithDiscount() - $new_discount);
                                $address->setBaseGrandTotal((float) $address->getBaseGrandTotal() - $new_discount);
                                if ($address->getDiscountDescription()) {
//                                    $tempDisAmount = ();
//                                    $tempDisLabel = ;
//                                    if($tempDisAmount > 0){ //Luat giam gia ap dung truoc co gia tri thap hon VIP 
//                                        $tempDisAmount = -$tempDisAmount;
//                                    }
                                    $address->setDiscountAmount($address->getDiscountAmount() - $new_discount);                                    
                                    $address->setBaseDiscountAmount($address->getBaseDiscountAmount() - $new_discount);
                                    $address->setDiscountDescription($address->getDiscountDescription() . ', ' . $new_discount_desc);
                                } else {
                                    $address->setDiscountAmount(-($new_discount));                                    
                                    $address->setBaseDiscountAmount(-($new_discount));
                                    $address->setDiscountDescription($new_discount_desc);
                                }
                                $address->save();
                            //}
                        }//end: if
                    } //end: foreach
                    //echo $quote->getGrandTotal();
                }
            }
        } else {
            Mage::log("*** FAIL applyDiscount: recursively loop when getQuote(). Exit", null, "vip_id.log");
            return false;
        }                
    }          
    
    public function logQuoteItemUpdateCart(){
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $email = $quote->getCustomerEmail();
        $listCart = Mage::helper("onestepcheckout")->createQuoteCart($quote);
        Mage::log("Update cart: email=" . $email . ", quote_id = " . $quote->getId() . ", quote_data_before=" . print_r($listCart, true), null, "quotecheckout.log");
    }
    
    public function calculateFinalPriceAfterDiscount($finalPrice, $subTotal, $discountAmount) {
        $discountPerProduct = ((-1) * $discountAmount / $subTotal) * $finalPrice;
        $result = $finalPrice - $discountPerProduct;
        return $result;
    }
    
    public function setDiscountFreeshipCode($observer)
    {
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0)
        {
            $_getQuoteCallCount++;
            $session = Mage::getSingleton('checkout/session');
            $quote = $session->getQuote();
            $_getQuoteCallCount--;

            $freeship_coupon_code = $observer->getEvent()->getQuote()->getFreeshipCode();
            //when shipping_method == null: quote has not been calculated shipping. We accept apply freeship code for cart without shipping_method. 
            //in checkout page, we will calculated again
            //condition: shipping_fee > 0 || shipping_method = null
            if ($freeship_coupon_code 
                    && ($quote->getShippingAddress()->getShippingInclTax() > 0 || !$quote->getShippingAddress()->getShippingMethod())
                    &&
                    (
                    !$quote->getShippingAddress()->getCountryId() 
                    ||
                    $quote->getShippingAddress()->getCountryId() == "VN"))
                    
            {
                $store = Mage::app()->getStore($quote->getStoreId());

                $model = Mage::getModel('salesrule/validator');
                $model->init($store->getWebsiteId(), $quote->getCustomerGroupId(), $quote->getFreeshipCode());

                $items = $quote->getShippingAddress()->getAllNonNominalItems();
                $isAllFree = true;

                foreach ($items as $item)
                {
                    if ($item->getNoDiscount())
                    {
                        $isAllFree = false;
                        $item->setFreeShipping(false);
                    }
                    else
                    {
                        /**
                         * Child item discount we calculate for parent
                         */
                        if ($item->getParentItemId())
                        {
                            continue;
                        }
                        $model->processFreeShipping($item);
                        $isItemFree = (bool) $item->getFreeShipping();
                        
                        $isAllFree = $isAllFree && $isItemFree;
                    }
                }
                
                $couponRule = Mage::getModel('salesrule/coupon')->load($freeship_coupon_code, 'code');
                $oRule = Mage::getModel('salesrule/rule')->load($couponRule->getRuleId());
                //check rule usage_limit per customer (because the _canProcesseRule function (core validate rule) only set for coupon_code in quote
                //so the function ignore the condition useage_limit
                //=> below code will check this condition 
                $hasTimesUsed = $this->checkRuleHasTimesUsedPerCustomer($oRule, $couponRule, $freeship_coupon_code, $quote->getCustomerId());
                
                //it is different from condition ($isAllFree && !$address->getFreeShipping()) (in core/Mage/SalesRule/Model/Quote/Freeshipping)
                //condition in core is only for "for matching item only" => set Freeshipping
                //the behind function compose  "for matching item only" and "for shipment with matching item" 
                //case 2: address's shipping = true
                // get grand total with discount from other rule
                $isAllFree = $isAllFree && $hasTimesUsed;

                if ($isAllFree)
                {
                    $canAddItems = $quote->isVirtual() ? ('billing') : ('shipping');

                    foreach ($quote->getAllAddresses() as $address)
                    {
                        if ($address->getAddressType() == $canAddItems)
                        {

                            $original_shipping_fee = $address->getShippingInclTax();
                            //no apply freeship discount for fresh product
                            //get fresh_product_shipping_fee for calculate freeship discount. Freeship discount only apply for book. 
                            //shipping_fee = $original_shipping_fee - $fresh_product_shipping_fee
//                            $fresh_product_shipping_fee = Mage::getModel('vietnamshipping/carrier_vietnamshippingNormal')->getUrbanDeliveryFee($quote->getShippingAddress());
                            $no_apply_coupon_shipping_fee = 0;
                            if ($session->getNoApplyCouponShippingAmount()){
                                $no_apply_coupon_shipping_fee = $session->getNoApplyCouponShippingAmount();
                            }
                            
                            $shipping_fee = $original_shipping_fee - $no_apply_coupon_shipping_fee;
                            $discount_freeship = $this->calculateFreeshipDiscount($oRule, $shipping_fee);
                            
                            if ($discount_freeship > 0){
                                $quote->setGrandTotal($quote->getGrandTotal() - $discount_freeship);
                                $quote->setBaseGrandTotal($quote->getGrandTotal() - $discount_freeship);
                                $quote->save();


                                $grand_total = $address->getGrandTotal();

                                $address->setFreeshipAmount($discount_freeship * (-1));

                                $nTotal = $grand_total - $discount_freeship;

                                $address->setGrandTotal($nTotal);
                                $address->setBaseGrandTotal($address->getBaseGrandTotal() - $discount_freeship);
                                $freeshipCouponLabel = $oRule->getStoreLabel();
                                if (empty($freeshipCouponLabel))
                                {
                                    $freeshipCouponLabel = strtoupper($oRule->getCouponCode());
                                }
                                Mage::getSingleton('core/session')->setFreeshipLabel($freeshipCouponLabel);
                                Mage::getSingleton('core/session')->setFreeshipCode($freeship_coupon_code);
                                $address->save();
                            } else {
                                 Mage::getSingleton('core/session')->setFreeshipLabel(null);
                                 Mage::getSingleton('core/session')->setFreeshipCode(null);
                                $quote->setFreeshipCode('')->save();
                            }
                        }
                    }
                }
                else
                {
                    Mage::getSingleton('core/session')->setFreeshipLabel(null);
                    Mage::getSingleton('core/session')->setFreeshipCode(null);
                    $quote->setFreeshipCode('')->save();
                }
            }
            else
            {
                Mage::getSingleton('core/session')->setFreeshipLabel(null);
                Mage::getSingleton('core/session')->setFreeshipCode(null);
                $quote->setFreeshipCode('')->save();
            }
        }
        else
        {
            //log error
        }
    }
    
    //copy the _canProcessRule function in app/code/core/Mage/SalesRule/Model/Validator
    public function checkRuleHasTimesUsedPerCustomer($rule, $couponCode, $freeshipCode, $customerId)
    {
        if ($rule->getId() && $rule->getCouponType() != Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON)
        {
            $coupon = $couponCode;
            if ($coupon->getId())
            {
                // check entire usage limit
                if ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit())
                {
                    return false;
                }
                // check per customer usage limit
                if ($customerId && $coupon->getUsagePerCustomer())
                {
                    $couponUsage = new Varien_Object();
                    Mage::getResourceModel('salesrule/coupon_usage')->loadByCustomerCoupon(
                            $couponUsage, $customerId, $coupon->getId());
                    if ($couponUsage->getCouponId() &&
                            $couponUsage->getTimesUsed() >= $coupon->getUsagePerCustomer()
                    )
                    {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function calculateFreeshipDiscount($rule, $original_shipping_fee)
    {
        //make sure rule is freeship

        $freeship_discount = 0;
        if ($original_shipping_fee)
        {
            $discount_amount = $rule->getDiscountAmount();
            $max_discount_fee = $rule->getMaxDiscountFee();
            switch ($rule->getSimpleAction())
            {
                case Mage_SalesRule_Model_Rule::CART_FIXED_ACTION;
                      if ($original_shipping_fee >= $discount_amount)
                    {
                        $freeship_discount = $discount_amount;
                    }
                    else
                    {
                        $freeship_discount = $original_shipping_fee;
                    }

                    break;
                case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                  
                    $freeship_discount = $original_shipping_fee * ($discount_amount/100);
                    break;
            }
            if ($max_discount_fee && $freeship_discount > $max_discount_fee)
            {
                $freeship_discount = $max_discount_fee;
            }
        }
        return round($freeship_discount);
    }


}
