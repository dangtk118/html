<?php

class Fahasa_Event_Helper_Buffetcombo extends Mage_Core_Helper_Abstract {
    
    /*
     * Create buffet combo from cart items
     */
    function pickBuffetcombo($cart_items, $to_lock_table = false, $adapter = null){
        $buffetcombo = $this->calculateBuffetcombo($cart_items, $to_lock_table, $adapter);
        $this->autoAddRemoveGift($buffetcombo);
        return $buffetcombo;
    }
    
    function calculateBuffetcombo($cart_items, $to_lock_table = false, $adapter = null){
        $checkout_session = Mage::getSingleton('checkout/session');
        
        if(!$this->isBuffetActive()){
            /// clear variable Buffetcombo in session
            $checkout_session->setBuffetcombo(null);
            return null;
        }
        
        $coupon_code = Mage::getSingleton('checkout/session')->getQuote()->getCouponCode();
        if($coupon_code){
            $checkout_session->setBuffetcombo(null);
            return null;
        }
        
        $combo_list_string = Mage::getStoreConfig('event_buffetcombo/config/combo_list');
        
        try{
            $combo_list = json_decode($combo_list_string, true);
        } catch (Exception $ex) {
            $checkout_session->setBuffetcombo(null);
            return null;
        }
        
        //Mage::log("Combo List:", null, "buffet.log");
        //Mage::log($combo_list, null, "buffet.log");
        
        if(count($cart_items) < (int)$combo_list[0]['count']){
            /// clear variable Buffetcombo in session
            $checkout_session->setBuffetcombo(null);
            return null;
        }
        
        $connection = $adapter ? $adapter: Mage::getSingleton('core/resource')->getConnection('core_read');
        $buffetcombo_id = Mage::getStoreConfig('event_buffetcombo/config/active_buffetcombo_id');
        
        if($this->isOutOfTotalCombo($connection, $buffetcombo_id, $to_lock_table)){
            /// clear variable Buffetcombo in session
            $checkout_session->setBuffetcombo(null);
            return null;
        }
        
        //Mage::log(count($cart_items), null, "buffet.log");
        /// $cart_buffet_results are buffet products that also in cart
        $cart_buffet_results = $this->getBuffetProductsFromCart($connection, $buffetcombo_id, $cart_items, $to_lock_table);

        $cart_buffet_items = array(); // Map (id => price)
        $buffet_products = array(); // Map (id => product)
        $is_out_of_stock = false;
        
        foreach($cart_buffet_results as $buffet_product){
            $buffet_products[$buffet_product['product_id']] = $buffet_product;
            /// Loop through cart item to so that we can use Item Object
            /// and we can get $cart_item->getPriceInclTax()
            foreach($cart_items as $cart_item){
                if($cart_item->getProductId() == $buffet_product['product_id']){
                    $cart_buffet_items[(int)$buffet_product['product_id']] = (int)$cart_item->getPriceInclTax();
                }
            }
            
            if($buffet_product['current_qty'] <= 0){
                $is_out_of_stock = true;
            }
        }
        
        //Mage::log("Cart Buffet Items:", null, "buffet.log");
        //Mage::log($cart_buffet_items, null, "buffet.log");
        if(count($cart_buffet_items) < (int)$combo_list[0]['count']){
            $checkout_session->setBuffetcombo(null);
            return null;
        }
        
        /// Sort buffet item by price, from high to low
        arsort($cart_buffet_items);
        //Mage::log("Sorted Buffet Items:", null, "buffet.log");
        //Mage::log($cart_buffet_items, null, "buffet.log");
        
        /// Create buffet combo
        $buffet_combo = null;
        
        foreach(array_reverse($combo_list) as $key=>$combo){
            if(count($cart_buffet_items) >= (int)$combo['count']){
                $id_price_map = array_slice($cart_buffet_items,0, (int)$combo['count'], true);
                $buffetcombo_products = array();
                foreach($buffet_products as $buffet_product){
                    $buffetcombo_products[] = $buffet_product;
                }

                $buffet_combo = array(
                    'index' => $key,
                    'ids' => array_keys($id_price_map),
                    'count' => (int)$combo['count'],
                    'price' => (int)$combo['price'],
                    'gift_id' => (int)$combo['gift_id'],
                    'products' => $buffetcombo_products,
                    'is_out_of_stock' => $is_out_of_stock
                );
                
                ///Mage::log($buffet_combo, null, "buffet.log");
                break;
            }
        }
        
        if(!$is_out_of_stock){
            /// Store Buffet Combo in Session
            $checkout_session->setBuffetcombo($buffet_combo);
        }else{
            $checkout_session->setBuffetcombo(null);
        }
        
        //Mage::log("Buffet Combo:", null, "buffet.log");
        //Mage::log($buffet_combo, null, "buffet.log");
        return $buffet_combo;
    }
    
