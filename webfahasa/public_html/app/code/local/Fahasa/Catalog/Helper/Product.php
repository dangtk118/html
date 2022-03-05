<?php

class Fahasa_Catalog_Helper_Product extends Mage_Core_Helper_Abstract
{
    const ERR_MISSING_VALUE = "ERR_MISSING_VALUE";
    const ERR_DETAIL_MINIUM_100_CHARACTERS = "ERR_DETAIL_MINIUM_100_CHARACTERS";
    const ERR_STAR_1_TO_5 = "ERR_STAR_1_TO_5";
    const EVENT_CART_LIMIT = 2;
    const COUPON_BG_SVG = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" class="coupon_bg"><g fill="none" fill-rule="evenodd"><g><g><g><g transform="translate(-544 -3050) translate(80 2072) translate(0 930) translate(464 48)"><path id="Frame_voucher_Web" d="M 110 144 h -98 a 12 12 0 0 1 -12 -12 v -120 a 12 12 0 0 1 12 -12 H 110 a 12.02 12.02 0 0 0 12 11.971 a 12.02 12.02 0 0 0 12 -11.971 H 524 a 12 12 0 0 1 12 12 V 132 a 12 12 0 0 1 -12 12 H 134 v -0.03 a 12 12 0 0 0 -24 0 v 0 Z" transform="translate(0.5 0.5)" fill="#fff" stroke="rgba(0,0,0,0)" stroke-miterlimit="10" stroke-width="1"/></g></g></g></g></g></svg>';
    const CONFIGURABLE_TYPE = 'configurable';
    const BUNDLE_TYPE = 'bundle';
    
    public function calculateDiscount($old_price, $new_price) {
        $old_price = (float) $old_price;
        $new_price = (float) $new_price;
        
        try {
            $discount = (int) ((($old_price-$new_price) / $old_price) * 100);
            return $discount;
            /*
             *  SHOULD WE ALLOW THIS ?
             */
            //$discount = max(0, min($discount, 100));
        } catch (Exception $ex) {
            $discount = 0;
        }

        return $discount;
    }
    
    public function displayPrice($old_price, $new_price) {
        $symbol = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
        $prices = array(
            'old_price' => number_format($old_price, 0, ",", ".") . $symbol,
            'new_price' => number_format($new_price, 0, ",", ".") . $symbol
        );
        return $prices;
    }
    
    public function loadCatalog($category_id, $filters, $limit, $currentPage, $order, $is_series_type = false, $is_reload_bar = false){
	$category_id = $this->cleanBug($category_id);
	$filters = $this->cleanBug($filters);
	$order = $this->cleanBug($order);
	if(!is_numeric($limit)){$limit = 24;}
	if(!is_numeric($currentPage)){$currentPage = 1;}
	
	if(!empty($order)){
	    if($is_series_type){
		$order = $this->checkOrderBy('series', $order);
	    }else{
		$order = $this->checkOrderBy('product', $order);
	    }
	}
	$time = time();
//mage::log("-----CATALOG READY--------", null, 'catalog.log');
	if(sizeof($filters) > 0){
	    $filters_param = [];
	    if(!empty($filters['price'])){
		$filters_param['price'] = $filters['price'];
		unset($filters['price']);
	    }
	    ksort($filters);
	    foreach($filters as $param_key=>$value){
		$attibute_valus_array = explode('_', $value);
		sort($attibute_valus_array);
		$filters_param[$param_key] = implode("_",$attibute_valus_array);
	    }
	    $filters = $filters_param;
	}
//mage::log("-----1--------".(time() - $time), null, 'catalog.log');
        Mage::app()->setCurrentStore(1);
        Mage::app('default');
        Mage::getSingleton("core/session", array("name" => "frontend"));
        //$websiteId = Mage::app()->getWebsite()->getId();
//mage::log("-----2--------".(time() - $time), null, 'catalog.log');
	if (!Mage::registry('current_category')) {
	    $store_id = Mage::app()->getStore();
	    $category = Mage::getModel('catalog/category')->setStoreId($store_id)->load($category_id);
	    
	    //validate category
	    if(empty($category) || empty($category->getId())){
		$result = array();
		$result['status'] = 0;
		$result['parent_categories'] = [];
		$result['category'] = [];
		$result['children_categories'] = [];
		$result['attributes'] = [];
		$result['product_list'] = [];
		$result['total_products'] = 0;
		$result['message'] = 'Category id Invalid';
		return $result;
	    }
	    if($category->getId() == 2){
		if(!$is_series_type){
		    $category->setData('name', $this->__('All Category'));
		}else{
		    $category->setData('name', $this->__('All Series'));
		}
	    }
	    Mage::register('current_category', $category);
	    if (!Mage::registry('current_entity_key')) {
		Mage::register('current_entity_key', $category->getPath());
	    }
	}else{
	    $category = Mage::registry('current_category');
	    
	    //validate category
	    if(empty($category) || empty($category->getId())){
		$result = array();
		$result['status'] = 0;
		$result['parent_categories'] = [];
		$result['category'] = [];
		$result['children_categories'] = [];
		$result['attributes'] = [];
		$result['product_list'] = [];
		$result['total_products'] = 0;
		$result['message'] = 'Category id Invalid';
		return $result;
	    }
	    if($category->getId() == 2){
		if(!$is_series_type){
		    $category->setData('name', $this->__('All Category'));
		}else{
		    $category->setData('name', $this->__('All Series'));
		}
	    }
	}
//mage::log("-----3--------".(time() - $time), null, 'catalog.log');
        $current_category = $category;
        if(!$category->hasChildren()){
            $category = $category->getParentCategory();
        }
	
//mage::log("-----4--------".(time() - $time), null, 'catalog.log');
	//get from cache
	if(!$is_reload_bar){
	    $result = $this->getCache($category_id, $filters, $limit, $currentPage, $order, ($is_series_type?'series':'catalog'));
	    if($result){
//		if(!empty($result['product_list'])){
//		    $result['product_list'] = $this->refreshDynParam($result['product_list']);
//		}
		return $result;
	    }
	}
	
	$filters_cache = $filters;

	$layer = Mage::getModel("catalog/layer");
	$layer->setCurrentCategory($category);

//mage::log("-----6--------".(time() - $time), null, 'catalog.log');

        $min_price = 0;//floor($layer->getProductCollection()->getMinPrice());
        $max_price = 999999999;
	$price_range= array(
	    'min' => $min_price,
	    'max' => $max_price,
	    'price_range' => array(
		'min' => $min_price,
		'max' => $max_price
	    )
	);
	
	$applied_minprice = $min_price;
	$applied_maxprice = $max_price;
	if(!empty($filters['price'])){
	    $prices = explode(',',$filters['price']);
	    $applied_minprice = ($prices[0]>0)?$prices[0]:0;
	    $applied_maxprice = $prices[1];
	    
	    if(is_numeric($applied_minprice) && is_numeric($applied_maxprice)){
		if($min_price != $applied_minprice || $max_price !=$applied_maxprice){
		    $price_range['min'] = (int)$applied_minprice;
		    $price_range['max'] = (int)$applied_maxprice;
		}
	    }
	}
	
//	if(empty($filters['price'])){
//	    $filters['price'] = $min_price.",".$max_price;
//	}

	$series_filter_str = '';
	if($is_series_type){
	    $series_filter_str = "and e.type_id = 'series'";
	}
	
//category bar
	$catalog_bar_filter_key = [];
	$first_supplier_id = null;
	if(!empty($filters['supplier_list'])){
	    $catalog_bar_filter_key['supplier_list'] = $filters['supplier_list'];
	}
	
	$category_bar = $this->getCache($category_id, $catalog_bar_filter_key, 24, 1, 'created_at', ($is_series_type?'series_bar':'catalog_bar'));
	if(empty($category_bar)){
	    $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $attribute_filter_conditions = '';
	    $filter_price_str = '';

	    $attributes = $layer->getFilterableAttributes();
	    $attribute_filter_conditions_array = array();
	    foreach ($filters as $key=>$item) {
		if($key != 'supplier_list'){continue;}
		$attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $key);
		if(!$attribute->getAttributeId()){continue;}
		
		$item_array = "";
		foreach (explode('_', $item) as $sub_val) {
		    if(empty($first_supplier_id)){$first_supplier_id = $sub_val;}
		    if(!$item_array)
			$item_array = $sub_val;
		    else
			$item_array .= ",".$sub_val;
		}
		$attribute_filter_conditions_array[] = " JOIN fhs_catalog_product_index_eav attribute_idx_".$key." "
		    . " ON attribute_idx_".$key.".entity_id = e.entity_id AND attribute_idx_".$key.".attribute_id = "
		    . $attribute->getAttributeId() ." AND attribute_idx_".$key.".store_id = cat_index.store_id"
		    . " AND attribute_idx_".$key.".value IN (". $item_array .") ";
	    }

	    /// other attributes
	    $attribute_filter_conditions = implode(" ", $attribute_filter_conditions_array);

	    // price sql
//	    if($min_price != $price_range['min'] || $max_price !=$price_range['max']){
//		$filter_price_str = "JOIN fhs_catalog_product_index_price_store price_index on price_index.entity_id = e.entity_id and price_index.website_id = '1' and price_index.customer_group_id = '0' and price_index.store_id = '1' and (price_index.final_price BETWEEN ".$price_range['min']." and ".$price_range['max'].") ";
//	    }
	    
	    $exclude_attribute_codes = array();
	    if($current_category->getId() == 5991){
		$exclude_attribute_codes['genres'] = 'genres';
	    }
	    /* Category */
	    /// Parent Category
	    $parent_categories = array();
	    if(!$is_series_type){
		$parent_categories[] = array(
		    'id' => 2,
		    'name' => $this->__('All Category'),
		    'path' => "/all-category.html"
		);
	    }else{
		$parent_categories[] = array(
		    'id' => 2,
		    'name' => $this->__('All Series'),
		    'path' => "/all-series.html"
		);
	    }
	    foreach ($category->getParentCategories() as $parent) {
		if($parent->getId() == 5991){
		    $exclude_attribute_codes['genres'] = 'genres';
		}
		$parent_categories[] = array(
		    'id' => $parent->getId(),
		    'name' => $parent->getName(),
		    'path' => $parent->getUrl()
		);
	    }

	    /// Children Category
	    $children_cats = $category->getChildrenCategories();
	    $children_cats_ids = array();
	    foreach($children_cats as $cat){
		$children_cats_ids[] = $cat->getId();
	    }
	    
	
	    $children_categories = array(); 
	    if(!empty($children_cats)){ 

		$children_cat_query = "SELECT DISTINCT e.entity_id, cat_index.category_id 
		    FROM fhs_catalog_product_entity e 
		    JOIN fhs_catalog_category_product_index cat_index on cat_index.product_id=e.entity_id and cat_index.store_id=1 and cat_index.visibility IN(2, 4) and cat_index.category_id in (".implode(",", $children_cats_ids). ")"
		    .$filter_price_str
		    .$attribute_filter_conditions;

		$children_cat_counter_query = "SELECT r.category_id as 'id', count(r.category_id) as 'count' FROM (" . $children_cat_query."
			WHERE e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1 AND (e.f_thanh_ly is null or e.f_thanh_ly != '1') ".$series_filter_str." 
			) r
			group by r.category_id order by count desc;";

    //	    mage::log("-----children_cat_counter_query--------", null, 'buffet.log');
    //	    mage::log($children_cat_counter_query, null, 'buffet.log');
    //	    mage::log("----------------", null, 'buffet.log');

//mage::log("-----children_categories_result - time:".(time() - $time)."--------".$children_cat_counter_query, null, 'catalog.log');
		$children_categories_result = $connection->fetchAll($children_cat_counter_query);
//mage::log("-----children_categories_result STOP - time:".(time() - $time)."--------", null, 'catalog.log');
    //            if(!empty($children_categories_result)){
    //		
    //                arsort($children_categories_result);
    //            }
		if(!empty($children_categories_result)){
		    foreach($children_categories_result as $key=>$cat_count){
			foreach($children_cats as $cat){
			    if($cat->getId() == $cat_count['id']){
				$children_categories[] = array(
				    'id' => $cat->getId(),
				    'name' => trim($cat->getName()),
				    'path' => $cat->getUrl(),
				    'count' => $cat_count['count']
				);
			    }
			}
		    }
		}
	    }

	    /* Filter */
	    $attribute_query = "SELECT DISTINCT e.entity_id, attr.attribute_id, attr.value 
		FROM fhs_catalog_product_entity e
		JOIN fhs_catalog_category_product_index cat_index ON cat_index.product_id=e.entity_id AND cat_index.store_id='1' AND cat_index.visibility IN(2, 4) AND cat_index.category_id = :category_id ".$filter_price_str."".$attribute_filter_conditions."
		JOIN fhs_catalog_product_index_eav attr ON attr.entity_id = e.entity_id AND attr.store_id = '1'";

	    $all_attributes = array();
	    foreach ($attributes as $attribute) {
		$attribute_limit = '';
		if(in_array($attribute->getAttributeCode(), $exclude_attribute_codes)){
		    continue;
		}
		$result_filter = null;
		if($attribute->getAttributeCode() == 'supplier_list' && !empty($first_supplier_id)){
		    $supplier_option_id = $this->getAttributeOptionStore($attribute->getAttributeCode(), $first_supplier_id, $is_series_type);
		    if(!empty($supplier_option_id)){
			$result_filter [] = $supplier_option_id;
		    }
		}
		if(empty($result_filter)){
		    $select_filter_counter = "select attr.*, IF(opt_vn.value_id > 0, opt_vn.value, opt.value) AS `value` 
					    from ( 
						SELECT A.attribute_id, A.value as 'id' , count(A.value) as 'count'
						FROM (
						    ".$attribute_query." and attr.attribute_id = '".$attribute->getAttributeId()."'
						    WHERE e.f_visibility = '4' AND e.f_stock_status = '1' AND e.f_status = '1' AND (e.f_thanh_ly is null or e.f_thanh_ly != '1') ".$series_filter_str."
						) A 
						group by A.attribute_id, A.value
						order by `count` desc
						".$attribute_limit."
					    ) attr 
					    join fhs_eav_attribute_option_value opt on opt.option_id = attr.id and opt.store_id = '0'
					    left join fhs_eav_attribute_option_value opt_vn on opt_vn.option_id = opt.option_id and opt_vn.store_id = '1'
					    order by attr.`count` desc, opt.value;";

		    $result_filter_bindings = array(
			'category_id' => $current_category->getId()
		    );
	//	    mage::log("-----".$attr['param']."--------", null, 'buffet.log');
	//	    mage::log($select_filter_counter, null, 'buffet.log');
	//	    mage::log("----------------", null, 'buffet.log');

//mage::log("-----select_filter_counter START - time:".(time() - $time)."--------".$select_filter_counter, null, 'catalog.log');
		    $result_filter = $connection->fetchAll($select_filter_counter, $result_filter_bindings);
//mage::log("-----select_filter_counter STOP - time:".(time() - $time)."--------", null, 'catalog.log');
		}
		
		if(!empty($result_filter)){
		    $attr = array(
			'id' => $attribute->getAttributeId(),
			'code' => $attribute->getAttributeCode(),
			'label' => ucfirst($attribute->getStoreLabel()),
			'param' => $this->convertLabelToParam($attribute->getStoreLabel()),
			'options' => array()
		    );
//		    if(array_key_exists($attr['code'], $filters)) {
//			$filters_array = explode('_', $filters[$attr['code']]);
//			foreach($result_filter as $option) {
//			    $is_option_selected = false;
//			    if(in_array($option['id'],$filters_array)){
//				$is_option_selected = true;
//			    }
//
//			    $option_param = $this->convertLabelToParam($option['value']);
//			    $attr['options'][] = array(
//				'id'=>$option['id'], 
//				'label'=> $option['value'], 
//				'selected' => $is_option_selected,
//				'param' => $option_param,
//				'count'=> $option['count']
//			    );
//			}
//		    } else {
		    foreach($result_filter as $option) {
			if(empty($option['param'])){$option['param'] = $this->convertLabelToParam($option['value']);}
			$attr['options'][] = array(
			    'id'=>$option['id'], 
			    'label'=> $option['value'], 
			    'selected' => false,
			    'param' => $option['param'],
			    'count'=> $option['count']
			);
			if($current_category->getId() == 2 && $attribute->getAttributeCode() == 'supplier_list' && empty($first_supplier_id)){
			    $this->setAttributeOptionStore($attribute->getAttributeCode(), $option['id'], $option, $is_series_type);
			}
		    }
//		    }
		    $all_attributes[$attribute->getAttributeCode()] = $attr;
		}
	    }

	    /*
	     *  Build Urls
	     */
	    $selected_attributes = [];
	    $this->buildUrl($selected_attributes, $parent_categories, $current_category->getUrl(), $children_categories, $all_attributes);
	    
	    $category_bar = array();
	    $category_bar['parent_categories'] = $parent_categories;
	    $category_bar['category'] = array(
		'id' => $current_category->getId(),
		'name' => trim($current_category->getName()),
		'path' => $current_category->getUrl()
	    );
	    $category_bar['children_categories'] = $children_categories;
	    $category_bar['attributes'] = $all_attributes;
		    
	    //save cache category bar
	    $this->setCache($category_id, $catalog_bar_filter_key, 24, 1, 'created_at', $category_bar, (time() - $time), ($is_series_type?'series_bar':'catalog_bar'));
	}
	if($is_reload_bar && empty($filters['supplier_list'])){
	    if(!empty($category_bar['attributes'])){
		if(!empty($category_bar['attributes']['supplier_list'])){
		    $category_bar_supplier_list = $category_bar['attributes']['supplier_list'];
		}
	    }
	}
	
	//refresh filter
	$attributes = array();
	$attribute_last = array();
	$item_count = 0;
	$item_count_max = 20;
	if(!empty($category_bar['attributes'])){
	    $attributes_sort = Mage::getStoreConfig('catalog/catalog_cache/attribute_array');
	    $attributes_sort = explode(",", $attributes_sort);
	    
	    //set attribute in list sort
	    foreach($attributes_sort as $key){
		if(!empty($category_bar['attributes'][$key])){
		    $attri_size = 20;
		    switch($key){
			case 'genres':
			    $attri_size = 100;
			    break;
			case 'supplier_list':
			    $attri_size = 50;
			    break;
		    }
		    $attributes[] = $this->getAttributeOption($key, $category_bar['attributes'][$key], $filters, $attri_size);
		}
	    }
	    
	    //set attribute not in list sort
	    foreach($category_bar['attributes'] as $key=>$attr){
		if(!in_array($key, $attributes_sort)){
		    $attributes[] = $this->getAttributeOption($key, $attr, $filters, 20);
		}
	    }
	    
	    $category_bar['attributes'] = $attributes;
	}
	
	
	
