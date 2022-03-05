<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Fahasa_Rediscart_Helper_Cart extends Mage_Core_Helper_Abstract {

//    public function addProduct($product, $params)
//    {
//
//        $success = false;
//        $message = null;
//        $error = false;
//
//        $time_start = microtime(true);
//
//        if ($product)
//        {
//            Mage::log("ADD CART " . $success . $message . " --- " . $time_start, null, "rediscart.log");
//            //check stock item
//            $qty = $params['qty'];
//            $result_check_stock = Mage::helper('rediscart/stock')->checkStockItemForProductId($product->getId(), $qty);
//            if (!$result_check_stock->getHasError())
//            {
//                //get cart_id from session
//                $session = Mage::getSingleton('checkout/session');
//                $customer = Mage::getSingleton('customer/session')->getCustomer();
//                $cart_id = $session->getRedisCartId();
//                if ($cart_id)
//                {
//                    if ($this->checkCartExistedById($cart_id))
//                    {
//                        $this->insertProductInCart($cart_id, $product, $params);
//                    }
//                    else
//                    {
//                        //error no cart in database => log
//                        //=> should insert a new cart in db
//                        $new_cart_id = $this->insertNewCart($customer->getId());
//                        $this->insertProductInCart($new_cart_id, $product, $params);
//
//                        $session->setRedisCartId($new_cart_id);
//                    }
//                }
//                else
//                {
//                    //check customer login or not
//                    if ($customer->getId())
//                    {
//                        //check customer has redis_cart_id before
//                        //= -1: has not been add cart before in redis
//                        $lastest_cart_id = $this->getLastestCartIdByCustomerId($customer->getId());
//                        if ($lastest_cart_id == -1)
//                        {
//                            $new_cart_id = $this->insertNewCart($customer->getId());
//                            $this->insertProductInCart($new_cart_id, $product, $params);
//
//                            $session->setRedisCartId($new_cart_id);
//                            $cart_id = $new_cart_id;
//                        }
//                        else
//                        {
//                            $this->insertProductInCart($lastest_cart_id, $product, $params);
//                            $session->setRedisCartId($lastest_cart_id);
//                            $cart_id = $lastest_cart_id;
//                        }
//                    }
//                    else
//                    {
//                        //never login before and add cart before
//                        $new_cart_id = $this->insertNewCart($customer->getId());
//                        $this->insertProductInCart($new_cart_id, $product, $params);
//
//                        $session->setRedisCartId($new_cart_id);
//                        $cart_id = $new_cart_id;
//                    }
//                }
//
//                $cart_items = $this->getItemsByCartId($cart_id);
//                Mage::helper("rediscart")->copyCartInRedis($cart_id, $cart_items);
//                $success = true;
//            }
//            else
//            {
//                $message = $result_check_stock->getMessage();
//                $error = true;
//            }
//        }
//
//
//        $time_end = microtime(true);
//        $time = $time_end - $time_start;
//
//        Mage::log("ADD CART " . $success . $message . " --- " . $time . " --- " . $time_end, null, "rediscart.log");
//        return array(
//            "success" => $success,
//            "message" => $message,
//            "error" => $error,
//        );
//    }


    public function addProduct($product_id, $params)
    {

        $success = false;
        $message = null;
        $error = false;

        $time_start = microtime(true);

        if ($product_id)
        {
            Mage::log("ADD CART " . $success . $message . " --- " . $time_start, null, "rediscart.log");
            //check stock item
            $qty = $params['qty'];
            $result_check_stock = Mage::helper('rediscart/stock')->checkStockItemForProductId($product_id, $qty);
            if ($result_check_stock && !$result_check_stock->getHasError())
            {
                //get cart_id from session
                $session = Mage::getSingleton('checkout/session');
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $cart_id = $session->getRedisCartId();
                if ($cart_id)
                {
                    if ($this->checkCartExistedById($cart_id))
                    {
                        $this->insertProductInCart($cart_id, $product_id, $params);
                    }
                    else
                    {
                        //error no cart in database => log
                        //=> should insert a new cart in db
                        $new_cart_id = $this->insertNewCart($customer->getId());
                        $this->insertProductInCart($new_cart_id, $product_id, $params);

                        $session->setRedisCartId($new_cart_id);
                    }
                }
                else
                {
                    //check customer login or not
                    if ($customer->getId())
                    {
                        //check customer has redis_cart_id before
                        //= -1: has not been add cart before in redis
                        $lastest_cart_id = $this->getLastestCartIdByCustomerId($customer->getId());
                        if ($lastest_cart_id == -1)
                        {
                            $new_cart_id = $this->insertNewCart($customer->getId());
                            $this->insertProductInCart($new_cart_id, $product_id, $params);

                            $session->setRedisCartId($new_cart_id);
                            $cart_id = $new_cart_id;
                        }
                        else
                        {
                            $this->insertProductInCart($lastest_cart_id, $product_id, $params);
                            $session->setRedisCartId($lastest_cart_id);
                            $cart_id = $lastest_cart_id;
                        }
                    }
                    else
                    {
                        //never login before and add cart before
                        $new_cart_id = $this->insertNewCart($customer->getId());
                        $this->insertProductInCart($new_cart_id, $product_id, $params);

                        $session->setRedisCartId($new_cart_id);
                        $cart_id = $new_cart_id;
                    }
                }

                $cart_items = $this->getItemsByCartId($cart_id);
                Mage::helper("rediscart")->copyCartInRedis($cart_id, $cart_items);
                $success = true;
            }
            else
            {
                $message = $result_check_stock->getMessage();

                $error = true;
            }
        }


        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("ADD CART " . $success . $message . " --- " . $time . " --- " . $time_end, null, "rediscart.log");
        return array(
            "success" => $success,
            "message" => $message,
            "error" => $error,
            "product" => $result_check_stock['product']
        );
    }

    public function deleteProductFromQuoteByProductIds($product_ids)
    {

        $time_start = microtime(true);

        $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getId();
        $quote_item_ids = $this->getQuoteItemIdByProductIds($product_ids, $quote_id);

	if(!empty($quote_item_ids)){
	    $cart = Mage::getSingleton('checkout/cart');
	    foreach ($quote_item_ids as $item)
	    {
		$itemId = $item['item_id']; //item id of particular item
		$cart->removeItem($itemId);
	    }
	    $cart->save();
	}

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("deleteProductFromQuoteByProductIds  --- " . $time, null, "rediscart.log");
    }

    public function deleteProduct($product_id)
    {
        $time_start = microtime(true);

        $success = false;
        $message = null;
        $error = false;
        try {
            $session = Mage::getSingleton('checkout/session');
            $cart_id = $session->getRedisCartId();
            if ($cart_id)
            {
                $old_redis_cart_items = Mage::helper('rediscart')->getCartFromRedisWithKey();

                $this->deleteProductFromCart($cart_id, $product_id);
                $cart_items = $this->getItemsByCartId($cart_id);

                //check product was added in quote => delete product from quote
                $this->deleteProductFromQuoteByProductIds($product_id);
                Mage::helper("rediscart")->copyCartInRedis($cart_id, $cart_items);

                $deleted_product = $old_redis_cart_items[$product_id];
                //qty of response's product is negative (for netcore) tracking Remove From Cart event
                $deleted_product['quantity'] = (-1) * $deleted_product['quantity'];
                $success = true;
            }
        } catch (Exception $ex) {
            $error = true;
            $message = "Error";
        }


        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("deleteProduct  --- " . $time, null, "rediscart.log");

        return $this->getCartWithTotals($cart_items, $success, $message, $error, array("deleted_product" => $deleted_product));
    }

    public function deleteProductFromCart($cart_id, $product_id)
    {
        $query = "delete from fhs_cart_item where cart_id = :cart_id and product_id = :product_id ";
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $binds = array(
            "cart_id" => $cart_id,
            "product_id" => $product_id
        );
        $write->query($query, $binds);
    }

    public function getItemsByCartId($cart_id, $return_key = false)
    {
        $time_start = microtime(true);

        $quote_id = $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getId();
        //only get fhs_sales_flat_quote_item has parent_item_id is null (parent product for bundle)
        $query = "select c.id, c.created_at, c.customer_id, ci.product_id, ci.qty, pe.sku, pe.type_id, name.value as name, 
		    if (url1.value is null, url.value, url1.value) as product_url, oprice.value as original_price, fprice.value as final_price, 
		   from_date.value as from_date, to_date.value as to_date, 
		   pe.category_main, pe.category_main_id, pe.category_mid, pe.category_mid_id, pe.category_1 as category_3, pe.category_1_id as category_3_id, 
		   pe.cat4 as category_4, pe.cat4_id as category_4_id, supplier.value as supplier, 
		   bs.option_id, bs.selection_qty, 
		   c_pe.entity_id as child_product_id, c_pe.sku as child_sku, 
		   c_name.value as child_name, c_oprice.value as child_original_price, c_fprice.value as child_final_price,
		   c_from_date.value as child_from_date, c_to_date.value as child_to_date, 
		   if(qoi.item_id is null, 0, 1) as is_checked ,
		   ifnull(soonRelease.value, 0) as 'soon_release' 
		   from 
		   fhs_cart c 
		   join fhs_cart_item ci on ci.cart_id = c.id 
		   left join fhs_catalog_product_entity pe on pe.entity_id = ci.product_id 
		   left join fhs_catalog_product_entity_varchar name on name.entity_id = pe.entity_id and name.attribute_id = 71 
		   left join fhs_catalog_product_entity_varchar url on url.entity_id = pe.entity_id and url.attribute_id = 98 and url.store_id = 0 
		   left join fhs_catalog_product_entity_varchar url1 on url1.entity_id = pe.entity_id and url1.attribute_id = 98 and url1.store_id = 1 
		   left join fhs_catalog_product_entity_decimal oprice on oprice.entity_id = pe.entity_id and oprice.attribute_id = 75 
		   left join fhs_catalog_product_entity_decimal fprice on fprice.entity_id = pe.entity_id and fprice.attribute_id = 76 
		   left join fhs_catalog_product_entity_datetime from_date on from_date.entity_id = pe.entity_id and from_date.attribute_id = 77 
		   left join fhs_catalog_product_entity_datetime to_date on to_date.entity_id = pe.entity_id and to_date.attribute_id = 78 
		   left join fhs_catalog_product_entity_varchar supplier on supplier.entity_id = pe.entity_id and supplier.attribute_id = 157 
		   left join fhs_catalog_product_bundle_selection bs on bs.parent_product_id = pe.entity_id 
		   left join fhs_catalog_product_entity c_pe on c_pe.entity_id = bs.product_id 
		   left join fhs_catalog_product_entity_varchar c_name on c_name.entity_id = c_pe.entity_id and c_name.attribute_id = 71 
		   left join fhs_catalog_product_entity_decimal c_oprice on c_oprice.entity_id = c_pe.entity_id and c_oprice.attribute_id = 75 
		   left join fhs_catalog_product_entity_decimal c_fprice on c_fprice.entity_id = c_pe.entity_id and c_fprice.attribute_id = 76 
		   left join fhs_catalog_product_entity_datetime c_from_date on c_from_date.entity_id = c_pe.entity_id and c_from_date.attribute_id = 77 
		   left join fhs_catalog_product_entity_datetime c_to_date on c_to_date.entity_id = c_pe.entity_id and c_to_date.attribute_id = 78 
		   LEFT JOIN fhs_catalog_product_entity_int soonRelease ON pe.entity_id = soonRelease.entity_id AND soonRelease.attribute_id = 155
		   left join fhs_sales_flat_quote_item qoi on qoi.quote_id = :quote_id and qoi.product_id = ci.product_id and qoi.parent_item_id is null 
		   where c.id = :cart_id  order by ci.id;";

        $binds = array(
            "cart_id" => $cart_id,
            "quote_id" => $quote_id
        );
        Mage::log("ARRAY" . print_r($binds, true), null, "rediscart.log");


        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $rs = $read->fetchAll($query, $binds);

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("TIME of get fhs_cart_item query with attribute to copy redis *********  --- " . $time, null, "rediscart.log");

        $rs = $this->parseCartItem($rs, $return_key);

        $time_end = microtime(true);
        $time = $time_end - $time_start;
        Mage::log("TIME of get fhs_cart_item query return IMAGE RESULT --- " . $time, null, "rediscart.log");
        return $rs;
    }

    public function getProductImageInCart($cart_items, $return_key)
    {

        $time_start = microtime(true);


        $result = array();
        $helperCatalogImage = Mage::helper('catalog/image');
        $cart_items_key = array();
        //???? IF BUNDLE
        foreach ($cart_items as $item)
        {
            $cart_items_key[$item['product_id']] = $item;
        }

        $product_ids = array_map(function($e) {
            return (int) $e['product_id'];
        }, $cart_items
        );
        $productCollection = Mage::getModel('catalog/product')->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $product_ids))
                ->addAttributeToSelect('image');

        $productCollection->load();
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("TIME to get image collection *********  --- " . $time, null, "rediscart.log");

        foreach ($productCollection as $product)
        {
            $product_id = $product->getId();
            $cart_items_key[$product_id]['image'] = (string) $helperCatalogImage->init($product, 'image')->resize(400, 400);
        }
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("TIME to RETURN parset init resize image --- " . $time, null, "rediscart.log");
        foreach ($cart_items as $item)
        {
            $item['image'] = $cart_items_key[$item['product_id']]['image'];
            if ($return_key)
            {
                $result[$item['product_id']] = $item;
            }
            else
            {
                $result[] = $item;
            }
        }

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("TIME to RETURN parset IMAGE finish *********  --- " . $time, null, "rediscart.log");
        return $result;
    }

    public function parseCartItem($rs, $return_key)
    {
        $time_start = microtime(true);
        $data = array();
        foreach ($rs as $product)
        {
            if ($data[$product['product_id']]['product_id'] == null)
            {
                $data[$product['product_id']]['product_id'] = $product['product_id'];
                $data[$product['product_id']]['quantity'] = (int)$product['qty'];
                $data[$product['product_id']]['sku'] = $product['sku'];
                $data[$product['product_id']]['type_id'] = $product['type_id'];
                $data[$product['product_id']]['name'] = $product['name'];
                $data[$product['product_id']]['product_url'] = "/" . $product['product_url'];
		$data[$product['product_id']]['original_price'] = (double) $product['original_price'];

                $data[$product['product_id']]['category_main'] = $product['category_main'];
                $data[$product['product_id']]['category_main_id'] = $product['category_main_id'];
                $data[$product['product_id']]['category_mid'] = $product['category_mid'];
                $data[$product['product_id']]['category_mid_id'] = $product['category_mid_id'];
                $data[$product['product_id']]['category_3'] = $product['category_3'];
                $data[$product['product_id']]['category_3_id'] = $product['category_3_id'];
                $data[$product['product_id']]['category_4'] = $product['category_4'];
                $data[$product['product_id']]['category_4_id'] = $product['category_4_id'];
                $data[$product['product_id']]['supplier'] = $product['supplier'];
		
                $data[$product['product_id']]['from_date'] = $product['from_date'];
                $data[$product['product_id']]['to_date'] = $product['to_date'];
		
                $data[$product['product_id']]['soon_release'] = $product['soon_release']?true:false;

		if(($product['final_price'] && Mage::app()->getLocale()->isStoreDateInInterval(null, $product['from_date'], $product['to_date'])) && (!empty($product['from_date']) || !empty($product['to_date']))){
		    $final_price = (double) $product['final_price'];
		}else{
		    $final_price = (double) $product['original_price'];
		}

                $data[$product['product_id']]['price'] = $final_price;

                $data[$product['product_id']]['row_total'] = (double) $final_price * (int) $product['qty'];

                $data[$product['product_id']]['is_checked'] = (boolean) $product['is_checked'];
            }
            if ($product['option_id'])
            {
                if ($data[$product['product_id']]['options'][$product['option_id']] == null)
                {
                    $data[$product['product_id']]['options'][$product['option_id']]['options_id'] = $product['option_id'];
                    $data[$product['product_id']]['options'][$product['option_id']]['product_id'] = $product['child_product_id'];
                    $data[$product['product_id']]['options'][$product['option_id']]['sku'] = $product['child_sku'];
                    $data[$product['product_id']]['options'][$product['option_id']]['name'] = $product['child_name'];
                    $data[$product['product_id']]['options'][$product['option_id']]['quantity'] = (int)$product['selection_qty'];
                    $data[$product['product_id']]['options'][$product['option_id']]['original_price'] = (double) $product['child_original_price'];

                    if ($product['child_final_price'] && Mage::app()->getLocale()->isStoreDateInInterval(null, $product['child_from_date'], $product['child_to_date']) && (!empty($product['child_from_date']) || !empty($product['child_to_date'])))
                    {
                        $child_final_price = (double) $product['child_final_price'];
                    }
                    else{
                        $child_final_price = (double) $product['child_original_price'];
                    }
                    $data[$product['product_id']]['options'][$product['option_id']]['price'] = $child_final_price;
                }
            }
            else
            {
                $data[$product['product_id']]['options'] = [];
            }
        }

        $data = $this->calculateBundlePrice($data);
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        Mage::log("CALCULATE BUNDLE PRICE FINISH ^^^^^^^^^^^^*********  --- " . $time, null, "rediscart.log");


        $data = $this->getProductImageInCart($data, $return_key);

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("GET IMAGE ^^^^^^^^^^^^*********  --- " . $time, null, "rediscart.log");
        return $data;
    }

    public function calculateBundlePrice($products)
    {
        foreach ($products as $product)
        {
            if ($product['type_id'] == 'bundle')
            {
                //final_price always save the discount percent (99 or null)
                $bundle_price = 0;
		$original_price = 0;

                foreach ($product['options'] as $option)
                {
                    $bundle_price += $option['price']*$option['quantity'];
		    $original_price += $option['original_price']*$option['quantity'];
                }

                if ($product['price'] && Mage::app()->getLocale()->isStoreDateInInterval(null, $product['from_date'], $product['to_date']) && (!empty($product['from_date']) || !empty($product['to_date'])))
                {
                    $final_price = round($bundle_price * ($product['price'] / 100));
                }
                else
                {
                    $final_price = $bundle_price;
                }

                $products[$product['product_id']]['price'] = $final_price;
                $products[$product['product_id']]['original_price'] = $original_price;

                $products[$product['product_id']]['row_total'] = $final_price * $product['quantity'];
            }
        }
        return $products;
    }

    public function getLastestCartIdByCustomerId($customer_id)
    {
        //??? shold be has field active to mark order was created before
        $query = "select id from fhs_cart where customer_id = :customer_id order by id desc limit 1 ";
        $binds = array(
            "customer_id" => $customer_id
        );
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $rs = $read->fetchAll($query, $binds);
        if (count($rs) > 0)
        {
            return $rs[0]['id'];
        }
        return -1;
    }

    public function insertNewCart($customer_id)
    {
        $insert = "insert into fhs_cart (customer_id, created_at) values (:customer_id, now()) ";
        $binds = array(
            "customer_id" => $customer_id
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->query($insert, $binds);
        $cart_id = $write->lastInsertId();
        return $cart_id;
    }

    public function insertProductInCart($cart_id, $product_id, $params)
    {
        $insert = "insert into fhs_cart_item (cart_id, product_id, qty) values (:cart_id, :product_id, :qty) on duplicate key update qty = qty + values(qty) ";
        $binds = array(
            "cart_id" => $cart_id,
            "product_id" => $product_id,
            "qty" => $params['qty']
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->query($insert, $binds);
    }

    //insert entry in fhs_cart + copy item from fhs_sales_flat_quote_item to fhs_cart_item 
    //return inserted new_cart_id
    public function copyQuoteToRedisCart($quote_id, $customer)
    {
        $new_cart_id = $this->insertNewCart($customer->getId());

        $this->insertMultipleItemFromQuoteId($new_cart_id, $quote_id);
        return $new_cart_id;
    }

    public function getNumItemsInQuote($quote_id)
    {
        $query = "select count(1) as num_items from fhs_sales_flat_quote_item where quote_id = :quote_id ";
        $binds = array(
            "quote_id" => $quote_id
        );
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $rs = $read->fetchAll($query, $binds);
        return $rs[0]['num_items'];
    }

    //function is used to for the first stage of deploy. When code is running redis for cart, customer has quote_item before
    //=> we will copy quote_items in fhs_cart_item
    public function insertMultipleItemFromQuoteId($new_cart_id, $quote_id)
    {
        $query = "insert into fhs_cart_item (cart_id, product_id, qty) select :new_cart_id, qi.product_id, qi.qty from fhs_sales_flat_quote_item qi where qi.quote_id = :quote_id and qi.parent_item_id is null ";
        $binds = array(
            "new_cart_id" => $new_cart_id,
            "quote_id" => $quote_id
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->query($query, $binds);
    }
    
    
    public function insertMissingMultipleItemFromQuoteId($new_cart_id, $quote_id)
    {
        $query = "insert into fhs_cart_item (cart_id, product_id, qty) "
                . "select :new_cart_id, qi.product_id, qi.qty "
                . "from fhs_sales_flat_quote_item qi where qi.quote_id = :quote_id and qi.parent_item_id is null and qi.price_incl_tax > 0 "
                . "ON DUPLICATE KEY UPDATE qty = values(qty) ";
        $binds = array(
            "new_cart_id" => $new_cart_id,
            "quote_id" => $quote_id
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->query($query, $binds);
    }

    public function getVisibleProductsInQuote()
    {
        
    }

    public function checkCartExistedById($cart_id)
    {
        $query = "select id from fhs_cart where id = :cart_id ";
        $binds = array(
            "cart_id" => $cart_id
        );
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $rs = $read->fetchAll($query, $binds);
        if (count($rs) > 0)
        {
            return true;
        }
        return false;
    }

    public function getStockItemFromProductIds($product_ids)
    {

        $product_ids_int = array_map(function($product_id) {
            return (int) $product_id;
        }, $product_ids);
        $time_start = microtime(true);


        $stock_rs = array();
        $productCollection = Mage::getModel('catalog/product')->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $product_ids_int))
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('status');

        Mage::getModel('cataloginventory/stock')->addItemsToProducts($productCollection);
        foreach ($productCollection as $product)
        {
            $stock_rs[$product->getId()] = $product->getStockItem();
            $stock_rs[$product->getId()]['product'] = $product;
        }

//        
//        $stock_query = "select pe.*, status.value as status, image.value as image, si.* from 
//fhs_catalog_product_entity pe 
//join fhs_cataloginventory_stock_item si on si.product_id  = pe.entity_id  
//join fhs_catalog_product_entity_int status on status.entity_id  = pe.entity_id  and status.attribute_id  = 96
//left join fhs_catalog_product_entity_varchar image on image.entity_id = pe.entity_id  and image.attribute_id = 85 and image.store_id  = 0
//where pe.entity_id  in (:product_ids)";
//        $binds = array(
//            "product_ids" => implode($product_ids_int, ","),
//        );
//        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
//        $rs = $read->fetchAll($stock_query, $binds);
//
//        foreach($rs as $item){
//            $product = new Mage_Catalog_Model_Product();
//            $product->setAttributeSetId($item['attribute_set_id']);
//            $product->setCat4($item['cat4']);
//            $product->setCatId($item['cat4_id']);
//            $product->setCategory1Id($item['category_1_id']);
//            $product->setCategoryMain($item['category_main']);
//            $product->setCategoryMainId($item['category_main_id']);
//            $product->setCategoryMid($item['category_mid']);
//            $product->setCategoryMidId($item['category_mid_id']);
//            $product->setCreatedAt($item['created_at']);
//            $product->setDiscountPercent($item['discount_percent']);
//            $product->setEntityId($item['entity_id']);
//            $product->setEntityTypeId($item['entity_type_id']);
//            $product->setFStatus($item['f_status']);
//            $product->setFStockStatus($item['f_stock_status']);
//            $product->setFThanhLy($item['f_thanh_ly']);
//            $product->setFVisibility($item['f_visibility']);
//            $product->setHasOptions($item['has_options']);
//            $product->setImage($item['image']);
//            $product->setIsInStock($item['is_in_stock']);
//            $product->setIsSalable($item['is_salable']);
////            $product->setLasSeenInstock($item['last_seen_instock']);
////            $product->setNumOrders($item['num_orders']);
////            $product->setNumOrdersMonth($item['num_orders_month']);
////            $product->setNumOrdersYear($item['num_orders_year']);
//            $product->setRequiredOptions($item['required_options']);
//            $product->setSku($item['sku']);
//            $product->setStatus($item['status']);
//            $product->setTax($item['tax']);
//            $product->setTypeId($item['type_id']);
//            
//                   
//            $stock_item = new Mage_CatalogInventory_Model_Stock_Item();
//            $stock_item->setBackorders($item['backorders']);
//            $stock_item->setEnableQtyIncrements($item['entity_qty_increments']);
//            $stock_item->setIsInStock($item['is_in_stock']);
//            $stock_item->setIsQtyDecimal($item['is_qty_decimal']);
//            $stock_item->setId($item['item_id']);
//            $stock_item->setManageStock($item['manage_stock']);
//            $stock_item->setMaxSaleQty($item['max_sale_qty']);
//            $stock_item->setMinQty($item['min_qty']);
//            $stock_item->setMinSaleQty($item['min_sale_qty']);
//            
//            $stock_item->setProduct($product);
////             $stock_rs[$product->getId()] = $product->getStockItem();
////            $stock_rs[$product->getId()]['product'] = $product;
//            $stock_item->setProductId($item['product_id']);
//            $stock_item->setProductStatusChanged($item['product_status_changed']);
//            $stock_item->setProductTypeId($item['product_type_id']);
//            $stock_item->setQty($item['qty']);
//            $stock_item->setQtyIncrements($item['qty_increments']);
//            $stock_item->setStockId($item['stock_id']);
//            $stock_item->setStockStatus($item['stock_status']);
//            $stock_item->setStockStatusChangedAuto($item['stock_status_changed_auto']);
//            $stock_item->setStockStatusChangedAutomatically($item['stock_status_changed_automatically']);
//            $stock_item->setStoreId($item['store_id']);
//            $stock_item->setTypeId($item['type_id']);
//            $stock_item->setUseConfigBackorders($item['use_config_backorders']);
//            $stock_item->setUseConfigEnableQtyInc($item['use_config_enable_qty_inc']);
//            $stock_item->setUseConfigEnableQtyIncrements($item['use_config_enable_qty_increments']);
//            $stock_item->setUseConfigManageStock($item['use_config_manage_stock']);
//            $stock_item->setUseConfigMaxSaleQty($item['use_config_max_sale_qty']);
//            $stock_item->setUseConfigMinQty($item['use_config_min_qty']);
//            $stock_item->setUseConfigMinSaleQty($item['use_config_min_sale_qty']);
//            $stock_item->setUseConfigNotifyStockQty($item['use_config_notify_stock_qty']);
//            $stock_item->setUseConfigQtyIncrements($item['use_config_qty_increments']);
//                    
//            $product->setStockItem($stock_item);
//            
//             $stock_rs[$product->getId()] = $stock_item;
//            $stock_rs[$product->getId()]['product'] = $product;
//            
//        }
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("getStockItemFromProductIds-##############--------  --- " . $time, null, "rediscart.log");
        return $stock_rs;
    }

//    public function checkStockItemForItems($data)
//    {
//        $product_ids = array_keys($data);
//        $stocks = $this->getStockItemFromProductIds($product_ids);
//
//        foreach ($data as $itemId => $itemInfo)
//        {
//            if (!isset($itemInfo['qty']))
//            {
//                continue;
//            }
//            $qty = (float) $itemInfo['qty'];
//            if ($qty <= 0)
//            {
//                continue;
//            }
//
//            /* @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
//            $stockItem = $stocks[$itemId];
//            if (!$stockItem)
//            {
//                continue;
//            }
//
//            $itemInfo['quantity'] = $qty;
//            $result = Mage::helper("rediscart/stock")->checkQuoteItemQtyForRedisCart($stockItem, $itemInfo);
//            if ($result->getHasError())
//            {
//                $item['has_error'] = true;
//                $item['message'] = $result->getMessage();
//                $item['desired_qty'] = $result->getDesiredQty();
//            }
//            $data[] = $item;
//        }
//
//        return $data;
//    }

    public function addProductInQuote($item)
    {
        $cart = Mage::getSingleton('checkout/cart');
        $params = array(
            "product" => $item['product_id'],
            "qty" => $item['qty'],
            "is_in_cart" => true,
        );

        $product = \Mage::getModel('catalog/product')->load((int) $item['product_id']);
        $cart->addProduct($product, $params);
        $cart->save();
    }

    public function checkStockItemForItem($product_id, $qty_raw)
    {
        $time_start = microtime(true);


        if (!isset($qty_raw))
        {
            return false;
        }
        $qty = (float) $qty_raw;
        if ($qty <= 0)
        {
            return false;
        }




        $time_end = microtime(true);
        $time = $time_end - $time_start;



        $result = Mage::helper("rediscart/stock")->checkStockItemForProductId($product_id, $qty, true);

        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("checkStockItemForItem  --- " . $time, null, "rediscart.log");
        return $result;
    }

    //update for 1 product with qty
    public function updateCartItems($product_id, $qty)
    {
        $time_start = microtime(true);

        $success = false;
        $message = null;
        $updated_product = null;
        $error = false;

        try {
            $session = Mage::getSingleton('checkout/session');
            $cart_id = $session->getRedisCartId();
            if ($cart_id)
            {
                $data = $this->checkStockItemForItem($product_id, $qty);
                if ($data)
                {

                    $old_redis_cart_items = Mage::helper('rediscart')->getCartFromRedisWithKey();


                    $time_end = microtime(true);
                    $time = $time_end - $time_start;

                    Mage::log(" get old_redis_cart_items  --- " . $time, null, "rediscart.log");
                    $desired_qty = $qty;
                    if ($data->getHasError())
                    {
                        $error = true;
                        $message = $data->getMessage();
                        $desired_qty = $data->getDesiredQty();
                    }

                    //default set $desired_qty = 1 for show price in UI. If $desired_qty = 0 -> row_total = 0
                    if ($desired_qty <= 0)
                    {
                        $desired_qty = 1;
                    }
                    $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

                    $update_sql = "update fhs_cart_item set qty = :qty where cart_id = :cart_id and product_id = :product_id ";

                    $params = array(
                        "product_id" => $product_id,
                        "qty" => $desired_qty,
                        "cart_id" => $cart_id,
                    );
                    $connection->query($update_sql, $params);

                    $time_end = microtime(true);
                    $time = $time_end - $time_start;

                    Mage::log("FINISH INSERT FHS_CART_ITEM CART copyCartInRedis  --- " . $time, null, "rediscart.log");


                    $cart_items = $this->getItemsByCartId($cart_id, true);
//                    $cart_items = array();
                    $time_end = microtime(true);
                    $time = $time_end - $time_start;

                    Mage::log("FINISH INSERT GET TIMES ARRAY CART copyCartInRedis  --- " . $time, null, "rediscart.log");

                    Mage::helper("rediscart")->copyCartInRedis($cart_id, array_values($cart_items));



                    $time_end = microtime(true);
                    $time = $time_end - $time_start;

                    Mage::log("FINISH UPDATE CART copyCartInRedis  --- " . $time, null, "rediscart.log");

                    $params_quote = array(
                        "product_id" => $product_id,
                        "qty" => $desired_qty
                    );
                    //check product was added in quote before???? NEED TO SEE 2 PRODUCTS UPDATED QTY
                    $is_checked = $cart_items[$params_quote['product_id']]['is_checked'];


                    //need to try catche for case: product is out of stock. but fhs_cart_item still has entry item with qty = 1 in database for save customer's shopping
                    //so for this add product in quote will be throw exception
                    try {
                        if ($is_checked == 1)
                        {
                            $this->addProductInQuote($params_quote);
                        }
                    } catch (Exception $ex) {
                        
                    }


                    //set updated_item for return response to tracking netcore
                    $updated_product = $old_redis_cart_items[$product_id];
                    //quantity field: qty customer has just added for product. Ex. current_qty = 2, customer update qty = 5 -> desired qty = 3
                    $updated_product['quantity'] = $qty - $updated_product['quantity'];




                    //set qty of updated product = $qty input to check message error to show UI, and qty in fhs_cart_item is desired_qty
                    $cart_items[$product_id]['quantity'] = $qty;
                    $cart_items = Mage::helper('rediscart/stock')->checkStockItemForRedisCart($cart_items);




                    $time_end = microtime(true);
                    $time = $time_end - $time_start;

                    Mage::log("TIME CHECK STOCK^^^^^  --- " . $time, null, "rediscart.log");
                    $success = true;
                }
            }
        } catch (Exception $ex) {
            
        }


        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("updateCartItems  --- " . $time, null, "rediscart.log");
        return $this->getCartWithTotals(array_values($cart_items), $success, $message, $error, array("updated_product" => $updated_product)
        );
    }

    public function getProductInRedisCartDbByProductIds($cart_id, $product_ids)
    {
        if (!empty($product_ids))
        {
            $query = "select id, product_id, qty as quantity from fhs_cart_item where cart_id = :cart_id and product_id in (" . $product_ids . ") order by id ";
            $binds = array(
                "cart_id" => $cart_id,
            );
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $rs = $read->fetchAll($query, $binds);
            return $rs;
        }
        return array();
    }

    public function getQuoteItemIdByProductIds($product_ids, $quote_id)
    {
        if (!empty($product_ids))
        {
            $query = "select item_id from fhs_sales_flat_quote_item where quote_id = :quote_id and product_id in (" . $product_ids . ") ";
            $binds = array(
                "quote_id" => $quote_id,
            );
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $rs = $read->fetchAll($query, $binds);
            return $rs;
        }
        return array();
    }

    public function getProductModelByProductIds($product_ids)
    {
        $productCollection = Mage::getModel('catalog/product')->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $product_ids))
                ->addAttributeToSelect('special_price')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('special_from_date')
                ->addAttributeToSelect('special_to_date')
                ->addAttributeToSelect('status')
                ->addAttributeToSelect('visibility')
                ->addAttributeToSelect('tax_class_id')
                ->addAttributeToSelect('price_type')
                ->addAttributeToSelect('sku_type')
                ->addAttributeToSelect('weight_type')
                ->addAttributeToSelect('price_view')
                ->addAttributeToSelect('shipment_type')
                ->addAttributeToSelect('is_in_stock')
                ->addAttributeToSelect('is_salable')
                ->addAttributeToSelect('group_price_changed')
                ->addAttributeToSelect('group_price_for_store_changed')
                ->addAttributeToSelect('tier_price_changed')
                ->addAttributeToSelect('tier_price_for_store_changed')

        ;
        Mage::getModel('cataloginventory/stock')->addItemsToProducts($productCollection);
        return $productCollection;
    }

    public function mergeRedisCartWithProductCollection($redis_cart_keys, $product_collection)
    {
        foreach ($product_collection as $product)
        {
            $redis_cart_keys[$product->getId()]['product_model'] = $product;
        }
        return $redis_cart_keys;
    }

    public function getDefaultTotals()
    {
        $totals = array();
        $default_totals = array("subtotal", "grand_total");
        foreach ($default_totals as $total)
        {
            $totals[] = new Fahasa_Rediscart_Model_Total($total, Mage::helper('core')->currency(0, true, false), $total);
        }
        return $totals;
    }

    public function addCheckedProductIntoQuote($product_ids, $checked)
    {

        $time_start = microtime(true);
        $success = false;
        $totals = null;
        $message = null;
        $error = false;

        try {
            $session = Mage::getSingleton('checkout/session');
            $cart_id = $session->getRedisCartId();
            if ($cart_id)
            {
                //true: add product in quote
                //false: remove product from quote
                if ($checked)
                {
                    $redis_cart_data = $this->getProductInRedisCartDbByProductIds($cart_id, $product_ids);

                    if (count($redis_cart_data) > 0)
                    {
                        $cart = Mage::getSingleton('checkout/cart');

                        foreach ($redis_cart_data as $item)
                        {
                            $redis_cart_keys[$item['product_id']] = $item;
                        }
                        $product_collection = $this->getProductModelByProductIds(explode(",", $product_ids));
                        $redis_cart_keys = $this->mergeRedisCartWithProductCollection($redis_cart_keys, $product_collection);

                        $cart_items = Mage::helper('rediscart/stock')->checkStockItemForRedisCart($redis_cart_data);
			if(!empty($cart_items)){
			    foreach ($cart_items as $item)
			    {
				if (!$item['has_error']){
				    try {
					$params = array(
					    "product" => $item['product_id'],
					    "qty" => $item['quantity'],
					    "is_in_cart" => true,
					);
					$cart->addProduct($redis_cart_keys[$item['product_id']]['product_model'], $params);
				    } catch (Exception $ex) {

				    }
				}
				else{
				    $error = true;
				    $messages = "Product is invalid";
				}
			    }
			    $cart->save();
			}

                        $cart_items = $this->getItemsByCartId($cart_id);
                        Mage::helper("rediscart")->copyCartInRedis($cart_id, $cart_items);
                        $session->setCartWasUpdated(true);
                        if (!$cart->getQuote()->getHasError())
                        {
                            $success = true;
                            $cart_items = Mage::helper('rediscart/stock')->checkStockItemForRedisCart($cart_items);
                        }
                        else
                        {
                            $error = true;
                            $messages = $cart->getQuote()->getMessages();
                            if (isset($messages['qty']))
                            {
                                $message = $messages['qty']->getCode();
                            }
                            $success = true;
                            $cart_items = Mage::helper('rediscart/stock')->checkStockItemForRedisCart($cart_items);
                        }
                    }
                    else
                    {
                        $error = true;
                        $messages = "Product is invalid";
                    }
                }
                else
                {
                    $quote_id = Mage::getSingleton('checkout/session')->getQuote()->getId();
                    $quote_item_ids = $this->getQuoteItemIdByProductIds($product_ids, $quote_id);

		    if(!empty($quote_item_ids)){
			$cart = Mage::getSingleton('checkout/cart');
			foreach ($quote_item_ids as $item)
			{
			    $itemId = $item['item_id']; //item id of particular item
			    $cart->removeItem($itemId);
			}
			$cart->save();
		    }
                    $cart_items = $this->getItemsByCartId($cart_id);
                    Mage::helper("rediscart")->copyCartInRedis($cart_id, $cart_items);
                    $cart_items = Mage::helper('rediscart/stock')->checkStockItemForRedisCart($cart_items);

                    $success = true;
                }
            }
        } catch (Exception $ex) {
            $message = $ex->getMessage();
        }

        if ($totals == null)
        {
            $totals = $this->getDefaultTotals();
        }
        $time_end = microtime(true);
        $time = $time_end - $time_start;

        Mage::log("ADD checked product in quote *********  --- " . $time, null, "rediscart.log");
        return $this->getCartWithTotals($cart_items, $success, $messages, $error);
    }
    
    public function unCheckedProductIntoQuote($product_ids, $cart_items){
	$result = false;
        $time_start = microtime(true);
        try {
            $session = Mage::getSingleton('checkout/session');
            $cart_id = $session->getRedisCartId();
            if ($cart_id)
            {
		$quote_id = Mage::getSingleton('checkout/session')->getQuote()->getId();
		$quote_item_ids = $this->getQuoteItemIdByProductIds($product_ids, $quote_id);

		if(!empty($quote_item_ids)){
		    $cart = Mage::getSingleton('checkout/cart');
		    foreach ($quote_item_ids as $item)
		    {
			$itemId = $item['item_id']; //item id of particular item
			$cart->removeItem($itemId);
		    }
		    $cart->save();
		}
		Mage::helper("rediscart")->copyCartInRedis($cart_id, $cart_items);
		$result = true;
            }
        } catch (Exception $ex) {}
        Mage::log("un checked product in quote *********  --- " . $time, null, "rediscart.log");
        return $result;
    }
    public function getProductIdsInRedisCartByCartId($cart_id)
    {
        $product_ids = array();
        $query = "select ci.product_id, ci.qty from fhs_cart c join fhs_cart_item ci on ci.cart_id = c.id "
                . "where c.id = :cart_id ";
        $binds = array(
            "cart_id" => $cart_id
        );
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $rs = $read->fetchAll($query, $binds);
        foreach ($rs as $item)
        {
            $product_ids[] = $item['product_id'];
        }
        return $product_ids;
    }

    public function checkAllProductsIntoQuote($checked)
    {
        $session = Mage::getSingleton('checkout/session');
        $cart_id = $session->getRedisCartId();
        if ($cart_id)
        {
            $product_ids = $this->getProductIdsInRedisCartByCartId($cart_id);
            return $this->addCheckedProductIntoQuote(implode($product_ids, ","), $checked);
        }
        return array(
            "success" => false
        );
    }
    
    public function getStaticQuote()
    {
        $registryKey = 'sales_quote';
        if (Mage::registry($registryKey))
        {
            return Mage::registry($registryKey);
        }
        
        $quote = null;
        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0)
        {
            $_getQuoteCallCount++;
            $onePage = Mage::getSingleton('checkout/type_onepage');
            $quote = $onePage->getQuote();
            $_getQuoteCallCount--;
            Mage::log("BEGIN GET CART in REDIS function", null, "rediscart.log");
           
            Mage::register($registryKey, $quote);
        }
        return $quote;
    }
    
    public function setStaticQuote($quote)
    {
        $registryKey = 'sales_quote';
        Mage::unregister($registryKey);
        Mage::register($registryKey, $quote);
    }

    public function getStaticTotals(){
         
           $registryKey = 'sales_quote_totals';
        if (Mage::registry($registryKey))
        {
            return Mage::registry($registryKey);
        }
        
       
        $quote = $this->getStaticQuote();
        $total = $quote->collectTotals();

        Mage::register($registryKey, $total);
        return $total;
    }
    
    