    function isOutOfTotalCombo($connection, $buffetcombo_id, $to_lock_table){
        $query_sql = "SELECT total_combo FROM fhs_event_buffetcombo WHERE id = :buffetcombo_id ";
        
        if($to_lock_table){
            $query_sql .= " FOR UPDATE;";
        } else {
            $query_sql .= ";";
        }
        
        $query_binding = array(
            'buffetcombo_id' => (int) $buffetcombo_id
        );
        
        $buffet_campaign = $connection->fetchRow($query_sql, $query_binding);
        if(!$buffet_campaign || (int)$buffet_campaign['total_combo'] <= 0){
            return true;
        }
        
        return false;
    }
    
    function getBuffetProductsFromCart($connection, $buffetcombo_id, $cart_items, $to_lock_table = false){
        if(!$buffetcombo_id){
            return array();
        }
        
        $ids = array();
        foreach($cart_items as $cart_item){
            $ids[] = $cart_item->getProductId();
        }
        $_temp_ids = implode(",", $ids);
        $query_sql = "SELECT * FROM fhs_event_buffetcombo_product "
            . " WHERE buffetcombo_id = :buffetcombo_id "
            . " AND product_id IN(" . $_temp_ids . ") ";
        
        if($to_lock_table){
            $query_sql .= " FOR UPDATE;";
        } else {
            $query_sql .= ";";
        }
        
        $query_binding = array(
            'buffetcombo_id' => (int) $buffetcombo_id
        );
        //Mage::log($query_sql, null, "buffet.log");
        $products_result = $connection->fetchAll($query_sql, $query_binding);
        return $products_result;
    }
    
    function getDeleteUrl($item){
        return Mage::getUrl(
            'checkout/cart/delete',
            array(
                'id'=>$item->getId(),
                'form_key' => Mage::getSingleton('core/session')->getFormKey(),
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core/url')->getEncodedUrl()
            )
        );
    }
    
    //// Item Class Mage Sale_Quote_Item
    function seperateNormalAndBuffetItems($items){
        $checkout_session = Mage::getSingleton('checkout/session');
        $buffet_combo = $checkout_session->getBuffetcombo();
        $normal_items = array();
        $buffet_items = array();
        foreach ($items as $item){
            $qty = $item->getQty();
            foreach($buffet_combo['ids'] as $key=>$id){
                if($item->getProductId() == $id){
                    $buffet_items[] = $item;
                    $qty -= 1;
                    // NOTE: setQty is never zero, check the function
                    // Don't need setQty, because it will trigger observer
                    //$item->setQty($qty);
                    $item->setData('qty', $qty);
                    //$item->setRowTotalInclTax($qty*$item->getPriceInclTax());
                    $item->setData('row_total_incl_tax', $qty*$item->getPriceInclTax());
                }
            }
            
            if($qty > 0){
                $normal_items[$item->getProductId()] = $item;
            }
        }
        
        return array(
            'normal_items' => $normal_items,
            'buffet_items' => $buffet_items
        );
    }
    
    // Item Class Mage Sale_Order_Item
    function orderSeperateNormalAndBuffetItems($items){
        $checkout_session = Mage::getSingleton('checkout/session');
        $buffet_combo = $checkout_session->getBuffetcombo();
        $normal_items = array();
        $buffet_items = array();
        foreach ($items as $item){
            $qty = $item['qty_ordered'];
            foreach($buffet_combo['ids'] as $key=>$id){
                if($item['product_id'] == $id){
                    $buffet_items[] = $item;
                    $qty -=1;
                    $item['qty_ordered'] = $qty;
                }
            }
            
            if($qty > 0){
                $normal_items[$item['product_id']] = $item;
            }
        }
        
        return array(
            'normal_items' => $normal_items,
            'buffet_items' => $buffet_items
        );
    }
    
