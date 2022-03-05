<?php

class Fahasa_Marketing_Block_Successpurchase extends Mage_Checkout_Block_Onepage_Success {

    public function getSuccessPurchaseJS() {
//        $pros = $this->getProduct();
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        $listSku = null;
        $contents = "[";
        foreach ($order->getAllItems() as $key => $item) {
            if ($listSku == null) {
                $listSku = "'" . $item->getSku() . "'";
            } else {
                $listSku = $listSku . ",'" . $item->getSku() . "'";
            }
            
            $product = $item->getProduct();
            $contents .= '{id: "' . $product->getSku() . '", quantity: ' . $item->getQtyOrdered() . ', name: "' .
                    Mage::helper('fhsmarketing')->removeDiacritics($item->getName())
                    . '", category_1: "' . $product->getCategoryMainId()
                    . '", category_2: "' . $product->getCategoryMidId() . '", category_3: "'
                    . $product->getData('category_1_id') . '", category_4: "' . $product->getData('cat4_id') . '", '
                    . 'supplier: "'. $product->getSupplier() . '"}';

            if ($key < count($order->getAllItems()) - 1)
            {
                $contents .= ",";
            }
        }
        $contents .= "]";
        
        $fbsuccesspurchase = "
            content_ids: [$listSku],
            content_type: 'product',
            value: " . round($order->getGrandTotal(), 2) . ",
            currency: 'VND',
            contents: ". $contents . "
        ";

        return $fbsuccesspurchase;
    }

    public function getGgSuccessPurchaseJS() {
        $sendTo = "'send_to': 'AW-857907211'";
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        $listSku = null;
        foreach ($order->getAllItems() as $item) {
            if ($listSku == null) {
                $listSku = "'" . $item->getProductId() . "'";
            } else {
                $listSku = $listSku . ",'" . $item->getProductId() . "'";
            }
        }

        $ggsuccesspurchase = "
            $sendTo,
            'dynx_itemid': [$listSku],
            'dynx_pagetype': 'conversion',
            'dynx_totalvalue': " . round($order->getGrandTotal(), 2) . ",
            'ecomm_prodid': [$listSku],
            'ecomm_pagetype': 'conversion',
            'ecomm_totalvalue': " . round($order->getGrandTotal(), 2) . ",
        ";
        return $ggsuccesspurchase;
    }
    
