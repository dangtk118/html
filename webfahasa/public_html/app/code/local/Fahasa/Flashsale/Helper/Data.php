<?php

class Fahasa_Flashsale_Helper_Data_Error {

    const DISABLED_FLASHSALE = "disabled_flashsale";
    const NO_ACTIVE_FLASHSALE = "no_active_flashsale";
    const NO_ACTIVE_PERIOD = "no_active_period";
    const NO_CONNECTION = "no_connection";
    const NO_PERIODS = "no_periods";
    const NO_PRODUCTS = "no_products";

}

class Fahasa_Flashsale_Helper_Data extends Mage_Core_Helper_Abstract {

    const FLASHSALE_KEY_NAME = "flashsale";
    const PERIOD_KEY_NAME = "flashsale_period";
    const PRODUCT_KEY_NAME = "product_entity";
    const SEPERATOR = ":";
    const FHS_FLASHSALE = "fhs_flashsale";
    const FHS_FLASHSALE_PERIOD = "fhs_flashsale_period";
    const FHS_FLASHSALE_PRODUCTS = "fhs_flashsale_product";
    const MAX_SALE_ITEM = 1;
    
    const GROUP_TYPE_NONE = 0;
    const GROUP_TYPE_CATEGORY_MID = 1;
    const GROUP_TYPE_SUPPLIER = 2;
    