    function isBuffetActive(){
        $is_active = Mage::getStoreConfig('event_buffetcombo/config/is_active');
        $buffetcombo_id = Mage::getStoreConfig('event_buffetcombo/config/active_buffetcombo_id');
        if (!$is_active || !$buffetcombo_id) {
            return false;
        }
        
        return true;
    }
    
    function hasBuffetOrder(){
        if (!isBuffetActive()) {
            return false;
        }
        
        $checkout_session = Mage::getSingleton('checkout/session');
        if($checkout_session->hasBuffetcombo()){
            return true;
        }
        return false;
    }
    
    function getBuffetOrderItems($order){
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        $query_sql = "SELECT * FROM fhs_event_buffetcombo_order WHERE order_increment_id = :order_increment_id LIMIT 1;";
        $query_binding = array(
            'order_increment_id' => (int) $order->getIncrementId()
        );
        
        $order_result = $connection->fetchRow($query_sql, $query_binding);
        
        $normal_items = array();
        $buffet_items = array();
        $buffet_product_ids = explode(",", $order_result['product_ids']);
        if(!$buffet_product_ids){
            return array();
        }
        
        foreach ($order->getAllVisibleItems() as $item){
            $qty = $item->getQtyOrdered();
            foreach($buffet_product_ids as $id){
                if($item->getProductId() == $id){
                    $buffet_items[] = $item;
                    $qty -= 1;
                    // NOTE: setQty is never zero, check the function
                    $item->setQtyOrdered($qty);
                    $item->setRowTotalInclTax($qty*$item->getPriceInclTax());
                }
            }
            
            if($qty > 0){
                $normal_items[$item->getProductId()] = $item;
            }
        }
        
        return array(
            'normal_items' => $normal_items,
            'buffet_items' => $buffet_items,
            'buffet_order' => $order_result
        );
    }
    
