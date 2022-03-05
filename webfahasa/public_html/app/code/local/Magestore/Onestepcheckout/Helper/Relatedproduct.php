<?php

class Magestore_Onestepcheckout_Helper_Relatedproduct extends Mage_Core_Helper_Abstract {

    public function mappingRelatedSkuToProductId($replaced_products, $product_ids)
    {
        $data = array();
        foreach ($replaced_products as $key => $prod){
            $related_sku = $prod['related_sku'];
            $related_id = array();
            foreach($related_sku as $sku){
                if ($product_ids[$sku]){
                    $related_id[] = $product_ids[$sku];
                }
            }
            $prod['related_id'] = $related_id;
            $data[$key] = $prod;
        }
        return $data;
    }

    public function getOutStockProductInCheckout($message = null, $regionId = null)
    {
        $success = false;
        $province = null;
        if (Mage::getStoreConfig('eventcart_config/config/show_checkout_stock') != 1 || !$cat_id = Mage::getStoreConfig('eventcart_config/config/cat_id_checkout_stock')){
            return array(
                "success" => false
            );
        }
        try {
            $quote = $this->getQuote();
            $quoteItems = $quote->getAllItems();

            $param = array();

            foreach ($quoteItems as $item)
            {
                $product = $item->getProduct();
                if ($product->getCategoryMainId() == $cat_id)
                {
                    if ($product[$product->getId()])
                    {
                        $old_qty = $param[$product->getSku()];
                        $data[$product->getSku()] = $item->getQty + $old_qty;
                    }
                    else
                    {
                        $param[$product->getSku()] = $item->getQty();
                    }
                }
            }
            if (count($param) > 0)
            {
                if (!$regionId){
                    $shipping_address = $quote->getShippingAddress();
                    $regionId = $shipping_address->getRegionId();
                }
                
                $cur_province = $this->getProvinceIdByRegionId($regionId);
                if (count($cur_province) > 0)
                {
                    $province_id = $cur_province[0]['province_id'];
                    $province = $cur_province[0]['province_name'];
                    //list all replace products of all quote item
                    $replace_product_lists = $this->callReplaceProductApi($param, $province_id);
                    if (count($replace_product_lists) > 0)
                    {
                        $replace_product_ids = (array_column(array_values($replace_product_lists), "related_sku"));

                        $skus = array();
                        foreach ($replace_product_ids as $e)
                        {
                            $skus = array_merge($skus, $e);
                        }

                        $product_ids = $this->getProductIdBySkus($skus);
                        $product_infos = Mage::helper('fahasa_catalog/product')->getProductByIds(array_unique($product_ids));
                      
                        $replace_product_lists = $this->mappingRelatedSkuToProductId($replace_product_lists, $product_ids);

                        $data = array();

                        $helperCatalogImage = Mage::helper('catalog/image');
                        //merge product infos with quoteItems
                        foreach ($quoteItems as $item)
                        {
                            $product = $item->getProduct();
                            $replace_quote_item = array();
                            $item_sku = $product->getSku();
                            $is_out_stock = false;
                            if ($replace_product_lists[$item_sku])
                            {
                                $cur_replace_items = $replace_product_lists[$item_sku];
                                foreach ($cur_replace_items['related_id'] as $rid)
                                {
                                    if ($product_infos[$rid])
                                    {
                                        $replace_quote_item[] = $product_infos[$rid];
                                    }
                                }
                                //if product does not have any replace product, we will not show notice for this product
                                if (count($replace_quote_item) > 0){
                                    $is_out_stock = !$cur_replace_items['is_enough_qty'];
                                }
                            }
                            $data[] = array(
                                "quote_item_id" => (int) $item->getId(),
                                "product_id" => (int) $product->getId(),
                                "name" => $product->getName(),
                                "qty" => $item->getQty(),
                                "image" => (string) $helperCatalogImage->init($product, 'small_image')->resize(400, 400),
                                "price" => $item->getBasePriceInclTax(),
                                "url" => $product->getProductUrl(),
                                "out_stock" => $is_out_stock,
                                "replace_products" => $replace_quote_item,
                            );
                        }
                       
                    }
                }
              
            }

            $success = true;
        } catch (Exception $ex) {
             Mage::log("Exception get related product code " .$ex, null, "related_product.log");
            $message = "ERROR_SERVER";
        }
        return array(
            "success" => $success,
            "message" => $message,
            "data" => $data,
            "province" => $province
        );
    }

