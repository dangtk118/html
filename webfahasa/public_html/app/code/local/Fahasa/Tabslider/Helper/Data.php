<?php

class Fahasa_Tabslider_Helper_Data extends Mage_Core_Helper_Abstract {

    const CONTENT_BASED = 'content_based';
    const COLLABORATIVE_FILTERING = 'collaborative_filtering';
    const SAME_AUTHOR = 'same_author';
    const MAX_ITEM = 61;

    public function getProductIdArray($productId,$seriesStr = '') {
	$product_helper = Mage::helper('fahasa_catalog/product');
	$productId = $productId;
	$seriesStr = $seriesStr;
	
        $sqlParams = array(
            "id1" => $productId
        );

        $sqlQuery = "SELECT relatedIds.id2 AS id, relatedIds.score, relatedIds.type, si.qty, si.is_in_stock, sr.value FROM (
	(SELECT id2, type, score FROM fahasa_product_recommendation
	WHERE id1 = :id1 AND type = 0 ORDER BY score DESC LIMIT 2000)
	UNION ALL
	(SELECT id2, type, score FROM fahasa_product_recommendation
	WHERE id1 = :id1 AND type = 1 ORDER BY score DESC LIMIT 2000)
        UNION ALL        
        (SELECT id2, type, score FROM fahasa_product_recommendation
	WHERE id1 = :id1 AND type = 2 ORDER BY score DESC LIMIT 2000)) relatedIds
JOIN fhs_cataloginventory_stock_item si ON si.product_id = id2
JOIN fhs_catalog_product_entity_int sr ON sr.attribute_id = 155 and sr.entity_id = id2
JOIN fhs_catalog_product_entity_int status ON status.attribute_id = 96 and status.entity_id = id2
WHERE (status.value = 1) AND ((si.is_in_stock = 1 AND si.qty > 0) OR (sr.value = 1))
ORDER BY relatedIds.type, relatedIds.score DESC";

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $result = $read->fetchAll($sqlQuery, $sqlParams);

        $idList = array(
            self::CONTENT_BASED => array(),
            self::COLLABORATIVE_FILTERING => array(),
            self::SAME_AUTHOR => array()
        );
        $top6 = array();
        foreach ($result as $r) {
            if ($r['type'] == '0' && count($idList[self::CONTENT_BASED]) < self::MAX_ITEM) {
                array_push($idList[self::CONTENT_BASED], $r['id']);
                if (count($top6) < 6) {
                    array_push($top6, $r['id']);
                }
            } else if ($r['type'] == '1' && count($idList[self::COLLABORATIVE_FILTERING]) < self::MAX_ITEM) {
                if (!in_array($r['id'], $top6)) {
                    array_push($idList[self::COLLABORATIVE_FILTERING], $r['id']);
                }
            } else if ($r['type'] == '2' && count($idList[self::SAME_AUTHOR]) < self::MAX_ITEM) {
                array_push($idList[self::SAME_AUTHOR], $r['id']);
            }
        }

        // If the list is too short, we merge results to the first list
        if (count($idList[self::COLLABORATIVE_FILTERING]) < 4) {
            $idList[self::CONTENT_BASED] = array_merge(
                    $idList[self::COLLABORATIVE_FILTERING], $idList[self::CONTENT_BASED]);
            $idList[self::COLLABORATIVE_FILTERING] = array();
        }
        $idList[self::CONTENT_BASED] = array_unique($idList[self::CONTENT_BASED]);