//mage::log("-----get products START - time:".(time() - $time)."--------", null, 'catalog.log');
        /*
         *  get products
         */
	$time = time();
        $layer->setCurrentCategory($current_category);
	$products = $layer->getProductCollection()->addAttributeToSelect('*');
        $products->getSelect()->where('e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1');
        $products->distinct(false);
	
        $products->getSelect()->joinLeft(array('rating' => 'book_rating'), 'rating.sku = e.sku',array('awsRatings','grRatings','awsAvgScore','grAvgScore'));
	
        $products->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', 
                array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary')
                , '{{table}}.store_id=1', 'left');
	
	$products->getSelect()->joinLeft(array('sed' => 'fahasa_seribook_extra_data'), 'sed.seriesset_id = e.entity_id',array('subscribes','label'));
	
        foreach ($filters as $key=>$item) {
            if($key!='price'){
		$item_array = explode('_',$item);
                $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $key);
		if(!$attribute->getAttributeId()){
		    continue;
		}
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $tableAlias = $attribute->getAttributeCode() . '_idx';

                $conditions = array(
                    "{$tableAlias}.entity_id = e.entity_id",
                    $connection->quoteInto("{$tableAlias}.attribute_id = ?",
                        $attribute->getAttributeId()),
                    $connection->quoteInto("{$tableAlias}.store_id = ?", 1),
                    $connection->quoteInto("{$tableAlias}.value IN (?)", $item_array)
                );
                
                $products->getSelect()->join(
                    array($tableAlias =>  Mage::getResourceModel('catalog/layer_filter_attribute')->getMainTable()),
                    implode(' AND ', $conditions),
                    array()
                );
            }
        }
        
        $products->addFinalPrice();
	if($min_price != $price_range['min'] || $max_price != $price_range['max']){
	    $products->getSelect()
		->where('price_index.final_price >= ' . $price_range['min'])
		->where('price_index.final_price < ' . $price_range['max']);
	}
        
        /*
         *  Set order by
         */
	if($order){
	    if($order == "min_price"){
		$sortObs = new Fahasa_Sortprice_Model_Observer();
		$sortObs->sortByMinPrice($products, 'min_price');
	    }else{
		if($order == "top_subscribes"){
		    $products->getSelect()->order(array("sed.subscribes".' desc'));
		}else{
		    $products->setOrder($order, 'desc');
		}
	    }
	}else{
	    $products->setOrder('num_orders', 'desc');
	}
	
        /*
         *  Set limit product limit
         */
        if($limit && $currentPage){
            $products->setPageSize($limit)->setCurPage($currentPage);
        }
	
	//loại bỏ hàng thanh lý ra khỏi danh sách mặc định
	$products->getSelect()->where("(e.f_thanh_ly is null or e.f_thanh_ly != '1')".$series_filter_str);
	
	$products->getSelect()->group('e.entity_id');
	
        
        $totalproducts = $products->getSize();
	
        $response = array();
	if($totalproducts > (intval($currentPage-1)* intval($limit))){
	    $helperDiscountLabel = Mage::helper('discountlabel');
	    $helperCatalogImage = Mage::helper('catalog/image');

	    //mage::log($products->getSelect()->__toString(), null, 'buffet.log');
	    $currency = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName();
	    $i=0;
	    foreach($products as $product){
		if($product->getTypeId() != 'series'){
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
			// remove  ratings fhs-no-mobile-block
			$rating_html = "<div class='ratings'>
				<div class='rating-box'>
				    <div class='rating' style='width:" . ($rating_count_average > 100 ? 100 : $rating_count_average) . "%'></div>
				</div>
			    <div class='amount'>(" . $ratings . ")</div>
			</div>";

		    $product = $this->getBundlePrice($product);

		    $response[$i] = array(
			'type_id'=>$product->getTypeId(),
			'type'=>$product->getTypeId(),
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
			"episode" => $product->getEpisode()
		    );
		}else{
		    $response[$i] = array(
			'type_id'=>$product->getTypeId(),
			'type'=>$product->getTypeId(),
			'product_id'=>$product->getId(),
			'series_id'=>$product->getSeriesId(),
			'product_name'=>$product->getName(),
			"product_url" => $product->getProductUrl(),
			"subscribes" => $product->getSubscribes(),
			"image_src" => (string)$helperCatalogImage->init($product, 'small_image')->resize(400, 400),
			"episode" => $product->getEpisode()
		    );
		}
		$i++;
	    }

	}
        
        $bestprodts = $response;
        $noofpages = $totalproducts/$limit;
	
	//end
        $result = array();
        $result['status'] = 1;
        $result['message']='Success';
	
        $result['parent_categories'] = $category_bar['parent_categories'];
        $result['category'] = $category_bar['category'];
        $result['children_categories'] = $category_bar['children_categories'];
        $result['attributes'] = $category_bar['attributes'];
        $result['price_range'] = $price_range;
	
        $result['total_products'] = $totalproducts;
        $result['product_list'] = $bestprodts;
        $result['noofpages'] = ceil($noofpages);
	
	//save catalog_cache
	$this->setCache($category_id, $filters_cache, $limit, $currentPage, $order, $result, (time() - $time), ($is_series_type?'series':'catalog'));
	
	//recache all supplier
	if($is_reload_bar && !empty($category_bar_supplier_list)){
	    echo "[SUCCESS][".date('H:i:s', strtotime("+7 hours"))."][".(time() - $time)."] cat_id= ".$category_id," is_series_all= ".$is_series_type."\n";
	    if(!empty($category_bar_supplier_list['options'])){
		$supplier_ids = $category_bar_supplier_list['options'];
		$supplier_size = sizeof($supplier_ids);
		$i = 0;
		foreach($supplier_ids as $key=>$supplier){
		    if(!empty($supplier['id']) && !empty($supplier['count'])){
			if($supplier['count'] > 0){
			    $time = time();
			    if(!$this->reloadCategoryBar($category_id, $supplier['id'], $is_series_type)){
				return;
			    }
			    $i++;
			    echo "[SUCCESS][".$i."/".$supplier_size."][".date('H:i:s', strtotime("+7 hours"))."][".(time() - $time)."] cat_id= ".$category_id," is_series_all= ".$is_series_type." ,supplier_id= ". $supplier['id']."\n";
			    if($i >= 50){
				return;
			    }
			}
		    }
		}
	    }
	    return;
	}
        return $result;
    }
    
    public function buildUrl($selected_attributes, &$parent_categories, $current_category_path, &$children_categories, &$all_attributes){
        
        /// Children Categories
        $count = count($children_categories);
        for($i=0; $i < $count; $i++){
            $children_categories[$i]['url'] = $this->removeHtmlExtension($children_categories[$i]['path']) 
                    . $this->selectedAttributesToUrl($selected_attributes);
        }
        
        /// Parent Categories
        $count = count($parent_categories);
        for($i=0; $i < $count; $i++){
            $parent_categories[$i]['url'] = $this->removeHtmlExtension($parent_categories[$i]['path']) 
                    . $this->selectedAttributesToUrl($selected_attributes);
        }
        
        /// Attributes
//        foreach($all_attributes as $key_attr=> $value_attr){
//            $attr = &$all_attributes[$key_attr];
//            
//            foreach($attr['options'] as $key_opt=> $value_opt){
//                $option = &$attr['options'][$key_opt];
//                
//                $option['url'] = $this->removeHtmlExtension($current_category_path) 
//                        . $this->selectedAttributesToUrl($selected_attributes, $attr['param'], $option['param']);
//            }
//        }
    }
    
    public function removeHtmlExtension($url){
        $array_url = explode(".html", $url);
        return $array_url[0];
    }
    
    public function selectedAttributesToUrl($selected_attributes, $new_attr_param=null, $new_option_param=null){
        $url = "";
        
        if($new_attr_param){
            if($selected_attributes[$new_attr_param]){
                $selected_attributes[$new_attr_param][] = $new_option_param;
            }else{
                $selected_attributes[$new_attr_param] = array($new_option_param);
            }
        }
        
        foreach($selected_attributes as $key=>$options){
            $url .= "/" . $key;
            $url .= "/" . implode("-", $options);
        }
        
        $url .= ".html";
        return $url;
    }
    
    private function getOptionByID($options, $id){
	foreach($options as $key=>$option){
	    if($option['id'] == $id){
		return $option;
	    }
	}
	return null;
    }
    
    public function loadProducts($category_id, $filters, $limit, $currentPage, $order, $is_series_type = false){
	$category_id = $this->cleanBug($category_id);
	$filters = $this->cleanBug($filters);
	$order = $this->cleanBug($order);
	if(!is_numeric($limit)){$limit = 24;}
	if(!is_numeric($currentPage)){$currentPage = 1;}
	
	if(!empty($order)){
	    if($is_series_type){
		$order = $this->checkOrderBy('series', $order);
	    }else{
		$order = $this->checkOrderBy('product', $order);
	    }
	}
	
	if(sizeof($filters) > 0){
	    $filters_param = [];
	    if(!empty($filters['price'])){
		$filters_param['price'] = $filters['price'];
		unset($filters['price']);
	    }
	    ksort($filters);
	    foreach($filters as $param_key=>$value){
		$attibute_valus_array = explode('_', $value);
		sort($attibute_valus_array);
		$filters_param[$param_key] = implode("_",$attibute_valus_array);
	    }
	    $filters = $filters_param;
	}
        Mage::app()->setCurrentStore(1);
        Mage::app('default');
        Mage::getSingleton("core/session", array("name" => "frontend"));
        //$websiteId = Mage::app()->getWebsite()->getId();
        
        $layer = Mage::getModel("catalog/layer");
	if (!Mage::registry('current_category')) {
	    $store_id = Mage::app()->getStore();
	    $category = Mage::getModel('catalog/category')->setStoreId($store_id)->load($category_id);
	    
	    //validate category
	    if(empty($category) || empty($category->getId())){
		$result = array();
		$result['status'] = 0;
		$result['product_list'] = [];
		$result['total_products'] = 0;
		return $result;
	    }
	    if($category->getId() == 2){
		if(!$is_series_type){
		    $category->setData('name', $this->__('All Category'));
		}else{
		    $category->setData('name', $this->__('All Series'));
		}
	    }
	    Mage::register('current_category', $category);
	    if (!Mage::registry('current_entity_key')) {
		Mage::register('current_entity_key', $category->getPath());
	    }
	}else{
	    $category = Mage::registry('current_category');
	    
	    //validate category
	    if(empty($category) || empty($category->getId())){
		$result = array();
		$result['status'] = 0;
		$result['product_list'] = [];
		$result['total_products'] = 0;
		return $result;
	    }
	    if($category->getId() == 2){
		if(!$is_series_type){
		    $category->setData('name', $this->__('All Category'));
		}else{
		    $category->setData('name', $this->__('All Series'));
		}
	    }
	}
	
	//get from cache
	$result = $this->getCache($category_id, $filters, $limit, $currentPage, $order, ($is_series_type?'series_product':'product'));
	if($result){
//	    if(!empty($result['product_list'])){
//		$result['product_list'] = $this->refreshDynParam($result['product_list']);
//	    }
	    return $result;
	}else{
	    $result = $this->getCache($category_id, $filters, $limit, $currentPage, $order, ($is_series_type?'series':'catalog'));
	    if($result){
		unset($result['parent_categories']);
		unset($result['category']);
		unset($result['children_categories']);
		unset($result['price_range']);
		unset($result['attributes']);
		return $result;
	    }
	}
	$time = time();
	$filters_cache = $filters;
	
        $layer->setCurrentCategory($category);
        $min_price = 0;//floor($layer->getProductCollection()->getMinPrice());
        $max_price = 999999999;
	
	if(empty($filters['price'])){
	    $filters['price'] = $min_price.",".$max_price;
	}
	
        $series_filter_str = '';
	if($is_series_type){
	    $series_filter_str = "and e.type_id = 'series'";
	}
	
        $products = $layer->getProductCollection()->addAttributeToSelect('*');
        $products->getSelect()->where('e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1');
        $products->distinct(false);
	
        $products->getSelect()->joinLeft(array('rating' => 'book_rating'), 'rating.sku = e.sku',array('awsRatings','grRatings','awsAvgScore','grAvgScore'));
	
        $products->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', 
                array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary')
                , '{{table}}.store_id=1', 'left');
	
	$products->getSelect()->joinLeft(array('sed' => 'fahasa_seribook_extra_data'), 'sed.seriesset_id = e.entity_id',array('subscribes','label'));
	
	//loại bỏ hàng thanh lý ra khỏi danh sách mặc định
	$products->getSelect()->joinLeft(array('hang_thanh_ly'=> 'fhs_catalog_product_entity_int'), "hang_thanh_ly.entity_id = e.entity_id and hang_thanh_ly.attribute_id = '221' and hang_thanh_ly.store_id = '0'", array("hang_thanh_ly" => "hang_thanh_ly.value"));
	$products->getSelect()->where("(hang_thanh_ly.value is null or hang_thanh_ly.value != '1')" . $series_filter_str);
	
        $applied_minprice=0;
        $applied_maxprice=0;
        foreach ($filters as $key=>$item) {
            if($key=='price'){
                $price_limit = explode(',',$item);
                $applied_minprice = ($price_limit[0]>0)?$price_limit[0]:0;
                $applied_maxprice = $price_limit[1];
                
            }else{
		$item_array = explode('_',$item);
                $attribute = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $key);
		if(!$attribute->getAttributeId()){
		    continue;
		}
                $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $tableAlias = $attribute->getAttributeCode() . '_idx';

                $conditions = array(
                    "{$tableAlias}.entity_id = e.entity_id",
                    $connection->quoteInto("{$tableAlias}.attribute_id = ?",
                        $attribute->getAttributeId()),
                    $connection->quoteInto("{$tableAlias}.store_id = ?", 1),
                    $connection->quoteInto("{$tableAlias}.value IN (?)", $item_array)
                );
                
                $products->getSelect()->join(
                    array($tableAlias =>  Mage::getResourceModel('catalog/layer_filter_attribute')->getMainTable()),
                    implode(' AND ', $conditions),
                    array()
                );
            }
        }
        
        $products->addFinalPrice();
        if(is_numeric($applied_minprice)&&is_numeric($applied_maxprice)) {
	    if($min_price != $applied_minprice || $max_price !=$applied_maxprice){
		$products->getSelect()
		    ->where('price_index.final_price >= ' . $applied_minprice)
		    ->where('price_index.final_price < ' . $applied_maxprice);
	    }
        }
        
        /*
         *  Set order by
         */
	if($order){
	    if($order == "min_price"){
		$sortObs = new Fahasa_Sortprice_Model_Observer();
		$sortObs->sortByMinPrice($products, 'min_price');
	    }else{
		if($order == "top_subscribes"){
		    $products->getSelect()->order(array("sed.subscribes".' desc'));
		}else{
		    $products->setOrder($order, 'desc');
		}
	    }
	}else{
	    $products->setOrder('num_orders', 'desc');
	}
	
	/*
         *  Set limit product limit
         */
        if($limit && $currentPage){
            $products->setPageSize($limit)->setCurPage($currentPage);
        }
	
	$products->getSelect()->group('e.entity_id');
	
        
        $totalproducts = $products->getSize();
	
        $response = array();
	if($totalproducts > (intval($currentPage-1)* intval($limit))){
	    $helperDiscountLabel = Mage::helper('discountlabel');
	    $helperCatalogImage = Mage::helper('catalog/image');

	    $currency = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getShortName();
	    $i=0;
	    foreach($products as $product){
		if($product->getTypeId() != 'series'){
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
			// remove  ratings fhs-no-mobile-block
			$rating_html = "<div class='ratings'>
				<div class='rating-box'>
				    <div class='rating' style='width:" . ($rating_count_average > 100 ? 100 : $rating_count_average) . "%'></div>
				</div>
			    <div class='amount'>(" . $ratings . ")</div>
			</div>";

		    $product = $this->getBundlePrice($product);
		    $response[$i] = array(
			'type_id'=>$product->getTypeId(),
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
			"episode" => $product->getEpisode()
		    );
		}else{
		    $response[$i] = array(
			'type_id'=>$product->getTypeId(),
			'product_id'=>$product->getId(),
			'series_id'=>$product->getSeriesId(),
			'product_name'=>$product->getName(),
			"product_url" => $product->getProductUrl(),
			"subscribes" => $product->getSubscribes(),
			"image_src" => (string)$helperCatalogImage->init($product, 'small_image')->resize(400, 400),
			"episode" => $product->getEpisode()
		    );
		}
		$i++;
	    }
	}
	
        $bestprodts = $response;
        $noofpages = $totalproducts/$limit;
	
        $result = array();
        $result['status'] = 1;
        
        if($totalproducts <= 0){
            $result['message'] = 'No products found. Please check the filter.';
        }else{
            $result['message']='Success';
        }
        
        $result['noofpages'] = ceil($noofpages);
        $result['total_products'] = $totalproducts;
        $result['product_list'] = $bestprodts;
        
	$this->setCache($category_id, $filters_cache, $limit, $currentPage, $order, $result, (time() - $time), ($is_series_type?'series_product':'product'));
        return $result;
    }
    
    public function convertLabelToParam($label){
        return str_replace(' ', '-', mb_strtolower($label));
    }
    
    public function getOrder($is_series_type = false)
    {
	$order = Mage::getSingleton('catalog/session')->getAjaxSortOrder();
	if($order){
	    if(!$is_series_type && $order == 'top_subscribes'){
		return "num_orders";
	    }else{
		return $order;
	    }
	}
	if($is_series_type){
	    return "top_subscribes";
	}
	return 'num_orders';
    }
    public function setOrder($order)
    {
	if($order){
	    Mage::getSingleton('catalog/session')->setAjaxSortOrder($order);
	}
    }
    public function getLimit()
    {
	$limit = Mage::getSingleton('catalog/session')->getAjaxLimitPage();
	if($limit){
	    return $limit;
	}
	return 24;
    }
    public function setLimit($limit)
    {
	if(is_numeric($limit)){
	    Mage::getSingleton('catalog/session')->setAjaxLimitPage($limit);
	}
    }
    public function calPages($currentPage, $limit, $product_total, $tool_limit = 5){
	$result = "";
	
	try {
	    $page_total = ceil($product_total/$limit);

	    if($page_total <= 1){
		return $result;
	    }
	    $start = 0;
	    $stop = 5;
	    if($currentPage > 1){
		$result = "<li title='Previous'><a onclick=\"catalog_ajax.Page_change('previous')\"><i class='fa fa-chevron-left'></i></a></li>";
	    }

	    if($currentPage < ($tool_limit/2)){
		$start = 0;
	    }
	    else if(($page_total - $currentPage) < ($tool_limit/2)){
		$start = $page_total - $tool_limit;
	    }
	    else{
		$start = $currentPage - ceil($tool_limit/2);
	    }
	    
	    if($start < 0){$start = 0;}
	    
	    $stop = $start + $tool_limit;

	    for($i = $start; $i < $stop; $i++){
		if($i < $page_total){
		    if($currentPage == ($i + 1)){
			$result .= "<li class='current'><a>".($i+1)."</a></li>";
		    }
		    else{
			$result .= "<li><a onclick='catalog_ajax.Page_change(".($i+1).")'>".($i+1)."</a></li>";
		    }
		}
	    }

	    if($currentPage < $page_total){
                $result .= "<li class='disable-li'><span>...</span</li>";
                $result .= "<li><a onclick='catalog_ajax.Page_change(". $page_total .")'>". $page_total ."</a></li>";
		//$result .= "<li title='Next'><a onclick=\"catalog_ajax.Page_change('next')\"><i class='fa fa-chevron-right'></i></a></li>";
                $result .= "<li title='Next'><a onclick=\"catalog_ajax.Page_change('next')\"><div class='icon-turn-right'>&nbsp;</div></a></li>";
	    }
	} catch (Exception $ex) {}
	
	return $result;
    }
    public function getSEO(){
	$result = [];
	$cat = Mage::registry('current_category');
	if(!empty($cat)){
	    $cat_name = "sản phẩm";
	    $cat_name_short = "SP";
	    if(strlen($cat->getUrlPath()) >= 7){
		if((substr( $cat->getUrlPath(), 0, 4 ) === "sach") || (substr( $cat->getUrlPath(), 0, 7 ) === "foreign")){
		    $cat_name = "sách";
		    $cat_name_short = "SACH";
		}
	    }

	    $result['title'] = "Tổng hợp ".$cat_name." ".$cat->getName()." tại Fahasa.com";
	    $result['description'] = $result['title'].", với ưu đãi hàng ngày lên tới 50%, giao hàng miễn phí toàn quốc chỉ từ 250k.";
	    $result['keywords'] = $cat_name." ".$cat->getName().", ".$cat_name_short." ".strtolower($cat->getName()).", ".strtolower($cat->getName());
	}
	return $result;
    }
    
    public function getCache($cat_id, $filters, $limit, $currentPage, $order, $type = 'catalog'){
	$cache_id = $type."|".serialize(strval($cat_id))."|".serialize($limit)."|".serialize($currentPage)."|".serialize($order)."|".serialize($filters);
	$data = $this->getCatalogStore($cache_id);
	if(empty($data)){
	    if ($data = $this->getCacheDB($cache_id)) {
		$data = unserialize($data);
		$this->setCatalogStore($cache_id, $data); 
	    }
	}
        return $data;
    }
    
    public function setCache($cat_id, $filters, $limit, $currentPage, $order, $data, $time_load, $type = 'catalog'){
	$cache_id = $type."|".serialize(strval($cat_id))."|".serialize($limit)."|".serialize($currentPage)."|".serialize($order)."|".serialize($filters);
	$this->setCacheDB($cat_id, $type, $cache_id, serialize($data), $time_load);
	$this->setCatalogStore($cache_id, $data);
    }
    
    public function getCategories(){
	$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	$sql = "select entity_id as 'id' from fhs_catalog_category_entity where	attribute_set_id = 3;";
        return $reader->fetchAll($sql);
    }
    public function getCatalogStore($key_store){
	try{
	    if(empty($key_store)){return $result;}
	    if(Mage::getStoreConfig('flashsale_config/config/redis_day_timelife') == 0){return;}
	    
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientCatalogAction();
	    if (!$redis_client->isConnected()) {
		return null;
	    }
	    
            $result = $redis_client->get("catalog:".$key_store);
	    if(!empty($result)){
		$result = unserialize($result);
		if(strtotime($result['timelife']) >  strtotime(date('Y-m-d', strtotime("+7 hours")))
		   && $result['queryfier'] == Mage::getStoreConfig('flashsale_config/config/redis_querylier_catalog')
		){
		    return $result['value'];
		}
	    }
	}catch (Exception $ex) {}
	return null;
    }
    public function setCatalogStore($key_store, $value){
	try{
	    $days = Mage::getStoreConfig('flashsale_config/config/redis_day_timelife');
	    if($days == 0){return;}
	    
	    if(empty($days) || !is_numeric($days)){
		$days = 1;
	    }
	    $value = serialize(array('value'=>$value,
				    'timelife'=>date('Y-m-d', strtotime("+7 hours ".$days." days")),
				    'queryfier'=>Mage::getStoreConfig('flashsale_config/config/redis_querylier_catalog')
				));
	    
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientCatalogAction();
	    if (!$redis_client->isConnected()) {
		return;
	    }
	    
	    $redis_client->set("catalog:".$key_store, $value);
	    $redis_client->close();
	}catch (Exception $ex){}
    }
    public function getProductStore($product_id, $attr_key = '', $is_attribute_frontend = false){
        Mage::log('------ BEGIN GET PRODUCT STORE ' . microtime(true), null, "redis_product_debug.log" );
        try{
	    if(!Mage::getStoreConfig('flashsale_config/config_product_redis/is_active')){return null;}
	    
	    $product = Mage::registry('product_store_'.$product_id);
	    
            Mage::log(' - get product registry ' . microtime(true), null, "redis_product_debug.log" );
	    if (empty($product) && $product != 1) {
                $product = array();
		$this->logtime("[start]");
		
		// Start Redis Connection
		$redis_client = Mage::helper("flashsale/redis")->createRedisClientProduct();
		if (!$redis_client->isConnected()) {
		    return;
		}

		$rProduct = $redis_client->hGetAll("product:".$product_id);
//		$single = $redis_client->hGet("product:151323", 'product_id');
//		$list = $redis_client->hMGet("product:151323", ['product_id', 'product_name']);
		$redis_client->close();
		
		$this->logtime("[loaded] product_id= ".$product_id);
		if(!empty($rProduct)){
		    foreach($rProduct as $key=>$item){
                        \Mage::log(' -Data name array : ' . $key , null, "redis_product_debug.log" );
			if(!$this->isEmpty($item)){
			    $product[$key] = $this->json_validate($item);
			}else{
			    unset($rProduct[$key]);
//			    echo 'unset => '.$key."=".$item."<br/>";
		    }
			    }
//		    foreach($product as $key=>$item){
//			echo 'set => '.$key."=".$item."<br/>";
//			if(is_array($item)){print_r($item); echo "<br/>";}
//		    }
//                    \Mage::log(' -Data name array 123123', null, "redis_product_debug.log" );
//                    \Mage::log(' -Data name array : ' . print_r($test,true) , null, "redis_product_debug.log" );
		    Mage::register('product_store_'.$product_id, $product);
		}else{
		    Mage::register('product_store_'.$product_id, 1);
		}
	    }
	    
	    if(empty($product) || $product == 1){
		return null;
	    }
	    
	    if(!empty($attr_key)){
		return $product[$attr_key];
	    }
	    if($is_attribute_frontend){
		return $this->getAttributeFilter($product, $is_attribute_frontend);
	    }
            
            $this->logtime("[loaded] get attribute filter product_id= ".$product_id);
            
	    return $this->getAttributeFilter($product, $is_attribute_frontend);
	}catch (Exception $ex){
	    mage::log("[ERROR] product_id= ".$product_id." ,attribute_key= ".$attr_key .", is_frontend=".$is_attribute_frontend.", msg=".$ex->getMessage(), null, 'redis_product.log');
	}
	return null;
    }
    
    public function getProductStoreV2($product_id, $is_attribute_frontend = false){
        try{
	    if(!Mage::getStoreConfig('flashsale_config/config_product_redis/is_active')){return null;}

	    $product = Mage::registry('product_store_'.$product_id);
	    $childsInfo = array();
            
	    if (empty($product) && $product != 1) {
                
                $attr_key = $this->getArrKeyAttribute();
                $product = array();
                
		$this->logtime("[start]");
		
		// Start Redis Connection
		$redis_client = Mage::helper("flashsale/redis")->createRedisClientProduct();
		if (!$redis_client->isConnected()) {
                    mage::log("[ERROR] product_id= ".$product_id." not connected", null, 'redis_product.log');
		    return null;
		}
                $keyPro = "product:" . $product_id;
                if(!$redis_client->exists($keyPro)){
                    mage::log("[ERROR] product_id= ".$product_id." not exists", null, 'redis_product.log');
                    $redis_client->close();
                    return null;
                }
                // Check Type_id product :
                $resultChilds = $this->getArrChild($product_id,$redis_client);
                $product_id = $resultChilds['product_id'] ?? $product_id;
                $childsInfo = $resultChilds['childsInfo'];
                
                // check list review_new
                $reviewsInfo = $this->getReviewsInfo($product_id,$redis_client, 1 , 10);

//                $rProduct = $redis_client->hGetAll("product:".$product_id);
                $pipeLineRedis = $redis_client->pipeline();
                $pipeLineRedis->hMGet($keyPro,$attr_key);
                $result = $pipeLineRedis->exec();
                $redis_client->close();
                 
                if (!empty($result) && count($result) > 0 && !empty($result[0])) {
                    $dataRedis = $result[0];
                    foreach ($dataRedis as $key => $value) {
                        if (!$this->isEmpty($this->json_validate($value),$key)) {
                            $product[$key] = $this->json_validate($value);
                        }
                    }
                }
//                $this->logtime("[loaded] product_id= ".$product_id);
                
                if (!empty($product)) {
                    $product['childConfigruableId'] = $resultChilds['childConfigruableId'];
                    $product['list_comment'] = $reviewsInfo;
                    if (!empty($childsInfo)) {
                        $product['childsInfo'] = $childsInfo;
                    } else {
                        $product['childsInfo'] = array();
                    }
                    
                    Mage::register('product_store_' . $product_id, $product);
                } else {
                    Mage::register('product_store_' . $product_id, 1);
                }
            }
	    
	    if(empty($product) || $product == 1){
		return null;
	    }
            
	    if($is_attribute_frontend){
		return $this->getAttributeFilter($product, $is_attribute_frontend);
	    }
            
            $this->logtime("[loaded] get attribute filter product_id= ".$product_id);
            
	    return $product;
	}catch (Exception $ex){
	    mage::log("[ERROR] product_id= ".$product_id." , is_frontend=".$is_attribute_frontend.", msg=".$ex->getMessage(), null, 'redis_product.log');
	}
        return null;
    }
    public function getSeriesStore($series_id){
        try{
	    if(!Mage::getStoreConfig('flashsale_config/config_product_redis/is_active')){return null;}

	    $series = Mage::registry('series_store_'.$series_id);
	    $childsInfo = array();
            
	    if (empty($series) && $series != 1) {
                
                $attr_key = array('series_id','name','keyword','url','episodes','product_series_id','episode_max','label','seriesset_id','subscribes');
                $series = array();
                
		// Start Redis Connection
		$redis_client = Mage::helper("flashsale/redis")->createRedisClientProduct();
		if (!$redis_client->isConnected()) {
		    return;
		}
		
                $keyPro = "seri:" . $series_id;
                $pipeLineRedis = $redis_client->pipeline();
                $pipeLineRedis->hMGet($keyPro,$attr_key);
                $result = $pipeLineRedis->exec();
                 $redis_client->close();
                 
                if (!empty($result) && count($result) > 0 && !empty($result[0])) {
                    $dataRedis = $result[0];
                    foreach ($dataRedis as $key => $value) {
                        if (!$this->isEmpty($this->json_validate($value),$key)) {
                            $series[$key] = $this->json_validate($value);
                        }
                    }
                }
                
                if (!empty($series)) {
                    Mage::register('series_store_' . $series_id, $series);
                } else {
                    Mage::register('series_store_' . $series_id, 1);
                }
            }
	    
	    if(empty($series) || $series == 1){
		return null;
	    }
            
	    return $series;
	}catch (Exception $ex){
	    mage::log("[ERROR] product_id= ".$series_id. ", msg=".$ex->getMessage(), null, 'redis_series.log');
	}
	return null;
    }
    
    public function getProductsStoreArray($listProductId, $attr_key = array(), $is_attribute_frontend = false) {
        try {
            if (!Mage::getStoreConfig('flashsale_config/config_product_redis/is_active')) {
                return null;
            }

            $products = array();
            if (empty($products) && count($attr_key) > 0) {
                $this->logtime("[start] array products ");

                // Start Redis Connection
                $redis_client = Mage::helper("flashsale/redis")->createRedisClientProduct();
                if (!$redis_client->isConnected()) {
                    return;
                }
                
                foreach ($listProductId as $proId) {
                    $item = array();
                    foreach ($attr_key as $value) {
                        $dataRedis = $redis_client->hGet("product:" . $proId, $value);
                        $item[$value] = $this->json_validate($dataRedis) ?? null;
                    }
                    //$this->getAttributeFilter($item, $is_attribute_frontend);
                    $products[] = $item;
                }
                $redis_client->close();
                $this->logtime("[loaded] array products = " . print_r($listProductId,true));
            }

            if (empty($products) || $products == 1) {
                return null;
            }

            return $products;
        } catch (Exception $ex) {
            mage::log("[ERROR] product_id= " . print_r($listProductId,true) . " ,attribute_key= " . print_r($attr_key,true) . ", is_frontend=" . $is_attribute_frontend . ", msg=" . $ex->getMessage(), null, 'redis_product.log');
        }
        return null;
    }

    public function logtime($info){
	if(!Mage::getStoreConfig('flashsale_config/config_product_redis/is_print_to_log')){return;}
	
	if(empty($startTime = Mage::registry('start_log_time'))){
	    $startTime = round(microtime(true) * 1000);
            mage::log("[Debug]".$info.", not set time start ", null, 'redis_product_debug.log');
	    Mage::register('start_log_time', $startTime);
	}
        mage::log("[Debug]".$info.",  time start =". $startTime , null, 'redis_product_debug.log');
	if($info == "[start]"){return;}
	mage::log("[Debug]".$info.", time=".(round(microtime(true) * 1000)-$startTime)."ms", null, 'redis_product_debug.log');
    }
    public function deleteCatalogStore(){
	try{
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientCatalogAction();
	    if (!$redis_client->isConnected()) {
		return;
	    }
	    
	    $redis_client->delete($redis_client->keys("catalog:*"));
	    $redis_client->close();
	}catch (Exception $ex){}
    }
    public function getAttributeOptionStore($attribute_code, $option_id, $is_series = 0){
	try{
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientCatalogAction();
	    if (!$redis_client->isConnected()) {
		return null;
	    }
	    
            $result = $redis_client->get("attribute:".($is_series?'1':'0').":".$attribute_code.":".$option_id);
	    if(!empty($result)){
		return unserialize($result);
	    }
	}catch (Exception $ex) {}
	return null;
    }
    public function setAttributeOptionStore($attribute_code, $option_id, $value, $is_series = 0){
	try{
	    $value = serialize($value);
	    
	    // Start Redis Connection
	    $redis_client = Mage::helper("flashsale/redis")->createRedisClientCatalogAction();
	    if (!$redis_client->isConnected()) {
		return;
	    }
	    
	    $redis_client->set("attribute:".($is_series?'1':'0').":".$attribute_code.":".$option_id, $value);
	    $redis_client->close();
	}catch (Exception $ex){}
    }
    public function getCacheDB($key_id){
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $binds = array('key_id' => $key_id);
	    $sql = "select value from fhs_catalog_cache where key_id = :key_id;";
	    $result = $reader->fetchRow($sql, $binds);
	    if($result){
		$result = $result['value'];
	    }
	} catch (Exception $ex) {
	    Mage::log("[ERROR] getCacheDB: ". $ex->getMessage(), null, "catalog.log");
	}
	return $result;
    }
    public function setCacheDB($cat_id, $type, $key_id, $data, $time_load){
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array(
		    'key_id' => $key_id,
		    'value' => $data,
		    'category_id' => $cat_id,
		    'type' => $type,
		    'time_load' => $time_load);
	    $sql = "INSERT INTO fhs_catalog_cache(key_id, category_id, type, value, time_load) 
		    VALUES (:key_id, :category_id, :type, :value, :time_load)
		    ON DUPLICATE KEY UPDATE 
		    is_processed=1,
		    category_id=VALUES(category_id),
		    type=VALUES(type),
		    value=VALUES(value),
		    time_load=VALUES(time_load);";
	    $writer->query($sql,$binds);
	} catch (Exception $ex) {
	    Mage::log("[ERROR] setCache: ". $ex->getMessage(), null, "catalog.log");
	}
    }
    public function removeCacheDB($key_ids){
	$result = false;
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $binds = array(
		    'key_ids' => $key_ids);
	    $sql = "delete from fhs_catalog_cache where key_id in(':key_ids');";
	    $writer->query($sql);
	    $result = true;
	} catch (Exception $ex) {
	    Mage::log("[ERROR] removeAllCacheDB: ". $ex->getMessage(), null, "catalog.log");
	}
	return $result;
    }
    public function removeAllCacheDB(){
	try{
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
//	    $sql = "delete c from fhs_catalog_cache c join(
//			select prod.key_id
//			from (select key_id, REPLACE(key_id, 'catalog|', '') as 'id' from fhs_catalog_cache where key_id like 'catalog|%'
//			) cat
//			join (select key_id, REPLACE(key_id, 'product|', '') as 'id' from fhs_catalog_cache where key_id like 'product|%'
//			) prod on prod.id = cat.id
//		    ) r on r.key_id = c.key_id;";
	    //$sql .= "delete from fhs_catalog_cache where key_id like '%s:5:\"price\"%';";
	    $sql = "update fhs_catalog_cache set is_processed = 0, value = '';";
	    $writer->query($sql);
	} catch (Exception $ex) {
	    Mage::log("[ERROR] removeAllCacheDB: ". $ex->getMessage(), null, "catalog.log");
	}
    }
    public function getReloadKeyCache($type = 'bar', $time_reload = 0, $limit = 1000){
	$result = null;
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    switch ($type){
		case 'bar':
		    $sql = "select key_id, type, time_load from fhs_catalog_cache where is_processed = 0 and value = '' and type in ('catalog_bar', 'series_bar') order by time_load desc limit ".$limit.";";
		    break;
		case 'catalog':
		    $sql = "select key_id, type, time_load from fhs_catalog_cache where is_processed = 0 and value = '' and time_load >= ".$time_reload." and type in ('catalog', 'series') order by time_load desc limit ".$limit.";";
		    break;
		case 'product':
		    $sql = "select key_id, type, time_load from fhs_catalog_cache where is_processed = 0 and value = '' and time_load >= ".$time_reload." and type in ('product', 'series_product') and category_id in (2, 4, 9, 11, 12, 14, 15, 214, 17, 19, 20, 6003, 6005, 6006, 6007, 6008, 6009, 6010, 6011, 6038, 6169, 6174, 6718, 86, 3165, 3166, 3925, 4216, 4199, 4782, 5004, 5387, 27, 69, 5945, 5961, 5421, 5518, 5991, 6181, 6321, 6641) order by time_load desc limit ".$limit.";";
		    break;
	    }
	    if(!empty($sql)){
		$result = $reader->fetchAll($sql);
	    }
	} catch (Exception $ex) {
	    echo "[ERROR] getReloadKeyCache: ". $ex->getMessage();
	    Mage::log("[ERROR] getReloadKeyCache: ". $ex->getMessage(), null, "catalog.log");
	}
	return $result;
    }
    public function getCountKeyCache(){
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select count(key_id) as 'count' from fhs_catalog_cache;";
	    $result = $reader->fetchRow($sql);
	} catch (Exception $ex) {
	    Mage::log("[ERROR] getCountKeyCache: ". $ex->getMessage(), null, "catalog.log");
	}
	return $result['count'];
    }
    public function reloadCache(){
	$result = [];
	$result['result'] = false;
	try{
	    //time_reload
	    echo "---------------------------------------------\n";
	    echo "CLEAR CACHE  \n";
	    $this->deleteCatalogStore();
	    $this->removeAllCacheDB();
	    echo "---------------------------------------------\n";
	    echo "BAR PROCESSING  \n";
	    echo "---------------------------------------------\n";
	    $limit_reload = Mage::getStoreConfig('catalog/catalog_cache/limit_reload');
	    $time_reload = Mage::getStoreConfig('catalog/catalog_cache/time_reload');
	    echo "limit_reload=".$limit_reload."\n";
	    echo "time_reload=".$time_reload."\n";
	    echo "---------------------------------------------\n";
	    if(Mage::getStoreConfig('catalog/catalog_cache/is_reload_all_bar')){
		$this->reloadCategoryBar(2);
	    }else{
		$key_ids = $this->getReloadKeyCache('bar', $time_reload, $limit_reload);
		$key_count = sizeof($key_ids);
		$i = 1;
		foreach ($key_ids as $key_id){
		    $this->reloadCacheKey($key_id, $i, $key_count);
		    $i++;
		}
	    }
	    echo "---------------------------------------------\n";
	    echo "CATALOG PROCESSING  \n";
	    echo "---------------------------------------------\n";
	    $key_ids = $this->getReloadKeyCache('catalog', $time_reload, $limit_reload);
	    $key_count = sizeof($key_ids);
	    $i = 1;
	    foreach ($key_ids as $key_id){
		$this->reloadCacheKey($key_id, $i, $key_count);
		$i++;
	    }
	    echo "---------------------------------------------\n";
	    echo "PRODUCT PROCESSING  \n";
	    echo "---------------------------------------------\n";
	    $key_ids = $this->getReloadKeyCache('product', $time_reload, $limit_reload);
	    $key_count = sizeof($key_ids);
	    $i = 1;
	    foreach ($key_ids as $key_id){
		$this->reloadCacheKey($key_id, $i, $key_count);
		$i++;
	    }
	    $result['result'] = true;
	} catch (Exception $ex) {
	    $result['message'] = $ex->getMessage();
	    Mage::log("[ERROR] reloadCache: ". $ex->getMessage(), null, "catalog.log");
	}
        return $result;
    }
    protected function reloadCacheKey($key_id, $i, $key_count){
	try {
	    $key_id_array = explode("|", $key_id['key_id']);
	    $type = $key_id['type'];
	    $cat_id = unserialize($key_id_array[1]);
	    $limit = unserialize($key_id_array[2]);
	    $currentPage = unserialize($key_id_array[3]);
	    $order = unserialize($key_id_array[4]);
	    if($key_id_array[5]){
		$filters = unserialize($key_id_array[5]);
	    }else{
		$filters = [];
	    }
	    if (Mage::registry('current_category')) {
		Mage::unregister('current_category');
	    }
	    if (Mage::registry('current_entity_key')) {
		Mage::unregister('current_entity_key');
	    }
	    if($type == 'catalog_bar'){
		$this->loadCatalog($cat_id, $filters, $limit, $currentPage, $order);
	    }elseif($type == 'series_bar'){
		$this->loadCatalog($cat_id, $filters, $limit, $currentPage, $order, true);
	    }elseif($type == 'catalog'){
		$this->loadCatalog($cat_id, $filters, $limit, $currentPage, $order);
	    }elseif($type == 'series'){
		$this->loadCatalog($cat_id, $filters, $limit, $currentPage, $order, true);
	    }elseif($type == 'product'){
		$this->loadProducts($cat_id, $filters, $limit, $currentPage, $order);
	    }elseif($type == 'series_product'){
		$this->loadProducts($cat_id, $filters, $limit, $currentPage, $order, true);
	    }

	    echo "Reloaded (".$i."/".$key_count.")(".$key_id['time_load']."s) key: ".$key_id['key_id']."\n";
	} catch (Exception $ex) {
	    echo "[WARNING] can't reload this key:".$key_id['key_id']."\n";
	}
    }
    public function reloadCategoryBar($category_id, $supplier_id = null, $is_series_type = null, $cat_level = 0){
	$category_id = $this->cleanBug($category_id);
	try{
	    $RELOAD_CAT_LEVEL_LIMIT = Mage::registry('RELOAD_CAT_LEVEL_LIMIT');
	    if(empty($RELOAD_CAT_LEVEL_LIMIT)){
		$RELOAD_CAT_LEVEL_LIMIT = Mage::getStoreConfig('catalog/catalog_cache/reload_bar_level_cat');
		if(empty($RELOAD_CAT_LEVEL_LIMIT)){
		    $RELOAD_CAT_LEVEL_LIMIT = 2;
		}
		if(!is_numeric($RELOAD_CAT_LEVEL_LIMIT)){
		    $RELOAD_CAT_LEVEL_LIMIT = 2;
		}
		Mage::register('RELOAD_CAT_LEVEL_LIMIT', $RELOAD_CAT_LEVEL_LIMIT);
	    }
	    if($cat_level > $RELOAD_CAT_LEVEL_LIMIT){return;}
	    
	    if(!$supplier_id){
		echo "[PROCESSING][".$cat_level."] cat_id= ".$category_id," is_series_all= ".$is_series_type."\n";
		if (Mage::registry('current_category')) {
		    Mage::unregister('current_category');
		}
		if (Mage::registry('current_entity_key')) {
		    Mage::unregister('current_entity_key');
		}
		if($is_series_type == null){
		    $this->loadCatalog($category_id, [], 24, 1, '', false, true);
		    $this->loadCatalog($category_id, [], 24, 1, '', true, true);
		}else{
		    $this->loadCatalog($category_id, [], 24, 1, '', $is_series_type, true);
		}
		$cat_level++;
		
		if($cat_level > $RELOAD_CAT_LEVEL_LIMIT){return;}
		
		$category = Mage::getModel('catalog/category')->setStoreId(1)->load($category_id);
		$children_cats = $category->getChildrenCategories();
		foreach($children_cats as $cat){
		    if($cat->getData('is_active')){
		    if (Mage::registry('current_category')) {
			Mage::unregister('current_category');
		    }
		    if (Mage::registry('current_entity_key')) {
			Mage::unregister('current_entity_key');
		    }
			$this->reloadCategoryBar($cat->getId(), null, null, $cat_level);
		    }
		}
	    }else{
		echo "[PROCESSING] cat_id= ".$category_id," is_series_all= ".$is_series_type." ,supplier_id= ".$supplier_id."\n";
		$filter = array('supplier_list' => $supplier_id);
		$this->loadCatalog($category_id, $filter, 24, 1, 'created_at', $is_series_type, true);
	    }
	}catch (Exception $ex) {
	    echo "[ERROR] cat_id= ".$category_id. " ,msg= ".$ex->getMessage()."\n";
	    return false;
	}
	return true;
    }
    public function getCommentList($product_id, $page, $page_size, $sorter){
	$product_id = $this->cleanBug($product_id);
	$page = $this->cleanBug($page);
	$page_size = $this->cleanBug($page_size);
	$sorter = $this->cleanBug($sorter);
		
	//$cache_type = "comment_list";
	//$cache_id = $cache_type.':product'.$product_id.":sorter".($sorter?trim($sorter):'')."page".($page?trim($page):'')."size".($page_size?trim($page_size):'');
	//$result = Mage::helper("fahasa_customer")->getPublicStore($cache_id, $cache_type);
	//if(empty($result)){
	    $list_comment = Mage::getModel('review/review')
		    ->getCollection()
		    //->addStoreFilter(Mage::app()->getStore()->getId())
		    ->addEntityFilter('product', $product_id)
		    ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED);
	    $list_comment->getSelect()
		    ->joinLeft(
			    array('ra' => 'fhs_reviews_action'), 
			    'main_table.review_id = ra.review_id and ra.type = "like"', 
			    array("sum(if(ra.customer_email is null, 0, 1)) as countLike")
		    )
		    ->group("main_table.review_id");
	    if ($sorter == 'last-review') {
		$list_comment->setDateOrder();
	    } else {
		$list_comment
			->setOrder('countLike', 'DESC')
			->setDateOrder();
	    }
	    $list_comment->setPageSize($page_size)
			->setCurPage($page)->addRateVotes();
	    //----------------------

	    // Count Reveiew comment FHS :
	    $summaryData = $this->getFHSRatingAverages($product_id);
	    $itemCount = $summaryData['reviews_count_fhs'];

	    $result = array();
	    $result['success'] = true;
    //	$result['total_comments'] = $list_comment->getSize();
	    $result['total_comments'] = $itemCount;

	    $review_ids = array();
	    foreach ($list_comment AS $review) {
		array_push($review_ids, $review->review_id);
	    }
	    if($review_ids){
		$review_ids = Mage::getModel('reviewsaction/reviewsaction')->getCustomerLikedList($review_ids, Mage::getSingleton('customer/session')->getCustomer()->getEmail())->getData();
	    }
	    $comments = array();
	    foreach ($list_comment AS $review) {
		$votes = $review->getRatingVotes();
		$total = 0;
		foreach ($votes AS $vote) {
		    $total += $vote->getPercent();
		}
		
		//cache review item
		$cache_review_id = 'product:'.$product_id.":review:".$review->review_id;
		$cache_review = (int)$review['countLike'];
		
		$comments[] = array(
		    'id' => $review->review_id,
		    'suborder_id' => $review->suborder_id,
		    'rating' => $total,
		    'title' => $review->title,
		    'detail' => Mage::helper('core')->htmlEscape($review->detail),
		    'nickname' => Mage::helper('core')->htmlEscape($review->nickname),
		    'countLike' => (int)$review['countLike'],
		    'review' => $this->getReviewTypeById($review_ids, $review->review_id),
		    'created_at' => date('d/m/Y', strtotime($review->created_at))
		);
	    }

	    $result['comment_list'] = $comments;
	    //Mage::helper("fahasa_customer")->setPublicStore($cache_id, $result, $cache_type);
