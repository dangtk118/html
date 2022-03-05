<?php

class Fahasa_Flashsale_Model_Observer {

    const SPECIAL_PRICE_TO_DATE_HOUR = 5;

    /*
     *  
     */
    public function correctFlashSaleQuantity($observer) {
        $is_active = Mage::getStoreConfig('flashsale_config/config/is_active');
        $flashsale_id = Mage::getStoreConfig('flashsale_config/config/active_flashsale_id');
        if (!$is_active || !$flashsale_id) {
            return;
        }

        $adapter = $observer->getEvent()->getData('adapter');
        
        /*
         *  Check case operator MINUS
         */
        $operator = $observer->getEvent()->getData('operator');
        $quote = $observer->getEvent()->getData('quote');

        Mage::log("---------------START: correctFlashSaleQuantity------------------", Zend_Log::INFO, "flashsale.log");
        Mage::log("Quote Id: ". $quote->getId(), Zend_Log::INFO, "flashsale.log");
        /*
         *  Get products in quote are also in flash sale
         */
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $quote_product_ids = array();
        foreach ($quote->getAllVisibleItems() as $item){
            $quote_product_ids[] = $item->getProductId();
        }
        
        $_temp_ids = implode(",", $quote_product_ids);
        $query_binding = array(
            'flashsale_id' => (int) $flashsale_id
        );
        
        $query_products = "SELECT fprt.period_id, fprt.product_id, fprt.current_qty, fprt.flashsale_price, " .
                "fprt.saved_max_item, fprt.saved_use_config_max_item,fprt.saved_min_item, fprt.saved_use_config_min_item, " .
                "pe.type_id " .
                "FROM fhs_flashsale_product fprt " .
                "JOIN fhs_flashsale_period fp ON (NOW() between fp.start_date  and fp.end_date) " .
                "AND fp.flashsale_id = :flashsale_id AND fprt.period_id = fp.id " .
                "JOIN fhs_catalog_product_entity pe ON fprt.product_id = pe.entity_id " .
                "WHERE fprt.flashsale_id = :flashsale_id AND fprt.product_id IN(" . $_temp_ids . ") FOR UPDATE;";

        $products_result = $connection->fetchAll($query_products, $query_binding);
        if (empty($products_result)) {
            return;
        }
        
        /*
         *  Update these Flash Sale product's quantities
         */
        $update_quantity_query = "UPDATE fhs_flashsale_product fprt " .
                "JOIN fhs_flashsale_period fp ON (NOW() between fp.start_date  and fp.end_date) " .
                "AND fp.flashsale_id = :flashsale_id AND fprt.period_id = fp.id " .
                "SET fprt.current_qty = CASE fprt.product_id ";
        
        foreach ($quote->getAllVisibleItems() as $item) {
            $update_quantity_query .= "WHEN " . $item->getProductId() . " THEN fprt.current_qty " . $operator . " " . $item->getQty() . " ";
        }

        $update_quantity_query .= " ELSE fprt.current_qty END ";
        $update_quantity_query .= " WHERE fprt.flashsale_id = :flashsale_id AND fprt.product_id IN(" . $_temp_ids . ");";
        $adapter->query($update_quantity_query, $query_binding);

        Mage::log("Update Flash Sale Product Quantity:", Zend_Log::INFO, "flashsale.log");
        Mage::log($update_quantity_query, Zend_Log::INFO, "flashsale.log");
        
        if(!$quote){
            // $quote is passed as null when $operator is '+' 
            // Then we don't need to update price. Only update price when out of stock
            return;
        }
        
        /*
         * Classifiy products base on current quantity
         */
        $out_of_stocks_product_ids = array();
        $zero_stock_product_ids = array();
        $below_stock_product_ids = array();
        $zero_stock_bundle_product_ids = array();

        foreach ($products_result as $product) {
            foreach ($quote->getAllVisibleItems() as $item) {
                if ($product['product_id'] != $item->getProductId()) {
                    continue;
                }
                
                Mage::log("Product Id: " . $product['product_id'] . " - Current Qty: " . $product['current_qty'], Zend_Log::INFO, "flashsale.log");
                if ($product['current_qty'] == $item->getQty()) {
                    $out_of_stocks_product_ids[] = $product['product_id'];
                    $zero_stock_product_ids[] = $product['product_id'];
                    if($product['type_id'] == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
                        $zero_stock_bundle_product_ids[] = $product['product_id'];
                    }
                }

                if ($product['current_qty'] < $item->getQty()) {
                    $out_of_stocks_product_ids[] = $product['product_id'];
                    $below_stock_product_ids[(int) $product['product_id']] = true;
                }
            }
        }

        //// If there is no flash sale products that are out of stock, do nothing
        if (count($out_of_stocks_product_ids) <= 0) {
            return;
        }

        Mage::log("Zero Stock Products:", Zend_Log::INFO, "flashsale.log");
        Mage::log($zero_stock_product_ids, Zend_Log::INFO, "flashsale.log");
        Mage::log("Below Stock Products:", Zend_Log::INFO, "flashsale.log");
        Mage::log($below_stock_product_ids, Zend_Log::INFO, "flashsale.log");
        Mage::log("Bundle Products (qty = 0):", Zend_Log::INFO, "flashsale.log");
        Mage::log($zero_stock_bundle_product_ids, Zend_Log::INFO, "flashsale.log");
        
        /*
         *  If customer has a flashsale product (has flashsale price) , and current_qty < 0
         *  Then notify customer that price has changed !
         */
        /// getAllVisibleItems() ( not getAllItems ) : will get only parent items
        foreach ($quote->getAllVisibleItems() as $item) { /// In each item in cart, Item Class = Sales_Model_Quote_Item
            foreach ($products_result as $product) {  /// In each flashsale product in the active period
                /// Ignore quote items that are not flashs sale
                if($item->getProductId() != $product['product_id']){
                    continue;
                }
                // If flashsale current qty < 0
                $throw_condition_1 = $below_stock_product_ids[(int) $item->getProductId()];

                $a = (int) $item->getPriceInclTax();
                $b = (int) $product['flashsale_price'];
                // If item cart has flash sale price
                $throw_condition_2 = $a == $b;
                
                Mage::log("Product Id:". $product['product_id'] ." - Price: " . $a . " - FS Price: " . $b, Zend_Log::INFO, "flashsale.log");
                Mage::log("Conditon 1 (current qty < 0): " . $throw_condition_2, Zend_Log::INFO, "flashsale.log");
                Mage::log("Conditon 2 (cart price == flashsale price): " . $throw_condition_3, Zend_Log::INFO, "flashsale.log");
                
                /// This will prevent race condition for concurrent requests
                /// Reject requests that qty < 0, and have flash sale price
                if ($throw_condition_1 && $throw_condition_2) {
                    Mage::log("Reject Quote - Id: " . $quote->getId(), Zend_Log::INFO, "flashsale.log");
                    Mage::throwException(Mage::helper('cataloginventory')->__('Flash Sale price has changed for product "%s"', $item->getName()));
                }
            }
        }
        
        /// To Update Flash Sale Price if stock qty = 0
        if (count($zero_stock_product_ids) <= 0) {
            return;
        }
        
        $_temp_ids = implode(",", $zero_stock_product_ids);
        $update_binding = array(
            'flashsale_id' => (int) $flashsale_id,
            'to_hours' => self::SPECIAL_PRICE_TO_DATE_HOUR
        );
        
        /*
         *  This query updates values: special price, special from date, special to date, final_price in index table
         *  max_sale_qty, use_config_max_sale_qty
         *  Note: this also update bundles
         */
        $festival_store = Mage::getStoreConfig('book_festival/config/store_code');
        
        $adapter->query("UPDATE fhs_catalog_product_entity pe " .
                "JOIN fhs_catalog_product_entity_datetime sf ON sf.entity_id = pe.entity_id AND sf.attribute_id = 77 " .
                "JOIN fhs_catalog_product_entity_datetime st ON st.entity_id = pe.entity_id AND st.attribute_id = 78 " .
                "JOIN fhs_catalog_product_entity_decimal sp ON sp.entity_id = pe.entity_id AND sp.attribute_id = 76 and sp.store_id != {$festival_store} " .
                "JOIN fhs_catalog_product_index_price_store pt ON pt.entity_id = pe.entity_id and pt.store_id != {$festival_store} " .
                "JOIN fhs_flashsale_period fp ON (NOW() between fp.start_date  and fp.end_date) AND fp.flashsale_id =:flashsale_id " .
                "JOIN fhs_flashsale_product fprt ON pe.entity_id = fprt.product_id AND fp.id = fprt.period_id " .
                "JOIN fhs_cataloginventory_stock_item si ON pe.entity_id = si.product_id " .
                "SET sf.value = DATE_ADD(CURDATE(), INTERVAL 0 HOUR), st.value = DATE_ADD(CURDATE(), INTERVAL :to_hours HOUR), " .
                "sp.value = fprt.final_price,pt.final_price = fprt.final_price, " .
                "si.max_sale_qty = fprt.saved_max_item, " .
                "si.use_config_max_sale_qty = fprt.saved_use_config_max_item, " .
                "si.min_sale_qty = fprt.saved_min_item, " .
                "si.use_config_min_sale_qty = fprt.saved_use_config_min_item " .
                "WHERE pe.entity_id IN(" . $_temp_ids . ");", $update_binding);

        Mage::log("Flash Sale Products (Current Qty = 0) to revert Price:", Zend_Log::INFO, "flashsale.log");
        Mage::log($zero_stock_product_ids, Zend_Log::INFO, "flashsale.log");
        
        /*
         *  Also updates children products that belong to bundles
         */
        if(count($zero_stock_bundle_product_ids) > 0){
            $_temp_bundle_ids = implode(",", $zero_stock_bundle_product_ids);
            
            $adapter->query("UPDATE fhs_catalog_product_entity pe " .
                    "JOIN fhs_catalog_product_entity_datetime sf ON sf.entity_id = pe.entity_id AND sf.attribute_id = 77 " .
                    "JOIN fhs_catalog_product_entity_datetime st ON st.entity_id = pe.entity_id AND st.attribute_id = 78 " .
                    "JOIN fhs_catalog_product_entity_decimal sp ON sp.entity_id = pe.entity_id AND sp.attribute_id = 76 and sp.store_id != {$festival_store} " .
                    "JOIN fhs_catalog_product_index_price_store pt ON pt.entity_id = pe.entity_id and pt.store_id != {$festival_store} " .
                    "JOIN fhs_catalog_product_bundle_selection bs ON bs.product_id = pe.entity_id AND sp.value IS NOT NULL AND sp.value > 0 ".
                    "JOIN fhs_flashsale_period fp ON (NOW() between fp.start_date  and fp.end_date) AND fp.flashsale_id =:flashsale_id " .
                    "JOIN fhs_flashsale_product fprt ON bs.parent_product_id = fprt.product_id AND fp.id = fprt.period_id " .
                    "JOIN fhs_cataloginventory_stock_item si ON pe.entity_id = si.product_id " .
                    "SET sf.value = DATE_ADD(CURDATE(), INTERVAL 0 HOUR), st.value = DATE_ADD(CURDATE(), INTERVAL :to_hours HOUR), " .
                    "sp.value = fprt.final_price,pt.final_price = fprt.final_price, " .
                    "si.max_sale_qty = fprt.saved_max_item, " .
                    "si.use_config_max_sale_qty = fprt.saved_use_config_max_item " .
                    "WHERE bs.parent_product_id IN(" . $_temp_bundle_ids . ");", $update_binding);
        }
        
        /*
         * Update redis "Out of Stock"
         */
        if ($products_result[0]) {
            $helper = Mage::helper("flashsale/data");
            $helper->setSoldOut($products_result[0]['period_id'], $zero_stock_product_ids);    
        }
            
        Mage::log("--------------- END: correctFlashSaleQuantity: ------------------", Zend_Log::INFO, "flashsale.log");
    }
}
