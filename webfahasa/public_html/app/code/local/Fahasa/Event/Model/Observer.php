<?php

class Fahasa_Event_Model_Observer {

    public function eventCorrectItemQty($observer){
        if(Mage::helper('event/buffetcombo')->isBuffetActive()){
            $this->correctBuffetcomboQuantity($observer);
        }
        
        if(Mage::getStoreConfig('event_discountoriginal/config/is_active')){
            $this->checkMarketingCostEventDiscountOriginal($observer);
        }
    }
    
    /*
     *  Event Buffet Combo: subtract stock quantity when making an order
     */
    public function correctBuffetcomboQuantity($observer) {
        $adapter = $observer->getEvent()->getData('adapter');
        $operator = $observer->getEvent()->getData('operator');
        $quote = $observer->getEvent()->getData('quote');
        
        Mage::log("---------------START: Buffetcombo ------------------", Zend_Log::INFO, "buffet.log");
        Mage::log("Quote Id: ". $quote->getId(), Zend_Log::INFO, "buffet.log");
        
        /// Try to create a buffet combo
        $checkout_session = Mage::getSingleton('checkout/session');
        $session_buffet_combo = $checkout_session->getBuffetcombo();
        $buffet_helper = Mage::helper("event/buffetcombo");
        $buffet_combo = $buffet_helper->pickBuffetcombo($quote->getAllVisibleItems(), true, $adapter);
        
        /*
         *  Update current_qty for those items that are in cart and in fhs_event_buffetcombo_product
         */
        $item_ids = array();
        $buffetcombo_id = Mage::getStoreConfig('event_buffetcombo/config/active_buffetcombo_id');
        
        $update_quantity_query = "UPDATE fhs_event_buffetcombo_product bp " .
                "SET bp.current_qty = CASE bp.product_id ";
        
        foreach ($quote->getAllVisibleItems() as $item) {
            $update_quantity_query .= "WHEN " . $item->getProductId() . " THEN bp.current_qty " . $operator . " " . $item->getQty() . " ";
            $item_ids[] = $item->getProductId();
        }
        
        $update_quantity_query .= " ELSE bp.current_qty END ";
        
        $_temp_ids = implode(",", $item_ids);
        $update_quantity_query .= " WHERE bp.buffetcombo_id = :buffetcombo_id AND bp.product_id IN(" . $_temp_ids . ");";
        
        $query_binding = array(
            'buffetcombo_id' => (int) $buffetcombo_id
        );
        
        $adapter->query($update_quantity_query, $query_binding);
        Mage::log("Update Buffet Product Quantity:", Zend_Log::INFO, "buffet.log");
        Mage::log($update_quantity_query, Zend_Log::INFO, "buffet.log");
        
        /*
         * Update "Out of Stock" items in Redis
         */
        $query_qty_sql = "SELECT * FROM fhs_event_buffetcombo_product WHERE buffetcombo_id = :buffetcombo_id AND product_id IN(" . $_temp_ids . ");";
        $query_qty_binding = array(
            'buffetcombo_id' => (int) $buffetcombo_id
        );
        $all_products_in_cart = $adapter->fetchAll($query_qty_sql, $query_qty_binding);
        
        $redis_client = Mage::helper("flashsale/redis")->createRedisClient();
        $redis_multi = $redis_client->multi();
        foreach ($all_products_in_cart as $product) {
            if ($product['current_qty'] <= 0) {
                $product_key = "buffetcombo:". (int)$buffetcombo_id .":product:". $product['product_id'];
                $redis_multi->hSet($product_key, "current_qty", 0);
            }
        }
        $redis_multi->exec();
        $redis_client->close();
        
        //// This cart doens't have buffet combo
        if(!$buffet_combo && !$session_buffet_combo){
            Mage::log("This cart doesn't have buffet combo: ", Zend_Log::INFO, "buffet.log");
            return;
        }
        
        //// This cart has buffet combo in session, but can't create it now
        //// This happens when total_combo <= 0 in fhs_event_buffetcombo
        if(!$buffet_combo && $session_buffet_combo){
            Mage::throwException(Mage::helper('event/buffetcombo')->__('There are no more Buffet Combos.'));
        }
        
        /*
         * Notify customer that buffet products are out of stock
         * This only for concurrent customers, prevent race condition
         */
        foreach ($buffet_combo['products'] as $product) {
            Mage::log("Product Id: " . $product['product_id'] . " - Current Qty: " . $product['current_qty'], Zend_Log::INFO, "buffet.log");
            if ($product['current_qty'] <= 0) {
                Mage::throwException(Mage::helper('event/buffetcombo')->__('A product in Buffet Combo has run out of stock.("%s")', $item->getName()));
            }
        }
        
        /*
         * Update Buffet Total Combo
         */
        $use_gift = Mage::getStoreConfig('event_buffetcombo/config/use_gift');
        $gift_id = $checkout_session->getData('buffetcombo_gift_id');
        
        /*
         *  When we have gift feature enabled, we update total_combo base on number of gifts.
         */
        if($use_gift){
            if($gift_id){
                $total_combo_update_query = "UPDATE fhs_event_buffetcombo SET total_combo = total_combo - 1 WHERE id = :buffetcombo_id;";
                $adapter->query($total_combo_update_query, $query_binding);
            }
        }else{
            $total_combo_update_query = "UPDATE fhs_event_buffetcombo SET total_combo = total_combo - 1 WHERE id = :buffetcombo_id;";
            $adapter->query($total_combo_update_query, $query_binding);
        }
        
        /*
         * Insert into Buffet Order History
         */
        $insert_query_binding = array(
            'order_increment_id' => $quote->getReservedOrderId(),
            'buffetcombo_id' => (int) $buffetcombo_id,
            'product_ids' => implode(",", $buffet_combo['ids']),
            'price' => $buffet_combo['price'],
            'count' => $buffet_combo['count'],
            'gift_id' => $gift_id
        );
        
        $insert_sql = "INSERT INTO fhs_event_buffetcombo_order(order_increment_id, buffetcombo_id, product_ids, price, count, gift_id) ".
            "VALUES(:order_increment_id, :buffetcombo_id, :product_ids, :price, :count, :gift_id); ";
        $adapter->query($insert_sql, $insert_query_binding);
        
        /*
         *  Clear all data in session
         */
        
        Mage::log("--------------- END ------------------", Zend_Log::INFO, "buffet.log");
    }

