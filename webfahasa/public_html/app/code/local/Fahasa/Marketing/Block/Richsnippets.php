<?php

class Fahasa_Marketing_Block_Richsnippets extends Mage_Catalog_Block_Product_View {

    public function getRichSnippets() {
	if(Mage::registry('current_product_redis')){
	    $product = Mage::registry('current_product_redis');
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
	    $json = array(
		"@context" => "http://schema.org/",
		"url" => $product['url'],
		"image" => $product['image'],
		"name" => $product['name']
	    );

	    // handle type for google Schema
	    $catMainId = $product['category_main_id'];
	    if (isset($catMainId) && in_array($catMainId, array(4, 3165))) {
		$json["@type"] = "Book";
		$json["author"] = $product['author'];
		$json["numberOfPages"] = $product['author'];
		$json["publisher"] = $product['publisher'];
		$json["isbn"] = $product['sku'];
	    } else {
		$json["@type"] = "Product";
		$json["sku"] = $product['sku'];
	    }
	    $json["description"] = $product['description'];
	    //Rating
	    $ratingCount = 0;
	    $rating_summary = $product['rating_summary'];
	    if(!empty($rating_summary)){
		$ratingCount = $rating_summary['reviews_count_fhs'];
	    }
	    // hidden $aggregateRating when $ratingCount = 0;
	    if ($ratingCount > 0) {
		$aggregateRating = array(
		    "@type" => "AggregateRating",
		    "ratingValue" => $rating_summary['rating_summary_fhs'],
		    "bestRating" => "100",
		    "ratingCount" => $ratingCount,
		    "worstRating" => "0"
		);
		$json["aggregateRating"] = $aggregateRating;
	    }
	    $availability = "http://schema.org/InStock";
	    if ($product['soon_release'] === "1") {
		$availability = "http://schema.org/PreOrder";
	    } else {
		if ($product['has_stock'] === "1") {
		    $availability = "http://schema.org/InStock";
		} else {
		    $availability = "http://schema.org/OutOfStock";
		}
	    }
	    $price = $product['final_price'];
	    //Price Offer
	    if (isset($catMainId) && in_array($catMainId, array(4, 3165))) {
		$offers = array(
		    "@type" => "Offer",
		    "availability" => $availability,
		    "price" => '' . round($price),
		    "priceCurrency" => "VND"
		);
	    }else{
		$offers = array(
		    "@type" => "Offer",
		    "availability" => $availability,
		    "price" => '' . round($price),
		    "priceCurrency" => "VND",
		    "url" => $product['url']
		);
	    }

	    $json["offers"] = $offers;
	}elseif($product = $this->getProduct()){
	    $categoryIds = $product->getCategoryIds();
	    if (count($categoryIds)) {
		$lastCategoryId = $categoryIds[count($product->getCategoryIds()) - 1];
		$_category = Mage::getModel('catalog/category')->load($lastCategoryId);

		$categoryname = $_category->getName();
	    }
	    $rating = Mage::getModel('review/review_summary')
		    ->setStoreId(Mage::app()->getStore()->getId())
		    ->load($product->getId());

	    $json = array(
		"@context" => "http://schema.org/",
		"url" => Mage::getBaseUrl() . $product->getUrlPath(),
		"image" => Mage::getBaseUrl() . "media/catalog/product" . $product->getImage(),
		"name" => $product->getName()
	    );

	    // handle type for google Schema
	    $catMainId = $product->getCategoryMainId();
	    if (isset($catMainId) && in_array($catMainId, array(4, 3165))) {
		$json["@type"] = "Book";
		$json["author"] = $product->getAuthor();
		$json["numberOfPages"] = $product->getQtyOfPage();
		$json["publisher"] = $product->getPublisher();
		$json["isbn"] = $product->getSku();
	    } else {
		$json["@type"] = "Product";
		$json["sku"] = $product->getSku();
	    }
	    $json["description"] = $this->getDesc($product);
	    //Rating
	    $ratingCount = $rating->getReviewsCount() == null ? 0 : $rating->getReviewsCount();
	    // hidden $aggregateRating when $ratingCount = 0;
	    if ($ratingCount > 0) {
		$aggregateRating = array(
		    "@type" => "AggregateRating",
		    "ratingValue" => $rating->getRatingSummary() == null ? 0 : $rating->getRatingSummary(),
		    "bestRating" => "100",
		    "ratingCount" => $ratingCount,
		    "worstRating" => "0"
		);
		$json["aggregateRating"] = $aggregateRating;
	    }
	    $availability = "http://schema.org/InStock";
	    if ($product->getSoonRelease() === "1") {
		$availability = "http://schema.org/PreOrder";
	    } else {
		if ($product->getIsInStock() === "1") {
		    $availability = "http://schema.org/InStock";
		} else {
		    $availability = "http://schema.org/OutOfStock";
		}
	    }
	    $price = $product->getFinalPrice();
	    if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
		if (sizeof(Mage::getModel('bundle/product_price')->getTotalPrices($product)) > 0) {
		    $price = Mage::getModel('bundle/product_price')->getTotalPrices($product)[0];
		}
	    }
	    //Price Offer
	    if (isset($catMainId) && in_array($catMainId, array(4, 3165))) {
		$offers = array(
		    "@type" => "Offer",
		    "availability" => $availability,
		    "price" => '' . round($price),
		    "priceCurrency" => "VND"
		);
	    }else{
		$offers = array(
		    "@type" => "Offer",
		    "availability" => $availability,
		    "price" => '' . round($price),
		    "priceCurrency" => "VND",
		    "url" => Mage::getBaseUrl() . $product->getUrlPath()
		);
	    }

	    $json["offers"] = $offers;
	}
        
        return json_encode($json, JSON_UNESCAPED_SLASHES);
    }

    public function getDesc($product) {
        $des = str_replace("<br/>", " ", str_replace("<br />", " ", $product->getDescription()));
        $str = $product->getName() . ", " . $des;
        $wraptext = wordwrap(trim(strip_tags($str)), 155, "---\n---", false);
        $wraptext = str_replace('"', "'", $wraptext);
        $breakpos = strpos($wraptext, "---\n---");
        if ($breakpos) {
            return substr($wraptext, 0, strpos($wraptext, "---\n---")) . ' ...';
        } else {
            return $wraptext;
        }
    }

}