public function getTotals()
    {
        $time_start = microtime(true);

        static $_getQuoteCallCount = 0;
        if ($_getQuoteCallCount == 0)
        {
            $_getQuoteCallCount++;
            $onePage = Mage::getSingleton('checkout/type_onepage');
            $quote = $onePage->getQuote();
            $_getQuoteCallCount--;
            Mage::log("BEGIN GET CART in REDIS function", null, "rediscart.log");
            $quote->collectTotals();
            $totals = $quote->getTotals();
            $this->setStaticQuote($quote);

            $data = array();
            $grand_total = null;
            foreach ($totals as $k => $v)
            {
                if ($k == "tax")
                {
                    continue;
                }
                else if ($k == "shipping")
                {
                    $price = $v->getValue() + $v->getValue() * 0.1;
                }
                else
                {
                    $price = $v->getValue();
                }
                $obj = (object) [
                            "title" => $v->getTitle(),
                            "price" => round($price),
                            "code" => $v->getCode()
                ];
                if ($v->getCode() == "grand_total")
                {
                    $grand_total = $obj;
                }
                else
                {
                    array_push($data, $obj);
                }
            }
            if ($grand_total)
            {
                array_push($data, $grand_total);
            }


            $time_end = microtime(true);
            $time = $time_end - $time_start;

            Mage::log("getTotals  --- " . $time, null, "rediscart.log");
            return $data;
        }
    }

    public function deleteAllItemsInQuote()
    {
        $cart = Mage::getSingleton('checkout/cart');
        $allItems = $cart->getQuote()->getAllVisibleItems(); //returns all the items in session
        foreach ($allItems as $item)
        {
            $itemId = $item->getItemId(); //item id of particular item
            $cart->removeItem($itemId);
        }
        $cart->save();
    }
    
    public function handleMissingProductInQuote($redis_cart, $session){
        $quote = $this->getStaticQuote();
        
        $same = true;
        foreach ($quote->getAllItems() as $item){
            if (!in_array($item->getProductId(), array_column($redis_cart['items'], 'product_id'))){
                $same = false;
                break;
            }
        }
        
        if (!$same)
        {
            $quote_id = $session->getQuoteId();
            $cart_id = $session->getRedisCartId();
            $this->insertMissingMultipleItemFromQuoteId($cart_id, $quote_id);
            $cart_items = $this->getItemsByCartId($cart_id);
            Mage::helper("rediscart")->copyCartInRedis($cart_id, $cart_items);
            $redis_cart = array(
                "items" => $cart_items
            );
            Mage::unregister('redis_cart');
            Mage::register('redis_cart', $redis_cart);
        }
        return $redis_cart;
    }

    public function getCartFromRedisWithTotalsInDb()
    {
        $redis_cart = array();
        $redis_cart['success'] = false;
        try {
            $time_start = microtime(true);
 Mage::log("BEGIN GET CART FUNCTION IN PHP ------------------ " . $time_start, null, "rediscart.log");

            $redis_cart = Mage::helper('rediscart')->getCartFromRedis();
            ///???? NEED TO CHECK STOCK FOR REDIS CART AND CART TOGETHER
            //check stock item qty 
            $cart_items = Mage::helper('rediscart/stock')->checkStockItemForRedisCart($redis_cart['items']);

            //????THINK: when update cart has error desired_qty, we will update qty = desired_qty. But when get cart in UI, we do not update qty in fhs_cart_item by desired_qty

            $redis_cart['items'] = array_reverse($cart_items);
            
            $session = Mage::getSingleton('core/session');

            $session_checkout = Mage::getSingleton('checkout/session');
            //handle for case quotxle has product but fhs_cart_item not contain product
            $redis_cart = $this->handleMissingProductInQuote($redis_cart, $session_checkout);
            $redis_cart['totals'] = $this->getTotals();
            
            
            
            
            
            $event_cart = Mage::helper('eventcart')->checkEventCart(null, false, true);
            $redis_cart['event_cart'] = $event_cart;
            $redis_cart['event_cart_front'] = Mage::helper('fahasa_catalog/product')->getFirstEventCart($event_cart);
	    $redis_cart['can_payment'] = $this->canPayment($error_cart, $messages);
	    $redis_cart['messages'] = $messages;
	    $redis_cart['error_cart'] = $error_cart;
	    
            $coupon_code = $session->getCouponCode();
            $coupon_label = $session->getCouponLabel();

            $freeship_coupon_code = $session->getFreeshipCode();
            $freeship_coupon_label = $session->getFreeshipLabel();

            $redis_cart['couponCode'] = $coupon_code;
            $redis_cart['couponLabel'] = $coupon_label;
            $redis_cart['freeshipCouponCode'] = $freeship_coupon_code;
            $redis_cart['freeshipCouponLabel'] = $freeship_coupon_label;




            $redis_cart['success'] = true;
            $time_end = microtime(true);
            $time = $time_end - $time_start;

            Mage::log("getCartFromRedisWithTotalsInDb  --- " . $time, null, "rediscart.log");
             Mage::log("BEGIN GET CART FUNCTION IN PHP ------------------ " . microtime(true), null, "rediscart.log");

        } catch (Exception $ex) {
        }
        return $redis_cart;
    }

    public function getCartWithTotals($cart_items, $success, $message, $error, $additional_info = null)
    {
        $redis_cart = array();
        $redis_cart['items'] = array_reverse($cart_items);
        $redis_cart['totals'] = $this->getTotals();
        $event_cart = Mage::helper('eventcart')->checkEventCart(null, false, true);
        $redis_cart['event_cart'] = $event_cart;
	$redis_cart['event_cart_front'] = Mage::helper('fahasa_catalog/product')->getFirstEventCart($event_cart);
        $redis_cart['success'] = $success;
        $redis_cart['error'] = $error;
        $redis_cart['can_payment'] = $this->canPayment($error_cart, $messages, $message);
        $redis_cart['messages'] = $messages;
        $redis_cart['error_cart'] = $error_cart;

	$session = Mage::getSingleton('core/session');
	$coupon_code = $session->getCouponCode();
	$coupon_label = $session->getCouponLabel();

	$freeship_coupon_code = $session->getFreeshipCode();
	$freeship_coupon_label = $session->getFreeshipLabel();

	$redis_cart['couponCode'] = $coupon_code;
	$redis_cart['couponLabel'] = $coupon_label;
	$redis_cart['freeshipCouponCode'] = $freeship_coupon_code;
	$redis_cart['freeshipCouponLabel'] = $freeship_coupon_label;
	    
        if ($additional_info)
        {
            $redis_cart = array_merge($redis_cart, $additional_info);
        }
        return $redis_cart;
    }

    //merge cart_item in guest into customer login
    //flow in magento quote: delete entry in fhs_sales_flat_quote + fhs_sales_flat_quote_item
    //insert item in fhs_sales_flat_quote_item with quote_id of customer login
    public function mergeGuestItemsToCustomerItems($customer)
    {
        $session = Mage::getSingleton('checkout/session');
        $redis_cart_helper = Mage::helper("rediscart/cart");

        $redis_cart_id = $session->getRedisCartId();

        //guest has add cart before
        //else: guest has never add cart before (empty cart)
        if ($redis_cart_id)
        {
            $customer_lastest_cart_id = $redis_cart_helper->getLastestCartIdByCustomerId($customer->getId());
            //customer has item in fhs_cart_item
            if ($customer_lastest_cart_id != -1)
            {
                //????? TEST OK
                //1. update cart_id of guest's fhs_cart_item by customer_lastest_cart_id 
                //(do not delete entry in fhs_cart_item and insert item again because id of guest's items is greater than id of customer's items)
                //2. delete fhs_cart where cart_id = redis_cart_id
                $this->updateNewCartIdForItemsInDb($redis_cart_id, $customer_lastest_cart_id);

                $this->deleteCartOfGuestInDb($redis_cart_id);

                $cart_items = $this->getItemsByCartId($customer_lastest_cart_id);
                Mage::helper("rediscart")->copyCartInRedis($customer_lastest_cart_id, $cart_items);
                $session->setRedisCartId($customer_lastest_cart_id);
            }
            else
            {
                //???? TEST OK
                //update customer_id for entry in fhs_cart (guest's cart)
                $this->updateCustomerIdForCartInDb($redis_cart_id, $customer->getId());
                //don't need copy cart in redis and set redis_cart_id in session because data is not changed (it is not necessary)
                //session storage still save the old redis_cart_id and cart redis storage save the old redis_cart_id's data
            }
        }
        else
        {
            //we don't need handle when guest login success -> we don't copy customer's cart_id into redis
            //Because in getCartFromRedis function, we handle copy redis cart id into redis when customer get cart
        }
    }

    public function updateCustomerIdForCartInDb($cart_id, $customer_id)
    {
        $query = "update fhs_cart set customer_id = :customer_id where id = :cart_id ";
        $binds = array(
            "cart_id" => $cart_id,
            "customer_id" => $customer_id
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->query($query, $binds);
    }

    //$redis_cart_id: old_cart_id (guest cart_id)
    public function deleteCartOfGuestInDb($redis_cart_id)
    {
        $query = "delete from fhs_cart where id = :cart_id ";
        $binds = array(
            "cart_id" => $redis_cart_id
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->query($query, $binds);
    }

    //$redis_cart_id: old_cart_id (guest cart_id)
    //$customer_redis_cart_id: new_cart_id
    public function updateNewCartIdForItemsInDb($redis_cart_id, $customer_redis_cart_id)
    {
        $query = "update fhs_cart_item set cart_id = :new_cart_id where cart_id = :old_cart_id ";
        $binds = array(
            "new_cart_id" => $customer_redis_cart_id,
            "old_cart_id" => $redis_cart_id
        );
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->query($query, $binds);
    }

    public function addWishlistToCart($itemId)
    {
        $success = false;
        $message = null;

        /* @var $item Mage_Wishlist_Model_Item */
        $item = Mage::getModel('wishlist/item')->load($itemId);
        if (!$item->getId()){
            return false;
        }

//        $product = Mage::getModel('catalog/product')
//                ->setStoreId(Mage::app()->getStore()->getId())
//                ->load();
	
        $wishlist = Mage::getModel('wishlist/wishlist')->load($item->getWishlistId());

        $session = Mage::getSingleton('wishlist/session');

        try {
            $params = array(
                'qty' => 1
            );

            $redis_cart_result = $this->addProduct($item->getProductId(), $params);

            if ($redis_cart_result['success'])
            {
		$this->trackingAddCart($item->getProductId());
                $item->delete();
                $wishlist->save();
                $success = true;
            }
            else
            {
                $message = $redis_cart_result['message'];
            }
        } catch (Mage_Core_Exception $e) {
            if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE)
            {
                $session->addError($this->__('This product(s) is currently out of stock'));
            }
            else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS)
            {
                Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
            }
            else
            {
                Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addException($e, $this->__('Cannot add item to shopping cart'));
        }

        return array(
            "success" => $success,
            "message" => $message
        );
    }

    public function addAllWishlistToCart()
    {
        $success = false;
        $message = null;
	
	$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomerId());
	
	$collection = $wishlist->getItemCollection()->setVisibilityFilter();
	
        $messages   = array();
        $addedItems = array();
        $notSalable = array();
        $hasOptions = array();
	
	foreach ($collection as $item) {
	    try {
		$redis_cart_result = $this->addProduct($item->getProductId(), array('qty' => 1));

		if ($redis_cart_result['success']){
		    $this->trackingAddCart($item->getProductId());
		    $addedItems[] = $item->getProduct();
		    $item->delete();
		}
	    } catch (Mage_Core_Exception $e) {
                if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
                    $notSalable[] = $item;
                } else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                    $hasOptions[] = $item;
                } else {
                    $messages[] = $this->__('%s for "%s".', trim($e->getMessage(), '.'), $item->getProduct()->getName());
                }
		
	    } catch (Exception $e) {
		Mage::logException($e);
                $messages[] = Mage::helper('wishlist')->__('Cannot add the item to shopping cart.');
	    }
	}
	
        if($notSalable) {
            $products = array();
            foreach ($notSalable as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = Mage::helper('wishlist')->__('Unable to add the following product(s) to shopping cart: %s.', join(', ', $products));
        }
	
        if($hasOptions) {
            $products = array();
            foreach ($hasOptions as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = Mage::helper('wishlist')->__('Product(s) %s have required options. Each of them can be added to cart separately only.', join(', ', $products));
        }

        if($messages) {
            $isMessageSole = (count($messages) == 1);
            if ($isMessageSole && count($hasOptions) == 1) {
                $item = $hasOptions[0];
		$item->delete();
		$this->trackingAddCart($item->getProductId());
                $redirectUrl = $item->getProductUrl();
            } else {
                $wishlistSession = Mage::getSingleton('wishlist/session');
                foreach ($messages as $message) {
                    $wishlistSession->addError($message);
                }
            }
        }
	
	if($addedItems){
	    try {
                $wishlist->save();
		$success = true;
            }
            catch (Exception $e) {
                Mage::getSingleton('wishlist/session')->addError($this->__('Cannot update wishlist'));
            }

            $products = array();
            foreach ($addedItems as $product) {
                $products[] = '"' . $product->getName() . '"';
            }

            Mage::getSingleton('checkout/session')->addSuccess(
                Mage::helper('wishlist')->__('%d product(s) have been added to shopping cart: %s.', count($addedItems), join(', ', $products))
            );
	}

        return array(
            "success" => $success,
            "message" => $message,
            "redirectUrl" => $redirectUrl
        );
    }
    
    public function trackingAddCart($product_id, $is_wishlist = true){
	$marketing_helper = Mage::helper('fhsmarketing');
	$product_params = "";
	$product_data = Mage::helper('fahasa_catalog/productredis')->getProductID($product_id, false, true);
		
        if (Mage::getStoreConfig('netcore/general/enable') == 1){
            $netcore = Mage::getSingleton('customer/session')->getNetcore();
            if(!$netcore){
                $netcore = "";
            }
            $product_params = $marketing_helper->getProductToCartNetcore($product_data);
            $netcore .= "smartech('dispatch', 'Add To Cart',{\"items\": [".$product_params."]});";
	    if($is_wishlist){
		$netcore .= "smartech('dispatch', 'Remove from Wishlist',{\"items\": [".$marketing_helper->getProductViewNetcore($product_data)."]});";
	    }
            Mage::getSingleton('customer/session')->setNetcore($netcore);
        }
	
        if (Mage::getStoreConfig('suggestion/general/enable') == 1){
            $suggestion = Mage::getSingleton('customer/session')->getSuggestion();
            if(!$suggestion){
                $suggestion = "";
            }
	    if(empty($product_params)){
		$product_params = $marketing_helper->getProductToCartNetcore($product_data);
	    }
	    $suggestion .= "Suggestion(SESSION_ID, 'Add To Cart',{\"items\": [".$product_params."]});";
            Mage::getSingleton('customer/session')->setSuggestion($suggestion);
        }
        
        if (Mage::getStoreConfig('enhanced_ecom/general/enable') == 1){
            $enhanced_ecom = Mage::getSingleton('customer/session')->getEnhancedEcom();
            if (!$enhanced_ecom){
                $enhanced_ecom = "";
            }
	    $enhanced_ecom_data = $marketing_helper->getEnhancedEcomAddToCart($product_data);

	    $enhanced_ecom .= "dataLayer.push({'event': 'addToCart', 'ecommerce': {'currencyCode': '{$enhanced_ecom_data['currency']}', 'add': {'products': ["
                    .json_encode($enhanced_ecom_data, JSON_UNESCAPED_UNICODE)
                    . "]}}});";

            Mage::getSingleton('customer/session')->setEnhancedEcom($enhanced_ecom);
        }
    }
    
    public function refreshCopyRedisCart()
    {
        $session = Mage::getSingleton('checkout/session');
        $cart_id = $session->getRedisCartId();
        if ($cart_id)
        {
            $cart_items = $this->getItemsByCartId($cart_id, true);
            Mage::helper("rediscart")->copyCartInRedis($cart_id, array_values($cart_items));
        }
    }
    
    public function canPayment(&$error_cart, &$messages, $message = null){
	$error_cart = array();
	$messages = array();
	if(!empty($message)){
	    $messages[] = $message;
	}
	$quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
	if (!Mage::helper('checkout')->canOnepageCheckout()) {
	    $msg = $this->__('The onepage checkout is disabled.');
	    $error_cart[] = $msg;
	    $messages[] = $msg;
	    return false;
        }
        if (!$quote->hasItems()) {
	    $msg = $this->__('You have not selected any products to buy.');
	    $error_cart[] = $msg;
	    //$messages[] = $msg;
	    return false;
        }
	if($quote->getHasError()){
	    $items = $quote->getAllVisibleItems();
	    foreach($items as $item){
		if($item->getHasError()){
		    $msg = $item->getMessage();
		    if(!empty($msg)){
			$error_cart[] = $msg;
			$messages[] = $msg;
		    }
		}
	    }
	    if(!empty($error_cart)){
		return false;
	    }
	}
        if (!$quote->validateMinimumAmount()) {
	    $msg = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                Mage::getStoreConfig('sales/minimum_order/error_message') :
                Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');
            $error_cart[] = $msg;
	    $messages[] = $msg;
            return false;
        }
	return true;
    }
}
