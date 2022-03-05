<?php

class Fahasa_Event_Helper_Buymorediscountmore extends Mage_Core_Helper_Abstract {
    
    function isActive(){
        $is_active = Mage::getStoreConfig('event_buy_more_discount_more/config/is_active');
        
        if(!$is_active){
            return false;
        }
        
        $valid_from = Mage::getStoreConfig('event_buy_more_discount_more/config/valid_from');
        $valid_to = Mage::getStoreConfig('event_buy_more_discount_more/config/valid_to');
        
        //date_default_timezone_set('Asia/Ho_Chi_Minh');
        $date_valid_from = new DateTime($valid_from);
        $date_valid_to = new DateTime($valid_to);
        $now_date = new DateTime();
        
        if($now_date >= $date_valid_from && $now_date <= $date_valid_to){
            return true;
        }
        
        return false;
    }
    
    /*
     *  Calculate Discount for event 'Buy More Discount More'
     *  Example: [{"qty_step": 4, "discount": 50},{"qty_step": 5, "discount": 70},{"qty_step": 6, "discount": 99}]
        MUA 4 GIẢM 50% CHO SP THỨ 4
        MUA 5 GIẢM 70% CHO SP THỨ 5
        MUA 6 GIẢM 99% CHO SP THỨ 6
     */
    public function calculateDiscount($quote){
        $rule_json = Mage::getStoreConfig('event_buy_more_discount_more/config/rule_json');
        $rule_json = json_decode($rule_json, true);
        
        if (!$rule_json) {
            /// If no rule, wrong json format, return discount = 0;
            return 0;
        }
        
        /*
         *  Condition 1: No other coupon codes , Store Id != offline store (!=5)
         */
        $festival_store = Mage::getStoreConfig('book_festival/config/store_code');
        $store_id = Mage::app()->getStore()->getId();
        $coupon_code = Mage::getSingleton('checkout/session')->getQuote()->getCouponCode();
        if($coupon_code || $store_id==$festival_store){
            return 0;
        }
        
        /*
         *  Condition 2: Items Cat Id must be in product category id
         */
        $product_category_id = Mage::getStoreConfig('event_buy_more_discount_more/config/product_category');
        $exclude_product_category_id = Mage::getStoreConfig('event_buy_more_discount_more/config/product_category_exclude');
        if($exclude_product_category_id){
	    $exclude_cats = explode(",",$exclude_product_category_id);
	}
        
        $satisfyItems = array();
        foreach($quote->getAllVisibleItems() as $item){
            $product = $item->getProduct();
//            $is_in_product_category = false;
//            foreach($product->getCategoryIds() as $category_id){
//                if($product_category_id == $category_id){
//                    $is_in_product_category = true;
//                }
//            }
//            
//            if(!$is_in_product_category){
//                return 0;
//            }
            
            //filter product satisfy with category and product is not a gift 
            if ($this->checkProductSatifyCategory($product, $product_category_id, $exclude_cats) && !$item->getIsFreeProduct()){
                $satisfyItems[] = $item;
            }
        }
        
        /*
         *  Condition 3: Check customer group
         */
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if(!$customer){
            return 0;
        }
        
        $customer_groups = Mage::getStoreConfig('event_buy_more_discount_more/config/customer_groups');
        if($customer_groups){
            $customer_groups = explode(",", $customer_groups);
        }
        
        if(!in_array((int)$customer->getGroupId(), $customer_groups)){
            return 0;
        }
        
        /*
         *  Condition 4: At least #number of items , base on rules in json
         */
        // Get discount percent base on qty
        // Example: If quote has 5 items $highest_step is {"qty_step": 5, "discount": 70}
        $cart_total = (int) count($satisfyItems);
        $highest_step = null;
        
        foreach($rule_json as $step){
            if((int)$step['qty_step'] == $cart_total){
                $highest_step = $step;
                break;
            }
        }
        
        /// For example: If there are more than 6 items, 
        /// $highest_step is still {"qty_step": 6, "discount": 99}
        $last_step = $rule_json[count($rule_json)-1];
        if($last_step && $cart_total > (int)$last_step['qty_step']){
            $highest_step = $last_step;
        }
        
        if(!$highest_step){
            return 0;
        }
        
        /*
         *  Condition 5: Sub-total < 5,000,000 Dong
         *  Quote Grand Total here is 'Thanh Tien' on Web UI
         */
        $max_subtotal = (int)Mage::getStoreConfig('event_buy_more_discount_more/config/max_subtotal');
        $quote_GrandTotal = $quote->getGrandTotal();
        
        if((int)$quote_GrandTotal >= $max_subtotal){
            return 0;
        }
        
        /// Try to get discount_percent
        $discount_percent = 0;
        if($highest_step && (int)$highest_step['discount'] > 0){
            $discount_percent = (int)$highest_step['discount'];
        }
        
        /// Get the lowest-price product in cart
        $lowest_price = PHP_INT_MAX ;
        $lowest_price_product = null;
        foreach($satisfyItems as $item){
            if($lowest_price > $item->getPriceInclTax()){
                $lowest_price = $item->getPriceInclTax();
                $lowest_price_product = $item;
            }
        }
        
        $discount_amount = 0;
        if($lowest_price_product){
            $discount_amount = $lowest_price_product->getPriceInclTax() * ($discount_percent/100);
            $discount_item_message = Mage::getStoreConfig('event_buy_more_discount_more/config/discount_item_message');
            $lowest_price_product->addMessage($discount_item_message);
        }
        
        return $discount_amount;
    }
    
    public function checkProductSatifyCategory($product, $product_category_id, $exclude_cats) {
        $cat_main = $product->getCategoryMainId();
        $cat_mid = $product->getCategoryMidId();
        $cat_id_3 = $product->getData('category_1_id');
        if ($cat_main != $product_category_id) {
            return false;
        }

        foreach ($exclude_cats as $exc_cat) {
            $exc_cat = explode("-", $exc_cat);
            if ($exc_cat[0] == $cat_mid) {
                if ($exc_cat[1]) {
                    if ($exc_cat[1] == $cat_id_3) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

        return true;
    }
}
