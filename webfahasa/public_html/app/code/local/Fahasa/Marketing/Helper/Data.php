<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Data
 *
 * @author Thang Pham
 */
class Fahasa_Marketing_Helper_Data extends Mage_Core_Helper_Abstract {
    /**
     * Return hash md5 of email address, and "" otherwise
     * @return string
     */
    public function getHashEmail(){
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer_email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
            return md5($customer_email);
        }
        return "";
    }

    public function getUserEmail(){
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer_email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
            return $customer_email;
        }
        return "";
    }

    public function getRealUserEmail(){
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer_email = Mage::getSingleton('customer/session')->getCustomer()->getRealEmail();
            return $customer_email;
        }
        return "";
    }

    /**
     * Return 'd' for normal website and m for mobile
     * 
     */
    public function getSiteType(){
        $isMobile = Mage::helper('fhsmobiledetect')->isMobile();
        if($isMobile){
            return "m";
        }else{
            return "d";
        }
    }

    public static function apiPost($url, $jsonMessage, $header){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonMessage);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        
        //Tell cURL that it should only spend 5 seconds
        //trying to connect to the URL in question.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        //A given cURL operation should only take
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $server_output = curl_exec($ch);
        curl_close ($ch);
        return $server_output;
    }
    
    public static function apiPostChinMedia($url){
        $code = "false";
        try{
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_HEADER, false );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
            $response = curl_exec($ch);
            $xml = new SimpleXMLElement($response);
            $code = strval($xml->code);
            Mage::log("Postback link: " . $url , null, "chin_media.log");
        } catch (Exception $ex) {
            Mage::log("Postback error message: " .$ex->getMessage() , null, "chin_media.log");
        }
        return $code;
    }
    
    public static function apiPostULU($url, $content){
        $code = "false";
        try{
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HTTPHEADER,
		    array("Content-type: application/json"));
	    curl_setopt($curl, CURLOPT_POST, true);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
	    $json_response = curl_exec($curl);
	    
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
	    if ($status != 200) {
		return $code;
	    }
	    curl_close($curl);
	    $response = json_decode($json_response, true);
	    
	    $code = $response['code'];
            Mage::log("Postback link: " . $url , null, "ulu.log");
        } catch (Exception $ex) {
            Mage::log("Postback error message: " .$ex->getMessage() , null, "ulu.log");
        }
        return $code;
    }
    
    public function logAccessTradeInDB($data, $fpointAfter, $output) {
        $this->insertLogOrderDB($data, $fpointAfter);
        $this->insertLogItemDB($data);
        $this->insertLogActionDB($data, $output);
    }
    
    // insert Access Trade order
    public function insertLogOrderDB($data, $fpointAfter) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "INSERT INTO `fhs_access_trade_order` "
                . "("
                . "`tracking_id`, "
                . "`order_id`, "
                . "`transaction_value`, "
                . "`created_at`, "
                . "`created_by`, "
                . "`fpoint` "
                . ") "
                . "  VALUES ("
                . "'" . $data['tracking_id'] . "', "
                . "'" . $data['conversion_id'] . "',"
                . "'" . $data['transaction_value'] . "',"
                . "now(),"
                . "'magento',"
                . "'" . $fpointAfter . "'"
                . ");";
        $write->query($query);
        Mage::log("*** Access Trade - insert db order data: orderId=" . $data['conversion_id'] . " - query: " . print_r($query, true), null, "accesstrade.log");
    }

    // insert Access Trade item 
    public function insertLogItemDB($data) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        foreach ($data["itemsSaveDB"] as $item) {
            $query = "INSERT INTO `fhs_access_trade_item` "
                    . "("
                    . "`order_id`, "
                    . "`product_id`, "
                    . "`price_after_discount`, "
                    . "`price`, "
                    . "`quantity`, "
                    . "`category_main`, "
                    . "`category_main_id`, "
                    . "`created_by`, "
                    . "`created_at` "
                    . ") "
                    . "  VALUES ("
                    . "'" . $data['conversion_id'] . "',"
                    . "'" . $item['id'] . "',"
                    . "'" . $item['priceAfterDiscount'] . "',"
                    . "'" . $item['priceBefore'] . "',"
                    . "'" . $item['quantity'] . "',"
                    . "'" . $item['category'] . "',"
                    . "'" . $item['category_id'] . "',"
                    . "'magento',"
                    . "now()"
                    . ");";
            $queryList = $queryList . $query;
        }
        $write->query($queryList);
        Mage::log("*** Access Trade - insert db order item data: orderId=" . $data['conversion_id'] . " - query: " . print_r($queryList, true), null, "accesstrade.log");
    }

    // log Access Trade action create order
    public function insertLogActionDB($data, $output) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "INSERT INTO `fhs_access_trade_action_log` "
                . "("
                . "`order_id`, "
                . "`price`, "
                . "`created_by`,"
                . "`result` "
                . ") "
                . "  VALUES ("
                . "'" . $data['conversion_id'] . "',"
                . "'" . $data['transaction_value'] . "',"
                . "'magento',"
                . "'" . $output . "'"
                . ");";
        $write->query($query);
        Mage::log("*** Access Trade - log db create order data: orderId=" . $data['conversion_id'] . " - query: " . print_r($query, true), null, "accesstrade.log");
    }
    
    public function getProductViewNetcore($product_data = null, $product = null){
	if(empty($product_data) && empty($product)){return null;}
	
	if(!empty($product_data)){
	    $product = $product_data;
	    
	    $final_price = $product['final_price'];
	    $price = $product['price'];
	    $discount = $product['discount_percent'];
	    $product_id = (int)$product['entity_id'];
	    $name = $product['name'];
	    $cat_main = $product['category_main'];
	    $cat_mid = $product['category_mid'];
	    $image = $product['image'];
	    $url = $product['url'];
	    $cat_3 = $product['category_3'];
	    $cat_4 = $product['category_4'];
	}elseif(!empty($product)){
	    if(Mage::helper('discountlabel')->getBundlePrice($product)){
		$price = $product->getData('price');
		$final_price = $product->getFinalPrice();
	    }
	    else{
		if($product->getFinalPrice()) {
		    $final_price = $product->getFinalPrice();
		} elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
		    $final_price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
		}
		$price = $product->getPrice();
	    }
	    $discount = Mage::helper('discountlabel')->handleDiscountPercent($product);
	    $product_id = (int)($product->getId());
	    $name = $product->getName();
	    $cat_main = $product->getCategoryMain();
	    $cat_mid = $product->getCategoryMid();
	    $image = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."/catalog/product".$product->getImage();
	    $url = Mage::getBaseUrl().$product->getUrlPath();
	    $cat_3 = $product->getData('category_1');
	    $cat_4 = $product->getData('cat4');
	}
	
	$result = array();
	$result['prid'] = $product_id;
	$result['name'] = addslashes($name);
	$result['price'] = round($price, 2);
	$result['final_price'] = round($final_price, 2);
	$result['price_text'] = number_format(round($price, 2), 0, ",", ".");
	$result['final_price_text'] = number_format(round($final_price, 2), 0, ",", ".");
	$result['category_main'] = addslashes($cat_main);
	$result['category_mid'] = addslashes($cat_mid);
	$result['image'] = $image;
	$result['url'] = $url;
	$result['discount'] = $discount;
	$result['category_3'] = addslashes($cat_3);
	$result['category_4'] = addslashes($cat_4);
	
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    
    
    public function getSearchParamsForNetcore(){
        $searchText = Mage::helper("catalogSearch")->getQueryText();
        $netcoreSearchParams = array();
        $netcoreSearchParams['s^search_text'] = $searchText;
        return json_encode($netcoreSearchParams, JSON_UNESCAPED_UNICODE);
    }
    
    public function getProductToCartNetcore($product_data = null, $product = null){
	if(empty($product_data) && empty($product)){return null;}
	
	$brand = 'Fahasa';
        $currency = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName();
	if(!empty($product_data)){
	    $product = $product_data;
	    $final_price = $product['final_price'];
	    $price = $product['price'];
	    $discount = $product['discount_percent'];
	    $product_id = (int)$product['entity_id'];
	    $name = $product['name'];
	    $cat_main = $product['category_main'];
	    $cat_mid = $product['category_mid'];
	    $image = $product['image'];
	    $url = $product['url'];
	    $cat_3 = $product['category_3'];
	    $cat_4 = $product['category_4'];
	    if(!empty($product['publisher'])){
		$brand = $product['publisher'];;
	    }elseif(!empty($product['supplier'])){
		$brand = $product['supplier'];
	    }
	}elseif(!empty($product)){
	    if(Mage::helper('discountlabel')->getBundlePrice($product)){
		$price = $product->getData('price');
		$final_price = $product->getFinalPrice();
	    }
	    else{
		if($product->getFinalPrice()) {
		    $final_price = $product->getFinalPrice();
		} elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
		    $final_price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
		}
		$price = $product->getPrice();
	    }
	    if(!empty($product->getPublisher())){
		$brand = $product->getPublisher();
	    }else if(!empty($product->getSupplier())){
		$brand = $product->getSupplier();
	    }
	    $discount = Mage::helper('discountlabel')->handleDiscountPercent($product);
	    $product_id = (int)($product->getId());
	    $name = $product->getName();
	    $cat_main = $product->getCategoryMain();
	    $cat_mid = $product->getCategoryMid();
	    $image = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."/catalog/product".$product->getImage();
	    $url = Mage::getBaseUrl().$product->getUrlPath();
	    $cat_3 = $product->getData('category_1');
	    $cat_4 = $product->getData('cat4');
	}

        $result = array();
        $result['prid'] = $product_id;
        $result['name'] = addslashes($name);
        $result['brand'] = addslashes($brand);
        $result['variant'] = '';
        $result['prqt'] = 1;
        $result['price'] = round($price, 2);
        $result['final_price'] = round($final_price, 2);
	$result['price_text'] = number_format(round($price, 2), 0, ",", ".");
	$result['final_price_text'] = number_format(round($final_price, 2), 0, ",", ".");
        $result['currency'] = $currency;
	$result['category_main'] = addslashes($cat_main);
	$result['category_mid'] = addslashes($cat_mid);
	$result['image'] = $image;
	$result['url'] = $url;
        $result['discount'] = $discount;
	$result['category_3'] = addslashes($cat_3);
	$result['category_4'] = addslashes($cat_4);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    
    public function logChinMediaInDB($data, $output) {
        $this->insertChinLogOrderDB($data);
        $this->insertChinLogActionDB($data, $output);
    }
    // insert Chin Media order
    public function insertChinLogOrderDB($data) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "INSERT INTO `fhs_chin_media_order` "
                . "("
                . "`click_id`, "
                . "`order_id`, "
                . "`order_total`, "
                . "`created_by`, "
                . "`fpoint`, "
                . "`status` "
                . ") "
                . "  VALUES ("
                . "'" . $data['click_id'] . "', "
                . "'" . $data['order_id'] . "',"
                . "'" . $data['order_total'] . "',"
                . "'magento',"
                . "'" . $data['fpoint_value'] . "',"
                . "'pending'"
                . ");";
        $write->query($query);
        Mage::log("*** Chin media - insert db order data: orderId=" . $data['conversion_id'] . " - query: " . print_r($query, true), null, "chin_media.log");
    }

    // log Chin Media action create order
    public function insertChinLogActionDB($data, $output) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "INSERT INTO `fhs_chin_media_action_log` "
                . "("
                . "`order_id`, "
                . "`order_total`, "
                . "`created_by`,"
                . "`action`,"
                . "`result` "
                . ") "
                . "  VALUES ("
                . "'" . $data['order_id'] . "',"
                . "'" . $data['order_total'] . "',"
                . "'magento',"
                . "'insert',"
                . "'" . $output . "'"
                . ");";
        $write->query($query);
        Mage::log("*** Chin media - log db create order data: orderId=" . $data['order_id'] . " - query: " . print_r($query, true), null, "chin_media.log");
    }
    
    // insert ULU order
    public function insertULULogOrderDB($visitor_id, $order_id, $fpoint, $order_total) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "INSERT INTO `fhs_ulu_order` "
                . "("
                . "`visitor_id`, "
                . "`order_id`, "
                . "`order_total`, "
                . "`created_by`, "
                . "`fpoint`, "
                . "`status` "
                . ") "
                . "  VALUES ("
                . "'" . $visitor_id . "', "
                . "'" . $order_id . "',"
                . "'" . $order_total . "',"
                . "'magento',"
                . "'" . $fpoint . "',"
                . "'pending'"
                . ");";
        $write->query($query);
        Mage::log("*** ULU - insert db order data: orderId=" . $order_id . " - query: " . print_r($query, true), null, "ulu.log");
    }

    // log ULU action create order
    public function insertULULogActionDB($order_id, $order_total, $output) {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = "INSERT INTO `fhs_ulu_action_log` "
                . "("
                . "`order_id`, "
                . "`order_total`, "
                . "`created_by`,"
                . "`action`,"
                . "`result` "
                . ") "
                . "  VALUES ("
                . "'" . $order_id . "',"
                . "'" . $order_total . "',"
                . "'magento',"
                . "'insert',"
                . "'" . $output . "'"
                . ");";
        $write->query($query);
        Mage::log("*** ULU - log db create order data: orderId=" . $order_id . " - query: " . print_r($query, true), null, "ulu.log");
    }
    
    public function getEnhancedEcomAddToCart($product_data = null, $product = null){
	if(empty($product_data) && empty($product)){return null;}
	
        $currency = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName();
	if(!empty($product_data)){
	    $product = $product_data;
	    
	    $final_price = $product['final_price'];
	    $sku = $product['sku'];
	    $name = $product['name'];
	    $cat_main = $product['supplier'];
	    $cat_mid = $product['category_mid'];
	}elseif(!empty($product)){
	    if(Mage::helper('discountlabel')->getBundlePrice($product)){
		$final_price = $product->getFinalPrice();
	    }
	    else{
		if($product->getFinalPrice()) {
		    $final_price = $product->getFinalPrice();
		} elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
		    $final_price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
		}
	    }
	    $sku = $product->getSku();
	    $name = $product->getName();
	    $supplier = $product->getSupplier();
	    $cat_mid = $product->getCategoryMid();
	}
        
        $result = array();
        $result['name'] = addslashes($name);
        $result['id'] = $sku;
        $result['price'] = round($final_price, 2);
        $result['category'] = addslashes($cat_mid);
        $result['supplier'] = addslashes($supplier);
        $result['quantity'] = 1;
        $result['currency'] = $currency;
        
        return $result;
    }
    
    //copy from Successpurchase block
    public function postSuccessAccessTradePurchase($order_id) {
        //obtain tracking id from cookies
        $trackingId = Mage::getModel("core/cookie")->get("_aff_sid");
        $source = Mage::getModel("core/cookie")->get("_aff_network");
        return Mage::helper('fhsmarketing')->postSuccessAccessTradePurchaseByOrderId($order_id, $source, $trackingId);
    }
    
    public function postSuccessAccessTradePurchaseByOrderId($order_id, $source, $trackingId) {
        //obtain tracking id from cookies
        $accessKey = "GfYfyqjlgbSSwv4j-yikVzV6nsyoqX7x";
        Mage::log("*** Access Trade - Order Id: " . $order_id . " - Tracking Id: " . $trackingId . " - and source: " . $source, null, "accesstrade.log");
        $accessTrade = array();
        if ($trackingId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

            Mage::log("*** Access Trade - Order Id: " . $order_id . " - Subtotal With fee: " . $order->getBaseSubtotalInclTax()
                    . " - and Discount Amt: " . $order->getDiscountAmount(), null, "accesstrade.log");
            $fpointAmout = 0;
            if ($order->getTryoutDiscount()) {
                $fpointAmout = ($order->getTryoutDiscount() == null ? 0 : $order->getTryoutDiscount()) * (-1);
            }

            $accessTrade['_access_key'] = $accessKey;
            $accessTrade['conversion_id'] = $order_id;
            $accessTrade['conversion_result_id'] = "30";
            $accessTrade['tracking_id'] = $trackingId;
            $accessTrade['transaction_id'] = $order_id;
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $accessTrade['transaction_time'] = str_replace('+07:00', '', date('c'));

            $items = array();

            // use save in db AT item
            $itemsSaveDB = array();

            $orderItems = array();
            $total = 0;
            foreach ($order->getAllItems() as $item) {
                $product = $item->getProduct();
                if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE &&
                        $product->getPriceType() == Mage_Bundle_Model_Product_Price::PRICE_TYPE_DYNAMIC) {
                    //we skip bundle dynamic price, as individual items will be processed later on
                    continue;
                }
                $price = $item->getBaseOriginalPrice();
                if ($product->getStatus() == 1 && $price > 0) {
                    $p = array();
                    $p['id'] = $product->getId();
                    $p['sku'] = $product->getSku();
                    $p['name'] = $product->getName();
                    $p['quantity'] = intval($item->getQtyOrdered());

                    $discountAmount = abs($order->getDiscountAmount() == null ? 0 : $order->getDiscountAmount());

                    // handle item price after discount
                    if ($discountAmount > 0) {
                        $priceBefore = round($price, 2);
                        $discountAmountItem = round(((($price) / $order->getBaseSubtotalInclTax()) * ($discountAmount)), 2);
                        $priceAfterDiscount = $priceBefore - $discountAmountItem;
                    } else {
                        $priceBefore = round($price, 2);
                        $discountAmountItem = 0;
                        $priceAfterDiscount = $priceBefore;
                    }

                    // handle item price before tax 
                    if (in_array($product['category_main_id'], array(4, 3165))) {
                        // QV/NV tax 5%
                        $priceAfterDiscount = round(($priceAfterDiscount / (1 + 0.05)), 2);
                    } else {
                        // other tax 10%
                        $priceAfterDiscount = round(($priceAfterDiscount / (1 + 0.1)), 2);
                    }

                    $p['price'] = $priceAfterDiscount;
                    $total = $total + ($p['quantity'] * $p['price']);
                    Mage::log("total=" . $total . "price =" . $p['price'], null, "accesstrade.log");
                    $p['category'] = $product['category_main'];
                    $categoryId = $this->handleCategory($product['category_main_id'], $product['category_mid_id']);
                    if ($fpointAmout > 0) {
                        $p['category_id'] = "100000";
                    } else {
                        $p['category_id'] = $categoryId;
                    }
                    array_push($items, $p);

                    //use show in page successpurchase
                    $item = array();
                    $item["itemid"] = $p['id'];
                    $item['quantity'] = $p['quantity'];
                    $item['price'] = $p['price'];
                    $item['catid'] = $p['category_id'];
                    array_push($orderItems, $item);

                    $p['priceBefore'] = $priceBefore;
                    $p['priceAfterDiscount'] = $priceAfterDiscount;
                    $p['discountAmountItem'] = $discountAmountItem;
                    array_push($itemsSaveDB, $p);
                }
            }

            $extra = array();
            $extra['fpoint_value'] = $fpointAmout;
            if ($order->getStoreId() == 4){
                $extra['platform'] = 'app';
            } else {
                $extra['platform'] = 'web';
            }
            
            $accessTrade['extra'] = $extra;
            $accessTrade['transaction_value'] = round($total, 2);

            $accessTrade['items'] = $items;
            $dataInputJson = json_encode($accessTrade);
            $accessTrade['itemsSaveDB'] = $itemsSaveDB;
            Mage::log("*** Access Trade - Order Id: " . $order_id . " - Data Body: " . print_r($accessTrade, true), null, "accesstrade.log");
            Mage::log("*** Access Trade - Order Id: " . $order_id . " - JSON Data Body: " . print_r($dataInputJson, true), null, "accesstrade.log");
            $urlAccessTradeConversionPostBack = "https://api.accesstrade.vn/v1/postbacks/conversions";
            $header = array(
                'Content-Type:application/json',
                'Content-Length: ' . strlen($dataInputJson),
                'Authorization: Token ' . $accessKey
            );
            $output = Mage::helper('fhsmarketing')->apiPost($urlAccessTradeConversionPostBack, $dataInputJson, $header);
            Mage::log("*** Access Trade - Order Id: " . $order_id . " - Output Response: " . $output, null, "accesstrade.log");

            // log in db
            Mage::helper("fhsmarketing")->logAccessTradeInDB($accessTrade, $fpointAmout, $output);

            //use show in page successpurchase
            $accesstrade_order_info = array();
            $accesstrade_order_info['order_id'] = $order_id;
            $accesstrade_order_info['amount'] = $accessTrade['transaction_value'];
            $accesstrade_order_info['discount'] = $order->getDiscountAmount();
            $accesstrade_order_info['order_items'] = $orderItems;

            $data_accesstrade_order_info = json_encode($accesstrade_order_info);
            Mage::log("*** Access Trade #" . $order_id . " - AT.track_order:" . print_r($accesstrade_order_info, true), null, "accesstrade.log");
            return $data_accesstrade_order_info;
        } else {
            //Mage::log("*** Access Trade trackingId is null. Order Id: " .$order_id, null, "accesstrade.log");
            return "null";
        }
    }
    
      public function handleCategory($catId, $cateMidId) {
        $catOk = "0";
        if($catId == null){
            return "0";
        }
//        list category not in Commission (category_mid_id)
//        Teaching Resources & Education (ID: 4388)
//        Dictionaries & Languages (ID: 5421)
//        tu dien tieng viet : 19
        $catNotCommission = array(4388, 5421, 19);

//        list category in Commission (category_main_id)
//        - Sách quốc văn: 7% category_id = 4
//        - văn phòng phẩm: 7% category_id = 86
//        - đồ chơi: 7% category_id = 5991
//        - Sách ngoại văn: 5% category_id = 3165
        $catCommission = array(4, 86, 5991, 3165);

//        check commission
        if (in_array($catId, $catCommission)) {
//            check not commission
            if (!in_array($cateMidId, $catNotCommission)) {
                $catOk = $catId;
            }
        }
        return $catOk;
    }
   
    public function removeDiacritics($str)
    {
       $text = preg_replace('/\p{M}/u', '', Normalizer::normalize($str, Normalizer::FORM_D));
       return preg_replace("/(đ)/", 'd', preg_replace("/(Đ)/", 'D', $text));
    }

}
