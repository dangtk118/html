<?php
class Fahasa_Catalog_Helper_Productredis extends Mage_Core_Helper_Abstract
{
    const URL_IMAGE_REDIS = 'https://www.fahasa.com/';
    const URL_IMAGE_LOCALHOST = 'https://test.fahasa.com/media/catalog/product';
//    const URL_IMAGE_LOCALHOST = 'http://192.168.1.18/media/catalog/product';
    
    public function getUrlImage(){
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';
    }
    
    public function getProductID($productId, $is_mobile = true, $is_basic = false) {
        $cache_key = "product_".$productId."_".($is_mobile?'1':'0')."_".($is_basic?'1':'0');
	//get to cache
	if ($result = Mage::registry($cache_key)){return $result;}
	
//      $productId = 357446;
        $beginaa = round(microtime(true) * 1000);
        \Mage::log(' -AFTER GET REDIS DATA DONE : ' . round(microtime(true) * 1000) , null, "redis_product_debug.log" );

        $product_helper = \Mage::helper('fahasa_catalog/product');

        $product_data = $product_helper->getProductStoreV2($productId);
        
        if (empty($product_data)) {
            return null;
        }

       \Mage::log(' -AFTER GET REDIS DATA DONE : ' . round(microtime(true) * 1000) , null, "redis_product_debug.log" );
        // confiurable product :
       $product_configurable = $this->isConfigurableProduct($product_data);
         
        // set product : 
        $productId = $product_configurable['product_id_main'];
        $product_data = $product_configurable['product'];
//        $product = $product_input;

        //lấy thông tin sản phẩm
        $result["entity_id"] = $product_data['product_id'];
        $result["entity_id_sub"] = $product_data['product_id'];
        $result["category"] = $product_data['cat3_name'];
        $result["category_main"] = $product_data['cat1_name'];
        $result["category_mid"] = $product_data['cat2_name'];
        $result["category_3"] = $product_data['cat3_name'];
        $result["category_4"] = $product_data['cat4_name'];
        $result["category_main_id"] = $product_data['cat1_id'];
        $result["category_mid_id"] =$product_data['cat2_id'];
        $result["category_3_id"] = $product_data['cat3_id'];
        $result["category_4_id"] = $product_data['cat4_id'];
        $result["type_id"] = $product_data['type_id'];
        $result["sku"] = (string) $product_data['sku'];
        $result["has_options"] = 0; //$product->has_options;
        $result["required_options"] = 0; //$product->required_options;
        $result["created_at"] = $product_data['created_at'];
        $result["updated_at"] = $product_data['updated_at'];
        $result["name"] = $product_data['product_name'];
        $result["episode"] = $product_data['episode_display'];
	$result['qty'] = $product_data['qty'];
        $result["meta_title"] = null; //$product->meta_title;
        $result["meta_description"] = null; //$product->meta_description;
        $result["image_path"] = $product_data['image_url'];
        $result["image"] = Mage::helper('catalog/image')->init(Mage::getModel('catalog/product'), 'small_image',  $result["image_path"])->resize(600, 600)->__toString();
        $result["small_image"] = $this::URL_IMAGE_REDIS . $product_data['resize_image_url'];
        $result["thumbnail"] = $this::URL_IMAGE_REDIS . $product_data['resize_image_url'];
	
        $result["isConfigurable"] = $product_configurable['isConfigurable'];
        $result["childs"] = $product_configurable['listChild'];
        $result["disable_select"] = $product_configurable['disable_select'];

        // set product price configurable 
        if ($product_configurable['isConfigurable'] && !empty($product_configurable['listChild'])) {
            $productDefault = $product_configurable['product_default'];
            $result["entity_id_sub"] = $productDefault['product_id'];
            $cProduct = $productDefault;
        } else {
            $cProduct = $product_data;
        }

        // handle set attribute confiruable
        if ($product_data['type_id'] == "configurable") {
            $result["min_price"] = $cProduct['final_price'];
            $result["max_price"] = $cProduct['price'];
        } else {
            $result["final_price"] = $cProduct['final_price'];
            $result["price"] = $cProduct['price'];
        }
        
                
        $result["visibility"] = $cProduct['visibility'];
        $result["status"] = $cProduct['active'];
        $result["soon_release"] = (string) $cProduct['soon_release'];
        $result["qty_of_page"] = $cProduct['qty_of_page'];
        $result["is_available"] = $cProduct['stock_status'] ? true : false;
        $result["discount_percent"] = $cProduct['discount'] ?? 0;
        if ($cProduct['stock_status']) {
            $result["stock_available"] = "in_stock";
        } else {
            $result["stock_available"] = "out_of_stock";
        }
        if ($cProduct['stock_status']) {
            $result["has_stock"] = true;
        } else {
            $result["has_stock"] = false;
        }

        // null ? mobile khong su dung 
        $result["image_label"] = null; //$product->image_label;
        $result["small_image_label"] = null; //$product->small_image_label;
        $result["thumbnail_label"] = null; //$product->thumbnail_label;
        $result["gift_message_available"] = null; //$product->gift_message_available;
        

        $result["publish_year"] = $product_data['publish_year'];
        $result["size"] = $product_data['size'];
        $result["author"] = $product_data['author'][0]['key'];
        $result["publisher"] = $product_data['publisher'];
        
        if($product_data['supplier_list'] && count($product_data['supplier_list']) > 0 ){
            $result['supplier'] = $product_data['supplier'][0]['value'];
            $result['supplier_id'] = $product_data['supplier'][0]['key'];
        }
        
        $result["translator"] = $product_data['translator'];
        $result["weight"] = $product_data['weight'];
        $result["country_of_manufacture"] = null; //$product->country_of_manufacture;
        $result["tax_class_id"] = null; //$product->tax_class_id;
        $result["weight_type"] = null; //$product->weight_type;
        $result["featured"] = null; //$product->featured;
        
        if($product_data['book_layout'] && count($product_data['book_layout']) > 0 ){
            $result['book_layout'] = $product_data['book_layout'][0]['value'];
        }
        $result["exclusive"] = null; //$product->exclusive;

        $result["description"] = $product_data['description'];

        $result["short_description"] = null; //$product->short_description;
        $result["meta_keyword"] = null; //$product->meta_keyword;

        //get more product attributes
        $pro_sku = $product_data['sku'];
	if($is_mobile){
	    $result["attributes"] = $this->getProductAtributesRedis($product_data, $productId, $pro_sku);
	    $result["list_comment"] = $this->getListCommentRedis($product_data);
	}else{
	    //set for web
	    $result["url"] = Mage::getBaseUrl().$product_data['product_url'];
	    $result["url_key"] = str_replace('.html', '', $product_data['product_url']);
	    if(!empty($product_data['links'])){
		$result["links"] = $product_data['links'];
	    }
	    $result["attributes"] = $product_helper->getAttributeFilter($product_data, true);
	    
	    $rating_fhs_html = $product_helper->getRattingHtml($product_data);
	    if(!empty($rating_fhs_html)){
		$result['rating_fhs_html'] = $rating_fhs_html;
	    }
	    $rating_other_desktop_html = $product_helper->getRattingOtherHtml($product_data, $productId, 'desktop');
	    $rating_other_mobile_html = $product_helper->getRattingOtherHtml($product_data, $productId, 'mobile');
	    if(!empty($rating_other_desktop_html) && !empty($rating_other_mobile_html)){
		$rating_other = array();
		$rating_other['desktop'] = $rating_other_desktop_html;
		$rating_other['mobile'] = $rating_other_mobile_html;

		$result['rating_other'] = $rating_other;
	    }
	}

        $date_now = date("Y-m-d", strtotime('+7 hours'));

        //chu xu ly ko co To
        if ($product_data['special_from_date'] <= $date_now && $date_now <= $product_data['special_to_date']) {
            $result["special_from_date"] = $product_data['special_from_date'];
            $result["special_to_date"] = $product_data['special_to_date'];
        }

        $result["news_from_date"] = null;
        $result["news_to_date"] = null;

        //nhiều hình ảnh , videos
        $result['media_gallery'] = $this->getVideosAndImagesRedis($product_data);
        /// set product media configurable 
        if ($product_configurable['isConfigurable'] && !empty($product_configurable['media_gallery'])) {
            $result['media_gallery']['images'] = array_merge($result['media_gallery']['images'], $product_configurable['media_gallery']['images']);
        }

        $result["rating_summary"] = $this->getFHSRatingAveragesRedis($productId,$product_data);
        $result["rating_summary"] += $this->getAmazonReviewCountDetail($product_data['sku']);
//        $result["list_comment"] = null;
        $result["list_related"] = [];
        $result['list_related2'] = array();
        $result['maxRelated'] = 15;

        // handle Configurable expectedDateMsg
        if ($product_configurable['isConfigurable'] && !empty($product_configurable['listChild'])) {
            $productDefault = $product_configurable['product_default'];
            $rExpectedDateMsgData = $this->getExpectedDateMsgByProductRedis($productDefault);
        } else {
            $rExpectedDateMsgData = $this->getExpectedDateMsgByProductRedis($product_data);
        }

        if (!empty($rExpectedDateMsgData)) {
            $result['expectedDateMsg'] = $rExpectedDateMsgData['expectedDateMsg'];
            if ($rExpectedDateMsgData['expectedDate']) {
                $result['expectedDate'] = $rExpectedDateMsgData['expectedDate'];
            }
        }
	if(!$is_basic){
	    $result['list_bundled'] = array();
	    if ($result["type_id"] == "bundle") {
		$productsBundle = $this->getProductBundledRedis($productId,$product_data);
		$result['list_bundled'] = $productsBundle['bundled_items'];
		//$result['price'] = $productsBundle['price'];
		//$result['final_price'] = $productsBundle['special_price'];
	    }
	    // list_bundled = null : other child item is out_of_stock
	    if ($result['list_bundled'] == null && $result["type_id"] == "bundle") {
		$result["stock_available"] = "out_of_stock";
	    }
	    
	    $result["enableVoteProduct"] = \Mage::getStoreConfig('game/voteproduct/enable');
	    $result["enableFlashsale"] = \Mage::getStoreConfig('flashsale_config/config/is_active');
	    $result["enableBuffetCombo"] = \Mage::getStoreConfig('event_buffetcombo/config/is_active');
	    $show_buffetcombo_icon = \Mage::getStoreConfig('event_buffetcombo/config/show_buffetcombo_icon');
	    if ($result["enableBuffetCombo"] || $show_buffetcombo_icon) {
		$result["buffetcombo_tooltip"] = \Mage::getStoreConfig('event_buffetcombo/config/page_detail_tooltip');
		$result["buffetcombo_icon"] = \Mage::getBaseUrl('media') . "event/" . \Mage::getStoreConfig('event_buffetcombo/config/buffetcombo_icon');
	    }

	    //get promotion_message 
            //$result['promotion'] = null;
	    \Mage::log(' -START GET promotion_message : ' . round(microtime(true) * 1000) , null, "redis_product_debug.log" );
	    $result['promotion'] = \Mage::helper('eventcart')->getProductPromotion($productId);
	    \Mage::log(' -AFTER GET promotion_message DONE : ' . round(microtime(true) * 1000) , null, "redis_product_debug.log" );

	}else{
	    $result["series_id"] = !empty($product_data['seri_id'])?$product_data['seri_id']:null;
	}
        $useConfigStatus = $product_data['use_config_min_sale_qty'];
        if ($useConfigStatus == 0) {
            $minQty = $product_data['min_sale_qty'];
        } else {
           $minQty = $product_data['min_qty'];
        }
        $result["min_qty"] = $minQty;

        $result["success"] = true;
        // if price = null return error json
        if (($result["price"] == 0) && ($result["type_id"] == "simple")) {
            $result = "";
        }

        // no show product with "Not Visible Individually"
        if ($product_data['visibility'] == "1"|| $product_data['visibility'] === 1) {
            $result = "";
        }
        $Endaa = round(microtime(true) * 1000);
        $test = ($Endaa - $beginaa);
        \Mage::log(' ---DONE function productRedis : ' . $Endaa , null, "redis_product_debug.log" );
        
        \Mage::log(' --DONE function productRedis : ' . $test , null, "redis_product_debug.log" );
        

	//set to cache
	if (!Mage::registry($cache_key)){Mage::register($cache_key, $result);}
	
        return $result;
    }
    