    function copyDataToRedis(){
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $buffetcombo_id = Mage::getStoreConfig('event_buffetcombo/config/active_buffetcombo_id');
        
        $query_binding = array(
            'buffetcombo_id' => (int) $buffetcombo_id
        );
        
        /// Query All Buffet Products in Campaign
        $query_products = "SELECT bp.product_id, bp.name as product_name, bp.product_url, bp.current_qty, category_id, "
                        ."if(sp.final_price = 0,0,concat(format(sp.final_price,0,'vi_VN'),'đ')) as 'display_final_price', "
                        ."if(sp.final_price = 0,0,sp.final_price) as 'final_price', "
                        ."if(p.value = 0, 0, concat(format(p.value, 0, 'vi_VN'), 'đ')) as 'display_price', "
                        ."if(p.value = 0, 0, p.value) as 'price', "
                        ."if(sp.final_price = p.value,0,ROUND(((p.value - sp.final_price)/p.value)*100)) as 'discount', "
                        ."pe.type_id, "
                        ."if(bd.final_price = 0, 0, concat(format(bd.final_price, 0, 'vi_VN'), 'đ')) as 'bundle_display_final_price', "
                        ."if(bd.final_price = 0, 0, bd.final_price) as 'bundle_final_price', "
                        ."if(bd.price = 0, 0, concat(format(bd.price, 0, 'vi_VN'), 'đ')) as 'bundle_display_price', "
                        ."if(bd.price = 0, 0, bd.price) as 'bundle_price', "
                        ."if(bd.final_price = 0, 0, ROUND(((bd.price - bd.final_price) / bd.price) * 100)) as 'bundle_discount', "
			."if(category_id = 6305, 500, pe.num_orders) as num_orders, if(category_id = 6305, 500, pe.num_orders_year) as num_orders_year, "
                        ."if(category_id = 6305, 500, pe.num_orders_month) as num_orders_month, "
                        ."url0.value as product_url_store_0, url1.value as product_url_store_1 "
                        ."FROM fhs_catalog_product_entity pe  "
                        ."LEFT JOIN fhs_catalog_product_entity_decimal p ON p.entity_id = pe.entity_id AND p.attribute_id = 75 "
                        . "LEFT JOIN fhs_catalog_product_index_price_store sp on sp.entity_id = pe.entity_id and sp.store_id = 1 and sp.customer_group_id = 0  "
                        ."LEFT JOIN fhs_catalog_product_entity_varchar url0 ON pe.entity_id = url0.entity_id AND url0.attribute_id = 98 and url0.store_id=0 "
                        ."LEFT JOIN fhs_catalog_product_entity_varchar url1 ON pe.entity_id = url1.entity_id AND url1.attribute_id = 98 and url1.store_id=1 "
                        ."JOIN fhs_catalog_product_index_price_store bd on pe.entity_id = bd.entity_id and bd.customer_group_id = 0 and bd.store_id = 1 "
                        ."JOIN fhs_event_buffetcombo_product bp ON pe.entity_id = bp.product_id AND buffetcombo_id = :buffetcombo_id "
                        ."LEFT JOIN fhs_catalog_product_entity_int visi on visi.attribute_id = 102 and visi.entity_id = pe.entity_id "
                        ."LEFT JOIN fhs_cataloginventory_stock_item stock on stock.product_id = pe.entity_id "
                        ."where visi.value = 4 and stock.qty > 0 ;";
        
        $order_by_types = array(
            'week' => 'num_orders',
            'month' => 'num_orders_month',
            'year' => 'num_orders_year'
        );
        
        $products_results = array();
        foreach($order_by_types as $key => $order_by){
            $_query = $query_products ." order by ". $order_by ." desc;";
            $products_results[$key] = $connection->fetchAll($_query, $query_binding);
            
            if (empty($products_results[$key])) {
                return array(
                    "result" => false,
                    "msg" => "Query returns empty results."
                );
            }
        }
        
        $products_count = $products_results['week']?count($products_results['week']):0;
        
        /// Start Redis Connection
        $helper_redis = Mage::helper("flashsale/redis");
        $redis_client = $helper_redis->createRedisClient();
        if (!$redis_client->isConnected()) {
            return array(
                "result" => false,
                "msg" => "Can't connect to Redis."
            );
        }
        
        /*
         *  Delete previous combo:*
         */
        $redis_client->delete($redis_client->keys("buffetcombo:*"));
        $image_helper = Mage::helper('catalog/image');
                
        // Copy all products
        foreach ($products_results['week'] as $product) {
            $product_key = "buffetcombo:". (int)$buffetcombo_id .":product:". $product['product_id'];

            $product_model = Mage::getModel('catalog/product')->load($product['product_id']);
            $product['image_src'] = (string)$image_helper->init($product_model, 'small_image')->resize(400, 400);
            if($product['product_url_store_1']){
                $product['product_url'] = $product['product_url_store_1'];
            }else{
                $product['product_url'] = $product['product_url_store_0'];
            }

            unset($product['product_url_store_0']);
            unset($product['product_url_store_1']);
                
            if($product['type_id']=='bundle'){
                $product['display_price'] = $product['bundle_display_price'];
                $product['display_final_price'] = $product['bundle_display_final_price'];
                $product['discount'] = $product['bundle_discount'];
                $product['price'] = $product['bundle_price'];
                $product['final_price'] = $product['bundle_final_price'];
            }
            
            $redis_client->hMSet($product_key, $product);
        }
        
        $all_cat = array(
            'list' => array(),
            'count' => 0
        );
        
        $categories = array();
        foreach ($products_results as $order_key => $products_result){
            foreach ($products_result as $product) {
                if(!$categories[$order_key]){
                    $categories[$order_key] = array();
                }
                
                if($categories[$order_key][$product['category_id']]){
                    $categories[$order_key][$product['category_id']]['list'][] = $product['product_id'];
                    $categories[$order_key][$product['category_id']]['count'] += 1;
                }else{
                    $categories[$order_key][$product['category_id']] = array(
                        'list' => array($product['product_id']),
                        'count' => 1
                    );
                }
                
                if(!$all_cat[$order_key]){
                    $all_cat[$order_key] = array();
                }
                
                $all_cat[$order_key]['list'][] = $product['product_id'];
                $all_cat[$order_key]['count'] += 1;
            }
        }
        
        foreach ($categories as $order_key => $order_value) {
            foreach ($order_value as $cat_id => $cat) {
                $cat_key = "buffetcombo:". (int)$buffetcombo_id . ":order:" . $order_key . ":category:". $cat_id;
                $cat_json = json_encode($cat);
                $redis_client->set($cat_key,$cat_json);
            }
        }
        
        foreach($order_by_types as $key => $order_by){
            $all_cat_key = "buffetcombo:". (int)$buffetcombo_id . ":order:" . $key . ":category:all";
            $all_cat_json = json_encode($all_cat[$key]);
            $redis_client->set($all_cat_key,$all_cat_json);
        }
        
        $redis_client->close();
        return array(
            "result" => true,
            "total" => $products_count
        );
    }
    