        $cbs = '';
        if (count($idList[self::CONTENT_BASED]) > 0) {
            # Trung: in case of Balo, add a seeAllLink button to direct to /balo page
            # Hardcode for now, generic logic will be implemented in the future
            $product = Mage::registry('product');
            if ($product == null) {
                $product = Mage::getModel('catalog/product')->load($productId);
            }
            $prodCat3Id = $product->getData('category_1_id');
            $prodCat2Id = $product->getData('category_mid_id');
            $prodCat1Id = $product->getData('category_main_id');
            $seeAllLink = '';
            if ($prodCat3Id == 266) {
                $seeAllLink = ',"seeAllLink":"balo?fhs_campaign=SEEALL_PRODREC"';
            }
            else if ($prodCat3Id == 6309) {
                $seeAllLink = ',"seeAllLink":"tap?fhs_campaign=SEEALL_PRODREC"';
            }
            else if ($prodCat2Id == 279) {
                $seeAllLink = ',"seeAllLink":"but?fhs_campaign=SEEALL_PRODREC"';
            }
            else if ($prodCat3Id == 268) {
                $seeAllLink = ',"seeAllLink":"hop-but?fhs_campaign=SEEALL_PRODREC"';
            }
            else if ($prodCat2Id == 6365) {
                $seeAllLink = ',"seeAllLink":"board-game?fhs_campaign=SEEALL_PRODREC"';
            }
            else if ($prodCat1Id == 5991) {
                $seeAllLink = ',"seeAllLink":"cua-tiem-giac-mo-do-choi?fhs_campaign=SEEALL_PRODREC"';
            }
            else if ($prodCat2Id == 11) {
                $seeAllLink = ',"seeAllLink":"sach-kinh-te-mua-manh-giam-bao?fhs_campaign=SEEALL_PRODREC"';
            }

            $cbs = '{"cbs": {'
                    . '       "label": "' . $this->__("Fahasa recommends") . '",'
                    . '       "label_mobile": "' . $this->__("Fahasa recommends") . '",'
                    . '       "list": "' . implode(',', $idList[self::CONTENT_BASED]) . '",'
                    . '       "fhsCampaign": "?fhs_campaign='.$this->getRelatedProduct2CampaignStr().'"'
                    . $seeAllLink
                    . '}}';
        }

        // Collaborative filtering
        $cfs = '';
        // Frequently bought together
        if (count($idList[self::COLLABORATIVE_FILTERING]) > 0) {
            $cfs = '{"cfs": {'
                    . '       "label": "' . $this->__("Frequently bought together") . '",'
                    . '       "label_mobile": "' . $this->__("Frequently bought together") . '",'
                    . '       "fhsCampaign": "?fhs_campaign='.$this->getRelatedProduct2CampaignStr().'",'
                    . '       "list": "' . implode(',', $idList[self::COLLABORATIVE_FILTERING]) . '"'
                    . '}}';
        }

        // Same author
        // By author
        $sas = '';
        if (count($idList[self::SAME_AUTHOR]) > 0) {
            $sas = '{"sas": {'
                    . '       "label": "' . $this->__("By author") . '",'
                    . '       "label_mobile": "' . $this->__("By author") . '",'
                    . '       "fhsCampaign": "?fhs_campaign='.$this->getRelatedProduct2CampaignStr().'",'
                    . '       "list": "' . implode(',', $idList[self::SAME_AUTHOR]) . '"'
                    . '}}';
        }


        $dataStr = '';
        if ($seriesStr != ''){
            $dataStr .= $seriesStr;
        }
        if ($cbs != '') {
            $dataStr .= ((empty($dataStr) ? '' : ',') . $cbs);
        }
//        if ($seriesStr != ''){
//            $dataStr .= ((empty($dataStr) ? '' : ',') . $seriesStr);
//        }
        if ($cfs != '') {
            $dataStr .= ((empty($dataStr) ? '' : ',') . $cfs);
        }
        if ($sas != '') {
            $dataStr .= ((empty($dataStr) ? '' : ',') . $sas);
        }
        