    /*
     *  Event Discount Original: Check marketing cost
     */
    public function checkMarketingCostEventDiscountOriginal($observer){
        $quote = $observer->getEvent()->getData('quote');
        
        $campaign_id = Mage::getStoreConfig('event_discountoriginal/config/active_campaign_id');
        
        $query = "SELECT d.id as 'campaign_id', d.name, d.from_date, d.to_date, d.discount_percent, dp.rule_id, 
                    dp.id as 'period_id', dp.start_time, dp.stop_time, dp.max_mkt_fee, 
                    dp.discountoriginal_id, dp.current_mkt_fee, dp.coupon_code , s.is_active
                    FROM fhs_event_discountoriginal_period dp
                    JOIN fhs_event_discountoriginal d ON d.id = dp.discountoriginal_id
                    JOIN fhs_salesrule s ON s.rule_id = dp.rule_id
                    WHERE discountoriginal_id = :id
                    AND NOW() >= start_time AND NOW() < stop_time LIMIT 1;";
        
        $query_binding = array(
            'id'=> $campaign_id
        );
        
        $adapter = $observer->getEvent()->getData('adapter');
        $event_period = $adapter->fetchRow($query, $query_binding);
        
        if(!$event_period){
            return;
        }
        
        $event_period['coupon_code'] = trim($event_period['coupon_code']);
        
        if((string)$event_period['coupon_code'] != (string)$quote->getCouponCode()){
            return;
        }
        
        $discountTotal = 0;
        foreach ($quote->getAllItems() as $item){
            $discountTotal += $item->getDiscountAmount();
        }

        $event_period['current_mkt_fee'] = (int)$event_period['current_mkt_fee'] + $discountTotal;

        $sql_update = "UPDATE fhs_event_discountoriginal_period SET current_mkt_fee = :fee where id = :id;";
        $sql_binding = array(
            'id' => $event_period['period_id'],
            'fee' => $event_period['current_mkt_fee']
        );

        $adapter->query($sql_update, $sql_binding);

        /// if current fee is > max, deactivate rule
        if($event_period['current_mkt_fee'] >= (int)$event_period['max_mkt_fee']){
            if($event_period['is_active']){
                $sql_update = "update fhs_salesrule set is_active = 0 WHERE rule_id =:rule_id ;";
                $sql_binding = array(
                    'rule_id' => $event_period['rule_id']
                );
                
                $adapter->query($sql_update, $sql_binding);
            } else {
                Mage::throwException(Mage::helper('event')->__('Coupon has been expired.'));
            }
        }
    }
    
    /*
     *  Event Buy More Discount More: calculate and apply discount
     */
    public function applyEventBuyMoreDiscountMore($observer){
        //Mage::log("Enter Event 1", null, "a.log");

        static $_getQuoteCallCount = 0;        
        if ($_getQuoteCallCount != 0) {
            return;
        }
//        $_getQuoteCallCount++;
//        $quote = Mage::getSingleton('checkout/session')->getQuote();
//        $_getQuoteCallCount--;
        $quote = Mage::helper("rediscart/cart")->getStaticQuote();

        //Mage::log("Enter Event 2", null, "a.log");
        $event_helper = Mage::helper("event/buymorediscountmore");
        if(!$event_helper->isActive()){
            return;
        }

        $helper = Mage::helper("event/data");
        //$quote = $observer->getEvent()->getQuote();
        $discount_label = Mage::getStoreConfig('event_buy_more_discount_more/config/discount_label');

        $discount_amount = $event_helper->calculateDiscount($quote);
        $helper->applyCustomDiscount($quote, $discount_amount, $discount_label);
    }
    
