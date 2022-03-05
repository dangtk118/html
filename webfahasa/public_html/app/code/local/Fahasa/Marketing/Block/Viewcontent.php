<?php 
class Fahasa_Marketing_Block_Viewcontent extends Mage_Catalog_Block_Product_View
{
    public function getViewContentJS(){
	if($product = Mage::registry('current_product_redis')){
	    $price = $product['final_price'];
	    if(!empty($product['category_4']) && $product['category_4'] != "N/A"){
		$categoryname = $product['category_4'];
	    }elseif(!empty($product['category_3']) && $product['category_3'] != "N/A"){
		$categoryname = $product['category_3'];
	    }elseif(!empty($product['category_mid']) && $product['category_mid'] != "N/A"){
		$categoryname = $product['category_mid'];
	    }elseif(!empty($product['category_main']) && $product['category_main'] != "N/A"){
		$categoryname = $product['category_main'];
	    }else{
		$categoryname = '';
	    }
	    $name = $product['name'];
	    $sku = $product['sku'];
	    $category_main_id = $product['category_main_id'];
	    $category_mid_id = $product['category_mid_id'];
	    $category_3_id = $product['category_3_id'];
	    $category_4_id = $product['category_4_id'];
	    $supplier = $product['supplier'];
	}elseif($product = $this->getProduct()){
	    $categoryIds = $product->getCategoryIds();
	    if(count($categoryIds) ){
		$lastCategoryId = $categoryIds[count($product->getCategoryIds())-1];
		$_category = Mage::getModel('catalog/category')->load($lastCategoryId);

		$categoryname= $_category->getName();
	    }
	    if($product->getFinalPrice()) {
		$price = $product->getFinalPrice();
	    } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
		$price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
	    }
	    $name = $product->getName();
	    $sku = $product->getSku();
	    $category_main_id = $product->getCategoryMainId();
	    $category_mid_id = $product->getCategoryMidId();
	    $category_3_id = $product->getData('category_1_id');
	    $category_4_id = $product->getData('cat4_id');
	    $supplier = $product->getSupplier();
	}
        
        return "content_name: '".str_replace("'", "", $name)."',
		content_category: '".str_replace("'", "", $categoryname)."',
		content_ids: ['".$sku."'],
		content_type: 'product',
		value: ".round($price).",
		currency: 'VND',
		category_1: '" . $category_main_id . "',
		category_2: '" . $category_mid_id . "',
		category_3: '" . $category_3_id . "',
		category_4: '" . $category_4_id . "',
		supplier: '". $supplier . "'
	    ";
    }
    
    public function getGgContentJS(){
        $sendTo = "'send_to': 'AW-857907211'";
	if($product = Mage::registry('current_product_redis')){
	    $product_id = $product['entity_id'];
	    $price = $product['final_price'];
	}elseif($product = $this->getProduct()){
	    $product_id = $product->getId();
	    if($product->getFinalPrice()) {
		$price = $product->getFinalPrice();
	    } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
		$price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
	    }
	}
        
        $ggcontent ="
            $sendTo,
            'dynx_itemid': '".$product_id."',
            'dynx_pagetype': 'offerdetail',
            'dynx_totalvalue': ".round($price).",
            'ecomm_prodid': '".$product_id."',
            'ecomm_pagetype': 'product',
            'ecomm_totalvalue': ".round($price).",
        ";
        return $ggcontent;
    }
    
    public function getProductContentForCriteo(){
	if($product = Mage::registry('current_product_redis')){
	    $product_id = $product['entity_id'];
	}else{
	    $product = $this->getProduct();
	    $product_id = $product->getId();
	}
        return $product_id;
    }
    
    /**
     * Ematic Retry IQ - View Product
     */
    public function getProductViewRetryIQ(){
        $brand = 'Fahasa';
	if($product = Mage::registry('current_product_redis')){
	    $product_id = $product['entity_id'];
	    $name = $product['name'];
	    $image = $product['image'];
	    $url = $product['url'];
	    $price = $product['final_price'];
	    if(!empty($product['publisher'])){
		$brand = $product['publisher'];;
	    }elseif(!empty($product['supplier'])){
		$brand = $product['supplier'];
	    }
	}elseif($product = $this->getProduct()){
	    $product_id = $product->getId();
	    $name = $product->getName();
	    $image = "https://www.fahasa.com/media/catalog/product" . $product->getImage();
	    $url = $product->getUrl();
	    if($product->getFinalPrice()) {
		$price = $product->getFinalPrice();
	    } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
		$price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
	    }
	    if($product->getPublisher() != null){
		$brand = $product->getPublisher();
	    }else if($product->getSupplier() != null){
		$brand = $product->getSupplier();
	    }
	}
        $retryIQProductCotent = array();
        $retryIQProductCotent['id'] = $product_id;
        $retryIQProductCotent['price'] = number_format(round($price),0,".",",");
        $retryIQProductCotent['name'] = $name;
        $retryIQProductCotent['brandName'] = $brand;
        $retryIQProductCotent['imageUrl'] = $image;
        $retryIQProductCotent['link'] = $url;
        return json_encode($retryIQProductCotent);
    }
    
    //Enhanced Ecommerce
    public function getUAProductView(){
	if($product = Mage::registry('current_product_redis')){
	    $sku = $product['sku'];
	    $supplier = $product['supplier'];
	    $name = addslashes($product['name']);
	    $category_2 = addslashes($product['category_mid']);
	}elseif($product = $this->getProduct()){
	    $product_id = $product->getId();
	    $sku = $product->getSku();
	    $supplier = $product->getSupplier();
	    $name = addslashes($product->getName());
	    $category_2 = addslashes($product->getCategoryMid());
	}
        $result = "dataLayer.push({'ecommerce': {"
                . "'detail': {"
                . "'products': [{'name':'{$name}', 'id': '{$sku}',"
                . "'price': '{$price}', 'category': '{$category_2}', "
                . "'brand': '{$supplier}'}]}}});";
        return $result;
    }
}