        return '[' . $dataStr . ']';
    }

    public function getBlockData($productId) {
	$product_helper = Mage::helper('fahasa_catalog/product');
	$productId = $product_helper->cleanBug($productId);
	
        $data = $this->getProductIdArray($productId);
        $parsedData = json_decode($data, true);
        $result = [];
        foreach ($parsedData as $key => $value) {
            foreach ($value as $k => $v) {
                $block = Mage::app()->getLayout()->createBlock('tabslider/tabslider1');
                $collection = $block->getTabSliderProductCollection(true, $v, 0);
                array_push($result, array(
                     "label" => $v["label_mobile"],
                    "data" => $collection,
                    "seeAllLink" => $v["seeAllLink"],
                        ));
            }
        }
        return $result;
    }
    
    public function getProducts($products_limit, $sort_by, $max_ck, $min_ck, $category_id, $block_type, $attribute_code, $attribute_value, $attribute_data, $product_ids_str, $product_id, $exclude_catId,
            $is_tab_slider = true, $show_ui_progress, $backup_cat_id = 0, $backup_order_by = 'num_orders',$series_id, $page = null, $show_buy_now = false){
        /// constants
        $MAX_PRODUCTS_LIMIT = 60;

	$product_helper = Mage::helper('fahasa_catalog/product');
	$products_limit = $product_helper->cleanBug($products_limit);
	$sort_by = $product_helper->cleanBug($sort_by);
	$max_ck = $product_helper->cleanBug($max_ck);
	$min_ck = $product_helper->cleanBug($min_ck);
	$category_id = $product_helper->cleanBug($category_id);
	$block_type = $product_helper->cleanBug($block_type);
	$attribute_code = $product_helper->cleanBug($attribute_code);
	$attribute_value = $product_helper->cleanBug($attribute_value);
	$attribute_data = $product_helper->cleanBug($attribute_data);
	$product_ids_str = $product_helper->cleanBug($product_ids_str);
	$product_id = $product_helper->cleanBug($product_id);
	$exclude_catId = $product_helper->cleanBug($exclude_catId);
	$is_tab_slider = $product_helper->cleanBug($is_tab_slider);
	$show_ui_progress = $product_helper->cleanBug($show_ui_progress);
	$backup_cat_id = $product_helper->cleanBug($backup_cat_id);
	$backup_order_by = $product_helper->cleanBug($backup_order_by);
	$series_id = $product_helper->cleanBug($series_id);
	$show_buy_now = $product_helper->cleanBug($show_buy_now);
		
        $products_limit = isset($products_limit)? (int)$products_limit: 0;
        $products_limit = $products_limit>$MAX_PRODUCTS_LIMIT ?  $MAX_PRODUCTS_LIMIT: $products_limit;
        
        $data = array(
            "sort_by" => $sort_by,
            "max_ck" => $max_ck,
            "min_ck" => $min_ck,
            "category_id" => $category_id,
            "block_type" => $block_type,
            "attribute_code" => $attribute_code,
            "attribute_value" => $attribute_value,
            "attribute_data" => $attribute_data,
            "list" => $product_ids_str,
            "product_id" => $product_id,
            "exclude_catId" => $exclude_catId,
            "is_tab_slider" => $is_tab_slider,
            "series_id" => $series_id,
        );
        
        $block_tabslider1 = Mage::app()->getLayout()->createBlock('tabslider/tabslider1');
        $block_tabslider1->setNumberOfDisplayItem($products_limit);
        /// $type parameter of getTabSliderProductCollection() is not used
        $products = $block_tabslider1->getTabSliderProductCollection(true, $data, 0);
        
        $returnProducts = array();
        
        $helperDiscountLabel = Mage::helper('discountlabel');
        $helperCatalogImage = $block_tabslider1->helper('catalog/image');
        $helperCatalogOutput = $block_tabslider1->helper('catalog/output');
        foreach ($products as $product) {

            if($show_buy_now){
		$cart_info = $product_helper->getAddtoCartInfo(null, $product);
	    }
            $returnProducts[] = array(
                "id" => $product->getId(),
                "discount_label_html" => $helperDiscountLabel->handleDisplayDiscountLabel($product, true, false),
                "product_url" => $product->getProductUrl(),
                "image_label" => $block_tabslider1->getImageLabel($product, 'small_image'),
                "image_src" => (string) $helperCatalogImage->init($product, 'small_image')->resize(400, 400),
                "name_a_title" => $block_tabslider1->stripTags($product->getName(), null, true),
                "name_a_label" => $helperCatalogOutput->productAttribute($product, $product->getName(), 'name'),
                "price_html" => $helperDiscountLabel->displayProductPriceOnWeb($product),
                "rating_html" => $block_tabslider1->getFahasaSummaryHtml($product),
                "price" => $product->getData('price'),
                "final_price" => $product->getFinalPrice(),
                "product_id" => $product->getId(),
                "discount_percent" => $helperDiscountLabel->handleDiscountPercent($product),
                "bar_html" => $show_ui_progress == "true" && $product_ids_str ? $this->getBarHtml($product->getId()) : "",
		"episode" => $product->getEpisode(),
                "type_id" => $product->getTypeId(),
                "soon_release" => (int) $product->getSoonRelease(),
                "stock_available" => $product->stockItem->is_in_stock > 0 ? "in_stock" : "out_of_stock",
                "add_to_cart_info" => !empty($cart_info)?$cart_info:null
            );
           
        }
	
	//check and get more product fill minimum list
        $product_ids = explode(',', $product_ids_str);
	$products_minimum = sizeof($product_ids);
	$limit = $this->get_prod_count();
	if($products_minimum >= $limit){
	    $products_minimum = $limit;
	}
	$products_size = sizeof($products);
	if($products_size < $products_minimum && $products_minimum > 0 && $backup_cat_id){
	    $limit = $products_minimum - $products_size;
	    $data = array(
		"sort_by" => $backup_order_by,
		"max_ck" => $max_ck,
		"min_ck" => $min_ck,
		"category_id" => $backup_cat_id,
		"block_type" => "fill",
		"attribute_code" => "",
		"attribute_value" => "",
		"attribute_data" => "",
		"list" => '',
		"product_id" => "",
		"exclude_catId" => "",
		"is_tab_slider" => $is_tab_slider,
		"exclude_prod_ids" => $product_ids_str,
		"limit" => $limit,
	    );
	    $products_fill = $block_tabslider1->getTabSliderProductCollection(true, $data, 0);
	    
	    foreach ($products_fill as $product) {
		$returnProducts[] = array(
		    "id" => $product->getId(),
		    "discount_label_html" => $helperDiscountLabel->handleDisplayDiscountLabel($product, true, false),
		    "product_url" => $product->getProductUrl(),
		    "image_label" => $block_tabslider1->getImageLabel($product, 'small_image'),
		    "image_src" => (string) $helperCatalogImage->init($product, 'small_image')->resize(400, 400),
		    "name_a_title" => $block_tabslider1->stripTags($product->getName(), null, true),
		    "name_a_label" => $helperCatalogOutput->productAttribute($product, $product->getName(), 'name'),
		    "price_html" => $helperDiscountLabel->displayProductPriceOnWeb($product),
		    "rating_html" => $block_tabslider1->getFahasaSummaryHtml($product),
		    "price" => $product->getData('price'),
		    "final_price" => $product->getFinalPrice(),
		    "product_id" => $product->getId(),
		    "discount_percent" => $helperDiscountLabel->handleDiscountPercent($product),
		    "bar_html" => $show_ui_progress == "true" && $product_ids_str ? $this->getBarHtml($product->getId()) : "",
		    "episode" => $product->getEpisode()
		);
	    }
	}
	
        return $returnProducts;
    }
    public function getDataBlockByListId($listId,$blockName){
	$product_helper = Mage::helper('fahasa_catalog/product');
        $dataParams = array(
            "identifier" => $product_helper->cleanBug($blockName)
                
        );
        $sqlSelect = "select * from fhs_cms_block where identifier =  :identifier";
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $result = $read->fetchAll($sqlSelect, $dataParams);
        if(count($result) > 0){
            $dataJson = $result[0]['content']; 
        }
        return $dataJson;
    }
    
    public function getProductPageSlider($arrayProductId,$products_limit,$category_id,$mobile_grid_page,$type_name_order = "num_orders",$show_ui_progress){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$arrayProductId = $product_helper->cleanBug($arrayProductId);
	$products_limit = $product_helper->cleanBug($products_limit);
	$category_id = $product_helper->cleanBug($category_id);
	$mobile_grid_page = $product_helper->cleanBug($mobile_grid_page);
	$type_name_order = $product_helper->cleanBug($type_name_order);
	$show_ui_progress = $product_helper->cleanBug($show_ui_progress);
	
        $MAX_PRODUCTS_LIMIT = 60;
        //$sort_by = "discount";// mac dinh
        
        $products_limit = isset($products_limit)? (int)$products_limit: 24;
        $products_limit = $products_limit>$MAX_PRODUCTS_LIMIT ?  $MAX_PRODUCTS_LIMIT: $products_limit;
        $category = ($category_id == 'all')|| ($category_id == null )? null : $category_id; 
        
        $data = array(
            "list" => $arrayProductId,
            "category_id" => $category,
            "sort_by" => $type_name_order,
        );
        

        $block_tabslider1 = Mage::app()->getLayout()->createBlock('tabslider/tabslider1');
        //$block_tabslider1->setNumberOfDisplayItem($products_limit);
        
        $returnProducts = array();
        $helperDiscountLabel = Mage::helper('discountlabel');
        $helperCatalogImage = $block_tabslider1->helper('catalog/image');
        $helperCatalogOutput = $block_tabslider1->helper('catalog/output');
        
        $products = $block_tabslider1->getTabSliderProductCollection(true, $data, 0, $products_limit );
        $listProductId = array(); // lay nhung ID cua product;
        
        // lay product thuoc tat ca category
        foreach ($products as $product) {
            $price = $product->getPrice();
            $specialprice = $product->getFinalPrice();

            $returnProducts[] = array(
                "product_id" => $product->getId(),
                "discount" => $product->getDiscountPercent(),
                "category_mid" => $product->getCategoryMid(),
                "category_mid_id" => $product->getCategoryMidId(),
                "display_final_price" => $product->getFinalPrice(),
                "display_price" => $product->getPrice(),
                "product_name" => $product->getName(),
                "product_url" => $product->getProductUrl(),
                "discount_label_html" => $helperDiscountLabel->handleDisplayDiscountLabel($product, true, false),
                "image_label" => $block_tabslider1->getImageLabel($product, 'small_image'),
                "image_src" => (string) $helperCatalogImage->init($product, 'small_image')->resize(400, 400),
                "name_a_title" => $block_tabslider1->stripTags($product->getName(), null, true),
                "name_a_label" => $helperCatalogOutput->productAttribute($product, $product->getName(), 'name'),
                "price_html" => $mobile_grid_page ? $helperDiscountLabel->displayProductPriceMobile($product, $price, $specialprice) : $helperDiscountLabel->displayProductPriceOnWeb($product),
                "rating_html" => $mobile_grid_page ? $block_tabslider1->getFahasaSummaryHtml1($product) : $block_tabslider1->getFahasaSummaryHtml($product),
                "bar_html" => $show_ui_progress ? $this->getBarHtml($product->getId()) : "",
		"episode" => $product->getEpisode()
            );

            array_push($listProductId, $product->getId());
        }
        $result = [
            "returnProducts" => $returnProducts,
            "listProductId" => $listProductId
           
        ];
        return $result;
    }
    
     /*
     * product nao co data tu redis => show 
     * khong co data => mac dinh la : gia tri 0;
     */
    public function getBarHtml($product) {
        $text = "Đã bán 0";
        $string .= "<div class='progress position-bar-gridslider color-progress-grid'>" .
                "<div class='progress-bar color-bar-grid " . $product . "-bar' role='progressbar' aria-valuenow='0' aria-valuemin='0' aria-valuemax='100'></div>" .
                "<div class='text-progress-bar'><span class='" . $product . "-bar'>" . $text . "</span></div>" .
                "</div>";
        return $string;
    }
    
    public function getRecommendedProducts($_product_id){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$_product_id = $product_helper->cleanBug($_product_id);
	
	$result = [];
	try{
	    $data_str = $this->getProductIdArray($_product_id);
	    if(!empty($data_str)){
		$data = json_decode($data_str);
		if(!empty($data[0])){
		    $list = reset($data[0])->list;
		    $product_ids = explode(',', $list);
		    $products = Mage::helper('fahasa_catalog/product')->getProductByProductIdsWithSortBy($product_ids, '', 1, 5);
		    foreach($products as $product){
			//get rating
			$rating_html = "";
			$awsAvgScore_data = $product->getAwsAvgScore();
			$grAvgScore_data = $product->getGrAvgScore();
			$awsRatings_data = $product->getAwsRatings();
			$grRatings_data = $product->getGrRatings();
			$rating_count_average = 0;
			$ratings = $product->getFhsReviewsCount()?$product->getFhsReviewsCount():0;
			$fhsAvgScore = $product->getFhsRatingSummary()?$product->getFhsRatingSummary():0;
			if(!empty($awsAvgScore_date) || !empty($grAvgScore_data)){
			    $amzAvgScore = 0;
			    $grAvgScore = 0;
			    if($awsAvgScore_data){
				$amzAvgScore = ($awsAvgScore_date/5)*100;
			    }
			    if($grAvgScore_data){
				$grAvgScore = ($grAvgScore_data/5)*100;
			    }
			    if($awsRatings_data){
				$ratings += $awsRatings_data;
			    }
			    if($grRatings_data){
				$ratings += $grRatings_data;
			    }
			    $rating_count_average = max($fhsAvgScore, $amzAvgScore, $grAvgScore);
			}
			else{
			    $rating_count_average = $fhsAvgScore;
			}
			if ($rating_count_average > 0){
			    // remove class ratings fhs-no-mobile-block
			    $rating_html = "<div class='ratings'>
				    <div class='rating-box'>
					<div class='rating' style='width:".($rating_count_average>100?100:$rating_count_average)."%'></div>
				    </div>
				<div class='amount'>(".$ratings.")</div>
			    </div>";
			}

			$helperDiscountLabel = Mage::helper('discountlabel');
			$helperCatalogImage = Mage::helper('catalog/image');
			$result[$product->getId()] = array(
			    'product_id'=>$product->getId(),
			    'product_name'=>$product->getName(),
			    'product_finalprice'=> number_format($product->getData('final_price'), 0, ",", "."),
			    'product_price'=> number_format($product->getData('price'), 0, ",", "."),
			    "rating_html" => $rating_html,
			    "soon_release" => $product->getSoonRelease(),
			    "product_url" => $product->getProductUrl(),
			    "image_src" => (string)$helperCatalogImage->init($product, 'small_image')->resize(400, 400),
			    "discount" => $helperDiscountLabel->handleDiscountPercent($product),
			    "discount_label_html" => $helperDiscountLabel->handleDisplayDiscountLabel($product, true, false),
			    "price_html" => $helperDiscountLabel->displayProductPriceOnWeb($product),
			    'final_price' => $product->getData('final_price'),
			    'price' => $product->getData('price') ? $product->getData('price') : 0,
			    "episode" => $product->getEpisode()
			);
		    }
		}
	    }
	}catch (Exception $ex) {}
	return $result;
    }
    function get_prod_count($isMobile) 
    {         
        if(isset($_GET['limit'])){
           $prodcount = intval($_GET['limit']);
        }  else {
            if($isMobile){
                $prodcount = 8;
            }else{
                $prodcount = 24;
            }
        }
        return $prodcount;
    }
    public function getRelatedProduct2CampaignStr() {
        return "RELATED_PRODUCT_2";
    }
    
   public function execPostRequest($url, $data, $log_file_name = 'recommended_api') {
        $curl = curl_init($url);
	curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );

