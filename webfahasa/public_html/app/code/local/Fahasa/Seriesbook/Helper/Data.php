<?php 
class Fahasa_SeriesBook_Helper_Data_Error {


}

class Fahasa_Seriesbook_Helper_Data extends Mage_Core_Helper_Abstract {
    
    public function getProductsBySeriesId($series_id, $sort_by, $page, $limit, $is_first = false){
	$result = array();
        $result['success'] = false;
	$result['is_over'] = false;
	$result['products'] = [];
	
	if(empty($series_id) || !is_numeric($series_id)){return $result;}
	
	try{
	    $productt_helper = Mage::helper('fahasa_catalog/product');
	    if($is_first){
		$result['series_info'] = $this->getSeriesInfoFromDB($series_id, true);
	    }
	    $product_ids = $this->getProductsBySeriesIdFromDB($series_id, $page, $limit, $sort_by);
	    
	    $products = $productt_helper->getProductByIds($product_ids);
	    
	    if(!empty($products)){
		if(sizeof($products) < $limit){
		    $result['is_over'] = true;
		}
		$is_show_add_to_cart = Mage::getStoreConfig('seriesbook_config/config/is_show_add_to_cart');
		foreach($products as $product){
		    $product_list[] = $productt_helper->getProductBasic($product, $is_show_add_to_cart);
		    
		}
	    }else{
		$result['is_over'] = true;
	    }
	    
	    if($page == 1){
		if(Mage::getSingleton('customer/session')->isLoggedIn()){
		    $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
		    $result['is_follow'] = $this->isFollowSeriesBook($customer_id, $series_id);
		}else{
		    $result['is_follow'] = false;
		}
	    }
	    $result['products'] = $product_list;
	    $result['success'] = true;
	}catch (Exception $ex) {}
	return $result;
    }
    