    public function getProductIdBySkus($skus)
    {
        if (count($skus) > 0)
        {
            $skus_str = "'" . implode("','", $skus) . "'";
             $query = " select pe.entity_id as product_id, pe.sku from fhs_catalog_product_entity pe "
                    . "join fhs_cataloginventory_stock_item si on si.product_id = pe.entity_id "
                    . " where pe.sku in (" . $skus_str . ") and si.qty > 0 and si.is_in_stock = 1 ";
            $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");

            $rs = $readConnection->fetchAll($query);
            $product_ids = array();
            foreach ($rs as $prod)
            {
                $product_ids[$prod['sku']] = $prod['product_id'];
            }
            return $product_ids;
        }
        return array();
    }

    public function getProvinceIdByRegionId($region_id)
    {
        $query = " select p.province_id, p.province_name from fhs_directory_country_region r join fhs_vietnamshipping_province p on r.default_name = p.province_name "
                . "where r.region_id = :region_id ";
        $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
        $vars = array(
            "region_id" => $region_id
        );
        $province_id = $readConnection->fetchAll($query, $vars);
        return $province_id;
    }

    public function callReplaceProductApi($param, $province_id)
    {
        $data = array();
        foreach ($param as $key => $item)
        {
            $data[] = array(
                "sku" => $key,
                "qty" => $item
            );
        }
        $request = array(
            "token" => "fahasa_checkoutsuggestion",
            "province_id" => $province_id,
            "products" => $data,
        );

        try{
            $url = Mage::getStoreConfig('eventcart_config/config/checkout_stock_api');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);
            $response = curl_exec($ch);
            curl_close($ch);
            $response = (array) json_decode($response);
            if ($response['status'] == "Successfull")
            {
                $products_response = $response['data'];
                $result = array();
                foreach ($products_response as $product)
                {
                    $product = (array) $product;
                    $result[$product['sku']] = array(
                        "related_sku" => $product['related_sku'],
                        "is_enough_qty" => $product['is_enough_qty']
                    );
                }
                return $result;
            }
        } catch (Exception $ex) {
            Mage::log("Exception get related product api " .$ex, null, "related_product.log");
        }

        return null;
    }

    public function replaceProductInCart($new_product_id, $quote_item_id, $regionId)
    {
        try {
            $cart = Mage::getSingleton('checkout/cart');
            $quote = $this->getQuote();
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item)
            {
                if ($item->getId() == $quote_item_id)
                {
                    $qty = $item->getQty();
                    $cart->removeItem($item->getId());

                    //add cart
                    $product = \Mage::getModel('catalog/product')->load((int) $new_product_id);
                    $params = array(
                        "qty" => $qty,
                        "is_in_cart" => false,
                        "type_product" => $product->getTypeId(),
                    );
                    $cart->addProduct($product, $params);
                    $cart->save();
                    $message = "Thay thế sản phẩm thành công";
                    return $this->getOutStockProductInCheckout($message, $regionId);
                }
            }
        } catch (Exception $ex) {
             $message = $ex->getMessage();
        }

        return array(
            "success" => false,
            "message" => $message,
        );
    }

    public function getQuote()
    {
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0)
        {
            $_getQuoteCallCount++;
//            $onePage = Mage::getSingleton('checkout/type_onepage');
//            $quote = $onePage->getQuote();
            $quote = Mage::helper("rediscart/cart")->getStaticQuote();
            $_getQuoteCallCount--;

            return $quote;
        }
        return null;
    }

    public function deleteProductInCart($quote_item_id, $regionId)
    {
        if ($quote_item_id)
        {
            $quote = $this->getQuote();
            $quote->removeItem($quote_item_id)
                    ->save();
            return $this->getOutStockProductInCheckout($regionId);
        }
        return array(
            "success" => false
        );
    }

    public function updateProductInCart($quote_item_id, $qty, $regionId)
    {
        if ($quote_item_id)
        {
            $quote = $this->getQuote();

            try {
                $params = array(
                    "qty" => $qty,
                    "is_in_cart" => true
                );
                $quote->updateItem($quote_item_id, $params)->save();
               
                return $this->getOutStockProductInCheckout($regionId);
            } catch (Exception $ex) {
                $message = $ex->getMessage();
            }
        }
        return array(
            "success" => false,
            "message" => $message
        );
    }

}
