<?php 
class Fahasa_Marketing_Block_Addtocart extends Mage_Checkout_Block_Cart
{
    /**
     * Add to cart tracking code for FB
     * @return string
     */
    public function getAddToCartJS(){
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $listSku = null;
        $contents = "[";
        foreach ($cart->getAllItems() as $key => $item) {
            if($listSku==null){
                $listSku = "'".$item->getSku()."'";
            }  else {
                $listSku = $listSku . ",'" . $item->getSku() . "'";
            }
            $product = $item->getProduct();
            $contents .= '{id: "' . $product->getSku() . '", quantity: ' . $item->getQty() . ', name: "' 
                    . Mage::helper('fhsmarketing')->removeDiacritics($item->getName())
                    . '", category_1: "' . $product->getCategoryMainId()
                    . '", category_2: "' . $product->getCategoryMidId() . '", category_3: "'
                    . $product->getData('category_1_id') . '", category_4: "' . $product->getData('cat4_id') . '", '
                    . 'supplier: "'. $product->getSupplier() . '"}';
            if ($key < count($cart->getAllItems()) - 1)
            {
                $contents .= ",";
            }
        }
        $contents .= "]";
        
        $fbaddtocart = "
            content_name: 'Shopping Cart',
            content_ids: [$listSku],
            content_type: 'product',
            value: ".round($cart->getGrandTotal()).",
            currency: 'VND',
            contents: ". $contents . "
        ";
        return $fbaddtocart;
    }
    
    /**
     * Add to cart tracking code for Google
     * @return string
     */
    public function getGgAddToCartJS(){
        $sendTo = "'send_to': 'AW-857907211'";
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $listSku = null;
        foreach ($cart->getAllItems() as $item) {
            if($listSku==null){
                $listSku = "'".$item->getProductId()."'";
            }  else {
                $listSku = $listSku . ",'" . $item->getProductId() . "'";
            }
        }
        
        $ggcontent ="
            $sendTo,
            'dynx_itemid': [$listSku],
            'dynx_pagetype': 'conversionintent',
            'dynx_totalvalue': ".round($cart->getGrandTotal()).",
            'ecomm_prodid': [$listSku],
            'ecomm_pagetype': 'cart',
            'ecomm_totalvalue': ".round($cart->getGrandTotal())."
        ";
        return $ggcontent;
    }
    
    public function getItemsInCartForCriteo(){
        $cart = Mage::getModel('checkout/cart')->getQuote();        
        $items = array();
        foreach ($cart->getAllItems() as $item) {
            $str = "{id: \"" . $item->getProductId() . "\", ";
            $str .= "price: " . intval($item->getBaseOriginalPrice()) . ", ";
            $str .= "quantity: " . intval($item->getQty()) . "}";
            array_push($items, $str);            
        }
        return "[" . implode(",", $items) . "]";
    }    
    
    public function getCartItemsForCheckoutForRetryIQ(){
        $cart = Mage::getModel('checkout/cart')->getQuote();        
        $items = array();
        foreach ($cart->getAllItems() as $item) {
            $product = $item->getProduct();
                $price = $item->getBaseOriginalPrice();
            if($product->getStatus() == 1 && $product->getVisibility() == 4 
                    && $price > 0){
                $brand = 'Fahasa';
                if($product->getPublisher() != null){
                    $brand = $product->getPublisher();
                }else if($product->getSupplier() != null){
                    $brand = $product->getSupplier();
                }
                $retryIQCheckout = array();
                $retryIQCheckout['id'] = $product->getId();
                $retryIQCheckout['price'] = number_format(round($price),0,".",",");
                $retryIQCheckout['name'] = $product->getName();
                $retryIQCheckout['brandName'] = $brand;
                $retryIQCheckout['imageUrl'] = "https://www.fahasa.com/media/catalog/product" . $product->getSmallImage();
                $retryIQCheckout['link'] = "https://www.fahasa.com/" . $product->getUrlKey() . ".html";
                array_push($items, $retryIQCheckout);  
            }
        }
        return json_encode($items);
    }
    