    public function copyDataFromMysqlToRedis() {

        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');

        $flashsale_id = (int) Mage::getStoreConfig('flashsale_config/config/active_flashsale_id');
        $query_flashsale_binds = array('id' => $flashsale_id);
        /// Query Flashsale
        $query_flashsale = "select * from " . Fahasa_Flashsale_Helper_Data::FHS_FLASHSALE . " where id = :id";
        $flashsale_result = $connection->fetchRow($query_flashsale, $query_flashsale_binds);
        if (empty($flashsale_result)) {
            return array(
                "result" => false,
                "error_type" => Fahasa_Flashsale_Helper_Data_Error::NO_ACTIVE_FLASHSALE
            );
        }

        /// Query Flashsale Periods
        $query_period = "select * from " . Fahasa_Flashsale_Helper_Data::FHS_FLASHSALE_PERIOD . " where flashsale_id = :id";
        $periods_result = $connection->fetchAll($query_period, $query_flashsale_binds);
        if (empty($periods_result)) {
            return array(
                "result" => false,
                "error_type" => Fahasa_Flashsale_Helper_Data_Error::NO_PERIODS
            );
        }

        /// Query Products
        $query_products = "select fp.id, fp.flashsale_id, fp.period_id, fp.product_id, fp.original_price, fp.final_price, fp.flashsale_price, fp.total_items, fp.total_sold, "
                . "name.value as product_name, url0.value as product_url_store_0, url1.value as product_url_store_1, img_url.value as image_src, "
                . "if (count(child_price.value) > 1, 1, 0) as is_combo, price_index.final_price as bundle_final_price, "
                . "pe.category_mid, pe.category_mid_id, supplier.value as supplier, supplier_name.name as supplier_name ,ifnull(pe.episode, '') as 'episode'"
                . "from " . Fahasa_Flashsale_Helper_Data::FHS_FLASHSALE_PRODUCTS . " fp "
                // Don't join with index price table, use prices from flashsale product table
                //. "join fhs_catalog_product_index_price_store ps on ps.entity_id = fp.product_id and ps.store_id = 1 and ps.customer_group_id = 0 "
                . " join fhs_flashsale_period p on p.id = fp.period_id and p.flashsale_id = fp.flashsale_id  "
                . "join fhs_catalog_product_entity pe on pe.entity_id = fp.product_id "
                . "join fhs_catalog_product_entity_varchar name on name.entity_id = fp.product_id and name.attribute_id = 71 "
                . "left join fhs_catalog_product_entity_varchar url0 ON fp.product_id = url0.entity_id AND url0.attribute_id = 98 and url0.store_id=0 "
                . "left join fhs_catalog_product_entity_varchar url1 ON fp.product_id = url1.entity_id AND url1.attribute_id = 98 and url1.store_id=1 "
                . "join fhs_catalog_product_entity_varchar img_url on fp.product_id=img_url.entity_id and img_url.attribute_id=85 "
                . "join fhs_catalog_product_index_price_store price_index on price_index.entity_id = fp.product_id and "
                . "price_index.store_id = 1 and price_index.customer_group_id = 0 "
                . "left join fhs_catalog_product_bundle_selection bs on bs.parent_product_id = fp.product_id "
                . "left join fhs_catalog_product_entity_decimal child_price on child_price.entity_id = bs.product_id and child_price.attribute_id = 76 "
                . "and child_price.value > 0 "
                . "left join fhs_catalog_product_entity_varchar supplier on supplier.entity_id = pe.entity_id and supplier.attribute_id = 157 "
                . "left join fhs_page_keyword_url supplier_name on supplier_name.dataId = supplier.value "
                . "where fp.flashsale_id = :id  and p.end_date > now() and DATEDIFF(p.start_date ,now()) <= 2 "
                . "group by fp.period_id, fp.product_id order by fp.id ";
        $products_result = $connection->fetchAll($query_products, $query_flashsale_binds);
        echo "Period_id  =  ". $flashsale_id; 
        echo "TOTAL QUERY " . count($products_result) . "\n";
        if (empty($products_result)) {
            return array(
                "result" => false,
                "error_type" => Fahasa_Flashsale_Helper_Data_Error::NO_PRODUCTS
            );
        }

        /// Start Redis Connection
        $helper_redis = Mage::helper("flashsale/redis");
        $redis_client = $helper_redis->createRedisClientFlashSale();
        if (!$redis_client->isConnected()) {
            echo "No connect redis ";
            return array(
                "result" => false,
                "error_type" => Fahasa_Flashsale_Helper_Data_Error::NO_CONNECTION
            );
        }

        /*
         *  Delete flashsale:*
         */
        ///$redis_client->delete($redis_client->keys(FLASHSALE_KEY_NAME. ":*"));
        ///$redis_client->delete($redis_client->keys(PERIOD_KEY_NAME. ":*"));
        $redis_client->delete($redis_client->keys("flashsale:*"));
        $redis_client->delete($redis_client->keys("flashsale_period:*"));

        /// Periods
        $period_key = Fahasa_Flashsale_Helper_Data::PERIOD_KEY_NAME . Fahasa_Flashsale_Helper_Data::SEPERATOR;
        $catalog_product = Mage::helper('fahasa_catalog/product');
        $helperCatalogImage = Mage::helper('catalog/image');
        $category_periods = array();
        
        $group_type = Mage::getStoreConfig('flashsale_config/config/group_type');
        $helper_product = \Mage::helper('fahasa_catalog/Productredis');

        foreach ($periods_result as $period) {
            echo "copy period " . $period['id'] . "\n";
            $period_product_ids = array();
            $period_category_ids = array();
            /*
             *  Store Products Data
             */
            foreach ($products_result as $product) {
                if ($product['period_id'] !== $period['id']) {
                    continue;
                }
               
                $period_product_ids[] = $product['product_id'];
                $product_key = $period_key . $period['id'] . Fahasa_Flashsale_Helper_Data::SEPERATOR
                        . Fahasa_Flashsale_Helper_Data::PRODUCT_KEY_NAME
                        . Fahasa_Flashsale_Helper_Data::SEPERATOR
                        . $product['product_id'];
                
                /// Resize Image
                //// Set image url
                
                $product_model = $helper_product->getProductID($product['product_id'], false, true);
                if (!$product_model){
                    echo "no product";
                }
                $product['image_src'] = $product_model['small_image'];
                if($product['product_url_store_1']){
                    $product['product_url'] = Mage::getBaseUrl() . $product['product_url_store_1'];
                }else{
                    $product['product_url'] = Mage::getBaseUrl() . $product['product_url_store_0'];
                }
                
                unset($product['product_url_store_0']);
                unset($product['product_url_store_1']);
                if ($product["is_combo"]){
                    $product["final_price"] = $product["bundle_final_price"];
                }
                /// Calcualte Discount
                $product['old_discount'] = $catalog_product->calculateDiscount($product['original_price'], $product['final_price']);
                $product['discount'] = $catalog_product->calculateDiscount($product['original_price'], $product['flashsale_price']);
                /// Calculate Price
//                $old_prices = $catalog_product->displayPrice($product['original_price'], $product['final_price']);
//                $prices = $catalog_product->displayPrice($product['original_price'], $product['flashsale_price']);
                $orginal_price_format = number_format($product['original_price'], 0, ',', '.');
                $final_price_format = number_format($product['final_price'], 0, ',', '.');
                $flashsale_price_format = number_format($product['flashsale_price'], 0, ',', '.');
                $old_prices = array(
                    "old_price" => $orginal_price_format,
                    "new_price" => $final_price_format
                );
                $prices = array(
                    "old_price" => $orginal_price_format,
                    "new_price" => $flashsale_price_format
                );
                $product['display_old_price'] = $prices['old_price'];
                $product['display_old_final_price'] = $old_prices['new_price'];
                $product['display_new_price'] = $prices['new_price'];
                
                //create buffer_value at first time to show UI
                $rand_buffer_value = rand(40, 80);
                $product['buffer_value'] = $rand_buffer_value;
                $product['total_items'] = $rand_buffer_value;
                
                $product_json = json_encode($product);
                $redis_client->set($product_key, $product_json);
                //set category list
                if ($group_type == Fahasa_Flashsale_Helper_Data::GROUP_TYPE_CATEGORY_MID){
                     //set category list
                    if ($period_category_ids[$product['category_mid_id']])
                    {
                        $period_category_ids[$product['category_mid_id']]['product_ids'][] = $product['product_id'];
                        $period_category_ids[$product['category_mid_id']]['product_count'] += 1;
                    }
                    else
                    {
                        $period_category_ids[$product['category_mid_id']] = array(
                            'product_ids' => array($product['product_id']),
                            'product_count' => 1,
                            'name' => $product['category_mid']
                        );
                    }
                } else if ($group_type == Fahasa_Flashsale_Helper_Data::GROUP_TYPE_SUPPLIER){
                     //set category list
                    if ($period_category_ids[$product['supplier']])
                    {
                        $period_category_ids[$product['supplier']]['product_ids'][] = $product['product_id'];
                        $period_category_ids[$product['supplier']]['product_count'] += 1;
                    }
                    else
                    {
                        $period_category_ids[$product['supplier']] = array(
                            'product_ids' => array($product['product_id']),
                            'product_count' => 1,
                            'name' => $product['supplier_name']
                        );
                    }
                }
                
            }
            
            $period['product_ids'] = $period_product_ids;
            $period['product_count'] = count($period_product_ids);
            
            $period['categories'] = array();
            foreach ($period_category_ids as $key => $period_category_ids)
            {
                $period['categories'][] = array(
                    'id' => $key,
                    'name' => $period_category_ids['name'],
                    'product_count' => count($period_category_ids['product_ids']),
                    "product_ids" => $period_category_ids['product_ids'],
                );
            }
            array_multisort(array_column($period['categories'], 'product_count'), SORT_DESC, $period['categories']);
            $category_periods[$period['id']] = $period['categories'];

            $period_json = json_encode($period);
            $redis_client->set($period_key . $period['id'], $period_json);
        }
        
        
         /// Parses Results into key => value
        /// Flashsale
        $flashsale_key = Fahasa_Flashsale_Helper_Data::FLASHSALE_KEY_NAME . Fahasa_Flashsale_Helper_Data::SEPERATOR . $flashsale_id;
        $flashsale_periods = array();
        foreach ($periods_result as $period) {
            $flashsale_period_categories = array_map(function($item){
                return array(
                    "id" => $item['id'],
                    "name" => $item["name"],
                );
            }, $category_periods[$period['id']]);
            
            //check whether group type is active
            if ($group_type != Fahasa_Flashsale_Helper_Data::GROUP_TYPE_NONE){
                array_unshift($flashsale_period_categories, array(
                    "id" => 0,
                    "name" => "Tất cả"
                ));
            }

            $flashsale_periods[] = array(
                "period_id" => $period['id'],
                "start_date" => $period['start_date'],
                "end_date" => $period['end_date'],
                "categories" => $flashsale_period_categories,
            );
        }

        $flashsale_result['periods'] = $flashsale_periods;
        $flashsale_json = json_encode($flashsale_result);

        $redis_client->set($flashsale_key, $flashsale_json);

        $redis_client->close();
        return array(
            "result" => true,
            "msg" => "Success!"
        );
    }
    