    public function getProductsBySeriesIdFromDB($series_id, $page = 0, $limit = 0, $order_by = ''){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$series_id = $product_helper->cleanBug($series_id);
	$order_by = $product_helper->cleanBug($order_by);
	$page = $product_helper->cleanBug($page);
	$limit = $product_helper->cleanBug($limit);
	
	$result = [];
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $limit_str = "";
	    if($limit != 0 && $page != 0){
		$limit_str = "limit ".($limit*($page-1)).", ".$limit;
	    }
	    $order_by_str = "s.episodeInt desc, pe.created_at desc";
	    if(!empty($order_by)){
		switch($order_by){
		    case 'num_orders':
			$order_by_str = "pe.num_orders desc, s.episodeInt desc, pe.created_at desc";
			break;
		    case 'num_orders_month':
			$order_by_str = "pe.num_orders_month desc, s.episodeInt desc, pe.created_at desc";
			break;
		    case 'num_orders_year':
			$order_by_str = "pe.num_orders_year desc, s.episodeInt desc, pe.created_at desc";
			break;
		    case 'product_view':
			$order_by_str = "pe.product_view desc, s.episodeInt desc, pe.created_at desc";
			break;
		    case 'product_view_month':
			$order_by_str = "pe.product_view_month desc, s.episodeInt desc, pe.created_at desc";
			break;
		    case 'product_view_year':
			$order_by_str = "pe.product_view_year desc, s.episodeInt desc, pe.created_at desc";
			break;
		    case 'discount_percent':
			$order_by_str = "pe.discount_percent desc, s.episodeInt desc, pe.created_at desc";
			break;
		    case 'created_at':
			$order_by_str = "pe.created_at desc, s.episodeInt desc";
			break;
		    case 'min_price':
			$order_by_str = '';
			break;
		    default :
			$order_by_str = "s.episodeInt desc, pe.created_at desc";
		}
	    }
	    if(!empty($order_by_str)){
		$sql = "select s.product_id
		from fahasa_seribook s
		JOIN fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.type_id != 'series' and pe.f_visibility = 4 AND pe.f_stock_status = 1 AND pe.f_status = 1
		where s.fahasa_seribook_id = ".$series_id." 
		order by ".$order_by_str." ".$limit_str.";";
	    }else{
		$sql = "select s.product_id
		from fahasa_seribook s
		JOIN fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.type_id != 'series' and pe.f_visibility = 4 AND pe.f_stock_status = 1 AND pe.f_status = 1
		left join fhs_catalog_product_index_price_store pstore on pstore.entity_id = pe.entity_id and pstore.store_id = 1 and pstore.customer_group_id = 0
		where s.fahasa_seribook_id = ".$series_id." 
		order by pstore.min_price asc, s.episodeInt desc, pe.created_at desc ".$limit_str.";";
	    }
	    
	    $data = $reader->fetchAll($sql);
	    if(!empty($data)){
		foreach($data as $item){
		    if(!empty($item['product_id'])){
			array_push($result, $item['product_id']);
		    }
		}
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function getSeriesByProductIdFromDB($product_id){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$product_id = $product_helper->cleanBug($product_id);
	
        $data = null;
	try{
            $product_id = (int)$product_id;
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select fahasa_seribook_id from fahasa_seribook where product_id = :productId;";
            $sqlParams = array(
                "productId" => $product_id
            );
            $result = $reader->fetchAll($sql, $sqlParams);
            if(!empty($result)){
		foreach($result as $item){
		    if(!empty($item['fahasa_seribook_id'])){
			$data = $item['fahasa_seribook_id'];
		    }
                    break;
		}
	    }
	} catch (Exception $ex) {}
	return $data;
    }
    
    public function getSeriesBook($follow, $page, $limit){
	$result = array();
        $result['success'] = false;
	$result['is_over'] = false;
	$result['seriesbook'] = [];
	try{
	    if(!Mage::getSingleton('customer/session')->isLoggedIn()){
		return $result;
	    }
	    $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
    
	    $seriesBook = $this->getSeriesBookFromDB($customer_id, $follow, $page, $limit);
	    
	    if(!empty($seriesBook)){
		if(sizeof($seriesBook) < $limit){
		    $result['is_over'] = true;
		}
		else{
		    $count = $this->getSeriesBookCountFromDB($customer_id, (($follow)?1:0));
		    if($count <= ($page*$limit)){
			$result['is_over'] = true;
		    }
		}
		
		$result['seriesbook'] = $seriesBook;
	    }else{
		$result['is_over'] = true;
	    }
	    $result['success'] = true;
	}catch (Exception $ex) {}
	return $result;
    }
    
    public function getSeriesSet($sort_by, $page, $limit){
	$result = array();
        $result['success'] = false;
	$result['is_over'] = false;
	$result['series'] = [];
	try{
	    $seriesBook = $this->getSeriesSetFromDB($sort_by, $page, $limit);
	    
	    if(!empty($seriesBook)){
		if(sizeof($seriesBook) < $limit){
		    $result['is_over'] = true;
		}
		
		$result['series'] = $seriesBook;
	    }else{
		$result['is_over'] = true;
	    }
	    $result['success'] = true;
	}catch (Exception $ex) {}
	return $result;
    }
    
    //follow = 3: all
    public function getSeriesBookFromDB($customer_id, $follow, $page, $limit){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$page = $product_helper->cleanBug($page);
	$limit = $product_helper->cleanBug($limit);
	
	$result = [];
	try{
	    $follow_str = '';
	    switch ($follow){
		case 0:
		case 1:
		    $follow_str = " and is_follow = ".$follow;
		    break;
	    }
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql =  "select s.fahasa_seribook_id as 'seribook_id', if((sed.name is not null and sed.name != ''), sed.name, name.value) as 'product_name', sed.subscribes, sed.episode_max as 'episode', sed.label, ifnull(img_url.value,'/placeholder/default/noimage.jpg') as 'image_src', (pe.created_at + INTERVAL 7 HOUR) as 'created_at'
		    from 
		    (
			    select seribook_id
			    from fhs_customer_seribook
			    where is_active = 1 and customer_id = ".$customer_id.$follow_str." 
			    group by seribook_id
		    ) cst
		    join fahasa_seribook s on s.fahasa_seribook_id = cst.seribook_id
		    JOIN ( 
			    select s.fahasa_seribook_id, Max(s.episodeInt) as 'episodeInt'
			    from fahasa_seribook s
			    join fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4 and pe.f_stock_status = 1 and pe.f_status = 1
			    group by s.fahasa_seribook_id
		    ) sm on sm.fahasa_seribook_id = s.fahasa_seribook_id and sm.episodeInt = s.episodeInt
		    join fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4 and pe.f_stock_status = 1 and pe.f_status = 1
		    JOIN fhs_catalog_product_entity_varchar name ON name.entity_id = pe.entity_id AND name.attribute_id = 71
		    LEFT JOIN fhs_catalog_product_entity_varchar img_url ON pe.entity_id = img_url.entity_id AND img_url.attribute_id=85
		    LEFT JOIN fahasa_seribook_extra_data sed on sed.series_id = s.fahasa_seribook_id
		    group by s.fahasa_seribook_id
		    order by pe.created_at desc, pe.entity_id desc
		    limit ".($limit*($page-1)).", ".$limit.";"; 
	    
	    $result = $reader->fetchAll($sql);
	    if(!empty($result)){
		$day_show_new_label = Mage::getStoreConfig('seriesbook_config/config/day_show_new_label');
		if(!is_numeric($day_show_new_label)){
		    $day_show_new_label = "";
		}
		$product_empty = Mage::getModel('catalog/product');
		foreach($result as $key=>$item){
		    $item['image_src'] = Mage::helper('catalog/image')->init($product_empty, 'small_image',$item['image_src'])->resize(400, 400)->__toString();
		    $item['is_new'] = false;
		    $item['episode_label'] = $item['label']." mới nhất: ".$item['episode'];
//		    $episode_str = $item['episode'];
//		    $episode = "";
//		    for($i = 0; $i < strlen($episode_str); $i++ ){
//			$char = substr($episode_str, $i, 1);
//			if(!empty($episode)){
//			    $episode .= $char;
//			}else if($char != "0"){
//			    $episode .= $char;
//			}
//		    }
//		    if(!empty($episode)){
//			$item['episode'] = $episode;
//		    }
		    if(!empty($day_show_new_label)){
			$datetime_toshow = date('Y-m-d H:i:s', strtotime($item['created_at'].'+'.$day_show_new_label.'day'));
			$current_datetime = date('Y-m-d H:i:s', strtotime('+7 hours'));
			if($datetime_toshow >=  $current_datetime){
			    $item['is_new'] = true;
			}
		    }
		    $result[$key] = $item;
		}
	    }
	} catch (Exception $ex) {}
	return $result;
    }

    public function getSeriesBookCountFromDB($customer_id, $follow){
	$result = [];
	try{
	    $follow_str = '';
	    switch ($follow){
		case 0:
		case 1:
		    $follow_str = " and cs.is_follow = ".$follow;
		    break;
	    }
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = 
		    "select count(r.product_id) as 'count'
		    from (
			    select cs.seribook_id, s.product_id
			    from fhs_customer_seribook cs
			    join fahasa_seribook s on s.fahasa_seribook_id = cs.seribook_id
			    JOIN fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4 AND pe.f_stock_status = 1 AND pe.f_status = 1
			    where cs.is_active = 1 and cs.customer_id = ".$customer_id.$follow_str."
			    group by cs.seribook_id
		    ) r;";
	    $result = $reader->fetchRow($sql);
	    if(!empty($result['count'])){
		return $result['count'];
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function getSeriesSetFromDB($sort_by, $page, $limit){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$page = $product_helper->cleanBug($page);
	$limit = $product_helper->cleanBug($limit);
	
	$result = [];
	try{
	    $limit_str = "";
	    if($limit != 0 && $page != 0){
		$limit_str = "limit ".($limit*($page-1)).", ".$limit;
	    }
	    $order_by_str = "cst.subscribes desc";
	    if(!empty($sort_by)){
		switch($sort_by){
		    case 'num_orders':
			$order_by_str = "pe.num_orders desc";
			break;
		    case 'num_orders_month':
			$order_by_str = "pe.num_orders_month desc";
			break;
		    case 'num_orders_year':
			$order_by_str = "pe.num_orders_year desc";
			break;
		    case 'product_view':
			$order_by_str = "pe.product_view desc";
			break;
		    case 'product_view_month':
			$order_by_str = "pe.product_view_month desc";
			break;
		    case 'product_view_year':
			$order_by_str = "pe.product_view_year desc";
			break;
		    case 'discount_percent':
			$order_by_str = "pe.discount_percent desc";
			break;
		    case 'created_at':
			$order_by_str = "pe.created_at desc";
			break;
		    case 'min_price':
			$order_by_str = '';
			break;
		}
	    }
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    if(!empty($order_by_str)){
		$sql = "select s.fahasa_seribook_id as 'seribook_id', ifnull(sed.name,'') as 'seriesbook_name', sed.episode_max as 'episode', sed.label, cst.subscribes, ifnull(img_url.value,'/placeholder/default/noimage.jpg') as 'image_src' 
			from 
			(
			    select seribook_id, count(seribook_id) as 'subscribes'
			    from fhs_customer_seribook
			    where is_active = 1 and is_follow = 1
			    group by seribook_id
			) cst
			join fahasa_seribook s on s.fahasa_seribook_id = cst.seribook_id
			JOIN ( 
			    select s.fahasa_seribook_id, Max(s.episodeInt) as 'episodeInt'
			    from fahasa_seribook s
			    join fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4 and pe.f_stock_status = 1 and pe.f_status = 1
			    group by s.fahasa_seribook_id
			) sm on sm.fahasa_seribook_id = s.fahasa_seribook_id and sm.episodeInt = s.episodeInt
			join fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4 and pe.f_stock_status = 1 and pe.f_status = 1
			LEFT JOIN fhs_catalog_product_entity_varchar img_url ON pe.entity_id = img_url.entity_id AND img_url.attribute_id=85
			LEFT JOIN fahasa_seribook_extra_data sed on sed.series_id = s.fahasa_seribook_id
			where sed.name is not null and sed.name != '' and sed.episode_max_int != 0
			group by s.fahasa_seribook_id
			order by ".$order_by_str."
			 ".$limit_str.";";
	    }else{
		$sql = "select s.fahasa_seribook_id as 'seribook_id', ifnull(sed.name,'') as 'seriesbook_name', s.episodeStr2 as 'episode', cst.subscribes, ifnull(img_url.value,'/placeholder/default/noimage.jpg') as 'image_src' 
			from 
			(
			    select seribook_id, count(seribook_id) as 'subscribes'
			    from fhs_customer_seribook
			    where is_active = 1 and is_follow = 1
			    group by seribook_id
			) cst
			join fahasa_seribook s on s.fahasa_seribook_id = cst.seribook_id
			JOIN ( 
			    select s.fahasa_seribook_id, Max(s.episodeInt) as 'episodeInt'
			    from fahasa_seribook s
			    join fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4 and pe.f_stock_status = 1 and pe.f_status = 1
			    group by s.fahasa_seribook_id
			) sm on sm.fahasa_seribook_id = s.fahasa_seribook_id and sm.episodeInt = s.episodeInt
			join fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4 and pe.f_stock_status = 1 and pe.f_status = 1
			LEFT JOIN fhs_catalog_product_entity_varchar img_url ON pe.entity_id = img_url.entity_id AND img_url.attribute_id=85
			LEFT JOIN fahasa_seribook_extra_data sed on sed.series_id = s.fahasa_seribook_id
			left join fhs_catalog_product_index_price_store pstore on pstore.entity_id = pe.entity_id and pstore.store_id = 1 and pstore.customer_group_id = 0
			where sed.name is not null and sed.name != '' and sed.episode_max_int != 0
			group by s.fahasa_seribook_id
			order by pstore.min_price asc
			 ".$limit_str.";";
	    }
	    
	    $result = $reader->fetchAll($sql);
	    if(!empty($result)){
		foreach($result as $key=>$item){
		    $item['type_id'] = 'series';
		    $item['product_url'] = "/seriesbook/index/series/id/".$item['seribook_id'];
		    $item['product_name'] = $item['seriesbook_name'];
		    $item['image_src'] = Mage::helper('catalog/image')->init(Mage::getModel('catalog/product'), 'small_image',$item['image_src'])->resize(400, 400)->__toString();
		    $item['episode_label'] = $item['label']." mới nhất: ".$item['episode'];
		    $result[$key] = $item;
		}
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function setSeriesBook($series_id, $is_over, $follow, $page, $limit){
	$result = array();
        $result['success'] = false;
	$result['is_over'] = false;
	$result['seriesbook'] = [];
	try{
	    if(!Mage::getSingleton('customer/session')->isLoggedIn()){
		return $result;
	    }
	    $customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
	    
	    $this->setSeriesBookToDB($customer_id, $series_id, (($follow)?1:0));
	    if(!$is_over){
		$seriesBook = $this->getSeriesBookFromDB($customer_id, ((!$follow)?1:0), $page, $limit);

		if(!empty($seriesBook)){
		    $count = $this->getSeriesBookCountFromDB($customer_id, ((!$follow)?1:0));
		    if($count <= ($page*$limit)){
			$result['is_over'] = true;
		    }
		    
		    $result['seriesbook'] = $seriesBook;
		}else{
		    $result['is_over'] = true;
		}
	    }else{
		$result['is_over'] = true;
	    }
	    
	    $result['success'] = true;
	}catch (Exception $ex) {}
	return $result;
    }
    
    public function setSeriesBookPage($series_id, $follow){
	$result = array();
        $result['success'] = false;
	try{
	    if(Mage::getSingleton('customer/session')->isLoggedIn()){
		$customer_id = Mage::getSingleton('customer/session')->getCustomer()->getEntityId();
		$result['result'] = $this->setSeriesBookToDB($customer_id, $series_id, ((!$follow)?0:1));
		if($result['result']){
		    $series_Data = $this->getSeriesDataExtraFromDB('series_id', array($series_id));
		    if(!empty($series_Data[$series_id])){
			$result['subscribes'] = $series_Data[$series_id]['subscribes'];
		    }
		}
		$result['success'] = true;
	    }
	}catch (Exception $ex) {}
	return $result;
    }
    
    public function setSeriesBookToDB($customer_id, $series_id, $follow){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$series_id = $product_helper->cleanBug($series_id);
	
	$result = false;
	if(empty($customer_id) || empty($series_id)){return $result;}
	
	try{
	    $follow_value = 0;
	    switch ($follow){
		case 0:
		case 1:
		    $follow_value = $follow;
		    break;
	    }
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "CALL setFollowSeriesBook(".$customer_id.",".$series_id.",".$follow_value.");";
	    $reader->fetchRow($sql);
	    $result = true;
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function getSeriesInfoFromDB($series_id, $is_getAttribute = false){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$series_id = $product_helper->cleanBug($series_id);
	
	$result = [];
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select sed.series_id as 'seribook_id', if((sed.name != '' and sed.name is not null), sed.name, name.value) as 'seriesbook_name', sed.episode_max as 'episode', sed.label, sed.subscribes, sed.url, ifnull(img_url.value,'/placeholder/default/noimage.jpg') as 'image_src', s.product_id, if((sed.name != '' and sed.name is not null), 1, 0) as 'has_series_name' 
		    from fahasa_seribook_extra_data sed 
		    join fahasa_seribook s on s.fahasa_seribook_id = sed.series_id
		    JOIN ( 
			select s.fahasa_seribook_id, Max(s.episodeInt) as 'episodeInt'
			from fahasa_seribook s
			join fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4
			group by s.fahasa_seribook_id
		    ) sm on sm.fahasa_seribook_id = s.fahasa_seribook_id and sm.episodeInt = s.episodeInt
		    join fhs_catalog_product_entity pe on pe.entity_id = s.product_id and pe.f_visibility = 4
		    join fhs_catalog_product_entity_varchar name ON name.entity_id = s.product_id AND name.attribute_id = 71
		    left join fhs_catalog_product_entity_varchar img_url ON s.product_id = img_url.entity_id AND img_url.attribute_id=85
		    where sed.series_id = ".$series_id."
		    group by sed.series_id;";
	    
	    $result = $reader->fetchRow($sql);
	    if(!empty($result)){
		$product_empty = Mage::getModel('catalog/product');
		if($is_getAttribute){
		    $product = \Mage::helper('fahasa_catalog/Productredis')->getProductID($result['product_id'], false);
		    $includeArray = array("author", "publisher");
		    $result['attributes'] = Mage::helper('fahasa_catalog/product')->getAdditionalData(null, $includeArray, $product);
		}
		if(!empty($product['image'])){
		    $result['image_src'] = $product['image'];
		}else{
		    $result['image_src'] = Mage::helper('catalog/image')->init(Mage::getModel('catalog/product'), 'small_image',$result['image_src'])->resize(600, 600)->__toString();
		}
		$result['episode_label'] = $result['label']." mới nhất: ".$result['episode'];
	    }else{
		$result = null;
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function getSeriesDataExtraFromDB($type, $ids){
	if(!empty($ids) && sizeof($ids) > 0){
	    switch ($type){
		case 'series_id':
		    $sql = "select series_id, seriesset_id, name, episode_max as 'episode', label, subscribes, url from fahasa_seribook_extra_data where series_id in (".implode(",", $ids).");";
		    break;
		case 'seriesset_id':
		    $sql = "select series_id, seriesset_id, name, episode_max as 'episode', label, subscribes, url from fahasa_seribook_extra_data where seriesset_id in (".implode(",", $ids).");";
		    break;
		case 'product_id':
		    $sql = "select s.product_id, sed.series_id, sed.seriesset_id, sed.name, sed.episode_max as 'episode', sed.label, sed.subscribes, concat('/seriesbook/index/series/id/',sed.series_id) as 'url' from fahasa_seribook s join fahasa_seribook_extra_data sed on sed.series_id = s.fahasa_seribook_id where s.product_id in (".implode(",", $ids).");";
		    break;
		default:
		    return null;
		}
	}else{
	    return null;
	}
	
	$result = [];
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $result_data = $reader->fetchAll($sql);
	    if(!empty($result_data)){
		foreach($result_data as $item){
		    switch ($type){
			case 'series_id':
			    $result[$item['series_id']] = $item;
			    break;
			case 'seriesset_id':
			    $result[$item['seriesset_id']] = $item;
			    break;
			case 'product_id':
			    $result[$item['product_id']] = $item;
			    break;
			default:
			    $result[] = $item;
		    }
		}
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function isFollowSeriesBook($customer_id, $series_id){
	$product_helper = Mage::helper('fahasa_catalog/product');
	$series_id = $product_helper->cleanBug($series_id);
	
	$result = false;
	if(empty($customer_id) || empty($series_id)){return $result;}
	
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select is_follow from fhs_customer_seribook where customer_id = ".$customer_id." and seribook_id = ".$series_id.";";
	    $data = $reader->fetchRow($sql);
	    if(!empty($data['is_follow'])){
		if(result['is_follow']){
		    $result = true;
		}
	    }
	} catch (Exception $ex) {}
	return $result;
    }

    protected function _isLoggedIn() {
        return $this->_getCustomerSession()->isLoggedIn();
    }
    protected function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }

    public function getFhsCampaignSeriPage() {
        return 'SERI_PAGE';
    }

    public function getFhsCampaignSeriProduct() {
        return 'SERI_PAGE_PRODUCT';
    }

    public function getFhsCampaignSeriProductRelated() {
        return 'SERI_RELATED_PRODUCT';
    }

    public function getFhsCampaignSeriNotification() {
        return 'SERI_NOTI';
    }

    public function getFhsCampaignSeriProductTab() {
        return 'SERI_TABSLIDER_PRODUCT';
    }
    
    public function getFhsCampaignSeriSet() {
        return 'SERI_SERTES_SET_PAGE';
    }
    
    public function getCampaignSeriesSetPage() {
        return "SERT_SET_PAGE";
    }
}