    function autoAddRemoveGift($buffetcombo){
        //Mage::log("Auto Add/Remove Gift", null, "buffet.log");
        $checkout_session = Mage::getSingleton('checkout/session');
        $gift_id = $checkout_session->getData('buffetcombo_gift_id');
        $quote = $checkout_session->getQuote();
        $use_gift = Mage::getStoreConfig('event_buffetcombo/config/use_gift');
        
        /// Clearn Up all gifts
        foreach ($quote->getAllItems() as $item) {
            if ($item->getIsFreeProduct()) {
                //Mage::log("Delete Free Item: ". $item->getName(), null, "buffet.log");
                $quote->removeItem($item->getId());
            }
        }
        
        if ($buffetcombo && $use_gift && $gift_id) {
            $combo_list_string = Mage::getStoreConfig('event_buffetcombo/config/combo_list');
            $combo_list = json_decode($combo_list_string, true);
            $combo_list = array_reverse($combo_list);

            $new_gift_id = $combo_list[(int) $buffetcombo['index']]['gift_id'];
            $this->addGift($quote, $new_gift_id, $checkout_session);
            //Mage::log("Auto Add/Remove Gift: Re-add Gift", null, "buffet.log");
        }
    }
    
    function addGift($quote, $gift_id, $checkout_session){
        //Mage::log("Add Gift", null, "buffet.log");
        $isGiftAdd = false;
        Mage::log("Begin add gift quote_id=" . $quote->getId() . ", email= " . $quote->getCustomerEmail(), null, "buffet_gift.log");
        foreach ($quote->getAllItems() as $item) {
            Mage::log("quote_id=". $quote->getId() . " product_id=" . $item->getProductId() . ", sku=". $item->getSku() . ', qty=' . $item->getQty() . ", name=" .$item->getName(), null, "buffet_gift.log");
            if ($item->getIsFreeProduct() && $item->getProductId() == $gift_id ) {
                Mage::log("INSIDE HAS GIFT_ID: quote_id=". $quote->getId() . " product_id=" . $item->getProductId() . ", sku=". $item->getSku() . ', qty=' . $item->getQty() . ", name=" .$item->getName() . " inside has gift_id", null, "buffet_gift.log");
                $isGiftAdd = true;
                break;
            }
        }
        if($isGiftAdd){
            return;
        }
        $product = Mage::getModel('catalog/product')->load($gift_id);
        if ($product->getTypeId() == "bundle") {
            $bundled_items = array();
            $optionCollection = $product->getTypeInstance()->getOptionsCollection();
            $selectionCollection = $product->getTypeInstance()->getSelectionsCollection($product->getTypeInstance()->getOptionsIds());
            $options = $optionCollection->appendSelections($selectionCollection);

            foreach ($options as $option) {
                $_selections = $option->getSelections();

                foreach ($_selections as $selection) {
                    $bundled_items[$option->getOptionId()][] = $selection->getSelectionId();
                }
            }
            
            //Mage::log("Gift Bundle", null, "buffet.log");
            $params = array('bundle_option' => $bundled_items, 'qty' => 1, 'product' => $gift_id, "is_free_product"  => 1);
        } else {
            //Mage::log("Gift Simple", null, "buffet.log");
            $params = array('qty' => 1, 'product' => $gift_id, "is_free_product"  => 1);
        }
        
        //we need load model product 2 times because it will cause web die in checkout/cart page
        $product = Mage::getModel('catalog/product')->load($gift_id);
        
        $checkout_session->setData('buffetcombo_gift_id', (int)$gift_id);
        $quote->addProduct($product, new Varien_Object($params));
        $quote->save();
        
        foreach ($quote->getAllItems() as $item) {
            Mage::log("After save quote: quote_id=". $quote->getId() . " product_id=" . $item->getProductId() . ", sku=". $item->getSku() . ', qty=' . $item->getQty() . ", name=" .$item->getName(), null, "buffet_gift.log");
        }
    }
    