    /*
     *  Set sold out from a list of ids
     */

    public function setSoldOut($period_id, $product_ids) {
        $redis_client = Mage::helper("flashsale/redis")->createRedisClientFlashSale();

        Mage::log("Period Id:" . $period_id, Zend_Log::INFO, "flashsale.log");

        $product_key = Fahasa_Flashsale_Helper_Data::PERIOD_KEY_NAME
                . Fahasa_Flashsale_Helper_Data::SEPERATOR
                . $period_id
                . Fahasa_Flashsale_Helper_Data::SEPERATOR
                . Fahasa_Flashsale_Helper_Data::PRODUCT_KEY_NAME . Fahasa_Flashsale_Helper_Data::SEPERATOR;

        foreach ($product_ids as $id) {
            $product_str = $redis_client->get($product_key . $id);
            $product = json_decode($product_str, true);
            if (!$product) {
                continue;
            }
            Mage::log("Set Sold out for Product Id:" . $id, Zend_Log::INFO, "flashsale.log");
            $product['total_sold'] = $product['total_items'];
            $product_json = json_encode($product);
            $redis_client->set($product_key . $id, $product_json);
        }

        $redis_client->close();
    }

    /*
     * Check Flash Sale Rules
     */

    public function checkFlashsaleRules($sale_quote) {

        $is_active = Mage::getStoreConfig('flashsale_config/config/is_active');
        $flashsale_id = Mage::getStoreConfig('flashsale_config/config/active_flashsale_id');
        /// If there is no Flash Sale, do nothing
        if (!$is_active || !$flashsale_id) {
            return array(
                'result' => true
            );
        }
        
        /*
         * Get only current FlashSale Products
         */
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query_products = "SELECT fprt.product_id, fprt.flashsale_price, si.max_sale_qty FROM fhs_flashsale_product fprt " .
                "JOIN fhs_flashsale_period fp ON (NOW() between fp.start_date  and fp.end_date) " .
                "AND fp.flashsale_id = :flashsale_id AND fprt.period_id = fp.id " .
                "JOIN fhs_cataloginventory_stock_item si ON si.product_id = fprt.product_id " .
                "WHERE fprt.flashsale_id = :flashsale_id";

        $query_binding = array(
            'flashsale_id' => (int) $flashsale_id
        );

        $products_result = $connection->fetchAll($query_products, $query_binding);
        $count = count($products_result);
        if($count <= 0){
            return array(
                'result' => true
            );
        }
        
        /// TODO: Check function getAllItems()
        $items = $sale_quote->getAllVisibleItems();
        $has_item_qty_above_1 = false;
        foreach ($products_result as $product) {
            foreach ($items as $item) {
                /// If items in cart are also in flashsale
                if ($product['product_id'] == $item->getProductId()) {
                    if ($item->getQty() > (int) $product['max_sale_qty']) {
                        $has_item_qty_above_1 = true;
                    }
                }
            }
        }
        
        if ($has_item_qty_above_1) {
            return array(
                'result' => false
            );
        }

        return array(
            'result' => true
        );
    }
    