    public function getYoutubeIdFromUrl($url) {
        preg_match('/(http(s|):|)\/\/(www\.|)yout(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $url, $results);
        return $results[6];
    }
     
    public function getExpectedDateMsgByProductRedis($product_data) {
        $result = array();
        if ($product_data['soon_release'] == 1) {
            
            $expectedDate = $product_data['expected_date'];
            $bookReleaseDate = $product_data['book_release_date'];
            $soon_release = $product_data['soon_release'];
            
            $message = \Mage::helper('fahasa_catalog/product')->getProductExpectedMsg(null,$soon_release, $expectedDate, $bookReleaseDate);
            $arrayExpectedDateMsg = array(
                "0" => $message[0],
                "1" => $message[1]
            );
            $result['expectedDateMsg'] = $arrayExpectedDateMsg;

            if ($bookReleaseDate != NULL && $date_now <= $bookReleaseDate) {
                $result['expectedDate'] = $bookReleaseDate;
            } else if ($expectedDate != NULL && $date_now <= $expectedDate) {
                $result['expectedDate'] = $expectedDate;
            }
        }
        return $result;
    }
    
    
    public function resizeImageProRedis($productId,$image, $image_path){
        $image_link = \Mage::helper('catalog/image')->init(Mage::getModel('catalog/product'), 'thumbnail',  $image_path)->resize(400, 400)->__toString();
        return $image_link;
    }
    public function getVideosAndImagesRedis($product_data) {
        //nhiều hình ảnh
        $result['media_gallery'] = array();
        $list_images['images'] = $product_data['media_gallery'];
        $productId = (int) $product_data['product_id'];
        $i_image = 0;
        $setEntity = false;
        foreach ($list_images['images'] as $value) {
            if ($value['disabled'] != '1') {
                $result['media_gallery']['images'][$i_image]['value_id'] = $value['value_id'];
                $result['media_gallery']['images'][$i_image]['file'] = $this->getUrlImage() . $value['image_url'];
//                $result['media_gallery']['images'][$i_image]['file'] = $this->resizeImageProRedis($productId, $value['image_url']);
                $result['media_gallery']['images'][$i_image]['label'] = $value['label'] ?? '';
                $result['media_gallery']['images'][$i_image]['position'] = $value['position'];
                $result['media_gallery']['images'][$i_image]['type'] = "image";
                if ($setEntity === false) {
                    $result['media_gallery']['images'][$i_image]['entity_id'] = $productId;
                    $setEntity = true;
                } else {
                    $result['media_gallery']['images'][$i_image]['entity_id'] = null;
                }
                $i_image = $i_image + 1;
            }
        }
        //nhiều video :
        if ($product_data['videos']) {
            $videos_json = $product_data['videos'];
            if ($videos_json) {
                foreach ($videos_json as $video) {
                    $linkVideo = null;
                    $typeVideo = null;
                    if ($video['video_link']) {
                        $parse = parse_url($video['video_link']);
                        $imageLink = $video['image_link'] ? $this->resizeImageProRedis($productId, $video['image_link'], $product_data["image_url"]) : null;
                        if ($parse['host'] && $parse['host'] = 'www.youtube.com') {
                            //$linkVideo = str_replace('/embed/', '', $parse['path']);
                            $linkVideo = $this->getYoutubeIdFromUrl($video['video_link']);
                            $typeVideo = 'youtube';
                        } else if ($video['video_type']) {
                            //example '/wysiwyg/NGAN/VIDEO-t8/MyCloset_Destroy Clip fhs.mp4';
                            $videoDeleteSpace = str_replace(' ', '%20', $video['video_link']);
                            $linkVideo = Mage::getBaseUrl('media') . $videoDeleteSpace;
                            $typeVideo = $video['video_type'];
                        }
                        if ($linkVideo && $typeVideo) {
                            $result['media_gallery']['images'][$i_image]['file'] = $linkVideo;
                            $result['media_gallery']['images'][$i_image]['label'] = '';
                            $result['media_gallery']['images'][$i_image]['position'] = $video['sort_order'];
                            $result['media_gallery']['images'][$i_image]['type'] = $typeVideo;
                            $result['media_gallery']['images'][$i_image]['imageLink'] = $imageLink;
                            $result['media_gallery']['images'][$i_image]['videoLink'] = $video['video_link'];
                            $i_image = $i_image + 1;
                        }
                    }
                }

                $ImageArray = $result['media_gallery']['images'];
                // sap xep vi tri theo position 
                $sortSuccess = usort($ImageArray, function($a, $b) {
                    return ($a['position'] > $b['position']) ? 1 : -1;
                });
                if ($sortSuccess) {
                    $result['media_gallery']['images'] = $ImageArray;
                }
            }
        }
        
        //nhiều magazine :
        if($product_data['magazine'] && !empty($product_data['magazine'])) {
            $positionZine = 0;
            $dataMagazine = $product_data['magazine'];
            if (!empty($result['media_gallery']) && !empty($result['media_gallery']['images']) && is_array($result['media_gallery']['images'])) {
                $lastItem = end($result['media_gallery']['images']);
                if ($lastItem && $lastItem['position']) {
                    $positionZine = $lastItem['position'] ?? 0;
                }
            }
            $urlMagazine = \Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "flashmagazine/images/page_images/";
            foreach ($dataMagazine as $value) {
                if ($value['is_active'] == 1 && $value['page_type'] == 'Image') {
                    $result['media_gallery']['images'][$i_image]['value_id'] = 'magazine' . $value['page_id'];
                    $result['media_gallery']['images'][$i_image]['file'] = $urlMagazine . $value['page_image'];
                    $result['media_gallery']['images'][$i_image]['label'] = $value['label'] ?? '';
                    $result['media_gallery']['images'][$i_image]['position'] = $positionZine + $value['page_sort_order'];
                    $result['media_gallery']['images'][$i_image]['type'] = "image";
                    $result['media_gallery']['images'][$i_image]['entity_id'] = null;
                    $i_image = $i_image + 1;
                }
            }
            
        }

        return $result['media_gallery']; 
    }
    
    public function isConfigurableProduct($product_data) {
        $_product = array();
        if ($product_data['childConfigruableId']) {
            // kiem tra child id hay khong ? 
            $_product['product'] = $product_data;
            $_product['isConfigurable'] = true;
            $_product['typeConfigurable'] = 'child';
            $_product['product_id_main'] = $product_data['product_id'];
            $id = $product_data['childConfigruableId'];
            $dataListChild = $this->getlistCHildOfParent($product_data, $id, 'child');
            $_product['listChild'] = $dataListChild['listProducts'];
            $_product['media_gallery'] = $dataListChild['media_gallery'];
            $_product['product_default'] = $dataListChild['product_default'];
            $_product['disable_select'] = $dataListChild['disable_select'];
        } elseif ($product_data['type_id'] == "configurable") {
            $_product['product'] = $product_data;
            $_product['isConfigurable'] = true;
            $_product['typeConfigurable'] = 'parent';
            $_product['product_id_main'] = $product_data['product_id'];
            $dataListChild = $this->getlistCHildOfParent($product_data, $_product['product_id_main'], 'parent');
            $_product['listChild'] = $dataListChild['listProducts'];
            $_product['media_gallery'] = $dataListChild['media_gallery'];
            $_product['product_default'] = $dataListChild['product_default'];
            $_product['disable_select'] = $dataListChild['disable_select'];
            // product_default : 
        } else {
            $_product['product'] = $product_data;
            $_product['isConfigurable'] = false;
            $_product['typeConfigurable'] = null;
            $_product['product_id_main'] = $product_data['product_id'];
            $_product['media_gallery'] = $_product['listChild'] = array();
            $_product['disable_select'] = true;
            $_product['product_default'] = null;
        }

        return $_product;
    }

    public function getlistCHildOfParent($product_data,$pro_id,$type) {
        $is_set_default = false;
        $products_child = array();
        $products_colors = [];
        $reponse['listProducts'] = array();
        $reponse['product_default'] = null;
        $reponse['media_gallery'] = array();
        $reponse['disable_select'] = false; // cam chon
        $product_default_disable = null;
        $is_bro_child = false;
        $bro_id = null;
        $bro_id_data = null;
        
        
        if (!empty($product_data['super_attribute']) && count($product_data['super_attribute']) > 0) {
            
            $dataSuperAttribute = $product_data['super_attribute'][0];
            $dataChilds = $dataSuperAttribute['childs'];
            $childIds = array();
           
            if(empty($dataChilds)){return $reponse;}
            
            $attr_key = array(
                "visibility",
                "product_id",
                "type_id",
                "sku",
                "product_name",
                "resize_image_url",
                "price",
                "final_price",
                "discount",
                "qty",
                "soon_release",
                "stock_status",
                "expected_date",
                "book_release_date",
                "media_gallery"
            );

            foreach ($dataChilds as $_child) {
                $products_colors[$_child['product_id']] = $_child['value'];
                //$childIds[] = $_child['product_id'];
            }
            
            //$products_data = Mage::helper('fahasa_catalog/product')->getProductsStoreArray($childIds, $attr_key);
            $products_data = $product_data['childsInfo'];
            
//            
            foreach ($products_data as $item) {
//                $product_item = \Mage::getModel('catalog/product')
//                        ->setStoreId(\Mage::app()->getStore()->getStoreId())
//                        ->load($item->getEntityId());
                
                $rquantity = (int) $item["qty"];
                
                $product_child = [];
                $product_child['sku'] = $item['sku'];
                $product_child['type_id'] = $item['type_id']; 
                $product_child['product_id'] = (int) $item['product_id'];
                $product_child['entity_id'] = (int) $item['product_id'];
                $product_child['product_name'] = $item['product_name'];
                $product_child['name'] = $item['product_name'];
                $product_child['att_value'] = $products_colors[$item['product_id']];
                $product_child['att_name'] = $dataSuperAttribute['attribute_name'];
                $product_child['att_code'] = $dataSuperAttribute['attribute_code'];
                $product_child['qty'] = $rquantity;
                $product_child['soon_release'] = (int) $item['soon_release'];
                $product_child['is_available'] = $item['stock_status'] ? true : false;
                $product_child["discount_percent"] = $item['discount'];
                $product_child['img_src'] = $this::URL_IMAGE_REDIS . $item['resize_image_url'];;
                if ($item['type_id'] == "bundle") {
                    $product_child["min_price"] = $item['final_price'];
                    $product_child["max_price"] = $item['price'];
                }
                    $product_child["final_price"] = (int) $item['final_price'];
                    $product_child["price"] = (int) $item['price'];
                
                if ($item['stock_status']) {
                    $product_child["has_stock"] = true;
                } else {
                    $product_child["has_stock"] = false;
                }
                
                // expectedDateMsg
                $rExpectedDateMsgData = $this->getExpectedDateMsgByProductRedis($item);
                if (!empty($rExpectedDateMsgData)) {
                    $product_child['expectedDateMsg'] = $rExpectedDateMsgData['expectedDateMsg'];
                    if ($rExpectedDateMsgData['expectedDate']) {
                        $product_child['expectedDate'] = $rExpectedDateMsgData['expectedDate'];
                    }
                }
                
                $item['media_gallery'] = Mage::helper('fahasa_catalog/product')->json_validate($item['media_gallery']);
                $item['videos'] = Mage::helper('fahasa_catalog/product')->json_validate($item['videos']);
                $item['magazine'] = Mage::helper('fahasa_catalog/product')->json_validate($item['magazine']);
                //nhiều hình ảnh , videos
                $media_gallery = $this->getVideosAndImagesRedis($item);
                if (!empty($media_gallery)) {
                    if (empty($reponse['media_gallery'])) {
                        $reponse['media_gallery'] = $media_gallery;
                    }else{
                       $reponse['media_gallery']['images'] = array_merge($reponse['media_gallery']['images'],$media_gallery['images']);
                    }
                }

                $product_child['is_disable'] = false;
                $product_child['is_default'] = false;
                //if ($rquantity <= 0  || !$product_item->isAvailable() || $product_item->getIsInStock() != 1) {
                if ($rquantity <= 0  || !$item['stock_status']) {
                    $product_child['is_disable'] = true;
                }
                 
                // set active attribute first : child
                if($type == 'child') {
                    if($pro_id == $product_child['product_id']){
                        $product_child['is_default'] = true;
                        $product_default_disable = $item;
                    }else{
                        if(!$product_child['is_disable'] && !$is_bro_child ){
                          $bro_id = $product_child['product_id'];
                          $bro_id_data = $item;
                          $is_bro_child = true;
                        }
                    }
                } else if ($type == 'parent') {
                    if (!$is_set_default && !$product_child['is_disable']) {
                        $is_set_default = true;
                        $product_child['is_default'] = true;
                        $reponse['product_default'] = $item;
                    } else {
                        $product_child['is_default'] = false;
                        // set var temp => using if dont have any default
                        $product_default_disable = $item;
                    }
                }
                $products_child[$item['product_id']] = $product_child;
            }
            
            //products child not set default :
            if (!$is_set_default) {
                if ($type == 'child') {
                    // case : cant set child id or child id disable (chinh no)
                    if ($products_child[$pro_id] && !$products_child[$pro_id]['is_disable']) {
                        // get data product chinh no :
                        $reponse['product_default'] = $product_default_disable;
                    } else if ($is_bro_child) {
                        // neu bro cua no co data => set bro cua no default
                        if ($products_child[$pro_id] && $products_child[$pro_id]['is_disable']) {
                            $products_child[$pro_id]['is_default'] = false;
                        }
                        $products_child[$bro_id]['is_default'] = true;
                        $reponse['product_default'] = $bro_id_data;
                    }else{
                        // neu bro cua no khong co set ve chinh no
                        $reponse['disable_select'] = true;
                        $reponse['product_default'] = $product_default_disable;
                    }
                }else{
                    $reponse['disable_select'] = true;
                    $reponse['product_default'] = $product_default_disable;
                }
                
            }
            $reponse['listProducts'] = $products_child;
        }
        
       
        return $reponse;
    }
    
    public function getProductAtributesRedis($product_data, $productId, $sku) {
        
        $result_temp = \Mage::helper('fahasa_catalog/product')->getAttributeFilter($product_data, true);
        $array_data = array();
        foreach ($result_temp as $key => $value) {
            if (is_array($value)) {
                $name = '';
                if ($key == 'author') {
                    $name = $result_temp[$key][0]['key'];
                } else {
                    $name = $result_temp[$key][0]['value'];
                }
                $array_data[$key] = $name;
            } else {
                $array_data[$key] = $value;
            }
        }
        $result = array();
        foreach ($array_data as $key => $value)
        {
            $result[] = array(
                "name" => $key,
                "value" => $value
            );
        }
        return $result;
    }

    /**
     * Danh sách combo theo sản phẩm
     * @param type $product
     * @return type
     */
    
    private function getProductBundledRedis($productId,$product_data) {       
    
        $bundleData = $product_data['list_bundled'];
        
        $bundlePrice = 0;
        $bundleSpecialPrice = 0;
        $bundled_items = array();
        $i = $j = 0;
        if ($bundleData && count($bundleData) > 0) {
            $products_data = $product_data['childsInfo'];
             foreach ($bundleData as $_child) {
                $temp['type']= $_child['type']; 
                $temp['position']= $_child['position'];
                $temp['required']= $_child['required'];
                $temp['option_id']= $_child['option_id'];
                $temp['selections'] = array();
               
               $pro_bundle = $_child['detail_list'];
               foreach ($pro_bundle as $_proChild) {
                    $proId = (string) $_proChild['product_id'];
                    $index_key = array_search($proId, array_column($products_data, 'product_id'));
                    $pro_data = $products_data[$index_key];
                    $bundled_items["visibility"] = $pro_data['visibility'];
                    $bundled_items["entity_id"] = $pro_data['product_id'];
                    $bundled_items["type_id"] = $pro_data['type_id'];
                    $bundled_items["sku"] = (string) $pro_data['sku'];
                    $bundled_items["selection_id"] = $_proChild['selection_id'];
                    $bundled_items["option_id"] = $_proChild['option_id'];
                    $bundled_items["is_default"] = $_proChild['is_default'];
                    $bundled_items["name"] = $pro_data['product_name'];
                    $bundled_items["small_image"] = $this::URL_IMAGE_REDIS . $pro_data['resize_image_url'];
                    $bundled_items["price"] = $pro_data['price'];
                    $bundled_items["final_price"] = $pro_data['final_price'];
                    $bundled_items["discount_percent"] = $pro_data['discount'];
                    $bundlePrice += $pro_data['price'] * $_proChild['selection_qty'];
                    $bundleSpecialPrice += $pro_data['final_price'];
		    
		    //set for web
		    $bundled_items["url"] = Mage::getBaseUrl().$pro_data['product_url'];
                    
                    $temp['selections'][] = $bundled_items;
                }
                $result['bundled_items'][] = $temp;
            }
            $result['price'] = $bundlePrice;
            $result['special_price'] = $bundleSpecialPrice;
            return $result;
        } else {
            $result['bundled_items'] = array();
            $result['price'] = $bundlePrice;
            $result['special_price'] = $bundleSpecialPrice;
            return $result;
        }
    }
    
    public function getFHSRatingAveragesRedis($productId, $product_data) {
        $fhsRating = array();
        $totalComment = array();
        $totalComment["1"] = 0;
        $totalComment["2"] = 0;
        $totalComment["3"] = 0;
        $totalComment["4"] = 0;
        $totalComment["5"] = 0;
        
        if (count($product_data['total_stars'] > 0)) {
            for ($index = 0; $index < count($product_data['total_stars']); $index++) {
                $dataItem = $product_data['total_stars'][$index];
                $totalComment[$index + 1] = $dataItem['value'];
            }
        }
	if(!empty($product_data['rating_fs'])){
	    $rating = $product_data['rating_fs'];
	    $reviews_count = !empty($rating['rating_count'])?$rating['rating_count']:0;
	    $rating_summary = !empty($rating['rating_summary'])?$rating['rating_summary']:0;
	}
	if(!empty($product_data['rating_aws'])){
	    $rating = $product_data['rating_aws'];
	    $reviews_count_amz_ = !empty($rating['rating_count'])?$rating['rating_count']:0;
	    $rating_summary_amz = !empty($rating['rating_summary'])?$rating['rating_summary']:0;
	}
	if(!empty($product_data['rating_gr'])){
	    $rating = $product_data['rating_gr'];
	    $reviews_count_gr = !empty($rating['rating_count'])?$rating['rating_count']:0;
	    $rating_summary_gr = !empty($rating['rating_summary'])?$rating['rating_summary']:0;
	}
	
        $fhsRating['total_star'] = $totalComment;  
	
        $fhsRating['reviews_count_fhs'] = !empty($reviews_count)?$reviews_count:0;
        $fhsRating['rating_summary_fhs'] = !empty($rating_summary)?$rating_summary:0;
	
        $fhsRating['reviews_count_amz'] = !empty($reviews_count_amz_)?$reviews_count_amz_:0;
        $fhsRating['rating_summary_amz'] = !empty($rating_summary_amz)?$rating_summary_amz:0;
	
        $fhsRating['reviews_count_gr'] = !empty($reviews_count_gr)?$reviews_count_gr:0;
        $fhsRating['rating_summary_gr'] = !empty($rating_summary_gr)?$rating_summary_gr:0;
	
               
        return $fhsRating;
    }
    
    public function getAmazonReviewCountDetail($sku) {        
//        $summaryDataAma = \Mage::getModel('amazonrating/amazonrating')->load($sku);
        $result = array();        
//        $result['reviews_count_ama'] = $summaryDataAma->getData('numericScore');
//        $result['rating_summary_ama'] = self::convertAmazonStarToNumericRating($summaryDataAma->getData('cssStarRating'));
        $result['reviews_count_ama'] = null;
        $result['rating_summary_ama'] = null;
        return $result;
    }
    
    
    public function getListCommentRedis($product_data) {
        $dataReviews = $product_data['list_comment'];
        $listData = $dataReviews['comment_list'];
        $list = null;
        if(!empty($listData) && count($listData) > 0){
            foreach ($listData AS $review) {
                $item['rating'] = (int) $review['rating_percent'];
                $item['title'] = $review['title']; // title
                $item['detail'] = $review['detail']; //detail
                $item['nickname'] = $review['nickname'];  // nickname
                $item['created_at'] = $review['created']; // created
                
                $list[] = $item;
            }
        }
        return $list;
    }
    
    //chuyen doi sao trong amazon rating
    public static function convertAmazonStarToNumericRating($css) {
        if($css == null){
            return null;
        }
        if (strpos($css, ' a-star-medium-5 ')) {
            return 5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-4-5')) {
            return 4.5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-4')) {
            return 4 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-3-5')) {
            return 3.5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-3')) {
            return 3 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-2-5')) {
            return 2.5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-2')) {
            return 2 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-1-5')) {
            return 1.5 * 100 / 5;
        }
        if (strpos($css, 'a-star-medium-1')) {
            return 1 * 100 / 5;
        }

        //khong co 0.5 sao
        return 1 * 100 / 5;
    }
    
}
?>