    public function getCartCheckoutForNetcore() {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $cartAmount = 0.00;
        $netcoreCheckout = array();
        $product_array = array();
        foreach ($cart->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            if(Mage::helper('discountlabel')->getBundlePrice($product)){
                $itemPrice = $product->getData('price');
                $final_price = $product->getFinalPrice();
            }
            else{
                if($product->getFinalPrice()) {
                    $final_price = $product->getFinalPrice();
                } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
                    $final_price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
                }
                $itemPrice = $product->getPrice();
            }
            $itemPriceCart = $item->getBaseOriginalPrice() * $item->getQty();  
            if($product->getStatus() == 1 && $product->getVisibility() == 4 && $itemPrice > 0){  
                $cartAmount = $cartAmount + $itemPriceCart;
                $items = array();
                $items['prid'] = (int)($product->getId());
                $items['name'] = ($product->getName());
                $items['prqt'] = $item->getQty();
                $items['price'] = round($itemPrice, 2);
                $items['final_price'] = round($final_price, 2);
                $items['price_text'] = number_format(round($itemPrice, 2), 0, ",", ".");
                $items['final_price_text'] = number_format(round($final_price, 2), 0, ",", ".");
                $items['category_main'] = $product->getCategoryMain();
                $items['category_mid'] = $product->getCategoryMid();
		$items['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."/catalog/product".$product->getSmallImage();
		$items['url'] = Mage::getBaseUrl().$product->getUrlPath();
		$items['discount'] = Mage::helper('discountlabel')->handleDiscountPercent($product);
                $items['category_3'] = $product->getData('category_1');
                $items['category_4'] = $product->getData('cat4');
                array_push($product_array, $items);
            }
        }
        $netcoreCheckout['amount'] = round($cartAmount);
        $netcoreCheckout["items"] = $product_array;
        return json_encode($netcoreCheckout, JSON_UNESCAPED_UNICODE);
    }
    
    public function getViewCartForNetcore() {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $cartAmount = 0.00;
        $netcoreCheckout = array();
        $product_array = array();
        foreach ($cart->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            if(Mage::helper('discountlabel')->getBundlePrice($product)){
                $itemPrice = $product->getData('price');
                $final_price = $product->getFinalPrice();
            }
            else{
                if($product->getFinalPrice()) {
                    $final_price = $product->getFinalPrice();
                } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
                    $final_price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
                }
                $itemPrice = $product->getPrice();
            }
            $itemPriceCart = $item->getBaseOriginalPrice() * $item->getQty();  
            if($product->getStatus() == 1 && $product->getVisibility() == 4 && $itemPrice > 0){  
                $cartAmount = $cartAmount + $itemPriceCart;
                $items = array();
                $items['prid'] = (int)($product->getId());
                $items['name'] = ($product->getName());
                $items['prqt'] = $item->getQty();
                $items['price'] = round($itemPrice, 2);
                $items['final_price'] = round($final_price, 2);
                $items['price_text'] = number_format(round($itemPrice, 2), 0, ",", ".");
                $items['final_price_text'] = number_format(round($final_price, 2), 0, ",", ".");
                $items['category_main'] = $product->getCategoryMain();
                $items['category_mid'] = $product->getCategoryMid();
		$items['image'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."/catalog/product".$product->getSmallImage();
		$items['url'] = Mage::getBaseUrl().$product->getUrlPath();
		$items['discount'] = Mage::helper('discountlabel')->handleDiscountPercent($product);
                $items['category_3'] = $product->getData('category_1');
                $items['category_4'] = $product->getData('cat4');
                array_push($product_array, $items);
            }
        }
        $netcoreCheckout['amount'] = round($cartAmount);
        $netcoreCheckout["items"] = $product_array;
        return json_encode($netcoreCheckout, JSON_UNESCAPED_UNICODE);
    }
    
    public function getUACheckout() {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $result = "{'event': 'checkout', 'ecommerce':{'checkout':{"
                . "'products':[";
        
        foreach ($cart->getAllVisibleItems() as $key=>$item){
            if ($key != 0){
                $result .= ",";
            }
            $result .= $item->getProductForEnhancedEcom($item->getQty());
            $key++;
        }
        $result .= "]}}}";
        return $result;
    }
}