    public function doesOrderHaveFlashSaleItem($quote){
        
        $is_active = Mage::getStoreConfig('flashsale_config/config/is_active');
        $flashsale_id = Mage::getStoreConfig('flashsale_config/config/active_flashsale_id');
        /// If there is no Flash Sale, do nothing
        if (!$is_active || !$flashsale_id) {
            return false;
        }
        
        /*
         * Get flashSale products in cart
         */
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $query_products = "SELECT fprt.product_id FROM fhs_flashsale_product fprt " .
                "JOIN fhs_flashsale_period fp ON (NOW() between fp.start_date  and fp.end_date) " .
                "AND fp.flashsale_id = :flashsale_id AND fprt.period_id = fp.id " .
                "JOIN fhs_cataloginventory_stock_item si ON si.product_id = fprt.product_id " .
                "WHERE fprt.flashsale_id = :flashsale_id";
        
        $query_binding = array(
            'flashsale_id' => (int) $flashsale_id
        );
        
        $products_result = $connection->fetchAll($query_products, $query_binding);
        $count = count($products_result);
        if($count <= 0){
            return false;
        }
        
        /// Get products in cart
        $items = $quote->getAllVisibleItems();
        $has_flash_item = false;
        foreach ($products_result as $product) {
            foreach ($items as $item) {
                /// If items in cart are also in flashsale
                if ($product['product_id'] == $item->getProductId()) {
                    $has_flash_item = true;
                    break;
                }
            }
        }
        
        return $has_flash_item;
    }

}