    /*
     *  Event Share facebook in fahasa birthday: render image for share
     */
    public function renderImageShare($observer){
	if(Mage::getStoreConfig('event_sharefacebook/share_render_image/is_active')){
	    $event_name = $observer->getEvent();
	    $customer_id = $observer->getId();
	    $text = $observer->getText();
	    
	    if($event_name != "test_image"){
		return;
	    }
	    
	    $helper = Mage::helper("event/data");
	    if(!empty($customer_id)){
		$customer_id = $helper->encryptor('decrypt',$customer_id);
		if($customer_id){
		    $customer = Mage::getModel('customer/customer')->load($customer_id);
		}
	    }
	    $background_image = Mage::getStoreConfig('event_sharefacebook/share_render_image/background_image');
	    if(empty($background_image)){
		return;
	    }

	    if(!empty($customer)){
		try{
		    $background_image = Mage::getBaseDir('media').'/event/'.$background_image;
		    // create background image layer
		    $img_path = pathinfo($background_image);
		    if($img_path['extension'] == 'png'){
			$image = imagecreatefrompng($background_image);
		    }else if($img_path['extension'] == 'jpep'){
			$image = imagecreatefromjpeg($background_image);
		    }

		    //image size
		    $width = imagesx($image);
		    $height = imagesy($image);
		    $fontSize = 14;

		    // Text fonts
		    $FONT_REGULAR = Mage::getBaseDir('skin').'/frontend/ma_vanese/ma_vanesa2/fonts/opensans-regular.ttf';
		    $FONT_BOLD = Mage::getBaseDir('skin').'/frontend/ma_vanese/ma_vanesa2/fonts/opensans-bold.ttf';
		    if(!empty($font_regular = Mage::getStoreConfig('event_sharefacebook/share_render_image/font_regular'))){
			$FONT_REGULAR = Mage::getBaseDir('media').$font_regular;
		    }
		    if(!empty($font_bold = Mage::getStoreConfig('event_sharefacebook/share_render_image/font_bold'))){
			$FONT_BOLD = Mage::getBaseDir('media').$font_bold;
		    }
		    
		    if(empty($text)){
			//calc time
			$time_ago = strtotime($customer->getCreatedAt().'+7 hour');
			$cur_time = strtotime('+7 hour');
			$time_elapsed = $cur_time - $time_ago;
			$days = round($time_elapsed / 86400 );
			
			$full_name = $customer->getLastname().' '.$customer->getFirstname();
			
			//Customer bought total
			$CustomerBoughtTotal = $helper->getCustomerBoughtTotal($customer_id);
			
			// Create text colours
//			$black = imagecolorallocate($image, 0, 0, 0);
//			$white = imagecolorallocate($image, 255, 255, 255);
//			$blue = imagecolorallocate($image, 51, 204, 255);
			$purple_light = imagecolorallocate($image, 157, 109, 255);
			$purple_dark = imagecolorallocate($image, 82, 61, 128);
			
			
			// Write Customer Name
			//$helper->writeText($image, 50, 14, $blue, $FONT_BOLD, $customer_name);
			imagettftext($image, $fontSize, 0, 358, 221, $purple_light, $FONT_BOLD, $full_name);

			//Write date created account 
			imagettftext($image, $fontSize, 0, 328, 265, $purple_dark, $FONT_BOLD, date('d/m/Y',strtotime('+7 hour')));

			//Write days created account 
			imagettftext($image, $fontSize, 0, 520, 265, $purple_light, $FONT_BOLD, $days." ngày");
			
			//Write bought total
			imagettftext($image, $fontSize, 0, 414, 335, $purple_light, $FONT_BOLD, number_format($CustomerBoughtTotal, 0, ",", "."). " đồng");
		    }else{
			$x = $observer->getX();
			$y = $observer->getY();
			$size = $observer->getSize();
			$bold = $observer->getBold();
			$cred = $observer->getCred();
			$cgreen = $observer->getCgreen();
			$cblue = $observer->getCblue();
			
			$color = imagecolorallocate($image, $cred, $cgreen, $cblue);
			$font = $FONT_REGULAR;
			if($bold){
			    $font = $FONT_BOLD;
			}
			//Write test to image
			imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
		    }

		    ob_start();
		    imagepng($image);
		    printf('<img id="output" src="data:image/png;base64,%s" />', base64_encode(ob_get_clean()));
		    imagedestroy($image);
		}catch (Exception $ex) {}
	    }else{
		printf('<img id="output" src="%s" />', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).Mage::getStoreConfig('event_sharefacebook/share_render_image/image_default'));
	    }
	}
    }
}