    public function getGgSuccessPurchaseJSConversion()
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        
        $result = "'send_to': 'AW-857907211/E8fhCNaths8BEIvAipkD',
                'value': '" . round($order->getGrandTotal(), 2) . "', 
                'currency': '" . Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName() . "', 
                'transaction_id': '" . $order->getIncrementId() . "'";
        return $result;
    }

    public function getSuccessPurchaseForCriteo() {
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        $items = array();
        foreach ($order->getAllItems() as $item) {
            $str = "{id: \"" . $item->getProductId() . "\", ";
            $str .= "price: " . intval($item->getBaseOriginalPrice()) . ", ";
            $str .= "quantity: " . intval($item->getQtyOrdered()) . "}";
            array_push($items, $str);
        }
        return "[" . implode(",", $items) . "]";
    }

    public function getSuccessPurchaseForRetryIQ() {
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        $items = array();
        foreach ($order->getAllItems() as $item) {
            $product = $item->getProduct();
            $price = $item->getBaseOriginalPrice();
            if ($product->getStatus() == 1 && $product->getVisibility() == 4 && $price > 0) {
                $brand = 'Fahasa';
                if ($product->getPublisher() != null) {
                    $brand = $product->getPublisher();
                } else if ($product->getSupplier() != null) {
                    $brand = $product->getSupplier();
                }
                $retryIQCheckout = array();
                $retryIQCheckout['id'] = $product->getId();
                $retryIQCheckout['price'] = number_format(round($price, 2), 0, ".", ",");
                $retryIQCheckout['name'] = $product->getName();
                $retryIQCheckout['brandName'] = $brand;
                $retryIQCheckout['imageUrl'] = "https://www.fahasa.com/media/catalog/product" . $product->getSmallImage();
                $retryIQCheckout['link'] = "https://www.fahasa.com/" . $product->getUrlKey() . ".html";
                array_push($items, $retryIQCheckout);
            }
        }
        return json_encode($items);
    }

    public function getSuccessPurchaseForNetcore() {
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        $netcoreCheckout = array();
        $product_array = array();
        $increment_id = $order->getData('increment_id');
        $amount = $order->getData('grand_total');
        $tryoutDiscount = $order->getData('tryout_discount');
        $discountAmount = $order->getData('discount_amount');

        $netcoreCheckout['increment_id'] = (int)$increment_id;
        $netcoreCheckout['amount'] = round((float) $amount);
        $netcoreCheckout['payment'] = $order->getPayment()->getMethod();
        if ($tryoutDiscount > 0) {
            $netcoreCheckout['tryout_discount'] = (float) $tryoutDiscount;
        }
        if ($discountAmount > 0) {
            $netcoreCheckout['discount_amount'] = (float) $discountAmount;
        }
        
        $product_ids = array();
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            
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
            if ($product->getStatus() == 1 && $product->getVisibility() == 4 && $final_price > 0) {
                $items = array();
                $items['prid'] = (int)($product->getId());
                $items['name'] = $product->getName();
                $items['prqt'] = (int)($item->getQtyOrdered());
                $items['price'] = round($price, 2);
                $items['final_price'] = round($final_price, 2);
                $items['category_main'] = $product->getCategoryMain();
                $items['category_mid'] = $product->getCategoryMid();
                $items['category_3'] = $product->getData('category_1');
                $items['category_4'] = $product->getData('cat4');
                
                array_push($product_array, $items);
            }
            $product_ids[] = (int)($product->getId());
        }

        $netcoreCheckout["items"] = $product_array;
        return array(
            'order' => $netcoreCheckout,
            'product_ids' => $product_ids
        );
    }

    public function postSuccessAccessTradePurchase() {
        //obtain tracking id from cookies
        $trackingId = Mage::getModel("core/cookie")->get("_aff_sid");
        $source = Mage::getModel("core/cookie")->get("_aff_network");
        return Mage::helper('fhsmarketing')->postSuccessAccessTradePurchaseByOrderId($this->getOrderId(), $source, $trackingId);
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
    public function postSuccessAccessChinMediaPurchase() {
        $utm_source = Mage::getModel("core/cookie")->get("utm_source");
        $click_id = Mage::getModel("core/cookie")->get("click_id");
        if (($utm_source == "chin" || $utm_source == "cityads") && $click_id != null) {
            $chin_media_host = "https://cityads.ru/service/postback?";
            $urlChinMediaPostBack = 
                "order_id={order_id}"
                ."&click_id={click_id}"
                ."&customer_type={customer_type}"
                ."&order_total={order_total}"
                ."&commission={commission}"
                ."&currency={currency}"
                ."&coupon={coupon}"
                ."&basket=[{basket}]"
                ."&status={status}";
            $chin_media = array();
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
            $currency = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName();
            $CouponCode = ($order->getCouponCode() != null)?$order->getCouponCode():"";
            $fpointAmout = 0;
            if ($order->getTryoutDiscount()) {
                $fpointAmout = ($order->getTryoutDiscount() == null ? 0 : $order->getTryoutDiscount()) * (-1);
            }
            $prod_param_template = "{\"pid\":\"{product_id}\",\"Pn\":\"{name}\",\"Up\":{price},\"Pc\":\"{category_main}\",\"Qty\":{qty}}";
            $prod_param = "";
            
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
                    $p['name'] = $product->getName();
                    $p['quantity'] = intval($item->getQtyOrdered());
                    
                    $priceBefore = round($price, 4);
                    // handle item price before tax 
                    $tax = intval($product->getTax());
                    if($tax){
                        $tax = ($tax/100)+1;
                    }else{
                        $tax = 1.05;
                    }
                    $priceAftersubtractTax = $priceBefore / $tax;
                    
                    $discountAmount = abs($order->getDiscountAmount() == null ? 0 : $order->getDiscountAmount());
                    
                    // handle item price after discount
                    if ($discountAmount > 0) {
                        $discountAmountItem = (($price) / $order->getBaseSubtotalInclTax()) * $discountAmount;
                        $priceAfterDiscount = $priceAftersubtractTax - $discountAmountItem;
                    } else {
                        $priceAfterDiscount = $priceAftersubtractTax;
                    }
                    
                    // handle item price after subtract Fpoint
                    if ($fpointAmout > 0) {
                        $fpointAmoutItem = (($price) / $order->getBaseSubtotalInclTax()) * $fpointAmout;
                        $priceAfterSubtractFpoint = $priceAfterDiscount - $fpointAmoutItem;
                    } else {
                        $priceAfterSubtractFpoint = $priceAfterDiscount;
                    }
                    
                    $p['price'] = ($priceAfterSubtractFpoint)<0 ? 0 : $priceAfterSubtractFpoint;
                    $p['category'] = $product['category_main'];
                    $total = $total + ($p['quantity'] * $p['price']);

                    //add to template array product
                    if($prod_param == ""){
                        $prod_param = $prod_param_template;
                    }
                    else{
                        $prod_param .= ",".$prod_param_template;
                    }
                    $prod_param = str_replace("{product_id}",$p['id'],$prod_param);
                    $prod_param = str_replace("{name}",$p['name'],$prod_param);
                    $prod_param = str_replace("{price}",round($p['price'], 2),$prod_param);
                    $prod_param = str_replace("{category_main}", $p['category'],$prod_param);
                    $prod_param = str_replace("{qty}",$p['quantity'],$prod_param);
                }
            }
            $chin_media['order_id'] = $this->getOrderId();
            $chin_media['click_id'] = $click_id;
            $chin_media['customer_type'] = "";
            $chin_media['order_total'] = round($total, 2);
            $chin_media['commission'] = "";
            $chin_media['currency'] = $currency;
            $chin_media['coupon'] = $CouponCode;
            $chin_media['basket'] = urlencode($prod_param);
            $chin_media['status'] = "new";
            
            // replace to template order
            $urlChinMediaPostBack = str_replace("{order_id}",$chin_media['order_id'],$urlChinMediaPostBack);
            $urlChinMediaPostBack = str_replace("{click_id}",$chin_media['click_id'],$urlChinMediaPostBack);
            $urlChinMediaPostBack = str_replace("{customer_type}",$chin_media['customer_type'],$urlChinMediaPostBack);
            $urlChinMediaPostBack = str_replace("{order_total}",$chin_media['order_total'],$urlChinMediaPostBack);
            $urlChinMediaPostBack = str_replace("{commission}",$chin_media['commission'],$urlChinMediaPostBack);
            $urlChinMediaPostBack = str_replace("{currency}",$chin_media['currency'],$urlChinMediaPostBack);
            $urlChinMediaPostBack = str_replace("{coupon}",$chin_media['coupon'],$urlChinMediaPostBack);
            $urlChinMediaPostBack = str_replace("{basket}",$chin_media['basket'],$urlChinMediaPostBack);
            $urlChinMediaPostBack = str_replace("{status}",$chin_media['status'],$urlChinMediaPostBack);
            
        $output = Mage::helper('fhsmarketing')->apiPostChinMedia($chin_media_host.$urlChinMediaPostBack);
        $chin_media['fpoint_value'] = $fpointAmout;
        Mage::helper("fhsmarketing")->logChinMediaInDB($chin_media, $output);
        Mage::log("data: " . json_encode($chin_media) , null, "chin_media.log");
        //return json_encode($chin_media, JSON_UNESCAPED_UNICODE).json_encode($prod_param, JSON_UNESCAPED_UNICODE);
        }
        Mage::log("-----Cookie Chin Media: orderID:".$this->getOrderId()."utm_source='" . $utm_source."', Click_ID='".$click_id."'", null, "chin_media.log");
        return "null";
    }

    public function getSuccessPurchaseUA(){
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
        $grand_total = doubleval($order->getGrandTotal());
        $result = "{'ecommerce':{'purchase':{'actionField': {'id': '{$order->getIncrementId()}', 'revenue': '{$grand_total}'},'products': [";
        foreach ($order->getAllVisibleItems() as $key => $item)
        {
            $product = $item->getProduct();
            if ($key != 0)
            {
                $result .= ",";
            }
            $qty = intval($item->getQtyOrdered());
            $name = addslashes($product->getName());
            $category_2 = addslashes($product->getCategoryMid());
            $price = doubleval($item->getPriceInclTax());
            $result .= "{'name':'{$name}', 'id': '{$product->getSku()}', 'price': '{$price}', "
                    . "'category': '{$category_2}', "
                    . "'quantity': {$qty}, 'brand': '{$product->getSupplier()}'}";
            $key++;
        }
        $result .= "]}}}";
        
        return $result;
    }
    
    public function postSuccessULUPurchase() {
        $utm_source = Mage::getModel("core/cookie")->get("utm_source");
        $visitor_id = Mage::getModel("core/cookie")->get("visitor_id");
        if ($utm_source == "ulu" && !empty($visitor_id)) {
            $url = "https://api.ulu.vn/api/v1/order";
	    
            $products = [];
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
            //$currency = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName();
            //$CouponCode = ($order->getCouponCode() != null)?$order->getCouponCode():"";
            $fpointAmout = 0;
            if ($order->getTryoutDiscount()) {
                $fpointAmout = ($order->getTryoutDiscount() == null ? 0 : $order->getTryoutDiscount()) * (-1);
            }
	    
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
                    $p['name'] = $product->getName();
                    $p['quantity'] = intval($item->getQtyOrdered());
                    
                    $priceBefore = round($price, 4);
                    // handle item price before tax 
                    $tax = intval($product->getTax());
                    if($tax){
                        $tax = ($tax/100)+1;
                    }else{
                        $tax = 1.05;
                    }
                    $priceAftersubtractTax = $priceBefore / $tax;
                    
                    $discountAmount = abs($order->getDiscountAmount() == null ? 0 : $order->getDiscountAmount());
                    
                    // handle item price after discount
                    if ($discountAmount > 0) {
                        $discountAmountItem = (($price) / $order->getBaseSubtotalInclTax()) * $discountAmount;
                        $priceAfterDiscount = $priceAftersubtractTax - $discountAmountItem;
                    } else {
                        $priceAfterDiscount = $priceAftersubtractTax;
                    }
                    
                    // handle item price after subtract Fpoint
                    if ($fpointAmout > 0) {
                        $fpointAmoutItem = (($price) / $order->getBaseSubtotalInclTax()) * $fpointAmout;
                        $priceAfterSubtractFpoint = $priceAfterDiscount - $fpointAmoutItem;
                    } else {
                        $priceAfterSubtractFpoint = $priceAfterDiscount;
                    }
                    
                    $p['price'] = ($priceAfterSubtractFpoint)<0 ? 0 : $priceAfterSubtractFpoint;
                    $p['category'] = $product['category_main'];
		    $sub_total = $p['quantity'] * $p['price'];
                    $total = $total + $sub_total;

                    //add to array products
		    $products[] = [
			//"category_id" => $p['category'],
			"category_id" => "FAHASA",
			"product_id" => $p['id'],
			"product_name" => $p['name'],
			"price" => round($p['price'], 2),
			"qty" => $p['quantity'],
			"sub_total" => round($sub_total, 2),
		    ];
                }
            }
	    
	    $data = [
		"visitor_id" => $visitor_id,
		"order_id" => $this->getOrderId(),
		"products" => $products
	    ];
	    $content = json_encode($data);
            
	    $helper = Mage::helper("fhsmarketing");
	    $output = $helper->apiPostULU($url, $content);
	    $helper->insertULULogOrderDB($visitor_id, $this->getOrderId(), $fpointAmout, round($total, 2));
	    $helper->insertULULogActionDB($this->getOrderId(), round($total, 2), $output);
        }
        Mage::log("-----Cookie ULU: orderID:".$this->getOrderId()."utm_source='" . $utm_source."', visitor_id='".$visitor_id."'", null, "ulu.log");
        return "null";
    }
}
