<?php
class Fahasa_Catalog_Helper_Productdb extends Mage_Core_Helper_Abstract
{
    public function getProductID($productId, $is_mobile = true) {
        $product_input = \Mage::getModel('catalog/product')
                ->setStoreId(\Mage::app()->getStore()->getStoreId())
                ->load($productId);
        // confiurable product : 
        $product_configurable = $this->isConfigurableProduct($product_input);

        // set product : 
        $productId = $product_configurable['product_id_main'];
        $product = $product_configurable['product'];
//        $product = $product_input;

        $handleDiscount = \Mage::helper('discountlabel/data');
        $book_layout = $product->getResource()->getAttribute("book_layout")
                        ->getFrontend()->getValue($product);
        //lấy thông tin sản phẩm
        $result["loadDataType"] = 'database';
        $result["entity_id"] = $product->entity_id;
        $result["entity_id_sub"] = $product->entity_id;
        $result["category"] = $product->category_1;
        $result["category_main"] = $product->category_main;
        $result["category_mid"] = $product->category_mid;
        $result["category_3"] = $product->category_1;
        $result["category_4"] = $product->cat4 == 'N/A' ? null : $product->cat4 ;
        $result["category_main_id"] = $product->category_main_id == "1" ? null : $product->category_main_id;
        $result["category_mid_id"] = $product->category_mid_id == "1" ? null : $product->category_mid_id;
        $result["category_3_id"] = $product->category_1_id == "1" ? null : $product->category_1_id;
        $result["category_4_id"] = $product->cat4_id == "1" ? null : $product->cat4_id;
        $result["type_id"] = $product->type_id;
        $result["sku"] = $product->sku;
        $result["has_options"] = $product->has_options;
        $result["required_options"] = $product->required_options;
        $result["created_at"] = $product->created_at;
        $result["updated_at"] = $product->updated_at;
        $result["name"] = $product->name;
        $result["meta_title"] = $product->meta_title;
        $result["meta_description"] = $product->meta_description;
        $result["image"] = \Mage::helper('catalog/image')->init($product, 'thumbnail', $product->getImage())->resize(400, 400)->__toString();
        $result["small_image"] = \Mage::helper('catalog/image')->init($product, 'thumbnail', $product->getImage())->resize(400, 400)->__toString();
        $result["thumbnail"] = \Mage::helper('catalog/image')->init($product, 'thumbnail', $product->getImage())->resize(400, 400)->__toString();
        
        //get more product attributes
        $pro_sku = $product->sku;
        $product_helper = \Mage::helper('fahasa_catalog/product');
        
        $result["isConfigurable"] = $product_configurable['isConfigurable'];
        $result["childs"] = $product_configurable['listChild'];
        $result["disable_select"] = $product_configurable['disable_select'];

        // set product price configurable 
        if ($product_configurable['isConfigurable'] && !empty($product_configurable['listChild'])) {
            $productDefault = $product_configurable['product_default'];
            $result["entity_id_sub"] = $productDefault->entity_id;
            $cProduct = $productDefault;
        } else {
            $cProduct = $product;
        }

        // handle set attribute confiruable
        if ($product->getTypeId() == "bundle") {
            $prices = \Mage::getModel('bundle/product_price')->getTotalPrices($cProduct);
            $result["min_price"] = $prices[0];
            $result["max_price"] = $prices[1];
        } else {
            $result["final_price"] = $cProduct->getFinalPrice();
            $result["price"] = $cProduct->getPrice();
        }
        $result["visibility"] = $cProduct->visibility;
        $result["status"] = $cProduct->status;
        $result["soon_release"] = $cProduct->soon_release;
        $result["qty_of_page"] = $cProduct->qty_of_page;
        $result["qty"] = $cProduct->stockItem->qty;
        $result["is_available"] = $cProduct->isAvailable();
        $result["discount_percent"] = $handleDiscount->handleDiscountPercent($cProduct);
        if ($cProduct->stockItem->is_in_stock > 0) {
            $result["stock_available"] = "in_stock";
        } else {
            $result["stock_available"] = "out_of_stock";
        }
        if ($cProduct->stockItem->qty > 0) {
            $result["has_stock"] = true;
        } else {
            $result["has_stock"] = false;
        }

        $result["image_label"] = $product->image_label;
        $result["small_image_label"] = $product->small_image_label;
        $result["thumbnail_label"] = $product->thumbnail_label;
        $result["gift_message_available"] = $product->gift_message_available;

        $result["publish_year"] = $product->publish_year;
        $result["size"] = $product->size;
        $result["author"] = $product->author;
        $result["publisher"] = $product->publisher;
        $supplier_id = $product->supplier;
        $supplier = null;
        if ($supplier_id) {
            $supplier_data = \Mage::helper('fahasa_catalog')->getDataSupplier($supplier_id);
            if ($supplier_data) {
                $supplier = $supplier_data['name'];
            }
        }
        $result['supplier'] = $supplier;
        $result['supplier_id'] = $supplier_id;
        $result["translator"] = $product->translator;
        $result["weight"] = $product->weight;
        $result["country_of_manufacture"] = $product->country_of_manufacture;
        $result["tax_class_id"] = $product->tax_class_id;
        $result["weight_type"] = $product->weight_type;
        $result["featured"] = $product->featured;

        $result["book_layout"] = $book_layout;
        $result["exclusive"] = $product->exclusive;

        $result["description"] = $product->description;

        $result["short_description"] = $product->short_description;
        $result["meta_keyword"] = $product->meta_keyword;

        //get more product attributes
        //$result["attributes"] = $this->getProductAtributes($productId, $product->sku);

        $date_now = date("Y-m-d", strtotime('+7 hours'));

        //chu xu ly ko co To
        if ($product->getSpecialFromDate() <= $date_now && $date_now <= $product->getSpecialToDate()) {
            $result["special_from_date"] = $product->special_from_date;
            $result["special_to_date"] = $product->special_to_date;
        }

        $result["news_from_date"] = $product->news_from_date;
        $result["news_to_date"] = $product->news_to_date;

        //nhiều hình ảnh , videos
        $result['media_gallery'] = $this->getVideosAndImages($product);
        /// set product media configurable 
        if ($product_configurable['isConfigurable'] && !empty($product_configurable['media_gallery'])) {
            $result['media_gallery']['images'] = array_merge($result['media_gallery']['images'], $product_configurable['media_gallery']['images']);
        }

        $result['list_bundled'] = array();
        if ($product->getTypeId() == "bundle") {
            $result['list_bundled'] = $this->getProductBundled($product);
            $result['price'] = $product->price;
            $result['final_price'] = $product->getFinalPrice();
        }
        $result["rating_summary"] = $this->getFHSRatingAverages($productId);
        $result["rating_summary"] += $this->getAmazonReviewCountDetail($product->sku);
        //$result["list_comment"] = $this->getListComment($productId, 1, 10);
        $result["list_related"] = [];
//        $result["list_related"] = $this->getProductRelated($product);
        // list_bundled = null : other child item is out_of_stock
        if ($result['list_bundled'] == null && $result["type_id"] == "bundle") {
            $result["stock_available"] = "out_of_stock";
        }
        $result['list_related2'] = array();
        $result['maxRelated'] = 15;
	
	if($is_mobile){
	    $result["attributes"] = $this->getProductAtributes($productId, $product->sku);
	    $result["list_comment"] = $this->getListComment($productId, 1, 10);
	}else{
	    //set for web
            $result["url"] = Mage::getBaseUrl() . $product->url_path;
            $result["url_key"] = $product->url_key;
            $result["links"] = $this->getProductLinks($productId);
	    $result["attributes"] = $product_helper->getProductAtributesFrontEnd($product, $productId);
	    
	    if(!empty($result["rating_summary"])){
		$rating_summary = $result["rating_summary"];
		$rating_summary_fhs = $rating_summary['rating_summary_fhs'];
		$reviews_count_fhs = $rating_summary['reviews_count_fhs'];
	    }
	    $rating_fs = array(
		'rating_fs' => array(
				'rating_summary' => !empty($rating_summary_fhs)?$rating_summary_fhs:0,
				'rating_count' => !empty($reviews_count_fhs)?$reviews_count_fhs:0
			    )
	    );
	    $rating_fhs_html = $product_helper->getRattingHtml($rating_fs);
	    if(!empty($rating_fhs_html)){
		$result['rating_fhs_html'] = $rating_fhs_html;
	    }
	    
	    //$rating_other_desktop_html = $product_helper->getRattingOtherHtml($product_data, $productId, 'desktop');
	    //$rating_other_mobile_html = $product_helper->getRattingOtherHtml($product_data, $productId, 'mobile');
//	    if(!empty($rating_other_desktop_html) && !empty($rating_other_mobile_html)){
//		$rating_other = array();
//		$rating_other['desktop'] = $rating_other_desktop_html;
//		$rating_other['mobile'] = $rating_other_mobile_html;
//
//		$result['rating_other'] = $rating_other;
//	    }
	}
        // handle Configurable expectedDateMsg
        if ($product_configurable['isConfigurable'] && !empty($product_configurable['listChild'])) {
            $productDefault = $product_configurable['product_default'];
            $rExpectedDateMsgData = $this->getExpectedDateMsgByProduct($productDefault);
        } else {
            $rExpectedDateMsgData = $this->getExpectedDateMsgByProduct($product);
        }

        if (!empty($rExpectedDateMsgData)) {
            $result['expectedDateMsg'] = $rExpectedDateMsgData['expectedDateMsg'];
            if ($rExpectedDateMsgData['expectedDate']) {
                $result['expectedDate'] = $rExpectedDateMsgData['expectedDate'];
            }
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
        $result['promotion'] = \Mage::helper('eventcart')->getProductPromotion($productId);

        $stockItem = $product->getData('stock_item');
        $useConfigStatus = $stockItem->getData('use_config_min_sale_qty');
        if ($useConfigStatus == 0) {
            $minQty = $stockItem->getData('min_sale_qty');
        } else {
            $minQty = $stockItem->getData('min_qty');
        }
        $result["min_qty"] = $minQty;

        $result["success"] = true;
        // if price = null return error json
        if (($result["price"] == 0) && ($result["type_id"] == "simple")) {
            $result = "";
        }

        // no show product with "Not Visible Individually"
        if ($product->visibility == "1") {
            $result = "";
        }
         

        return $result;
    }
    
    public function getYoutubeIdFromUrl($url) {
        preg_match('/(http(s|):|)\/\/(www\.|)yout(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $url, $results);
        return $results[6];
    }
    
    public function getExpectedDateMsgByProduct($product) {
        $result = array();
        if ($product->soon_release == 1) {
//            $expectedDate = $product->getExpectedDate();
            $message = \Mage::helper('fahasa_catalog/product')->getProductExpectedMsg($product);
            $arrayExpectedDateMsg = array(
                "0" => $message[0],
                "1" => $message[1]
            );
            $result['expectedDateMsg'] = $arrayExpectedDateMsg;

            $expectedDate = $product->getExpectedDate();
            $bookReleaseDate = $product->getBookReleaseDate();
            if ($bookReleaseDate != NULL && $date_now <= $bookReleaseDate) {
                $result['expectedDate'] = $bookReleaseDate;
            } else if ($expectedDate != NULL && $date_now <= $expectedDate) {
                $result['expectedDate'] = $expectedDate;
            }
        }
        return $result;
    }
    
    public function getVideosAndImages($product) {
        //nhiều hình ảnh
        $result['media_gallery'] = array();

        $list_images = $product->getMediaGallery();
        $i_image = 0;
        $setEntity = false;
        foreach ($list_images['images'] as $value) {
            if ($value['disabled'] != '1') {
                $result['media_gallery']['images'][$i_image]['value_id'] = $value['value_id'];
                $result['media_gallery']['images'][$i_image]['file'] = \Mage::helper('catalog/image')->init($product, 'thumbnail', $value['file'])->resize(400, 400)->__toString();
                $result['media_gallery']['images'][$i_image]['label'] = $value['label'];
                $result['media_gallery']['images'][$i_image]['position'] = $value['position'];
                $result['media_gallery']['images'][$i_image]['type'] = "image";
                if ($setEntity === false) {
                    $result['media_gallery']['images'][$i_image]['entity_id'] = $product->entity_id;
                    $setEntity = true;
                } else {
                    $result['media_gallery']['images'][$i_image]['entity_id'] = null;
                }
                $i_image = $i_image + 1;
            }
        }
        //nhiều video :
        if ($product->getVideos()) {
            $videos_json = json_decode($product->getVideos(), true);
            if ($videos_json) {
                foreach ($videos_json as $video) {
                    $linkVideo = null;
                    $typeVideo = null;
                    if ($video['video_link']) {
                        $parse = parse_url($video['video_link']);
                        $imageLink = $video['image_link'] ? \Mage::helper('catalog/image')->init($product, 'thumbnail', $video['image_link'])->resize(400, 400)->__toString() : null;
                        if ($parse['host'] && $parse['host'] = 'www.youtube.com') {
                            //$linkVideo = str_replace('/embed/', '', $parse['path']);
                            $linkVideo = $this->getYoutubeIdFromUrl($video['video_link']);
                            $typeVideo = 'youtube';
                        } else if ($video['video_type']) {
                            //example '/wysiwyg/NGAN/VIDEO-t8/MyCloset_Destroy Clip fhs.mp4';
                            $videoDeleteSpace = str_replace(' ', '%20', $video['video_link']);
                            $linkVideo = \Mage::getBaseUrl('media') . $videoDeleteSpace;
                            $typeVideo = $video['video_type'];
                        }
                        if ($linkVideo && $typeVideo) {
                            $result['media_gallery']['images'][$i_image]['file'] = $linkVideo;
                            $result['media_gallery']['images'][$i_image]['label'] = '';
                            $result['media_gallery']['images'][$i_image]['position'] = $video['sort_order'];
                            $result['media_gallery']['images'][$i_image]['type'] = $typeVideo;
                            $result['media_gallery']['images'][$i_image]['imageLink'] = $imageLink;
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
        
        return $result['media_gallery']; 
    }
    
    public function isConfigurableProduct($product) {
        $_product = array();
        if ($product->type_id == "configurable") {
            $_product['product'] = $product;
            $_product['isConfigurable'] = true;
            $_product['typeConfigurable'] = 'parent';
            $_product['product_id_main'] = $product->entity_id;
            $dataListChild =$this->getlistCHildOfParent($_product['product_id_main'],$_product['product_id_main'],'parent');
            $_product['listChild'] = $dataListChild['listProducts'];
            $_product['media_gallery'] = $dataListChild['media_gallery'];
            $_product['product_default'] = $dataListChild['product_default'];
            $_product['disable_select'] = $dataListChild['disable_select'];
            // product_default : 
        } else {
            // kiem tra child hay khong ? 
            $product_parent_ids = \Mage::getResourceSingleton('catalog/product_type_configurable')
                    ->getParentIdsByChild($product->getEntityId());
            if (!empty($product_parent_ids)) {
                $product_parent_configurable = \Mage::getModel('catalog/product')
                        ->setStoreId(\Mage::app()->getStore()->getStoreId())
                        ->load($product_parent_ids[0]);
                $_product['product'] = $product_parent_configurable;
                $_product['isConfigurable'] = true;
                $_product['typeConfigurable'] = 'child';
                $_product['product_id_main'] = $product_parent_configurable->entity_id;
                $id = $product->getEntityId();
                $dataListChild = $this->getlistCHildOfParent($_product['product_id_main'],$id,'child');
                $_product['listChild'] = $dataListChild['listProducts'];
                $_product['media_gallery'] = $dataListChild['media_gallery'];
                $_product['product_default'] = $dataListChild['product_default'];
                $_product['disable_select'] = $dataListChild['disable_select'];
            } else {
                $_product['product'] = $product;
                $_product['isConfigurable'] = false;
                $_product['typeConfigurable'] = null;
                $_product['product_id_main'] = $product->entity_id;
                $_product['media_gallery'] = $_product['listChild'] = array();
                $_product['disable_select'] = true;
                $_product['product_default'] = null;
                
            }
        }

        return $_product;
    }
    
    public function getlistCHildOfParent($pro_parent_id,$pro_id,$type) {
        $product_parent_configurable = \Mage::getModel('catalog/product')->load($pro_parent_id);
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
        
        if (!empty($product_parent_configurable->getChildrenProducts())) {
            
            // get attribute chosee ?? 
//            $productAttributeOptions  = $product_parent_configurable->getTypeInstance(true)->getConfigurableAttributesAsArray($product_parent_configurable);
//            $attributeOptions = array();
//            foreach ($productAttributeOptions as $productAttribute) {
//                foreach ($productAttribute['values'] as $attribute) {
//                    $attributeOptions[$productAttribute['label']][$attribute['value_index']] = $attribute['store_label'];
//                }
//            }

            foreach ($product_parent_configurable->getChildAttributeLabelMapping() as $_color) {
                if (!empty($_color['product_ids'][0])) {
                    $products_colors[$_color['product_ids'][0]] = $_color['label'];
                }
            }
            foreach ($product_parent_configurable->getChildrenProducts() as $item) {
                $product_item = \Mage::getModel('catalog/product')
                        ->setStoreId(\Mage::app()->getStore()->getStoreId())
                        ->load($item->getEntityId());
                $rquantity = (int)\Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_item)->getQty();
                $handleDiscount = \Mage::helper('discountlabel/data');
                
                
                $product_child = [];
                $product_child['sku'] = $product_item->sku;
                $product_child['type_id'] = $product_item->type_id; 
                $product_child['product_id'] = (int) $product_item->getEntityId();
                $product_child['entity_id'] = (int) $product_item->getEntityId();
                $product_child['product_name'] = $product_item->getName();
                $product_child['name'] = $product_item->getName();
                $product_child['att_value'] = $products_colors[$product_item->getEntityId()];
                $product_child['att_name'] = 'Màu';
                $product_child['att_code'] = 'color';
                $product_child['qty'] = $rquantity;
                $product_child['soon_release'] = (int) $product_item->getSoonRelease();
                $product_child['is_available'] = $product_item->isAvailable();
                $product_child["discount_percent"] = $handleDiscount->handleDiscountPercent($product_item);
                $product_child['img_src'] = \Mage::helper('catalog/image')->init($product_item, 'thumbnail', $product_item->getImage())->resize(400, 400)->__toString();
                if ($product_item->getTypeId() == "bundle") {
                    $prices = \Mage::getModel('bundle/product_price')->getTotalPrices($product_item);
                    $product_child["min_price"] = $prices[0];
                    $product_child["max_price"] = $prices[1];
                    $product_child["final_price"] = $prices[0];
                    $product_child["price"] = $prices[1];
                } else {
                    $product_child["final_price"] = (int) $product_item->getFinalPrice();
                    $product_child["price"] = (int) $product_item->getPrice();
                }
                if ($product_item->stockItem->qty > 0) {
                    $product_child["has_stock"] = true;
                } else {
                    $product_child["has_stock"] = false;
                }
                
                // expectedDateMsg
                $rExpectedDateMsgData = $this->getExpectedDateMsgByProduct($product_item);
                if (!empty($rExpectedDateMsgData)) {
                    $product_child['expectedDateMsg'] = $rExpectedDateMsgData['expectedDateMsg'];
                    if ($rExpectedDateMsgData['expectedDate']) {
                        $product_child['expectedDate'] = $rExpectedDateMsgData['expectedDate'];
                    }
                }
                
                //nhiều hình ảnh , videos
                $media_gallery = $this->getVideosAndImages($product_item);
                if (!empty($media_gallery)) {
                    if (empty($reponse['media_gallery'])) {
                        $reponse['media_gallery'] = $media_gallery;
                    }else{
                       $reponse['media_gallery']['images'] = array_merge($reponse['media_gallery']['images'],$media_gallery['images']);
                    }
                }

                $product_child['is_disable'] = false;
                $product_child['is_default'] = false;
                if ($rquantity <= 0  || !$product_item->isAvailable() || $product_item->getIsInStock() != 1) {
                    $product_child['is_disable'] = true;
                }
                 
                // set active attribute first : child
                if($type == 'child') {
                    if($pro_id == $product_child['product_id']){
                        $product_child['is_default'] = true;
                        $product_default_disable = $product_item;
                    }else{
                        if(!$product_child['is_disable'] && !$is_bro_child ){
                          $bro_id = $product_child['product_id'];
                          $bro_id_data = $product_item;
                          $is_bro_child = true;
                        }
                    }
                } else if ($type == 'parent') {
                    if (!$is_set_default && !$product_child['is_disable']) {
                        $is_set_default = true;
                        $product_child['is_default'] = true;
                        $reponse['product_default'] = $product_item;
                    } else {
                        $product_child['is_default'] = false;
                        // set var temp => using if dont have any default
                        $product_default_disable = $product_item;
                    }
                }
                $products_child[$item->getId()] = $product_child;
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
    
    public function getProductAtributes($productId, $sku)
    {
        $result_temp = array();
       
        $book_attribute_sql = "select * from "
                . "((select 1 as type, i.attribute_id,    ie.attribute_code, ie.frontend_label, if (iov.value is null, iov_0.value, iov.value) as value, eaa.sort_order "
                . "from fhs_catalog_product_entity pe "
                . "join fhs_eav_attribute_set es on es.entity_type_id = pe.entity_type_id and es.attribute_set_id = pe.attribute_set_id "
                . "join fhs_eav_attribute_group eag on eag.attribute_set_id = es.attribute_set_id and eag.attribute_group_name in ('Book Attributes', 'Other Product Attributes') "
                . "join fhs_eav_entity_attribute eaa on eaa.entity_type_id = es.entity_type_id and eaa.attribute_set_id = es.attribute_set_id and eaa.attribute_group_id = eag.attribute_group_id "
                . "join fhs_catalog_product_entity_int i on i.entity_id = pe.entity_id and i.attribute_id = eaa.attribute_id "
                . "join fhs_eav_attribute ie on ie.attribute_id = i.attribute_id "
                . "join fhs_eav_attribute_option io on io.attribute_id = ie.attribute_id and io.option_id = i.value "
                . "join fhs_eav_attribute_option_value iov_0 on iov_0.option_id = io.option_id and iov_0.store_id = 0 "
                . "left join fhs_eav_attribute_option_value iov on iov.option_id = io.option_id and iov.store_id = 1 "
                . "where pe.entity_id = :product_id) "
                . "union "
                . "( "
                . "select 2 as type, v.attribute_id, ve.attribute_code, ve.frontend_label, v.value, eaa.sort_order "
                . "from fhs_catalog_product_entity pe "
                . "join fhs_eav_attribute_set es on es.entity_type_id = pe.entity_type_id and es.attribute_set_id = pe.attribute_set_id "
                . "join fhs_eav_attribute_group eag on eag.attribute_set_id = es.attribute_set_id and eag.attribute_group_name in ('Book Attributes', 'Other Product Attributes') "
                . "join fhs_eav_entity_attribute eaa on eaa.entity_type_id = es.entity_type_id and eaa.attribute_set_id = es.attribute_set_id and eaa.attribute_group_id = eag.attribute_group_id "
                . "join fhs_catalog_product_entity_varchar v on v.entity_id = pe.entity_id and v.attribute_id = eaa.attribute_id and v.value is not null "
                . "join fhs_eav_attribute ve on ve.attribute_id = v.attribute_id "
                . "where pe.entity_id = :product_id "
                . ")) t order by t.type, t.sort_order ";
        $resource = \Mage::getSingleton('core/resource');

        $read = $resource->getConnection('core_read');
        $queryBinding = array(
            "product_id" => $productId
        );
        $attributes = $read->fetchAll($book_attribute_sql, $queryBinding);
        foreach ($attributes as $attribute)
        {
            $result_temp[$attribute["attribute_code"]] = $attribute["value"];
        }
        if ($result_temp["supplier"])
        {
            $supplier_data = \Mage::helper('fahasa_catalog')->getDataSupplier($result_temp["supplier"]);
            if ($supplier_data)
            {
                $result_temp["supplier"] = $supplier_data['name'];
            }
        }
        unset($result_temp["supplier_list"]);
        $result = array();
        $result[] = array(
            "name" => "sku",
            "value" => $sku
        );
        foreach ($result_temp as $key => $value)
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
    private function getProductBundled($product) {
        $bundled_items = array();
        $optionCollection = $product->getTypeInstance()->getOptionsCollection();
        $selectionCollection = $product->getTypeInstance()->getSelectionsCollection($product->getTypeInstance()->getOptionsIds());
        $options = $optionCollection->appendSelections($selectionCollection);
        $i = 0;
        
        $bundlePrice = 0;
        $bundleSpecialPrice = 0;
        
        foreach ($options as $option) {
            $option->setSelections($option->getSelections());
            $bundled_items[$i] = $option->getdata();
            $_selections = $option->getSelections();
            $bundled_items[$i]["selections"] = array();
            
            // child item in selection if null: child item out_of_stock 
            if($_selections == null){
                return array();
            }
            
            $j = 0;
            foreach ($_selections as $selection) {
                $bundled_items[$i]["selections"][$j]["visibility"] = $selection->visibility;
                $bundled_items[$i]["selections"][$j]["entity_id"] = $selection->entity_id;
                $bundled_items[$i]["selections"][$j]["type_id"] = $selection->type_id;
                $bundled_items[$i]["selections"][$j]["sku"] = $selection->sku;
                $bundled_items[$i]["selections"][$j]["has_options"] = $selection->has_options;
                $bundled_items[$i]["selections"][$j]["required_options"] = $selection->required_options;
                $bundled_items[$i]["selections"][$j]["created_at"] = $selection->created_at;
                $bundled_items[$i]["selections"][$j]["updated_at"] = $selection->updated_at;
                $bundled_items[$i]["selections"][$j]["selection_id"] = $selection->selection_id;
                $bundled_items[$i]["selections"][$j]["option_id"] = $selection->option_id;
                $bundled_items[$i]["selections"][$j]["position"] = $selection->position;
                $bundled_items[$i]["selections"][$j]["is_default"] = $selection->is_default;
                $bundled_items[$i]["selections"][$j]["selection_price_type"] = $selection->selection_price_type;
                $bundled_items[$i]["selections"][$j]["selection_price_value"] = $selection->selection_price_value;
                $bundled_items[$i]["selections"][$j]["selection_qty"] = $selection->selection_qty;
                $bundled_items[$i]["selections"][$j]["selection_can_change_qty"] = $selection->selection_can_change_qty;
                $bundled_items[$i]["selections"][$j]["name"] = $selection->name;
                $bundled_items[$i]["selections"][$j]["small_image"] = \Mage::helper('catalog/image')->init($selection, 'small_image')->resize(400, 400)->__toString();
                $bundled_items[$i]["selections"][$j]["thumbnail"] = \Mage::helper('catalog/image')->init($selection, 'small_image')->resize(400, 400)->__toString();
                $bundled_items[$i]["selections"][$j]["image_label"] = $selection->image_label;
                $bundled_items[$i]["selections"][$j]["small_image_label"] = $selection->small_image_label;
                $bundled_items[$i]["selections"][$j]["thumbnail_label"] = $selection->thumbnail_label;
                $bundled_items[$i]["selections"][$j]["msrp_enabled"] = $selection->msrp_enabled;
                $bundled_items[$i]["selections"][$j]["msrp_display_actual_price_type"] = $selection->msrp_display_actual_price_type;
                $bundled_items[$i]["selections"][$j]["author"] = $selection->author;
                $bundled_items[$i]["selections"][$j]["msrp"] = $selection->msrp;
                $bundled_items[$i]["selections"][$j]["news_from_date"] = $selection->news_from_date;
                $bundled_items[$i]["selections"][$j]["news_to_date"] = $selection->news_to_date;
                $bundled_items[$i]["selections"][$j]["status"] = $selection->status;
                $bundled_items[$i]["selections"][$j]["tax_class_id"] = $selection->tax_class_id;
                $bundled_items[$i]["selections"][$j]["is_in_stock"] = $selection->is_in_stock;
                $bundled_items[$i]["selections"][$j]["is_salable"] = $selection->is_salable;
                $bundled_items[$i]["selections"][$j]["store_id"] = $selection->store_id;
                $bundled_items[$i]["selections"][$j]["group_price_changed"] = $selection->group_price_changed;
                $bundled_items[$i]["selections"][$j]["image"] = \Mage::helper('catalog/image')->init($selection, 'small_image')->resize(400, 400)->__toString();
                $bundled_items[$i]["selections"][$j]["price"] = $selection->price;
                $bundled_items[$i]["selections"][$j]["final_price"] = $product->getPriceModel()->getSelectionPreFinalPrice($product, $selection);
                $bundled_items[$i]["selections"][$j]["discount_percent"] = $product->discount_percent;
                
                //set for web
		$bundled_items[$i]["selections"][$j]["url"] = Mage::getBaseUrl() . $product->url_path;
                
                $j = $j + 1;
                $bundlePrice += $selection->price * $selection->selection_qty;
                $bundleSpecialPrice += $product->getPriceModel()->getSelectionPreFinalPrice($product, $selection);
            }
            $i = $i + 1;
        }
        
        $product->setPrice($bundlePrice);
        $product->setFinalPrice($bundleSpecialPrice);

        return $bundled_items;
    }
    
    public function getFHSRatingAverages($productId) {
        $fhsRating = array();
        $totalComment = array();
        $totalComment["1"] = 0;
        $totalComment["2"] = 0;
        $totalComment["3"] = 0;
        $totalComment["4"] = 0;
        $totalComment["5"] = 0;
        $reviewCollection = \Mage::getModel('review/review')->getCollection()                                
                ->addStatusFilter(\Mage_Review_Model_Review::STATUS_APPROVED)
                ->addEntityFilter('product', $productId);
        $reviewCollection->getSelect()
                         ->join(array('rate' => 'fhs_rating_option_vote'), 'main_table.review_id=rate.review_id', array('rating' => 'rate.value', 'ratingPercent' => 'rate.percent'));       
        $reviewCount = 0;
        $percentSum = 0;
        foreach($reviewCollection as $review){
            $totalComment[$review['rating']]++;     
            $reviewCount++;
            $percentSum += (int)$review['ratingPercent'];
        }
        $fhsRating['total_star'] = $totalComment;    
        $fhsRating['reviews_count_fhs'] = null;
        $fhsRating['rating_summary_fhs'] = null;
        if($reviewCount > 0){
            $ratingP = round($percentSum/$reviewCount);
            $fhsRating['reviews_count_fhs'] = $reviewCount;
            $fhsRating['rating_summary_fhs'] = $ratingP == 0 ? null : round($percentSum/$reviewCount);        
        }                
        return $fhsRating;
    }
    
    public function getAmazonReviewCountDetail($sku) {        
        $summaryDataAma = \Mage::getModel('amazonrating/amazonrating')->load($sku);
        $result = array();        
        $result['reviews_count_ama'] = $summaryDataAma->getData('numericScore');
        $result['rating_summary_ama'] = self::convertAmazonStarToNumericRating($summaryDataAma->getData('cssStarRating'));
        return $result;
    }
    
    
    public function getListComment($productId, $page, $pageSize) {
        if (!isset($page)) {
            $page = Variable::DEFAULT_PAGE;
        }
        if (!isset($pageSize)) {
            $pageSize = Variable::DEFAULT_PAGE_SIZE;
        }
        $list_comment = \Mage::getModel('review/review')
                ->getCollection()
                //->addStoreFilter(\Mage::app()->getStore()->getId())
                ->addEntityFilter('product', $productId)
                ->addStatusFilter(\Mage_Review_Model_Review::STATUS_APPROVED);

        $count_list_comment = $list_comment;
        if ($count_list_comment->getSize() <= (($page-1) * $pageSize)) {
            return null;
        } else {
            $list_comment->setPageSize($pageSize)
                    ->setCurPage($page)
                    ->setDateOrder()->addRateVotes();
        }
        $result = array();
        $j = 0;

        foreach ($list_comment AS $review) {
            $votes = $review->getRatingVotes();
            $total = 0;
            foreach ($votes AS $vote) {
                $total += $vote->getPercent();
            }
            $result[$j]['rating'] = $total;
            $result[$j]['title'] = $review->title;
            $result[$j]['detail'] = $review->detail;
            $result[$j]['nickname'] = $review->nickname;
            $result[$j]['created_at'] = $review->created_at;
            $j = $j + 1;
        }
        return $result;
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
    
    public function getProductLinks($productId){
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
	$query = "SELECT`type` as 'key', label as 'value', link_url as 'url' FROM fhs_internal_product_linking where product_id = " . $productId;
	return $readConnection->fetchAll($query);
    }
}
?>