//	}else{
//	    $review_ids = array();
//	    foreach ($result AS $review) {
//		array_push($review_ids, $review->review_id);
//	    }
//	    if($review_ids){
//		$review_ids = Mage::getModel('reviewsaction/reviewsaction')->getCustomerLikedList($review_ids, Mage::getSingleton('customer/session')->getCustomer()->getEmail())->getData();
//	    }
//	    foreach ($list_comment AS $review) {
//		$votes = $review->getRatingVotes();
//		$total = 0;
//		foreach ($votes AS $vote) {
//		    $total += $vote->getPercent();
//		}
//
//		$comments[$j]['id'] = $review->review_id;
//		$comments[$j]['suborder_id'] = $review->suborder_id;
//		$comments[$j]['rating'] = $total;
//		$comments[$j]['title'] = $review->title;
//		$comments[$j]['detail'] = Mage::helper('core')->htmlEscape($review->detail);
//		$comments[$j]['nickname'] = Mage::helper('core')->htmlEscape($review->nickname);
//		$comments[$j]['countLike'] = (int)$review['countLike'];
//		$comments[$j]['review'] = $this->getReviewTypeById($review_ids, $review->review_id);
//		$comments[$j]['created_at'] = $review->created_at;
//		$j = $j + 1;
//	    }
//	}
	    
        return $result;
    }
    
    public function getReviewTypeById($review_ids, $review_id){
	$result = "";
	foreach ($review_ids as $review){
	    if($review['review_id'] == $review_id){
		return $review['type'];
	    }
	}
	return $result;
    }
    
    public function calCommentPages($currentPage, $limit, $comment_total, $tool_limit = 5){
	$result = "";
	
	try {
	    $page_total = ceil($comment_total/$limit);

	    if($page_total <= 1){
		return $result;
	    }
	    $start = 0;
	    $stop = 5;
	    if($currentPage > 1){
		$result = "<li title='Previous'><a onclick=\"prodComment.Page_change('previous')\"><i class='fa fa-chevron-left'></i></a></li>";
	    }

	    if($currentPage < ($tool_limit/2)){
		$start = 0;
	    }
	    else if(($page_total - $currentPage) < ($tool_limit/2)){
		$start = $page_total - $tool_limit;
	    }
	    else{
		$start = $currentPage - ceil($tool_limit/2);
	    }
	    
	    if($start < 0){$start = 0;}
	    
	    $stop = $start + $tool_limit;

	    for($i = $start; $i < $stop; $i++){
		if($i < $page_total){
		    if($currentPage == ($i + 1)){
			$result .= "<li class='current'><a>".($i+1)."</a></li>";
		    }
		    else{
			$result .= "<li><a onclick='prodComment.Page_change(".($i+1).");'>".($i+1)."</a></li>";
		    }
		}
	    }

	    if($currentPage < $page_total){
		$result .= "<li title='Next'><a onclick=\"prodComment.Page_change('next');\"><i class='fa fa-chevron-right'></i></a></li>";
	    }
	} catch (Exception $ex) {}
	
	return $result;
    }
    public function getFHSRatingAverages($productId) {
	$productId = $this->cleanBug($productId);
	$helper = Mage::helper("fahasa_customer");
	$cache_type = "rating_averages";
	$cache_id = $cache_type.':product:'.$productId;
	$data = $helper->getPublicStore($cache_id, $cache_type);
	if(empty($data)){
	    $data = array();
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
	    $data['total_star'] = $totalComment;    
	    $data['reviews_count_fhs'] = null;
	    $data['rating_summary_fhs'] = null;
	    if($reviewCount > 0){
		$ratingP = round($percentSum/$reviewCount);
		$data['reviews_count_fhs'] = $reviewCount;
		$data['rating_summary_fhs'] = $ratingP == 0 ? null : round($percentSum/$reviewCount);        
	    }
	    $helper->setPublicStore($cache_id, $data, $cache_type);
	}
        
        return $data;
    }
    public function addComment($productId, $star, $nickName, $title, $comment) {
	$result = 'error';
        
        $reviewId = "";

        if (!isset($productId) || strlen($productId) < 1 ||
                !isset($star) || strlen($star) < 1 ||
                !isset($nickName) || strlen($nickName) < 1 ||
                //!isset($title) || strlen($title) < 1 ||
                !isset($comment) || strlen($comment) < 1) {
            $result = $this::ERR_MISSING_VALUE;
        } else if (strlen($comment) < 100) {
            $result = $this::ERR_DETAIL_MINIUM_100_CHARACTERS;
        } else if ($star < 1 || $star > 5) {
            $result = $this::ERR_STAR_1_TO_5;
        } else {
            $star = $star + 10;
            $review = \Mage::getModel('review/review')
                    ->setEntityId(1) //review_entity: 1 - Product
                    ->setEntityPkValue($productId)
                    ->setStatusId(\Mage_Review_Model_Review::STATUS_PENDING)
                    ->setNickname($nickName)
                    ->setTitle($title)
                    ->setDetail($comment)
                    ->setCustomerId(\Mage::getSingleton('customer/session')->getCustomerId())
                    ->setStoreId(1)
                    ->setStores(array(1))
                    ->save();
            $rating[3] = $star;
            foreach ($rating as $ratingId => $optionId) {
                \Mage::getModel('rating/rating')
                        ->setRatingId($ratingId)
                        ->setReviewId($review->getId())
                        ->setCustomerId(\Mage::getSingleton('customer/session')->getCustomerId())
                        ->addOptionVote($optionId, $productId);

                $review->aggregate();
            }
            $reviewId = $review->getReviewId();
	    $result = '';
        }

        return $result;
    }
    
    public function checkProductInWishlist($productId) {
	$productId = $this->cleanBug($productId);
	
	$result = 0;
	if(!Mage::getSingleton('customer/session')->isLoggedIn()){
	    return $result;
	}
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        
        $wishList = \Mage::getModel('wishlist/wishlist')->loadByCustomer($customer);
        $wishListItemCollection = $wishList->getItemCollection();
        foreach ($wishListItemCollection as $item) {
            if($productId == $item->getProductId()){
                $result = 1;
                break;
            }
        }
        return $result;
    }
	
    public function getProductByProductIdsWithSortBy($product_ids, $sortBy, $page = 1, $pageSize = 12) {
	$product_ids = $this->cleanBug($product_ids);
	$sortBy = $this->cleanBug($sortBy);
	$page = $this->cleanBug($page);
	$pageSize = $this->cleanBug($pageSize);
	
	if(empty($product_ids) || sizeof($product_ids) < ($pageSize*($page -1)) || sizeof($product_ids) <= 0){
	    return null;
	}
        $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->joinTable('review/review_aggregate', 'entity_pk_value = entity_id', array('fhs_reviews_count' => 'reviews_count', 'fhs_rating_summary' => 'rating_summary'), '{{table}}.store_id=0','left')
                ->joinTable('multistoreviewpricingpriceindexer/product_index_price', 'entity_id = entity_id', array('price'=>'price', 'final_price' => 'final_price', 'min_price' => 'min_price'), 
                        '{{table}}.store_id='.Mage::app()->getStore()->getId(). ' and {{table}}.customer_group_id=0', 'left')
                ->addAttributeToFilter('entity_id', array('in' => $product_ids))
                ->setPageSize($pageSize)
                ->setCurPage($page);
        $collection->getSelect()->joinLeft(array('rating' => 'book_rating'), 'rating.sku = e.sku',array('awsRatings','grRatings','awsAvgScore','grAvgScore'));
        if($sortBy){
            if($sortBy != 'min_price'){
                $collection->getSelect()->order(array($sortBy.' DESC'));
            }
             else{
                 $collection->getSelect()->order(array("min_price ASC"));
            }
        }else{
            $collection->getSelect()->order(array("FIND_IN_SET(`e`.entity_id, '" . implode(",", $product_ids) . "')"));
        }
        //Move visibility and stock_status and status in to fhs_catalog_product_entity for better indexing
        $collection->getSelect()->where('e.f_visibility = 4 AND e.f_stock_status = 1 AND e.f_status = 1 AND e.type_id != "series"');
        $collection->distinct(false);
        return $collection;
    }
    
    public function getAdditionalData($product, $includeAttr = array(), $product_data = null)
    {
	$result = array();
	
	if(!empty($product_data)){
	    if(!empty($product_data['attributes'])){
		$attributes = $product_data['attributes'];
		
		$store_id = Mage::app()->getStore()->getStoreId();
	
		foreach($includeAttr as $attr_code){
		    if(!empty($attributes[$attr_code])){
			$label = $this->getAttributeTranslate($store_id, $attr_code);
			$value = $this->getAttributeItemValueHtml($store_id, $attr_code, $attributes[$attr_code], $includeAttr);
			if(!empty($value)){
			    $result[$attr_code] = array(
				'label'=> $label,
				'value'=> $value,
				'code' => $attr_code
			    );
			}
			
		    }
		}
		
	    }
	}else if(!empty($product)){
	    $helper = Mage::helper("fahasa_customer");
	    $cache_type = "product_additional";
	    $cache_id = $cache_type.':product:'.$product->getEntityId().":additional:".serialize($includeAttr);
	    $data = $helper->getPublicStore($cache_id, $cache_type);
	    if(empty($data)){
		$attributes = $product->getAttributes();
		foreach ($attributes as $attribute) {
		    if ($attribute->getIsVisibleOnFront() && in_array($attribute->getAttributeCode(), $includeAttr)) {
			$value = $attribute->getFrontend()->getValue($product);

			if (!$product->hasData($attribute->getAttributeCode())) {
			    $value = Mage::helper('catalog')->__('N/A');
			} elseif ((string)$value == '') {
			    $value = Mage::helper('catalog')->__('No');
			} elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
			    $value = Mage::app()->getStore()->convertPrice($value, true);
			}


			if (is_string($value) && strlen($value)) {
			    if($attribute->getAttributeCode() == 'supplier'){
				$value = Mage::helper("fahasa_catalog")->getDataSupplier($value);
			    }
			    if(!empty($value)){
				$result[$attribute->getAttributeCode()] = array(
				    'label' => $attribute->getStoreLabel(),
				    'value' => $value,
				    'code'  => $attribute->getAttributeCode()
				);
			    }
			}
		    }
		}
		$helper->setPublicStore($cache_id, $data, $cache_type);
	    }
	}
	
        
        return $result;
    }
    
    public function getProductExpectedMsg($product, $soon_release = null, $expected_date = null, $book_release_date = null){
	$result = array(0 => '', 1 => '');
	$lang = array(
	    0 => 'Coming soon',
	    1 => 'Date of stock at Fahasa %s',
	    2 => 'The date the Publisher plans to issue %s'
	);
	
	if(!empty($product)){
	    $soon_release = $product->getSoonRelease();
	    $expected_date = $product->getExpectedDate();
	    $book_release_date = $product->getBookReleaseDate();
	}
	try{
	    if(!empty($soon_release)){
		if($soon_release = 1){
		    if(!empty($book_release_date) && !empty($expected_date)){
			if(strtotime($book_release_date) < strtotime($expected_date)){
			    $book_release_date = null;
			}
		    }
		    if(!empty($book_release_date) && time() <= strtotime($book_release_date)){
			$result[0] = $this->__('The date the Publisher plans to issue %s', date('d/m/Y', strtotime($book_release_date)));
			$result[1] = str_replace('%s',date('d/m/Y', strtotime($book_release_date)), $lang[2]);
		    }else if(!empty($expected_date) && time() <= strtotime($expected_date)){
			$result[0] = $this->__('Date of stock at Fahasa %s', date('d/m/Y', strtotime($expected_date)));
			$result[1] = str_replace('%s',date('d/m/Y', strtotime($expected_date)), $lang[1]);
		    }else{
			$result[0] = $this->__("Coming soon");
			$result[1] = $lang[0];
		    }
		}
	    }
	}catch(Exception $ex) {}
	
	return $result;
    }
    
    public function cleanBug($param){
	if(empty($param)){return $param;};
	if(is_numeric($param)){return $param;};
	if(!is_string($param)){return $param;};
	
	$utf8 = array(
	    '/ç/'           =>   'c',
	    '/Ç/'           =>   'C',
	    '/ñ/'           =>   'n',
	    '/Ñ/'           =>   'N',
	    '/<|>|;|\(|\)|=|\[|\]|{|}/' =>   ' ',
	    '/–/'           =>   '-', // UTF-8 hyphen to "normal" hyphen
	    '/[’‘‹›‚]/u'    =>   ' ', // Literally a single quote
	    '/[“”«»„]/u'    =>   ' ', // Double quote
	    '/ /'           =>   ' ', // nonbreaking space (equiv. to 0x160)
	);
	$result =  preg_replace(array_keys($utf8), array_values($utf8), $param);
	return $result;
    }
    public function checkOrderBy($type, $order){
	$order_list = $this->getOrderList($type);
	if(!in_array($order, $order_list)){
	   $order = $this->getOrder(($type=='series')?true:false);
	}
	return $order;
    }
    public function getOrderList($type){
	$result = array();
	switch ($type){
	    case 'product':
		$result = array(
			"Weekly BestSeller"=>"num_orders",
			"Monthly BestSeller"=>"num_orders_month",
			"Yearly BestSeller"=>"num_orders_year",
			"Weekly Trending"=>"product_view",
			"Monthly Trending"=>"product_view_month",
			"Yearly Trending"=>"product_view_year",
			"Discount"=>"discount_percent",
			"Sale Price"=>"min_price",
			"Created At"=>"created_at",
			);
		break;
	    case 'series':
		$result = array(
			"Top Subscribes" =>"top_subscribes",
			"Weekly BestSeller"=>"num_orders",
			"Monthly BestSeller"=>"num_orders_month",
			"Yearly BestSeller"=>"num_orders_year",
			"Weekly Trending"=>"product_view",
			"Monthly Trending"=>"product_view_month",
			"Yearly Trending"=>"product_view_year",
			"Discount"=>"discount_percent",
			"Sale Price"=>"min_price",
			"Created At"=>"created_at",
			);
		break;
	}
	return $result;
    }
    public function refreshDynParam($product_list){
	if(empty($product_list) || sizeof($product_list) <= 0){return $data;}
	    $series_ids = [];
	    $product_ids = [];
	    try{
		foreach ($product_list as $key=>$item){
		if(!empty($item['type_id']) && !empty($item['series_id'])){
		    if($item['type_id'] == 'series'){
			array_push($series_ids, $item['series_id']);
		    }else{
			array_push($product_ids, $item['product_id']);
		    }
		}else{
		    array_push($product_ids, $item['product_id']);
		}
	    }

	    //refresh series
	    if(sizeof($series_ids) > 0){
		$series_data = Mage::helper('seriesbook')->getSeriesDataExtraFromDB('series_id', $series_ids);
		if(!empty($series_data)){
		    foreach ($product_list as $key=>$item){
			if(!empty($item['type_id']) && !empty($item['series_id'])){
			    if($item['type_id'] == 'series'){
				$series_item = $series_data[$item['series_id']];
				if(!empty($series_item['subscribes'])){
				    $item['subscribes'] = $series_item['subscribes'];
				    $item['episode'] = $series_item['label']." mới nhất: ".$series_item['episode'];
				    $product_list[$key] = $item;
				}
			    }
			}
		    }
		}
	    }
	}catch (Exception $ex) {}
	return $product_list;
    }
    public function getBundlePrice($product){
	if(!Mage::helper('discountlabel')->getBundlePrice($product)){
	    if($product->getFinalPrice()) {
		$final_price = $product->getFinalPrice();
	    } elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
		$final_price = Mage::getModel('bundle/product_price')->getTotalPrices($product,'min',1);
		$product->setDate('final_price', $final_price);
	    }
	}
	return $product;
    }
    protected function getAttributeOption($key, $attribute, $filters, $option_max){
	$attr = array(
	    'id' => $attribute['id'],
	    'code' => $attribute['code'],
	    'label' => $attribute['label'],
	    'param' => $attribute['param'],
	    'options' => array()
	);
	
	$option_count = 0;
	$options = array();
	$options_other = array();
	if(!empty($attribute['options'])){
	    if(array_key_exists($key, $filters)){
		$filters_array = explode('_', $filters[$key]);
		foreach($attribute['options'] as $option) {
		    if(in_array($option['id'], $filters_array)){
			$option['selected'] = true;
			$options[] = $option;
			$option_count++;
		    }else{
			$options_other[] = $option;
		    }
		}
		if($option_count < $option_max && sizeof($options_other) > 0){
		    foreach($options_other as $option) {
			$options[] = $option;
			$option_count++;
			if($option_count >= $option_max){
			    goto done;
			}
		    }
		}
		done:
		$attr['options'] = $options;
	    }else{
		foreach($attribute['options'] as $option) {
		    $options[] = $option;
		    $option_count++;
		    if($option_count >= $option_max){
			goto done2;
		    }
		}
		done2:
		$attr['options'] = $options;
	    }
	}
	return $attr;
    }
    public function getLanguagesList($page){
	$queryfier = Mage::getStoreConfig('bubble_queryfier/suffix_js_css/suffix');
	$skin_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN, true);
	
	$languages = array();
	
	//public
	switch ($page){
	    case 'cart':
	    case 'payment':
		$languages['ico_couponblue'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_couponblue.svg?q='.$queryfier;
		$languages['ico_coupongreen'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_coupongreen.svg?q='.$queryfier;
		$languages['ico_promotiongreen'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_promotiongreen.svg?q='.$queryfier;
		$languages['ico_promotionblue'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_promotionblue.svg?q='.$queryfier;
		$languages['ico_giftblue'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_giftblue.svg?q='.$queryfier;
		$languages['ico_giftgreen'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_giftgreen.svg?q='.$queryfier;
		$languages['ico_check'] = $skin_url.'frontend/ma_vanese/fahasa/images/promotion/ico_check.svg?q='.$queryfier;
		$languages['label_expired'] = $skin_url.'frontend/ma_vanese/fahasa/images/promotion/label_expired.svg?q='.$queryfier;
		$languages['label_saphet'] = $skin_url.'frontend/ma_vanese/fahasa/images/promotion/label_saphet.svg?q='.$queryfier;
		$languages['ico_ewallet'] = $skin_url.'frontend/ma_vanese/fahasa/images/promotion/ico_ewallet.svg?q='.$queryfier;
		$languages['ico_freeship'] = $skin_url.'frontend/ma_vanese/fahasa/images/promotion/ico_freeship.svg?q='.$queryfier;
		$languages['ico_gift'] = $skin_url.'frontend/ma_vanese/fahasa/images/promotion/ico_gift.svg?q='.$queryfier;
		$languages['ico_promotion'] = $skin_url.'frontend/ma_vanese/fahasa/images/promotion/ico_promotion.svg?q='.$queryfier;
		$languages['ico_gift'] = $skin_url.'frontend/ma_vanese/fahasa/images/promotion/ico_gift.svg?q='.$queryfier;
		$languages['progress_cheat_img'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico-cheatprogresss.svg?q='.$queryfier;
		$languages['ico_down_orange'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_down_orange.svg?q='.$queryfier;
		$languages['ico_couponemty'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_couponemty.svg?q='.$queryfier;
		$languages['no_promotion'] = $this->__("Don't have promotion");
		$languages['selling_out'] = $this->__("Selling out");
		$languages['Voucher_code'] = $this->__("Voucher code");
		$languages['detail'] = $this->__("Detail");
		$languages['viewmore'] = $this->__("View more");
		$languages['viewless'] = $this->__("View less");
		$languages['matched_title'] = $this->__("My rewards");
		$languages['notmatched_title'] = $this->__("Get more rewards");
		$languages['matched_voucher_title'] = $this->__("My rewards ");
		$languages['notmatched_voucher_title'] = $this->__("Get more rewards ");
		$languages['apply'] = $this->__("Apply");
		$languages['applied'] = $this->__("Applied");
		$languages['code_applied'] = $this->__("Coupon code applied");
		$languages['code_canceled'] = $this->__("Coupon code has been canceled");
		$languages['cancel'] = $this->__("Cancel");
		$languages['cancel_apply'] = $this->__(" Cancel ");
		$languages['copy_code'] = $this->__("Copy code");
		$languages['buy_more'] = $this->__("Buy More");
		$languages['error_msg'] = '*%s Điều kiện không thỏa';
		$languages['buy_more_msg'] = 'Mua thêm %sđ để nhận mã';
		$languages['coupon'] = $this->__("Coupon");
		$languages['delivery_code'] = $this->__("Delivery code");
		$languages['payment_promotion'] = $this->__("Payment promotion");
		$languages['other_promotion'] = $this->__("Other promotion");
		$languages['max_apply'] = $this->__("Max apply: ");
		$languages['apply_at_e_wallet'] = $this->__("Apply at e-wallets");
		$languages['auto_apply'] = $this->__("Automatically applied when conditions are met");
		$languages['ico_delete_green'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_delete_green.svg?q='.$queryfier;
		$languages['ico_delete_orange'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_delete_orange.svg?q='.$queryfier;
		$languages['buy_more_for_promotion'] = $this->__("Buy more %s for promotion");
		$languages['coupon_info'] = '<div class="fhs_label_note"><div class="fhs_label_coupon_label_info"><div>'.$this->__('Can apply multi promotion').' </div><div class="fhs_tooltip" style="margin-left: 0.25em;"><img src="'.$skin_url."frontend/ma_vanese/fahasa/images/promotion/ico-alert-grey.svg?q=".$queryfier.'"/><span>'.$this->__('Apply up 1 discount code <br/>and 1 freeship code').'</span></div></div></div>';
                $languages['items'] = $this->__("items");
		$languages['rewards'] = $this->__('Rewards');
                
		break;
            case 'checkout_stock':
                $languages['choose_replace_product'] = $this->__('Choose alternative product');
                $languages['product_not_available'] = $this->__('Products are not available in your area');
                $languages['product_recommendation'] = $this->__('Product Recommendation');
                $languages['choose_replace'] = $this->__('Choose replace');
                $languages['outstock_title'] = $this->__('Products are currently not available at the nearest warehouse');
                $languages['outstock_explain'] = $this->__('Due to social distancing, some products will be difficult to deliver to %s. To shorten the delivery time, you can choose another alternative product available in your area');
                $languages['slow_delivery'] = $this->__('*Delivery may be slower than expected');
                $languages['notice_slow_delivery'] = $this->__('Some products may be slow to deliver in your selected area.');
                $languages['viewnow'] = $this->__("View now");
                $languages['continue_payment'] = $this->__('Continue to pay');
                break;
	}
	
	//private
	switch ($page){
	    case 'product_view':
		$languages['choose_district'] = $this->__('Choose district');
		$languages['choose_wards'] = $this->__('Choose wards');
		break;
	    case 'payment':
		$languages['locale'] = Mage::app()->getLocale()->getLocaleCode();
		$languages['choose_country'] = $this->__('Choose country');
		$languages['choose_city'] = $this->__('Choose province/City');
		$languages['choose_district'] = $this->__('Choose district');
		$languages['choose_wards'] = $this->__('Choose wards');
		$languages['edit'] = $this->__('Edit');
		$languages['delete'] = $this->__('Delete');
		$languages['emptyshippingmethod'] = $this->__('Sorry, no quotes are available for this order at this time.');
		$languages['delivery_date'] = $this->__('Delivery date:');
		$languages['email_exist'] = $this->__('%s registered, %slogin</span> now.','Email', "<span class=\"fhs_login_button\" onclick=\"fhs_onestepcheckout.fillLoginName(this); fhs_account.showLoginPopup('login');\$jq('#login_password').focus(); \">");
		$languages['telephone_exist'] = $this->__('%s registered, %slogin</span> now.',$this->__('Phone number'), "<span class=\"fhs_login_button\" onclick=\"fhs_onestepcheckout.fillLoginName(this); fhs_account.showLoginPopup('login');\$jq('#login_password').focus(); \">");
		$languages['quantity'] = $this->__('Quantity');
		$languages['ico_promo_sp'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_promo_sp.svg?q='.$queryfier;
		$languages['processing'] = $this->__('Your order has been received and is processing');
		$languages['timeout'] = $this->__('Fahasa is processing your order, please wait a moment and try again to get result');
		$languages['notempty'] = $this->__("This infomation can't empty");
		$languages['yes'] = $this->__("Yes");
		$languages['overload'] = $this->__("Can't connect to server, please try again.");
		$languages['exp'] = $this->__("EXP");
		$languages['loading_img'] = $skin_url.'frontend/ma_vanese/fahasa/images/fpointstore/loading.png?q='.$queryfier;
		$languages['ico_viewmore'] = $skin_url.'frontend/ma_vanese/fahasa/images/ico_seemore_red.svg?q='.$queryfier;
		$languages['event_detail'] = $this->__("Event detail");
		$languages['choose_delivery_time'] = $this->__('Choose a delivery time');
		$languages['terms_conditions'] = $this->__('Terms & Conditions');
		break;
	    case 'login':
		$languages['show'] = $this->__('Show ');
		$languages['hide'] = $this->__('Hide');
		$languages['phoneinvalid'] = $this->__('Phone number invalid');
                $languages['phoneinvalid10'] = $this->__('Phone number must be 10 number');
		$languages['phoneexist'] = $this->__('Phone number already exist');
		$languages['emailinvalid'] = $this->__('Email invalid');
		$languages['taxcodeinvalid'] = $this->__('Taxcode invalid');
		$languages['otpinvalid'] = $this->__('OTP invalid');
		$languages['notemail'] = $this->__('This is not an email address!');
		$languages['notempty'] = $this->__("This infomation can't empty");
		$languages['nopass'] = $this->__('Enter a valid password!');
		$languages['30char'] = $this->__('Password must be 30 characters or less!');
		$languages['over200char'] = $this->__("Can't over 200 characters");
		$languages['notsame'] = $this->__('Passwords are not same!');
		$languages['wrong'] = $this->__('Phone number/Email or Password is wrong!');
		$languages['registered'] = $this->__('This email is already registered!');
		$languages['tryagain'] = $this->__('An error occurred, please try again');
		$languages['login'] = $this->__('Login');
		$languages['recoverypassword'] = $this->__('Recovery');
		$languages['dateinvalid'] = $this->__('Date invalid');
		$languages['change'] = $this->__('Change');
		$languages['loginfail'] = $this->__('Login failed');
		$languages['2word'] = $this->__('Full name must have 2 words above');
		$languages['copied'] = $this->__('Copied');
		$languages['close'] = $this->__('Close');
		$languages['img_loading'] = $skin_url."frontend/ma_vanese/fahasa/images/ring_loader.svg";
		$languages['close_img'] = $skin_url . 'frontend/ma_vanese/fahasa/images/ico_delete_gray.svg?q='.$queryfier;
		$languages['locale'] = Mage::app()->getLocale()->getLocaleCode();
		$languages['add_to_cart'] = $this->__("Add to cart");
		$languages['fail_icon'] = $skin_url.'frontend/ma_vanese/fahasa/images/logo-alert-fail.png?q='.$queryfier;
		$languages['out_of_stock'] = $this->__('Product temporarily out of stock');
		$languages['comingsoon'] = $this->__('comingsoon');
		$languages['cancel'] = $this->__("Cancel");
		$languages['add_to_cart_success'] = $this->__("Add to cart success");
		break;
	    case 'catalog':
		$languages['showless'] = $this->__('Show Less');
		$languages['showmore'] = $this->__('Show More');
		$languages['removethisitem'] = $this->__('Remove This Item');
		$languages['price'] = $this->__('Price');
		$languages['homepage'] = $this->__('HOME');
		$languages['homepage_title'] = $this->__('Go to Home Page');
		$languages['alert_onproduct'] = $this->__('There are no products matching the selection.');
		$languages['more-price'] = $this->__('above');
		$languages['delete_all'] = $this->__('Clear Filters');
		$languages['comingsoon'] = $this->__('comingsoon');
		break;
	}
	
	return $languages;
    }
    
    public function getPromotionPopupHtml($event_cart, $languages, $eventCart_keys){
	$result = '';
	try{
	    foreach($eventCart_keys as $key_name){
		if(!empty($event_cart[$key_name])){
		    $result .= $this->getPromotonListHtml($key_name, $event_cart[$key_name], $languages);
		}
	    }
	}catch(Exception $ex) {$result = '';}
	if(empty($result)){
	    $result = "<div class=\"fhs-event-promo-list-empty\"><img src=\"".$languages['ico_couponemty']."\"/><div>".$languages['no_promotion']."</div></div>";
	}
	return $result;
    }
    public function getPromotonListHtml($key_name, $list, $languages, $is_show_title = true){
	$result = '';
	try{
	    $list_html = '';
	    $list_show = '';
	    $list_more = '';
	    $list_expired = array();
	    $title = '';
	    
	    if($is_show_title){
		switch($key_name){
		    case 'affect_coupons':
			$title = '<div class="fhs-event-promo-list-title">'
				    .'<span>'.$languages['coupon'].'</span>'
				    .'<span class="fhs_label_note" style="margin-left: 8px;">'.$languages['max_apply'].'1</span>'
				.'</div>';
			break;
		    case 'affect_freeships':
			$title = '<div class="fhs-event-promo-list-title">'
				    .'<span>'.$languages['delivery_code'].'</span>'
				    .'<span class="fhs_label_note" style="margin-left: 8px;">'.$languages['max_apply'].'1</span>'
				.'</div>';
			break;
		    case 'affect_payments':
			$title = '<div class="fhs-event-promo-list-title">'
				    .'<span>'.$languages['payment_promotion'].'</span>'
				    .'<span class="fhs_label_note" style="margin-left: 8px;">'.$languages['apply_at_e_wallet'].'</span>'
				.'</div>';	    
			break;
		    default:
			$title = '<div class="fhs-event-promo-list-title">'
				    .'<span>'.$languages['other_promotion'].'</span>'
				    .'<span class="fhs_label_note" style="margin-left: 8px;">'.$languages['auto_apply'].'</span>'
				.'</div>';	
		}
	    }
	    
	    $i = 0;
	    foreach($list as $key_type=>$item_type){
		if(empty($item_type)){continue;}
		if($key_type != 'matched' && $key_type != 'not_matched'){continue;}

		foreach($item_type as $key_index=>$item){
		    $expired_class = '';
		    $almost_run_out_class = '';
		    $almost_run_out = '';
		    $icon = '';
		    $item_class = '';
		    if($is_show_title){
			if($item['matched']){
			    $key_type = 'matched';
			}else{
			    $key_type = 'not_matched';
			}
		    }

		    $keys = array(
			'key_type' => $key_type,
			'key_name' =>$key_name,
			'key_index' =>$key_index,
		    );
		    if(!empty($item['is_expired'])){
			$expired_class = 'expired';
		    }
		    if(empty($item['is_expired']) && $item['almost_run_out']){
			$almost_run_out = '<div class="label_expired"><img src="'.$languages['label_saphet'].'"/></div>';
		    }

		    switch($item['event_type']){
			//coupon - yellow
			case 1: 
			case 5: 
			    $item_class = 'fhs-event-promo-list-item-coupon';
			    $icon = '<div><img src="'.$languages['ico_promotion'].'"/></div>';
			    break;
			//coupon freeship - green
			case 4:
			case 6:
			    $item_class = 'fhs-event-promo-list-item-freeship';
			    $icon = '<div><img src="'.$languages['ico_freeship'].'"/></div>';
			    break;
			case 3: //payment - blue
			    $item_class = 'fhs-event-promo-list-item-payment';
			    $icon = '<div><img src="'.$languages['ico_ewallet'].'"/></div>';
			    break;
			default: //other - purple
			    $item_class = 'fhs-event-promo-list-item-other';
			    $icon = '<div><img src="'.$languages['ico_gift'].'"/></div>';

		    }
		    
		    $list_item = '<div class="fhs-event-promo-list-item '.$key_type.' '.($item_class?$item_class:'').' '.($expired_class?$expired_class:'').'">'
					.$this::COUPON_BG_SVG
					.$icon
					.'<div class="fhs-event-promo-item '.$expired_class.'">'.$this->getPromotionItemHtml($item, $keys, $languages, (!$is_show_title), $is_show_title).'</div>'
					.$almost_run_out
					.'</div>';
		    
		    if(empty($item['is_expired'])){
			if($i < $this::EVENT_CART_LIMIT || !$is_show_title){
			    $list_show .=  $list_item;
			}else{
			    $list_more .= $list_item;
			}
			$i++;
		    }else{
			$list_expired[] = $list_item;
		    }
		}
		if(sizeof($list_expired) > 0){
		    foreach ($list_expired as $list_item){
			if($i < $this::EVENT_CART_LIMIT || !$is_show_title){
			    $list_show .=  $list_item;
			}else{
			    $list_more .= $list_item;
			}
			$i++;
		    }
		}
	    }

	    if(!empty($list_show)){
		$list_html = '<div class="fhs-event-promo-list">'
			.'<!-- promotion '.$key_name.' -->'
			.$title
			.$list_show;
		if(!empty($list_more)){
		    $list_html .= '<div id="collapse_promo_list_'.$key_name.'" class="panel-collapse collapse out">'
				.$list_more
				.'</div>'
				.'<div class="fhs-event-promo-list-viewmore" onclick="setTimeout(function(){fhs_account.sizeCouponBg();},100);"><a class="collapse collapsed" data-toggle="collapse" href="#collapse_promo_list_'.$key_name.'"><span class="text-viewmore">'.$languages['viewmore'].'</span><span class="text-viewless">'.$languages['viewless'].'</span><img src="'.$languages['ico_down_orange'].'"/></a></div>';
		}
		$list_html .= '</div>';
		if(!empty($result)){
		    $result .= '<div class="fhs-event-promo-list-line"></div>';
		}
		$result .= $list_html;
	    }
	}catch(Exception $ex) {$result = '';}
	return $result;
    }
    public function getPromotionCartHtml($event_cart, $languages){
	$result = '';
	try{
	    foreach($event_cart as $key=>$item){
		$keys = array(
		    'key_type' => $item['key_type'],
		    'key_name' =>$item['key_name'],
		    'key_index' =>$item['key_index'],
		);
		
		$expired_class = '';
		if(!empty($item['is_expired'])){
		    $expired_class = 'expired';
		}
		$result .= '<div class="fhs-event-promo-item fhs-event-promo-item-line '.$expired_class.'">'
			    .$this->getPromotionItemHtml($item, $keys, $languages)
			    .'</div>';
	    }
	}catch(Exception $ex) {$result = '';}
	return $result;
    }
    public function getPromotionItemHtml($item, $keys, $languages, $is_outside = true, $is_show_btn = true){
	$result = '';
	    try{
		$error_str = '';
		$title_2 = '';
		$progress_bar = '';
		$btn_apply = '';
		$class_content_detail = "class='fhs-event-promo-list-item-content' onclick=\"fhs_promotion.showEventCartDetail(this, '".$keys['key_name']."','".$keys['key_index']."','".$keys['key_type']."',".($is_outside?'true':'false').",".($is_show_btn?'true':'false').");\"";
		$expired = '';
		
		if(!empty($item['title_2'])){
		    $title_2 = "<div class='fhs-event-promo-list-item-content-description'>".$item['title_2']."</div>";
		}

		if(empty($item['is_expired'])){
		    if(!empty($item['error'])){
			$error_str .= '<div class="fhs-event-promo-error">'.str_replace('%s', sizeof($item['error']), $languages['error_msg']).'</div>';
		    }
		    switch($item['event_type']){
			//coupon - yellow
			case 1: 
			case 5: 
			//coupon freeship - green
			case 4:
			case 6:
			    if ($item['applied']){
				$btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'.$languages['cancel_apply'].'" coupon="'.$item['coupon_code'].'" apply="0" class="fhs-btn-view-promo-coupon applied"><span>'.$languages['cancel_apply'].'</span></button>';
				$progress_bar = '<div class="fhs-event-promo-item-msg"><img src="'.$languages['ico_check'].'"/><span style="padding-left: 4px; color: #2F80ED;">'.$languages['applied'].'</span></div>';
			    }else{
				if($item['matched']){
				    $btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'.$languages['apply'].'" coupon="'.$item['coupon_code'].'" apply="1" class="fhs-btn-view-promo-coupon" ><span>'.$languages['apply'].'</span></button>';
				}else{
				    $btn_apply = '<a href="/'.$item['page_detail'].'"><button type="button" title="'.$languages['buy_more'].'" class="fhs-btn-view-promo"><span>'.$languages['buy_more'].'</span></button></a>';
				    if(!empty($item['need_total'])){
					$progress_bar = '<div class="fhs-event-promo-item-progress"><hr '.(($item['matched'] != 0)?"class=\'progress-success\'" : "").' style="width: '.$item['reach_percent'].'%;'.'"/><img class="progress-cheat" src="'.$languages['progress_cheat_img'].'"/></div>'
							.'<div class="fhs-event-promo-item-minmax">'
							.'<span>'.(!empty($item['need_total'])?(str_replace('%s', $item['need_total'], $languages['buy_more_for_promotion'])):'').'</span>'
							.'<span>'.(!empty($item['max_total'])?$item['max_total']:'').'</span>'
							.'</div>';
				    }
				}
			    }
			    break;
			case 3: //payment - blue
			    if($item['matched']){
				if(!empty($item['coupon_code'])){
				    $btn_apply = '<button type="button" onclick="fhs_account.copyCouponCode(\''.$item['coupon_code'].'\');" title="'.$languages['copy_code'].'" coupon="'.$item['coupon_code'].'" class="fhs-btn-view-promo-coupon" ><span>'.$languages['copy_code'].'</span></button>';
				}
			    }else{
				$btn_apply = '<a href="/'.$item['page_detail'].'"><button type="button" title="'.$languages['buy_more'].'" class="fhs-btn-view-promo"><span>'.$languages['buy_more'].'</span></button></a>';
				if(!empty($item['need_total'])){
				    $progress_bar = '<div class="fhs-event-promo-item-progress"><hr '.(($item['matched'] != 0)?"class=\'progress-success\'" : "").' style="width: '.$item['reach_percent'].'%;'.'"/><img class="progress-cheat" src="'.$languages['progress_cheat_img'].'"/></div>'
						    .'<div class="fhs-event-promo-item-minmax">'
						    .'<span>'.(!empty($item['need_total'])?(str_replace('%s', $item['need_total'], $languages['buy_more_for_promotion'])):'').'</span>'
						    .'<span>'.(!empty($item['max_total'])?$item['max_total']:'').'</span>'
						    .'</div>';
				}
			    }
			    break;
			default: //other - purple
			    if($item['applied']){
				$progress_bar = '<div class="fhs-event-promo-item-msg"><img src="'.$languages['ico_check'].'"/><span style="padding-left: 4px; color: #2F80ED;">'.$languages['applied'].'</span></div>';
			    }else{
				if($item['matched']){
				    $btn_apply = '<button type="button" onclick="fhs_promotion.applyCoupon(this);" title="'.$languages['apply'].'" coupon="'.$item['coupon_code'].'" apply="1" class="fhs-btn-view-promo-coupon" ><span>'.$languages['apply'].'</span></button>';
				}else{
				    $btn_apply = '<a href="/'.$item['page_detail'].'"><button type="button" title="'.$languages['buy_more'].'" class="fhs-btn-view-promo"><span>'.$languages['buy_more'].'</span></button></a>';
				    if(!empty($item['need_total'])){
					$progress_bar = '<div class="fhs-event-promo-item-progress"><hr '.(($item['matched'] != 0)?"class=\'progress-success\'" : "").' style="width: '.$item['reach_percent'].'%;'.'"/><img class="progress-cheat" src="'.$languages['progress_cheat_img'].'"/></div>'
							.'<div class="fhs-event-promo-item-minmax">'
							.'<span>'.(!empty($item['need_total'])?(str_replace('%s', $item['need_total'], $languages['buy_more_for_promotion'])):'').'</span>'
							.'<span>'.(!empty($item['max_total'])?$item['max_total']:'').'</span>'
							.'</div>';
				    }
				}
			    }
		    }
		}else{
		    $expired = '<div class="label-expired">'
				    .'<img src="'.$languages['label_expired'].'" />'
				.'</div>';
		}
		
		$result = '<div>'
				."<div ".$class_content_detail.">"
				    .'<div>'
					.'<div class="fhs-event-promo-list-item-content-title">'
					    .$item['title']
					.'</div>'
					.'<div class="fhs-event-promo-list-item-detail fhs_blue_link">'.$languages['detail'].'</div>'
				    .'</div>'
				    .$title_2
				    .$error_str
				.'</div>'
			    .'</div>';
			
		
		if(!empty($progress_bar) || !empty($btn_apply) || !empty($expired)){
		    $result .= 
			    '<div>'
				.'<div class="fhs-event-promo-item-progress-bar">'
				    .$progress_bar
				.'</div>'
				.'<div>'
				    .$btn_apply
				.'</div>'
			    .'</div>'
			    .$expired;;
		}
	}catch(Exception $ex) {$result = '';}
	return $result;
    }
    
    public function getAttributeFilter($attributes, $is_frontend = false){
	$result = array();
	
	if($is_frontend){
	    $attributes_keys = Mage::getStoreConfig('catalog/product_redis/product_keys_front');
	    $keys = explode(",", $attributes_keys);
                        
	    if(!empty($attributes)){
		//filter supplier
		if(!empty($attributes['supplier']) && !empty($attributes['supplier_list'])){
		    $supplier = $attributes['supplier'];
		    if(is_array($supplier)){
			if(!empty($supplier[0]['value']) && !empty($supplier[0]['url'])){
			    unset($attributes['supplier_list']);
			}else{
			    unset($attributes['supplier']);
			}
		    }else{
			if(!empty($supplier['value']) && !empty($supplier['url'])){
			    unset($attributes['supplier_list']);
			}else{
			    unset($attributes['supplier']);
			}
		    }
		    
		}
		foreach($keys as $key){
		    if(array_key_exists($key, $attributes)){
			$test = $attributes[$key];
			if(!$this->isEmpty($attributes[$key])){
			    $result[$key] = $attributes[$key];
			}
		    }
		}
	    }
	}else{
	    foreach($attributes as $key=>$item){
		if(!$this->isEmpty($item,$key)){
		    $result[$key] = $item;
		}
	    }
	}
	
	return $result;
    } 
    public function getAttributeTranslate($store_id, $attr_key = '', $attr_val = ''){
	$translates = array();
	if($store_id != 1){
	    if(empty($attr_val)){
		$attributes_keys = Mage::getStoreConfig('catalog/product_redis/product_keys_en_translate_label');
		if(!empty($attributes_keys)){
		    $attributes_array = explode(",", $attributes_keys);
		    foreach($attributes_array as $item){
			$tmp = explode('=', $item);
			$translates[$tmp[0]] = $tmp[1];
		    }
		}
		
		
//		$translates = array(
//		    'qty_of_page'=>'Quantity of Page',
//		    'publish_year'=>'Publish Year',
//		    'size'=>'Size',
//		    'author'=>'Author',
//		    'publisher'=>'Publisher',
//		    'supplier'=>'Supplier',
//		    'model_name'=>'Model Name',
//		    'translator'=>'Translator',
//		    'internal_supply'=>'Internal Supply',
//		    'pieces'=>'Pieces',
//		    'number'=>'Number',
//		    'case_diameter'=>'Case diameter',
//		    'genres'=>'Genres',
//		    'highlights'=>'highlights',
//		    'manufacturer'=>'Array',
//		    'color'=>'Array',
//		    'book_layout'=>'Book Layout',
//		    'warranty'=>'Warranty',
//		    'origin'=>'Origin',
//		    'material'=>'Material',
//		    'supplier_list'=>'Supplier',
//		    'age'=>'Age',
//		    'reading_level'=>'Reading Level',
//		    'ink_color'=>'Ink Color',
//		    'noi_san_xuat'=>'Made in',
//		    'languages'=>'Language',
//		    'specification'=>'Specification',
//		    'warning'=>'Safety Warning',
//		    'directions'=>'Directions',
//		    'stages'=>'stages',
//		    'weight'=>'Weight',
//		    'expected_date'=>'Expected Date',
//		    'book_release_date'=>'Book Release Date',
//		    'description'=>'Description',
//		    'sku'=>'Product code'
//		);
	    }else{
		return $attr_val;
	    }
	}else{
	    if(empty($attr_val)){
		$attributes_keys = Mage::getStoreConfig('catalog/product_redis/product_keys_vn_translate_label');
		if(!empty($attributes_keys)){
		    $attributes_array = explode(",", $attributes_keys);
		    foreach($attributes_array as $item){
			$tmp = explode('=', $item);
			$translates[$tmp[0]] = $tmp[1];
		    }
		}
		
//		$translates = array(
//		    'qty_of_page'=>'Số trang',
//		    'publish_year'=>'Năm XB',
//		    'size'=>'Kích Thước Bao Bì',
//		    'author'=>'Tác giả',
//		    'publisher'=>'NXB',
//		    'supplier'=>'Tên Nhà Cung Cấp',
//		    'model_name'=>'Số Hiệu Sản Phẩm',
//		    'translator'=>'Người Dịch',
//		    'internal_supply'=>'Nhà Cung Cấp Nội Bộ',
//		    'pieces'=>'Số Mảnh Ghép',
//		    'number'=>'Số Lượng/ Bộ',
//		    'case_diameter'=>'Kích thước mặt',
//		    'genres'=>'Genres',
//		    'highlights'=>'Điểm Nổi Bật',
//		    'manufacturer'=>'Thương Hiệu',
//		    'color'=>'Màu sắc',
//		    'book_layout'=>'Hình thức',
//		    'warranty'=>'Bảo hành',
//		    'origin'=>'Xuất Xứ Thương Hiệu',
//		    'material'=>'Chất liệu',
//		    'supplier_list'=>'Nhà Cung Cấp',
//		    'age'=>'Độ Tuổi',
//		    'reading_level'=>'Cấp Độ/ Lớp',
//		    'ink_color'=>'Màu Mực',
//		    'noi_san_xuat'=>'Nơi Gia Công & Sản Xuất',
//		    'languages'=>'Ngôn Ngữ',
//		    'specification'=>'Thông Số Kỹ Thuật',
//		    'warning'=>'Thông Tin Cảnh Báo',
//		    'directions'=>'Hướng Dẫn Sử Dụng',
//		    'stages'=>'Cấp Học',
//		    'weight'=>'Trọng lượng (gr)',
//		    'expected_date'=>'Dự Kiến Có Hàng',
//		    'book_release_date'=>'Ngày Dự Kiến Phát Hành',
//		    'description'=>'Mô tả',
//		    'sku'=>'Mã hàng',
//		    'Paperback'=>'Bìa Mềm'
//		);
	    }else{
		$attributes_keys = Mage::getStoreConfig('catalog/product_redis/product_keys_translate_value');
		if(!empty($attributes_keys)){
		    $attributes_array = explode(",", $attributes_keys);
		    foreach($attributes_array as $item){
			$tmp = explode('=', $item);
			$translates[$tmp[0]] = $tmp[1];
		    }
		}
		
//		$translates = array(
//		    'Puppet Book'=>'Sách Rối Tay',
//		    'Bath Book'=>'Sách Không Thấm Nước',
//		    'Big Book'=>'Sách Khổ Lớn',
//		    'Book with CD'=>'Sách Kèm Đĩa',
//		    'Box Set'=>'Bộ Hộp',
//		    'Cloth Book'=>'Sách Vải',
//		    'Convertible Book'=>'Sách Biến Đổi Mô Hình',
//		    'Hardback'=>'Bìa Cứng',
//		    'Leather'=>'Bìa Da',
//		    'Map'=>'Bản Đồ',
//		    'Paperback'=>'Bìa Mềm',
//		    'Pop-Up Book'=>'Sách Nổi',
//		    'Print Magazine'=>'Tạp Chí',
//		    'Puzzle'=>'Xếp Hình',
//		    'Sheet Music'=>'Bản Nhạc',
//		    'Level 1'=>'Cấp Độ 1',
//		    'Level 2'=>'Cấp Độ 2',
//		    'Level 3'=>'Cấp Độ 3',
//		    'Level 4'=>'Cấp Độ 4',
//		    'Level 5'=>'Cấp Độ 5',
//		    'Level 6'=>'Cấp Độ 6',
//		);
		
		if(array_key_exists($attr_val, $translates)){
		    return $translates[$attr_val];
		}else{
		    return $attr_val;
		}
	    }
	}
	
	if(array_key_exists($attr_key, $translates)){
	    return $translates[$attr_key];
	}
	
	return '';
    }
    public function getAttributeItemValueHtml($store_id, $key, $value, $attributes_link_filter, $campaign_attr = ''){
	$result = '';
	try{
	    if(!empty($value)){
		if(is_array($value)){
		    if(!empty($value['key']) && !empty($value['value'])){
			if(in_array($key, $attributes_link_filter)){
			    $result = '<a class="xem-chi-tiet" href="/all-category.html?'.$key.'='.$value['key'].'&'.$campaign_attr.'">'.$this->escapeHtml($this->getAttributeTranslate($store_id, '', $value['value'])).'</a>';
			}else{
			    if(!empty($value['url'])){
				$result = '<a class="xem-chi-tiet" href="'.$value['url'].'?'.$campaign_attr.'">'.$this->escapeHtml($this->getAttributeTranslate($store_id, '', $value['value'])).'</a>';
			    }else{
				$result = $this->escapeHtml($this->getAttributeTranslate($store_id, '', $value['value']));
			    }
			}
		    }else if(!empty($value['key'])){
			if($key == 'author'){
			    if(!empty($value['url'])){
				$result = '<a class="xem-chi-tiet" href="'.$value['url'].'?'.$campaign_attr.'">'.$this->escapeHtml($value['key']).'</a>';
			    }else{
				$result = $this->escapeHtml($value['key']);
			    }
			}
		    }else{
			$is_first = true;
			foreach($value as $item){
			    if($is_first){$is_first = false;}else{$result .= ',&nbsp;';}
			    $result_value = $this->getAttributeItemValueHtml($store_id, $key, $item, $attributes_link_filter, $campaign_attr);
			    if(!empty($result_value)){
				$result .= $result_value;
			    }else{
				$is_first = true;
			    }
			}
		    }
		}else{
		    if($key == 'expected_date' || $key == 'book_release_date'){
			$result = $this->escapeHtml(date('d/m/Y', strtotime($value)));
		    }else{
			$result = $this->escapeHtml($this->getAttributeTranslate($store_id, '', $value));
		    }
		}
	    }
	}catch(Exception $ex) {}
	return $result;
    }
    public function getRattingHtml($attributes = null, $product_id = null){
	if(empty($attributes)){
	    $attributes = $this->getProductStoreV2($product_id);
	}
	if(!empty($attributes)){
	    if(!empty($attributes['rating_fs'])){
		$rating_fs = $attributes['rating_fs'];
	    }
	}
	$rating_summary = 0;
	$rating_count = 0;
	$class_star = '';
	if(!empty($rating_fs['rating_summary'] && !empty($rating_fs['rating_count']))){
	    $rating_summary = !empty($rating_fs['rating_summary'])?$rating_fs['rating_summary']:0;
	    if($rating_summary > 100){$rating_summary = 100;}
	    $rating_count = !empty($rating_fs['rating_count'])?$rating_fs['rating_count']:"";
	    $rating_star = number_format(round((($rating_summary/100)*5),1), 1, ".", ".");
	    
	}
	
	if($rating_count <= 0){
	    $class_star = 'deactive';
	}
	$result = '<div class="ratings">'
		    .'<div class="rating-content">' 
			.'<table class="ratings-desktop">'
			    .'<tr>'
			    .'<td>'
				."<a onclick=\"prodComment.choiceTab('review');\">"
				    .'<div class="rating-box">'
					.'<div class="rating" style="width:'.$rating_summary.'%"></div>'
				    .'</div>'
				.'</a>'
			    .'</td>'
			    .'<td class="review-position"><p class="rating-links">'
				."<a onclick=\"prodComment.choiceTab('review');\">".'('.$rating_count.' '.$this->__('vote').')'."</a></p>"
			    .'</td>'
			.'</tr>'
			.'</table>'
			.'<table class="ratings-mobile ratings-short">'
			    .'<tr>'
			    .'<td>'
				."<a onclick=\"prodComment.choiceTab('review');\">"
				    ."<div class='icon-star ".$class_star."'></div>"
				    ."<div class='icon-star-text'>".$rating_star."</div>"
				.'</a>'
			    .'</td>'
			    .'<td class="review-position"><p class="rating-links">'
				."<a style=\"cursor: pointer;\" onclick=\"prodComment.choiceTab('review');\">".'('.$rating_count.')'."</a></p>"
			    .'</td>'
			.'</tr>'
			.'</table>'
			.'</div>'
		    .'</div>';
	
	return $result;
    }
    //type = 'desktop'
    //type = 'mobile'
    public function getRattingOtherHtml($attributes = null, $product_id = null, $type = ''){
	if(empty($attributes)){
	    $attributes = $this->getProductStoreV2($product_id);
	}
	if(!empty($attributes)){
	    if(!empty($attributes['rating_aws'])){
		$rating_aws = $attributes['rating_aws'];
	    }
	    if(!empty($attributes['rating_gr'])){
		$rating_gr = $attributes['rating_gr'];
	    }
	}
	
	
	$result = '';
	if(!empty($rating_aws) || !empty($rating_gr)){
	    if((!empty($rating_aws['main_url'] || !empty($rating_aws['alt_url'])))
		||(!empty($rating_gr['main_url']) || !empty($rating_gr['alt_url']))
	    ){
		$rating_aws_desktop_html = '';
		$rating_aws_mobile_html = '';
		if(!empty($rating_aws['main_url']) || !empty($rating_aws['alt_url'])){
		    $url = !empty($rating_aws['main_url'])?$rating_aws['main_url']:$rating['alt_url'];
		    $rating_summary = !empty($rating_aws['rating_summary'])?$rating_aws['rating_summary']:0;
		    if($rating_summary > 100){$rating_summary = 100;}
		    $rating_star = number_format(round((($rating_summary/100)*5),1), 1, ".", ".");
		    
		    $rating_count = !empty($rating_aws['rating_count'])?$rating_aws['rating_count']:"";
		    
		    if($rating_star){
			if(empty($type) || $type == 'desktop'){
			    $rating_aws_desktop_html = '<div class="rating-content">' 
							.'<table class="ratings-desktop">'
							    .'<tr>'
								.'<td><a target="_blank" href="'.$url.'"><span class="icon-amazon"></span></a></td>'
								.'<td>'
								    .'<a target="_blank" href="'.$url.'">'
									.'<div class="rating-box">'
									    .'<div class="rating" style="width:'.$rating_summary.'%"></div>'
									.'</div>'
								    .'</a>'
								.'</td>'
								.'<td class="review-position"><a target="_blank" href="'.$url.'"><span>'.($rating_count?'('.$rating_count.' '.$this->__('vote').')':'').'</span></a></td>'
							    .'</tr>'
							.'</table>'
						    .'</div>';
			}
			if(empty($type) || $type == 'mobile'){
			    $rating_aws_mobile_html = '<div class="rating-content">' 
							.'<table class="ratings-mobile ratings-short">'
							    .'<tr>'
								.'<td>'
								    ."<a target=\"_blank\" href=\"'.$url.'\">"
									."<div class='icon-star'></div>"
									."<div class='icon-star-text'>".$rating_star."</div>"
								    .'</a>'
								.'</td>'
								.'<td><a target="_blank" href="'.$url.'" style="margin-left:8px;"><span class="icon-amazon"></span></a></td>'
							    .'</tr>'
							.'</table>'
						    .'</div>';
			}
		    }
		}
		
		$rating_gr_desktop_html = '';
		$rating_gr_mobile_html = '';
		if(!empty($rating_gr['main_url']) || !empty($rating_gr['alt_url'])){
		    $url = !empty($rating_gr['main_url'])?$rating_gr['main_url']:$rating_gr['alt_url'];
		    $rating_summary = !empty($rating_gr['rating_summary'])?$rating_gr['rating_summary']:0;
		    if($rating_summary > 100){$rating_summary = 100;}
		    $rating_star = number_format(round((($rating_summary/100)*5),1), 1, ".", ".");
		    
		    $rating_count = !empty($rating_gr['rating_count'])?$rating_gr['rating_count']:"";
		    if($rating_star){
			if(empty($type) || $type == 'desktop'){
			    $rating_gr_desktop_html = '<div class="rating-content">' 
							    .'<table class="ratings-desktop">'
								.'<tr>'
								    .'<td><a target="_blank" href="'.$url.'"><span class="icon-goodread"></span></a></td>'
								    .'<td>'
									.'<a target="_blank" href="'.$url.'">'
									    .'<div class="rating-box">'
										.'<div class="rating" style="width:'.$rating_summary.'%"></div>'
									    .'</div>'
									.'</a>'
								    .'</td>'
								    .'<td class="review-position"><a target="_blank" href="'.$url.'"><span>'.($rating_count?'('.$rating_count.' '.$this->__('vote').')':'').'</span></a></td>'
								.'</tr>'
							    .'</table>'
							.'</div>'
				    ;
			}
			
			if(empty($type) || $type == 'mobile'){
			    $rating_gr_mobile_html = '<div class="rating-content">' 
							.'<table class="ratings-mobile ratings-short">'
							    .'<tr>'
								.'<td>'
								    ."<a target=\"_blank\" href=\"'.$url.'\">"
									."<div class='icon-star'></div>"
									."<div class='icon-star-text'>".$rating_star."</div>"
								    .'</a>'
								.'</td>'
								.'<td><a target="_blank" href="'.$url.'" style="margin-left:8px;"><span class="icon-goodread"></span></a></td>'
							    .'</tr>'
							.'</table>'
						    .'</div>';
			}
			
		    }
		}
		$rating_desktop_html = '';
		if(empty($type) || $type == 'desktop'){
		    $rating_desktop_html = $rating_aws_desktop_html.$rating_gr_desktop_html;
		}
		$rating_mobile_html = '';
		if(empty($type) || $type == 'mobile'){
		    $rating_mobile_html = $rating_aws_mobile_html.$rating_gr_mobile_html;
		}
		$result = '<div class="ratings">'
				.$rating_desktop_html
				.$rating_mobile_html
			    .'</div>';
	    }
	}
	return $result;
    }
    public function getPriceHtml($price = null, $final_price = null, $discount = null, $product_id = null){
	$result = "";
	
	$result = '';
	if(!empty($product_id) && !empty($price) && !empty($final_price)){
	    $discount_html = '';
	    if($price > $final_price && !empty($discount)){
		$discount_html = '<p class="old-price">
				<span class="price-label">Regular Price:</span>
				<span class="price" id="old-price-'.$product_id.'">'.number_format($price, 0, ",", ".").'&nbsp;đ</span>
				<span class="discount-percent">-'.$discount.'%</span>            
			    </p>';
	    }
//	    $result = '"<div class="price-box">'
//		    . '<span class="regular-price" id="product-price-226430">'
//		    . '<span class="price">180.000 đ</span></span>'
//		    . '</div>"';
	    $result = '<div class="price-box">
			    <p class="special-price">
				<span class="price-label">Special Price</span>
				<span class="price" id="product-price-'.$product_id.'">'.number_format($final_price, 0, ",", ".").'&nbsp;đ</span>
			    </p>
			    '.$discount_html.'
			</div>';
	}
	return $result;
    }

    public function getProductAtributesFrontEnd($product, $productId){
        $result = array();
       
        $sql = "select 
	    `author`.value as 'author', 
	    author_link.label as 'author_value',
	    author_link.link_url as 'author_url',
	    `publisher`.value as 'publisher', 
	    `supplier`.value as 'supplier', 
	    supplier_url.name as 'supplier_value',
	    supplier_url.pageUrl as 'supplier_url',
	    `manufacturer`.value as 'manufacturer', 
	    manufacturer_opt.value as 'manufacturer_value',
	    `color`.value as 'color', 
	    color_opt.value as 'color_value',
	    `book_layout`.value as 'book_layout', 
	    book_layout_opt.value as 'book_layout_value',
	    `warranty`.value as 'warranty', 
	    warranty_opt.value as 'warranty_value',
	    `origin`.value as 'origin', 
	    origin_opt.value as 'origin_value',
	    `material`.value as 'material', 
	    material_opt.value as 'material_value',
	    `supplier_list`.value as 'supplier_list', 
	    supplier_list_opt.value as 'supplier_list_value',
	    `age`.value as 'age', 
	    age_opt.value as 'age_value',
	    reading_level_opt.value as 'reading_level_value',
	    `ink_color`.value as 'ink_color', 
	    ink_color_opt.value as 'ink_color_value',
	    `noi_san_xuat`.value as 'noi_san_xuat', 
	    noi_san_xuat_opt.value as 'noi_san_xuat_value',
	    `languages`.value as 'languages', 
	    languages_opt.value as 'languages_value',
	    `specification`.value as 'specification', 
	    specification_opt.value as 'specification_value',
	    `warning`.value as 'warning', 
	    warning_opt.value as 'warning_value',
	    `directions`.value as 'directions', 
	    directions_opt.value as 'directions_value',
	    `stages`.value as 'stages', 
	    stages_opt.value as 'stages_value'
	    from fhs_catalog_product_entity pe 
	    left join fhs_catalog_product_entity_varchar `author` on `author`.entity_id = pe.entity_id and `author`.attribute_id =141 and `author`.entity_type_id=4 and `author`.store_id=0 
	    left join fhs_internal_product_linking author_link on author_link.product_id = pe.entity_id and author_link.`type` = 'author'
	    left join fhs_catalog_product_entity_varchar `publisher` on `publisher`.entity_id = pe.entity_id and `publisher`.attribute_id =142 and `publisher`.entity_type_id=4 and `publisher`.store_id=0 
	    left join fhs_catalog_product_entity_varchar `supplier` on `supplier`.entity_id = pe.entity_id and `supplier`.attribute_id =157 and `supplier`.entity_type_id=4 and `supplier`.store_id=0 
	    left join fhs_page_keyword_url supplier_url on supplier_url.dataId = `supplier`.value and supplier_url.`type` = 'supplier'
	    left join fhs_catalog_product_entity_int `manufacturer` on `manufacturer`.entity_id = pe.entity_id and `manufacturer`.attribute_id =81 and `manufacturer`.entity_type_id=4 and `manufacturer`.store_id=0 
	    left join fhs_catalog_product_entity_int `color` on `color`.entity_id = pe.entity_id and `color`.attribute_id =92 and `color`.entity_type_id=4 and `color`.store_id=0 
	    left join fhs_catalog_product_entity_int `book_layout` on `book_layout`.entity_id = pe.entity_id and `book_layout`.attribute_id =140 and `book_layout`.entity_type_id=4 and `book_layout`.store_id=0 
	    left join fhs_catalog_product_entity_int `warranty` on `warranty`.entity_id = pe.entity_id and `warranty`.attribute_id =152 and `warranty`.entity_type_id=4 and `warranty`.store_id=0 
	    left join fhs_catalog_product_entity_int `origin` on `origin`.entity_id = pe.entity_id and `origin`.attribute_id =153 and `origin`.entity_type_id=4 and `origin`.store_id=0 
	    left join fhs_catalog_product_entity_int `material` on `material`.entity_id = pe.entity_id and `material`.attribute_id =160 and `material`.entity_type_id=4 and `material`.store_id=0 
	    left join fhs_catalog_product_entity_int `supplier_list` on `supplier_list`.entity_id = pe.entity_id and `supplier_list`.attribute_id =194 and `supplier_list`.entity_type_id=4 and `supplier_list`.store_id=0 
	    left join fhs_catalog_product_entity_int `age` on `age`.entity_id = pe.entity_id and `age`.attribute_id =195 and `age`.entity_type_id=4 and `age`.store_id=0 
	    left join fhs_catalog_product_entity_int `reading_level` on `reading_level`.entity_id = pe.entity_id and `reading_level`.attribute_id =199 and `reading_level`.entity_type_id=4 and `reading_level`.store_id=0 
	    left join fhs_catalog_product_entity_int `ink_color` on `ink_color`.entity_id = pe.entity_id and `ink_color`.attribute_id =201 and `ink_color`.entity_type_id=4 and `ink_color`.store_id=0 
	    left join fhs_catalog_product_entity_int `noi_san_xuat` on `noi_san_xuat`.entity_id = pe.entity_id and `noi_san_xuat`.attribute_id =203 and `noi_san_xuat`.entity_type_id=4 and `noi_san_xuat`.store_id=0 
	    left join fhs_catalog_product_entity_int `languages` on `languages`.entity_id = pe.entity_id and `languages`.attribute_id =204 and `languages`.entity_type_id=4 and `languages`.store_id=0 
	    left join fhs_catalog_product_entity_int `specification` on `specification`.entity_id = pe.entity_id and `specification`.attribute_id =210 and `specification`.entity_type_id=4 and `specification`.store_id=0 
	    left join fhs_catalog_product_entity_int `warning` on `warning`.entity_id = pe.entity_id and `warning`.attribute_id =211 and `warning`.entity_type_id=4 and `warning`.store_id=0 
	    left join fhs_catalog_product_entity_int `directions` on `directions`.entity_id = pe.entity_id and `directions`.attribute_id =212 and `directions`.entity_type_id=4 and `directions`.store_id=0 
	    left join fhs_catalog_product_entity_int `stages` on `stages`.entity_id = pe.entity_id and `stages`.attribute_id =225 and `stages`.entity_type_id=4 and `stages`.store_id=0 
	    left join fhs_eav_attribute_option_value manufacturer_opt on manufacturer_opt.store_id=0 and manufacturer_opt.option_id = `manufacturer`.value
	    left join fhs_eav_attribute_option_value color_opt on color_opt.store_id=0 and color_opt.option_id = `color`.value
	    left join fhs_eav_attribute_option_value book_layout_opt on book_layout_opt.store_id=0 and book_layout_opt.option_id = `book_layout`.value
	    left join fhs_eav_attribute_option_value warranty_opt on warranty_opt.store_id=0 and warranty_opt.option_id = `warranty`.value
	    left join fhs_eav_attribute_option_value origin_opt on origin_opt.store_id=0 and origin_opt.option_id = `origin`.value
	    left join fhs_eav_attribute_option_value material_opt on material_opt.store_id=0 and material_opt.option_id = `material`.value
	    left join fhs_eav_attribute_option_value supplier_list_opt on supplier_list_opt.store_id=0 and supplier_list_opt.option_id = `supplier_list`.value
	    left join fhs_eav_attribute_option_value age_opt on age_opt.store_id=0 and age_opt.option_id = `age`.value
	    left join fhs_eav_attribute_option_value reading_level_opt on reading_level_opt.store_id=0 and reading_level_opt.option_id = `reading_level`.value
	    left join fhs_eav_attribute_option_value ink_color_opt on ink_color_opt.store_id=0 and ink_color_opt.option_id = `ink_color`.value
	    left join fhs_eav_attribute_option_value noi_san_xuat_opt on noi_san_xuat_opt.store_id=0 and noi_san_xuat_opt.option_id = `noi_san_xuat`.value
	    left join fhs_eav_attribute_option_value languages_opt on languages_opt.store_id=0 and languages_opt.option_id = `languages`.value
	    left join fhs_eav_attribute_option_value specification_opt on specification_opt.store_id=0 and specification_opt.option_id = `specification`.value
	    left join fhs_eav_attribute_option_value warning_opt on warning_opt.store_id=0 and warning_opt.option_id = `warning`.value
	    left join fhs_eav_attribute_option_value directions_opt on directions_opt.store_id=0 and directions_opt.option_id = `directions`.value
	    left join fhs_eav_attribute_option_value stages_opt on stages_opt.store_id=0 and stages_opt.option_id = `stages`.value
	    where pe.entity_id = :product_id;";
        $resource = \Mage::getSingleton('core/resource');

        $read = $resource->getConnection('core_read');
        $queryBinding = array(
            "product_id" => $productId
        );
        $data = $read->fetchRow($sql, $queryBinding);
        if(!empty($data)){
	    $result['sku'] = $product->getSku();
	    if(!$this->isEmpty($product->getQtyOfPage(), 'qty_of_page')){$result['qty_of_page'] = $product->getQtyOfPage();}
	    if(!$this->isEmpty($product->getPublishYear(), 'publish_year')){$result['publish_year'] = $product->getPublishYear();}
	    if(!$this->isEmpty($product->getSize(), 'size')){$result['size'] = $product->getSize();}
	    if(!$this->isEmpty($product->getPublisher(), 'publisher')){$result['publisher'] = $product->getPublisher();}
	    if(!$this->isEmpty($product->getModelName(), 'model_name')){$result['model_name'] = $product->getModelName();}
	    if(!$this->isEmpty($product->getTranslator(), 'translator')){$result['translator'] = $product->getTranslator();}
	    if(!$this->isEmpty($product->getInternalSupply(), 'internal_supply')){$result['internal_supply'] = $product->getInternalSupply();}
	    if(!$this->isEmpty($product->getPieces(), 'pieces')){$result['pieces'] = $product->getPieces();}
	    if(!$this->isEmpty($product->getNumber(), 'number')){$result['number'] = $product->getNumber();}
	    if(!$this->isEmpty($product->getCaseDiameter(), 'case_diameter')){$result['case_diameter'] = $product->getCaseDiameter();}
	    if(!$this->isEmpty($product->getWeight(), 'weight')){$result['weight'] = $product->getWeight();}
	    if(!$this->isEmpty($product->getExpectedDate(), 'expected_date')){$result['expected_date'] = $product->getExpectedDate();}
	    if(!$this->isEmpty($product->getBookReleaseDate(), 'book_release_date')){$result['book_release_date'] = $product->getBookReleaseDate();}
	    if(!$this->isEmpty($product->getSoonRelesse(), 'soon_release')){$result['soon_release'] = $product->getSoonRelesse();}
	    
	    if(!$this->isEmpty($data['author'], 'author')){$result['author'] = $this->getAttributeValue($data['author'], $data['author_value'], $data['author_url']);}
	    if(!$this->isEmpty($data['supplier'], 'supplier')){$result['supplier'] = $this->getAttributeValue($data['supplier'], $data['supplier_value'], $data['supplier_url']);}
	    if(!$this->isEmpty($data['color'], 'color')){$result['color'] = $this->getAttributeValue($data['color'], $data['color_value']);}
	    if(!$this->isEmpty($data['book_layout'], 'book_layout')){$result['book_layout'] = $this->getAttributeValue($data['book_layout'], $data['book_layout_value']);}
	    if(!$this->isEmpty($data['warranty'], 'warranty')){$result['warranty'] = $this->getAttributeValue($data['warranty'], $data['warranty_value']);}
	    if(!$this->isEmpty($data['origin'], 'origin')){$result['origin'] = $this->getAttributeValue($data['origin'], $data['origin_value']);}
	    if(!$this->isEmpty($data['material'], 'material')){$result['material'] = $this->getAttributeValue($data['material'], $data['material_value']);}
	    if(!$this->isEmpty($data['supplier_list'], 'supplier_list')){$result['supplier_list'] = $this->getAttributeValue($data['supplier_list'], $data['supplier_list_value']);}
	    if(!$this->isEmpty($data['age'], 'age')){$result['age'] = $this->getAttributeValue($data['age'], $data['age_value']);}
	    if(!$this->isEmpty($data['reading_level'], 'reading_level')){$result['reading_level'] = $this->getAttributeValue($data['reading_level'], $data['reading_level_value']);}
	    if(!$this->isEmpty($data['ink_color'], 'ink_color')){$result['ink_color'] = $this->getAttributeValue($data['ink_color'], $data['ink_color_value']);}
	    if(!$this->isEmpty($data['noi_san_xuat'], 'noi_san_xuat')){$result['noi_san_xuat'] = $this->getAttributeValue($data['noi_san_xuat'], $data['noi_san_xuat_value']);}
	    if(!$this->isEmpty($data['languages'], 'languages')){$result['languages'] = $this->getAttributeValue($data['languages'], $data['languages_value']);}
	    if(!$this->isEmpty($data['specification'], 'specification')){$result['specification'] = $this->getAttributeValue($data['specification'], $data['specification_value']);}
	    if(!$this->isEmpty($data['manufacturer'], 'manufacturer')){$result['manufacturer'] = $this->getAttributeValue($data['manufacturer'], $data['manufacturer_value']);}
	    if(!$this->isEmpty($data['warning'], 'warning')){$result['warning'] = $this->getAttributeValue($data['warning'], $data['warning_value']);}
	    if(!$this->isEmpty($data['directions'], 'directions')){$result['directions'] = $this->getAttributeValue($data['directions'], $data['directions_value']);}
	    if(!$this->isEmpty($data['stages'], 'stages')){$result['stages'] = $this->getAttributeValue($data['stages'], $data['stages_value']);}
	    if(!empty($product->getGenres())){$result['genres'] = $this->getGenresByIds($product->getGenres());}
	    
	    $result = $this->getAttributeFilter($result,  true);
        }
        
        return $result;
    }
    
    public function getSubmitUrl($product_id){
	$current_url = Mage::helper('core/url')->getCurrentUrl();
	$url_encode = Mage::helper('core')->urlEncode($current_url);
	$routeParams = array(
            Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => $url_encode,
            'product' => $product_id,
        );
	$routeParams[Mage_Core_Model_Url::FORM_KEY] = Mage::getSingleton('core/session')->getFormKey();
	return Mage::getUrl('checkout/cart/add', $routeParams);
    }
    protected function isEmpty($value,$key = null){
        // redis 1 so attr dang khong co data (null) nhung la kieu int => 0 
        if ($key) {
            $keysCheck = array(
                'super_attribute_parent_id',
            );
            $keysCheck2 = array(
                'cat1_id',
                'cat2_id',
                'cat3_id',
                'cat4_id',
            );
            if ( (in_array($key, $keysCheck2) && ($value === 0 || $value === 1)) || (in_array($key, $keysCheck) && $value === 0) ) {
                $value = 'null';
            }
	    if(($key == 'expected_date' || $key == 'book_release_date') && !empty($value)){
		if(time() > strtotime($value)){
		    $value = 'null';
		}
	    }
        }
        
        $result = true;
        if($value === "0" || $value === 0 || $value === 0.0 ){
            return false;
        }
	if(!empty($value) && $value != 'null' && $value != '[]' ){
	    $result = false;
	}
	return $result;
    }
    protected function getAttributeValue($attr_id, $value, $url = null){
	$result = array();
	$result[] = array(
	    'key' => $attr_id,
	    'url' => $url,
	    'value' => $value
	);
	return $result;
    }
    protected function getGenresByIds($genres_ids){
	$result = array();
	
	$sql = "select option_id, value from fhs_eav_attribute_option_value where store_id = 0 and option_id in (".$genres_ids.");";
        $resource = \Mage::getSingleton('core/resource');

        $read = $resource->getConnection('core_read');
        $queryBinding = array(
            "product_id" => $productId
        );
        $data = $read->fetchAll($sql, $queryBinding);
	
	foreach($data as $item){
	    $result[] = array(
		'key' => $item['option_id'],
		'value' => $item['value']
	    );
	}
	return $result;
    }
    function json_validate($string) {
	// decode the JSON data
	$result = json_decode($string, true);
	if(json_last_error() != JSON_ERROR_NONE){
	    $result = $string;
	}
	return $result;
    }
    
    function getArrKeyAttribute() {
	$attributes_keys = Mage::getStoreConfig('catalog/product_redis/product_keys');
	$keys = explode(",", $attributes_keys);
	
//        $keys = array(
//            "soon_release",
//            "manufacturer",
//            "visibility",
//            "genres",
//            "active",
////            "order_of_week",
//            "super_attribute_parent_id",
//            "internal_supply",
//            "stages",
//            "super_attribute",
//            "qty_of_page",
//            "media_gallery",
//            "publisher",
//            "material",
////            "author_url",
//            "cat1_name",
//            "publish_year",
//            "sku",
//            "supplier_url",
//            "rating_summary",
//            "price",
//            "size",
//            "cat2_name",
//            "warranty",
//            "cat1_id",
//            "final_price",
//            "episode_display",
//            "magazine",
//            "age",
//            "book_release_date",
//            "product_url",
//            "resize_image_url",
//            "min_sale_qty",
//            "news_from_date",
//            "supplier_name",
//            "product_id",
//            "rating_aws",
//            "specification",
//            "seri_id",
//            "author",
//            "rating_fs",
//            "episode",
//            "author_label",
//            "cat3_name",
//            "case_diameter",
////            "reviews",
////            "reviews_new",
////            "reviews_favourite",
////            "reviews_bought ",
//            "directions",
//            "supplier_list",
//            "links",
//            "language",
////            "order_of_month",
//            "rating_count",
//            "stage",
//            "translator",
//            "languages",
//            "type_id",
//            "expected_date",
////            "order_of_year",
//            "color",
//            "book_layout",
//            "weight",
//            "product_name",
////            "republish_id",
//            "use_config_min_sale_qty",
//            "cat2_id",
//            "cat3_id",
//            "ink_color",
//            "qty",
//            "created",
//            "cat4_name",
//            "origin",
//            "supplier_code",
//            "rating_avg",
//            "news_to_date",
////            "origin_of_production",
//            "description",
//            "reading_level",
//            "special_from_date",
//            "list_bundled",
//            "rating_gr",
//            "warning",
//            "noi_san_xuat",
//            "videos",
//            "direction",
//            "supplier",
//            "stock_status",
//            "cat4_id",
//            "total_stars",
//            "thanhly_status",
//            "pieces",
//            "model_name",
//            "min_qty",
//            "image_url",
//            "number",
//            "discount",
//        );

        return $keys;
    }
     
    public function getArrChild($product_id, $redis_client) {
        // configurable, bundle , has attribute_parent_id,  : get lischild
        $type_id = $redis_client->hGet("product:" . $product_id, "type_id");
        $type_id = $this->json_validate($type_id);
        $childsInfo = array();
        $resultChilds['childsInfo'] = $childsInfo;
        $resultChilds['childConfigruableId'] = null;
        $resultChilds['product_id'] = $product_id;
        if ($type_id == $this::CONFIGURABLE_TYPE) {
            $listId = array();
            $super_attribute = $redis_client->hGet("product:" . $product_id, "super_attribute");
            $super_attribute = $this->json_validate($super_attribute);
            
            if (count($super_attribute) > 0) {
                $dataSuperAttribute = $super_attribute[0];
                $dataChilds = $dataSuperAttribute['childs'];
                if (!empty($dataChilds)) {
                    foreach ($dataChilds as $_child) {
                        $listId[] = $_child['product_id'];
                    }
                    $attr_key = $this->getKeyAttributeChilds('configurable');
                    $childsInfo = $this->getChildsInfo($listId, $attr_key, $redis_client, false);
                }
            }
        } elseif ($type_id == $this::BUNDLE_TYPE) {
            $listId = array();
            $list_bundled = $redis_client->hGet("product:" . $product_id, "list_bundled");
            $list_bundled = $this->json_validate($list_bundled);
            foreach ($list_bundled as $listItem) {
                $pro_bundle = $listItem['detail_list'];
                foreach ($pro_bundle as $_child) {
                    $listId[] = $_child['product_id'];
                }
            }
            if (!empty($listId)) {
                $attr_key = $this->getKeyAttributeChilds();
                $childsInfo = $this->getChildsInfo($listId, $attr_key, $redis_client, false);
            }
        } else {
            // check child of parent ? 
            $super_attribute_parent_id = $redis_client->hGet("product:" . $product_id, "super_attribute_parent_id");
            $super_attribute_parent_id = $this->json_validate($super_attribute_parent_id);
            if ($super_attribute_parent_id) {
                $resultChilds['childConfigruableId'] = $product_id;
                $resultChilds['product_id'] = $super_attribute_parent_id;
                $listId = array();
                $super_attribute = $redis_client->hGet("product:" . $super_attribute_parent_id, "super_attribute");
                $super_attribute = $this->json_validate($super_attribute);
                if (count($super_attribute) > 0) {
                    $dataSuperAttribute = $super_attribute[0];
                    $dataChilds = $dataSuperAttribute['childs'];
                    if (!empty($dataChilds)) {
                        foreach ($dataChilds as $_child) {
                            $listId[] = $_child['product_id'];
                        }
                        $attr_key = $this->getKeyAttributeChilds('configurable');
                        $childsInfo = $this->getChildsInfo($listId, $attr_key, $redis_client, false);
                    }
                }
            }
        }
        if (!empty($childsInfo) && count($childsInfo)> 0 ) {
            $resultChilds['childsInfo'] = $childsInfo;
           //return array();
        }
        return $resultChilds;
    }
    
    public function getChildsInfo($listProductId, $attr_key = array(), $redis_client, $is_attribute_frontend = false) {
        try {
            $pipeLineRedis = $redis_client->pipeline();
            $products = array();
            if (empty($products) && count($attr_key) > 0) {
                foreach ($listProductId as $proId) {
                    $pipeLineRedis->hMGet("product:" . $proId,$attr_key);
                }
                $result = $pipeLineRedis->exec();
                if(!empty($result) && count($result) > 0){
                    $products = $result;
                }
            }

            if (empty($products) || $products == 1) {
                return null;
            }

            return $products;
        } catch (Exception $ex) {
            mage::log("[ERROR] product_id= " . print_r($listProductId, true) . " ,attribute_key= " . print_r($attr_key, true) . ", is_frontend=" . $is_attribute_frontend . ", msg=" . $ex->getMessage(), null, 'redis_product.log');
        }
        return null;
    }
    
    public function getReviewsInfo($product_id, $redis_client , $page = 1 , $pagelimit = 10) {
        $pipeLineRedis = $redis_client->pipeline();
        $pipeLineRedis->hGet("product:" . $product_id, "reviews_new");
        $result = $pipeLineRedis->exec();
        $dataReviews['comment_list'] = null;
        $dataReviews['total_comments'] = 0;
        if (!empty($result) && count($result) > 0) {
            $reviewIds = $this->json_validate($result[0]);
            $totalReviewIds = count($reviewIds);
            if (empty($page)) {$page = 1;}
            if (empty($pagelimit)) {$pagelimit = 10;}
            
            $dataReviews['total_comments'] = $totalReviewIds;
            $dataReviews['total_pages'] = (int) ($totalReviewIds / $pagelimit);
            $dataReviews['rpage'] = $page;
            $dataReviews['limit'] = $pagelimit;
            
            if(count($reviewIds) > 0 && count($reviewIds) > $pagelimit){
                $indexStart = (($page - 1) * $pagelimit);
                $reviewIds = array_slice($reviewIds,$indexStart, ($pagelimit - 1));
            }
            $attr_key = $this->getKeyAttributeChilds('review');
            $pipeLineRedis_2 = $redis_client->pipeline();
            foreach ($reviewIds as $reviewId) {
                $pipeLineRedis_2->hMGet("product_review:" . $reviewId, $attr_key);
            }
            $listReviews = $pipeLineRedis_2->exec();
            if (!empty($listReviews) && count($listReviews) > 0) {
                $dataReviews['comment_list'] = $listReviews;
            }
        }
        return $dataReviews;
    }

    public function getKeyAttributeChilds($type = "") {
        switch ($type) {
            case "configurable":
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
                    "media_gallery",
                    "magazine",
                    "videos"
                );
                return $attr_key;
            case "review":
                $attr_key = array(
                    "suborder_id",
                    "nickname",
                    "title",
                    "detail",
                    "customer_id",
                    "rating",
                    "review_id",
                    "rating_percent",
                    "detail_id",
                    "created",
                );
                return $attr_key;
            default:
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
                    "product_url"
                );
                return $attr_key;
        }
    }
    
    public function getProductByIds($product_ids, $is_basic = true){
	$result = array();
	$helper = \Mage::helper('fahasa_catalog/Productredis');
	foreach ($product_ids as $product_id){
	    $product = $helper->getProductID($product_id, false, $is_basic);
	    if(!empty($product)){
		$result[$product_id] = $product;
	    }
	}
	return $result;
    }
    public function getAddtoCartTrackingScript($product_data = null, $product = null){
	if(empty($product_data) && empty($product)){return '';}
	
	$result = '';
	$marketing_helper = Mage::helper('fhsmarketing');
	
	if (Mage::getStoreConfig('netcore/general/enable') == 1){
	    $netcore = $marketing_helper->getProductToCartNetcore($product_data, $product);
	    if(!empty($netcore)){
		$result .= "smartech('dispatch', 'Add To Cart', {'items': ".$netcore."});";
	    }
	}
	if (Mage::getStoreConfig('suggestion/general/enable') == 1){
	    if(empty($netcore)){
		$netcore = $marketing_helper->getProductToCartNetcore($product_data, $product);
	    }
	    
	    if(!empty($netcore)){
		$result .= 'Suggestion(SESSION_ID, "Add To Cart", {"items": ['.$netcore.']});';
	    }
	}
	if (Mage::getStoreConfig('enhanced_ecom/general/enable') == 1){
	    $enhancedEcomParams = $marketing_helper->getEnhancedEcomAddToCart($product_data, $product);

	    $result .= "dataLayer.push({'event': 'addToCart','ecommerce': {'currencyCode':'".$enhancedEcomParams['currency']."','add': {'products': [{'name': '".$enhancedEcomParams['name']."','id': '".$enhancedEcomParams['id']."','price': ".$enhancedEcomParams['price'].",'category': '".$enhancedEcomParams['category']."','brand': '".$enhancedEcomParams['supplier']."','quantity': 1}]}}});";
	}
	if(!empty($result)){
	    $result = "setTimeout(function(){".$result."},100);";
	}
	return $result;
    }
    public function getAddtoCartInfo($product_data = null, $product = null){
	if(empty($product_data) && empty($product)){return null;}
	$result = array();
	
	$can_buy = false;
	$action_script = '';
	$action_form = '';
	
	if(!empty($product_data)){
	    $product = $product_data;
	    
	    if($product['is_available'] && $product['has_stock'] && $product['qty'] > 0){
		$can_buy = true;
	    }
	    $action_script = $this->getAddtoCartTrackingScript($product);
	    $action_form = $this->getSubmitUrl($product['entity_id']);
	    
	}else if($product){
	    if($product->isAvailable() && $product->getStockItem()->getIsInStock()){
		$can_buy = true;
	    }
	    $action_script = $this->getAddtoCartTrackingScript(null, $product);
	    $action_form = $this->getSubmitUrl($product->getEntityId());
	}
	$result['can_buy'] = $can_buy;
	$result['action_form'] = $action_form;
	$result['action_script'] = $action_script;
	
	return $result;
    }
    public function getRatingTotalHtml($product_data, &$rating_percent){
	$result = '';	    
	
	$rating_percent = 0;
	$rating_count = 0;
	
	if($product_data['rating_summary']){
	    $rating = $product_data['rating_summary'];
	    $rating_star_fhs = 0;
	    if(!empty($rating['rating_summary_fhs']) && !empty($rating['reviews_count_fhs'])){
		$rating_star_fhs = round($rating['rating_summary_fhs'],1);
		$rating_count .= $rating['reviews_count_fhs'];
	    }
	    $rating_star_amz = 0;
	    if(!empty($rating['rating_summary_amz']) && !empty($rating['reviews_count_amz'])){
		$rating_star_amz = round(($rating['rating_summary_amz']),1);
		$rating_count .= $rating['reviews_count_amz'];
	    }
	    $rating_star_gr = 0;
	    if(!empty($rating['rating_summary_gr']) && !empty($rating['reviews_count_gr'])){
		$rating_star_gr = round($rating['rating_summary_gr'],1);
		$rating_count .= $rating['reviews_count_gr'];
	    }
	    $rating_percent = max($rating_star_fhs, $rating_star_amz, $rating_star_gr);
	    
	    if($rating_percent > 100){
		$rating_percent = 100;
	    }
	    $rating_count = "(".round($rating_count,1).")";
	}

	$result = "<div class='ratings'>
		    <div class='rating-box'>
			<div class='rating' style='width:".$rating_percent."%'></div>
		    </div>
		<div class='amount'>".$rating_count."</div>
	    </div>";
	
	return $result;
    }
    
    //return product array for ajax
    public function getProductBasic($product_data = null, $show_buy_now = false){
	$result = array();
	
	if(!empty($product_data)){
	    $product = $product_data;
	    
	    if($product['type_id'] == 'series'){
		$series = $this->getSeriesStore($product['series_id']);
		if(!empty($series)){
		    if(!empty($series['subscribes'])){
			$subscribes = $series['subscribes'];
		    }
		}
	    }
	    $product_id = $product['entity_id'];
	    $type_id = $product['type_id'];
	    $product_name = $product['name'];
	    $final_price = $product['final_price'];
	    $price = $product['price'];
	    $stock_available = $product['stock_available'];
	    $episode = $product['episode'];
	    $soon_release = $product['soon_release'];
	    $url = $product['url'];
	    $image_src = $product['small_image'];
	    $discount_percent = $product['discount_percent'];
	    
	    $rating_html = $this->getRatingTotalHtml($product, $rating_summary);
	    
	    if($show_buy_now){
		$cart_info = $this->getAddtoCartInfo($product);
	    }
	}
	
	$result = array('id'=> $product_id,
                        'type' => $type_id, // using for mobile
			'type_id' => $type_id,
			'product_id'=> $product_id,
			'name'=> $product_name,
			'product_name'=> $product_name,
			"image_label" => $product_name,
			'name_a_title'=> $product_name,
			"name_a_label" => $product_name,
			"rating_html" => $rating_html,
			"soon_release" => $soon_release,
			"product_url" => $url,
			"image_src" => $image_src,
			"discount" => $discount_percent,
			'final_price'=> $final_price,
			'price'=> $price,
			'stock_available' => $stock_available,
			"episode" => $episode,
			"subscribes" => !empty($subscribes)?$subscribes:null,
			"add_to_cart_info" => !empty($cart_info)?$cart_info:null,
			'rating_summary' => !empty($rating_summary)?$rating_summary:0,
			//"bar_html" => '<div class="progress position-bar-gridslider color-progress-grid"><div class="progress-bar color-bar-grid 373749-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 28%;"></div><div class="text-progress-bar"><span class="373749-bar">Đã bán 43</span></div></div>',
			"discount_label_html"=>""
		    );
	
	return $result;
    }
//    public function getProductBySku($sku){
//	
//    }
    
    public function mapAffectEvent($events){
        $data = array();
        foreach($events as $key => $event){
            $event['key_index'] = $key;
            $data[] = $event;
        }
        return $data;
    }
    
    public function getFirstEventCart($event_cart)
    {
        $event_cart_limit = 2;
        $event_cart_count = 0;
        $event_cart_matched_full = [];
        $event_cart_viewmore = false;
        if ($event_cart)
        {
            $affect_payments_matched = array_map(function($e) {
                $e['reach_percent'] = 99;
                return $e;
            }, $event_cart['affect_payments']['matched']);
          
            $affect_payments_matched = $this->mapAffectEvent($affect_payments_matched);
            $affect_coupons_not_matched = $this->mapAffectEvent($event_cart['affect_coupons']['not_matched']);
            $affect_freeships_not_matched = $this->mapAffectEvent($event_cart['affect_freeships']['not_matched']);
            $affect_payments_not_matched =  $this->mapAffectEvent($event_cart['affect_payments']['not_matched']);
            $affect_carts_not_matched =  $this->mapAffectEvent($event_cart['affect_carts']['not_matched']);
            
            $event_cart_not_matched = array_merge($affect_coupons_not_matched, $affect_freeships_not_matched,
                    $affect_payments_matched, $affect_payments_not_matched, $affect_carts_not_matched
            );
            array_multisort(array_column($event_cart_not_matched, 'order_index'), SORT_ASC, array_column($event_cart_not_matched, 'reach_percent'), SORT_DESC, $event_cart_not_matched);

            foreach ($event_cart_not_matched as $key_index => $item)
            {
                if ($event_cart_count >= $event_cart_limit)
                {
                    $event_cart_viewmore = true;
                    goto out_notmatch;
                }

                $item['key_name'] = $this->mappingEvenTypeToName($item['event_type']);
                if ($item['matched']){
                    $item['key_type'] = 'matched';
                } else {
                    $item['key_type'] = 'not_matched';
                }
                $item['key_index'] = $item['key_index'];
                $event_cart_show[] = $item;
                $event_cart_count++;
            }
            out_notmatch:

            $event_cart_matched = array_merge(
                    $event_cart['affect_coupons']['matched'], $event_cart['affect_freeships']['matched'], $event_cart['affect_carts']['matched']);
            array_multisort(array_column($event_cart_matched, 'order_index'), SORT_ASC, $event_cart_matched);
            foreach ($event_cart_matched as $key_index => $item)
            {
                if ($event_cart_count >= $event_cart_limit)
                {
                    $event_cart_viewmore = true;
                    goto out_match;
                }

                $item['key_name'] = $this->mappingEvenTypeToName($item['event_type']);
                $item['key_type'] = 'matched';
                $item['key_index'] = $key_index;
                $event_cart_show[] = $item;
                $event_cart_count++;
            }
            out_match:

            $event_cart_matched_full = array_merge($event_cart_matched, $affect_payments_matched);
        }
        return array(
            "event_cart_show" => $event_cart_show,
            "num_events_matched" => count($event_cart_matched_full),
            "event_cart_viewmore" => $event_cart_viewmore,
        );
    }

    public function mappingEvenTypeToName($index)
    {
        switch ($index)
        {
            case 0:
                return "affect_carts";
            case 1:
                return "affect_coupons";
            case 2:
                return "affect_carts";
            case 3:
                return "affect_payments";
            case 4:
                return "affect_freeships";
            case 5:
                return "affect_coupons";
            case 6:
                return "affect_freeships";
            default:
                return "affect_carts";
        }
    }

    function xss_clean($data)
    {
	    // Fix &entity\n;
	    $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
	    $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
	    $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
	    $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
	    
	    $data = str_replace(array('%27',"'",'"','\\'), array(' ',' ',' ', ' '), $data);

	    // Remove any attribute starting with "on" or xmlns
	    $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

	    // Remove javascript: and vbscript: protocols
	    $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
	    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
	    $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

	    // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
	    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	    $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

	    // Remove namespaced elements (we do not need them)
	    $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

	    do
	    {
		// Remove really unwanted tags
		$old_data = $data;
		$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
	    }
	    while ($old_data !== $data);

	    if(!empty($data)){$data = trim($data);}
	    
	    // we are done...
	    return $data;
    }
}   