    function removeGift($quote, $gift_id, $checkout_session) {
        //Mage::log("Remove Gift", null, "buffet.log");
        Mage::log("Begin remove gift - quote_id=" . $quote->getId() . ', email=' . $quote->getCustomerEmail(), null, "buffet_gift.log");
        foreach ($quote->getAllItems() as $item) {
            Mage::log("Begin remove gift: quote_id=". $quote->getId() . " product_id=" . $item->getProductId() . ", sku=". $item->getSku() . ', qty=' . $item->getQty() . ", name=" .$item->getName(), null, "buffet_gift.log");
        }
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getProductId() == $gift_id) {
                $quote->removeItem($item->getId());
            }
        }
        
        $checkout_session->unsetData('buffetcombo_gift_id');
        $quote->save();
        Mage::log("Remove Gift: after save quote - quote_id=" . $quote->getId() . ', email=' . $quote->getCustomerEmail(), null, "buffet_gift.log");
        foreach ($quote->getAllItems() as $item) {
            Mage::log("Remove Gift: After save quote: quote_id=". $quote->getId() . " product_id=" . $item->getProductId() . ", sku=". $item->getSku() . ', qty=' . $item->getQty() . ", name=" .$item->getName() , null, "buffet_gift.log");
        }
    }
    
    function getBuffetIcon(){
        $buffetcombo_icon = Mage::getStoreConfig('event_buffetcombo/config/buffetcombo_icon');
        $buffetcombo_icon = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "event/" . $buffetcombo_icon;
        return $buffetcombo_icon;
    }
    
    function getGiftIcon(){
        $gift_icon = Mage::getStoreConfig('event_buffetcombo/config/gift_icon');
        $gift_icon = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "event/" . $gift_icon;
        return $gift_icon;
    }
    
    function getGiftTitle(){
        return Mage::getStoreConfig("event_buffetcombo/config/gift_title");
    }
    
    public function addBuffetComboGift(){
        $checkout_session = Mage::getSingleton('checkout/session');
        $quote = $checkout_session->getQuote();
        $buffet_helper = Mage::helper("event/buffetcombo");
        $use_gift = false;
        
        /// Only Add/Remove Gift , if we can have buffet combo
        $buffetcombo = $checkout_session->getBuffetcombo();
        if(!$buffetcombo){
            $data = array(
                'result' => false,
                'error' => "No Buffet Combo"
            );
            
            return $data;
        }
        
        $message = "";
        //Mage::log("Gift ID: ". $gift_id, null, "buffet.log");
        $gift_id = $checkout_session->getData('buffetcombo_gift_id');
        if ($gift_id) {
            $product = \Mage::getModel('catalog/product')
                ->setStoreId(\Mage::app()->getStore()->getStoreId())
                ->load($gift_id);
            //Mage::log("Remove Gift", null, "buffet.log");
            $buffet_helper->removeGift($quote, $gift_id, $checkout_session);
            $message = "Bạn đã gỡ bỏ quà tặng " . $product->name . " thành công";

        } else {
           /*
             *  Select gift id for combo
             */
            
            $combo_list_string = Mage::getStoreConfig('event_buffetcombo/config/combo_list');
            $combo_list = json_decode($combo_list_string, true);
            $combo_list = array_reverse($combo_list);
            
            $gift_id = $combo_list[(int)$buffetcombo['index']]['gift_id'];            
            if ($gift_id) {
                $product = \Mage::getModel('catalog/product')
                        ->setStoreId(\Mage::app()->getStore()->getStoreId())
                        ->load($gift_id);
                //Mage::log("Add Gift", null, "buffet.log");
                $buffet_helper->addGift($quote, $gift_id, $checkout_session);
                $use_gift = true;
                $message = "Bạn đã thêm quà tặng " . $product->name . " thành công. ";
                if ($this->checkDeliveryMessage())
                {
                    $message .= Mage::getStoreConfig('event_buffetcombo/config/gift_delivery_message_mobile');
                }
            }
        }
        
        $data = array(
            'use_gift' => $use_gift,
            'message' => $message
        );
        
        return $data;
    }
    
    function checkDeliveryMessage(){
        $HO_CHI_MINH_REGION_ID = "485";
        
        $result = false;
        
        $gift_delivery = Mage::getStoreConfig('event_buffetcombo/config/gift_delivery');
        $gift_delivery_only_suburban = Mage::getStoreConfig('event_buffetcombo/config/gift_delivery_only_suburban');
        
        $checkout_session = Mage::getSingleton('checkout/session');
        $quote = $checkout_session->getQuote();
        $region_id = $quote->getShippingAddress()->getRegionId();
        
        if($gift_delivery){
            if($gift_delivery_only_suburban){
                if($region_id  != $HO_CHI_MINH_REGION_ID){
                    $result = true;
                }
            }else{
                $result = true;
            }
        }
        
        return $result;
    }


    function checkBuffetCombo(){
        $checkout_session = Mage::getSingleton('checkout/session');
        $buffetcombo = $checkout_session->getData('buffetcombo');
        $gift_id = $checkout_session->getData('buffetcombo_gift_id');
        $giftInfo = null;
        $use_gift = Mage::getStoreConfig('event_buffetcombo/config/use_gift');
        
        if ($use_gift){
            if ($buffetcombo){
                $giftInfo["image"] = \Mage::helper("event/buffetcombo")->getGiftIcon(); 
                $giftInfo["title"] = \Mage::helper("event/buffetcombo")->getGiftTitle();
            }

            $show_deliver_message = $this->checkDeliveryMessage();
            $deliver_message = "";
            if ($show_deliver_message){
                $deliver_message = Mage::getStoreConfig('event_buffetcombo/config/gift_delivery_message');
            }
      
            return array(
                'has_buffetcombo' => $buffetcombo ? true : false,
                'has_gift' => $gift_id ? true : false,
                'show_delivery_message' => $show_deliver_message,
                'delivery_message' => $deliver_message,
                'giftInfo' => $giftInfo
            );
        }else{
            //because no use_gift, mobile check based on has_buffetcombo flag.
            //has_buffetcombo: is used to show box gift
            return array(
                'has_buffetcombo' => false
            );
        }
    }
    
    public function setTimeUsedExpired(){
	$msg = "";
	try{
	    if (Mage::getStoreConfig('event_discountoriginal/config/is_active')){
		$discount_orginal_id = Mage::getStoreConfig('event_discountoriginal/config/active_campaign_id');
		if($discount_orginal_id){
		    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
		    $binds = array('discountoriginal_id' => $discount_orginal_id);
		    $sql = "update fhs_salesrule_coupon sc
			join (
			    select dp.id, dp.stop_time, c.coupon_id, c.code, c.times_used, c.usage_limit
			    from fhs_event_discountoriginal_period dp 
			    join fhs_salesrule r on dp.rule_id = r.rule_id and r.is_active = 1
			    join fhs_salesrule_coupon c on c.rule_id =r.rule_id and c.code = dp.coupon_code and c.times_used < c.usage_limit 
			    where dp.stop_time < now()
			    group by c.coupon_id
			) c on c.coupon_id = sc.coupon_id
			set sc.times_used = (sc.usage_limit + sc.times_used);";
		    $writer->query($sql, $binds);
		    $msg = "Done";
		}else{	
                    $msg = "can't get discount_orginal_id";
		}
	    }else{
		    $msg = "event not active";
		}
	} catch (Exception $ex) {
	    $msg = $ex->getMessage();
	}
	
        return array(
            "result" => true,
            "msg" => $msg
        );
    }
}