//        curl_setopt($curl, CURLOPT_TIMEOUT, 1);
//        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 4);
        
        // request --- 
        $Response = curl_exec($curl);
	
        if(curl_errno($curl)){
            $error_msg = curl_error($curl);
            Mage::log("ERROR curl exec " . $url . ", " . print_r($data, true) . " - message: " . print_r($error_msg, true)
                    . " - response: " . print_r($Response, true), null, $log_file_name.'.log');
        }
	
	$result = json_decode($Response, true);
	
        //close connection
        curl_close($curl);
        return $result;
    }
    
    public function getArrayTabSlider3($prodCat2Id,$prodCatMainId) {
        $arraySplit = array(
            array(
                "identify" => "mangaseries",
                "title" => "Fahasa Giới Thiệu",
                "only_type_id" => "0"
            ),
//            array( 
//                "identify" => "seriesMangaComic",
//                "title" => "Series Bộ",
//                "only_type_id" => "3"
//            ),
        );

        if ($prodCat2Id == 15 || $prodCatMainId == 86) {
            // add tab Các Bộ SGK
            $array2 = array(
                "identify" => "seriesSGK",
                "title" => "Các Bộ SGK",
                "only_type_id" => "4"
            );
            array_push($arraySplit, $array2);
        }

        if ($prodCat2Id == 15) {
            $array3 = array(
                "identify" => "vppdchs",
                "title" => "VPP - Dụng Cụ Học Sinh",
                "only_type_id" => "-1"
            );
            array_push($arraySplit, $array3);
        }
        // add boughttogheter index cuoi cung :
        $array4 = array(
            "identify" => "boughttogheter",
            "title" => "Sản phẩm cùng mua",
            "only_type_id" => "1"
        );
        array_push($arraySplit, $array4);

        return $arraySplit;
    }
}
