<?php 
class Fahasa_ProductViewed_Helper_Data_Error {


}

class Fahasa_ProductViewed_Helper_Data extends Mage_Core_Helper_Abstract {

    public function addProductViewed($product_id){
        $result = array();
        $result['success'] = false;
	try{
	    if(empty($product_id) || !is_numeric($product_id)){
		return;
	    }
	    if(Mage::getStoreConfig('productviewed_config/config/is_active')){
		$page_limit = Mage::getStoreConfig('productviewed_config/config/page_limit');
		$need_save = true;
		if($this->_isLoggedIn()){
		    $product_viewed_ids = $this->getProductViewedFromDB($this->_getCustomerSession()->getCustomerId());
		}else{
		    $products_viewed = $this->_getCustomerSession()->getProductsViewed();
		    $product_viewed_ids = $products_viewed['product_ids'];
		}
		
		
		//add product id
		if(!empty($product_viewed_ids)){
		    if(end($product_viewed_ids) == $product_id){
			$need_save = false;
		    }else{
			if(array_key_exists($product_id, $product_viewed_ids)){
			    unset($product_viewed_ids[$product_id]);
			}

			if(sizeof($product_viewed_ids) >= $page_limit){
				$offset = sizeof($product_viewed_ids) - ($page_limit-1);
				$product_viewed_ids = array_slice($product_viewed_ids, $offset, $page_limit, true);
			}
			$product_viewed_ids[$product_id] = $product_id;
		    }
		}else{
		    $product_viewed_ids = [];
		    $product_viewed_ids[$product_id] = $product_id;
		}
		
		$products_viewed = [];
		$products_viewed['product_ids'] = $product_viewed_ids;
		
		if($this->_isLoggedIn() && $need_save){
		    $this->setCustomerProductViewed($this->_getCustomerSession()->getCustomerId(), $products_viewed['product_ids']);   
		}
		
		if(empty($products_viewed['products']) || $need_save){
//		    $products = Mage::helper('fahasa_catalog/product')->getProductByProductIdsWithSortBy($products_viewed['product_ids'], '', 1, $page_limit);
//
//		    $product_list = [];
//		    if(!empty($products)){
//			foreach($products as $product){
//			    //get rating
//			    $rating_html = "";
//			    $awsAvgScore_data = $product->getAwsAvgScore();
//			    $grAvgScore_data = $product->getGrAvgScore();
//			    $awsRatings_data = $product->getAwsRatings();
//			    $grRatings_data = $product->getGrRatings();
//			    $rating_count_average = 0;
//			    $ratings = $product->getFhsReviewsCount()?$product->getFhsReviewsCount():0;
//			    $fhsAvgScore = $product->getFhsRatingSummary()?$product->getFhsRatingSummary():0;
//			    if(!empty($awsAvgScore_date) || !empty($grAvgScore_data)){
//				$amzAvgScore = 0;
//				$grAvgScore = 0;
//				if($awsAvgScore_data){
//				    $amzAvgScore = ($awsAvgScore_date/5)*100;
//				}
//				if($grAvgScore_data){
//				    $grAvgScore = ($grAvgScore_data/5)*100;
//				}
//				if($awsRatings_data){
//				    $ratings += $awsRatings_data;
//				}
//				if($grRatings_data){
//				    $ratings += $grRatings_data;
//				}
//				$rating_count_average = max($fhsAvgScore, $amzAvgScore, $grAvgScore);
//			    }
//			    else{
//				$rating_count_average = $fhsAvgScore;
//			    }
//			    if ($rating_count_average > 0){
//				// remove class ratings fhs-no-mobile-block
//				$rating_html = "<div class='ratings'>
//					<div class='rating-box'>
//					    <div class='rating' style='width:".($rating_count_average>100?100:$rating_count_average)."%'></div>
//					</div>
//				    <div class='amount'>(".$ratings.")</div>
//				</div>";
//			    }
//
//			    $helperDiscountLabel = Mage::helper('discountlabel');
//			    $helperCatalogImage = Mage::helper('catalog/image');
//			    $product_list[$product->getId()] = array(
//				'product_id'=>$product->getId(),
//				'product_name'=>$product->getName(),
//				'product_finalprice'=> number_format($product->getData('final_price'), 0, ",", "."),
//				'product_price'=> number_format($product->getData('price'), 0, ",", "."),
//				"rating_html" => $rating_html,
//				"soon_release" => $product->getSoonRelease(),
//				"product_url" => $product->getProductUrl(),
//				"image_src" => (string)$helperCatalogImage->init($product, 'small_image')->resize(400, 400),
//				"discount" => $helperDiscountLabel->handleDiscountPercent($product),
//				"discount_label_html" => $helperDiscountLabel->handleDisplayDiscountLabel($product, true, false),
//				'final_price' => $product->getData('final_price'),
//				'price' => $product->getData('price') ? $product->getData('price') : 0,
//				"episode" => $product->getEpisode()
//			    );
//			}
//		    }
//		    $products_viewed['products'] = $product_list;
		    $products_viewed['login_saved'] = false;
		}

		$this->_getCustomerSession()->setProductsViewed($products_viewed);
		$result['success'] = true;
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function getProductsViewed($is_page = false, $page = 1, $limit = 6){
        $result = array();
        $result['success'] = false;
	$result['products'] = null;
	
	if(($page < 1)){
	    $page = 1;
	}
	$offset = ($page - 1) * $limit;
	if(Mage::getStoreConfig('productviewed_config/config/is_active')){
	    $products_viewed = $this->getProductViewedfromSession();
	    if(!empty($products_viewed['products'])){
		$products_viewed['products'] = array_reverse($products_viewed['products']);
		
		$product_size = sizeof($products_viewed['products']);
		if($product_size > $offset){
		    $product_list = [];
		    if(!$is_page){
			$slider_limit = Mage::getStoreConfig('productviewed_config/config/slider_limit');

			if(sizeof($products_viewed['products']) > $limit){
			    $product_list = array_slice($products_viewed['products'], $offset, $limit);
			}else{
			    $product_list = $products_viewed['products'];
			}
		    }else{

			if(sizeof($products_viewed['products']) > $limit){
			    $product_list = array_slice($products_viewed['products'], $offset, $limit);
			}else{
			    $product_list = $products_viewed['products'];
			}
		    }
		}
		
		$result['success'] = true;
		$result['products'] = $product_list;
	    }
	}
	
	return $result;
    }
    
    public function getProductViewedfromSession(){
	try{
	    if($this->_isLoggedIn()){
		$product_viewed_ids = $this->getProductViewedFromDB($this->_getCustomerSession()->getCustomerId());
	    }else{
		$products_viewed = $this->_getCustomerSession()->getProductsViewed();
		$product_viewed_ids = $products_viewed['product_ids'];
	    }
	    
	    //init array when empty
	    if(empty($product_viewed_ids)){
		return null;
	    }
	    
	    foreach($product_viewed_ids as $key=>$item){
		if(!is_numeric($item)){
		    unset($product_viewed_ids[$key]);
		}
	    }
	    
	    $productt_helper = Mage::helper('fahasa_catalog/product');
	    
	    //init array for session
	    $products_viewed = [];
	    $products_viewed['product_ids'] = $product_viewed_ids;
	    
	    $page_limit = Mage::getStoreConfig('productviewed_config/config/page_limit');
	    
	    $product_ids = array();
	    $i = 0;
	    foreach($product_viewed_ids as $item){
		if($page_limit < $i){goto out_loop;}
		$product_ids[] = $item;
	    }
	    out_loop:
		
	    $products = $productt_helper->getProductByIds($product_ids);
	    
	    $product_list = array();
	    if(!empty($products)){
		foreach($products as $product){
		    $product_list[] = $productt_helper->getProductBasic($product, false);
		}
	    }
	    $products_viewed['products'] = $product_list;
	} catch (Exception $ex) {}
	return $products_viewed;
    }
    
    public function mergeProductViewedSessionToDB($customer_id){
	try{
	    if(Mage::getStoreConfig('productviewed_config/config/is_active')){
		$page_limit = Mage::getStoreConfig('productviewed_config/config/page_limit');
		$need_save = false;
		$has_change = false;
		$products_viewed = $this->_getCustomerSession()->getProductsViewed();
		if(!empty($products_viewed) && !empty($products_viewed['product_ids'])){
		    if(!$products_viewed['login_saved']){
			$product_viewed_ids = $products_viewed['product_ids'];
			$customer_product_viewed_ids = $this->getProductViewedFromDB($customer_id);
			if(!empty($customer_product_viewed_ids)){
			    $product_viewed_ids = $this->mergeTwoArray($customer_product_viewed_ids, $product_viewed_ids, $page_limit);
			    $has_change = true;
			}
			$products_viewed['product_ids'] = $product_viewed_ids;
			$need_save = true;
		    }
		}
		
		if($need_save){
		    $this->setCustomerProductViewed($customer_id, $products_viewed['product_ids']);   
		}
		
		if($has_change){
//		    $products = Mage::helper('fahasa_catalog/product')->getProductByProductIdsWithSortBy($products_viewed['product_ids'], '', 1, $page_limit);
//		    $product_list = [];
//		    
//		    if(!empty($products)){
//			foreach($products as $product){
//			    //get rating
//			    $rating_html = "";
//			    $awsAvgScore_data = $product->getAwsAvgScore();
//			    $grAvgScore_data = $product->getGrAvgScore();
//			    $awsRatings_data = $product->getAwsRatings();
//			    $grRatings_data = $product->getGrRatings();
//			    $rating_count_average = 0;
//			    $ratings = $product->getFhsReviewsCount()?$product->getFhsReviewsCount():0;
//			    $fhsAvgScore = $product->getFhsRatingSummary()?$product->getFhsRatingSummary():0;
//			    if(!empty($awsAvgScore_date) || !empty($grAvgScore_data)){
//				$amzAvgScore = 0;
//				$grAvgScore = 0;
//				if($awsAvgScore_data){
//				    $amzAvgScore = ($awsAvgScore_date/5)*100;
//				}
//				if($grAvgScore_data){
//				    $grAvgScore = ($grAvgScore_data/5)*100;
//				}
//				if($awsRatings_data){
//				    $ratings += $awsRatings_data;
//				}
//				if($grRatings_data){
//				    $ratings += $grRatings_data;
//				}
//				$rating_count_average = max($fhsAvgScore, $amzAvgScore, $grAvgScore);
//			    }
//			    else{
//				$rating_count_average = $fhsAvgScore;
//			    }
//			    if ($rating_count_average > 0){
//				// remove class ratings fhs-no-mobile-block
//				$rating_html = "<div class='ratings'>
//					<div class='rating-box'>
//					    <div class='rating' style='width:".($rating_count_average>100?100:$rating_count_average)."%'></div>
//					</div>
//				    <div class='amount'>(".$ratings.")</div>
//				</div>";
//			    }
//
//			    $helperDiscountLabel = Mage::helper('discountlabel');
//			    $helperCatalogImage = Mage::helper('catalog/image');
//			    $product_list[$product->getId()] = array(
//				'product_id'=>$product->getId(),
//				'product_name'=>$product->getName(),
//				'product_finalprice'=> number_format($product->getData('final_price'), 0, ",", "."),
//				'product_price'=> number_format($product->getData('price'), 0, ",", "."),
//				"rating_html" => $rating_html,
//				"soon_release" => $product->getSoonRelease(),
//				"product_url" => $product->getProductUrl(),
//				"image_src" => (string)$helperCatalogImage->init($product, 'small_image')->resize(400, 400),
//				"discount" => $helperDiscountLabel->handleDiscountPercent($product),
//				"discount_label_html" => $helperDiscountLabel->handleDisplayDiscountLabel($product, true, false),
//				'final_price' => $product->getData('final_price'),
//				'price' => $product->getData('price') ? $product->getData('price') : 0,
//				"episode" => $product->getEpisode()
//			    );
//			}
//		    }
//		    $products_viewed['products'] = $product_list;
		}
		
		if($has_change || $need_save){
		    $products_viewed['login_saved'] = true;
		    $this->_getCustomerSession()->setProductsViewed($products_viewed);
		}
	    }
	} catch (Exception $ex) {}
    }
    
    public function mergeTwoArray($old, $new, $page_limit){
	foreach($new as $item){
	    if(end($old) != $item){
		if(array_key_exists($item, $old)){
		    unset($old[$item]);
		}

		if(sizeof($old) >= $page_limit){
		    $offset = sizeof($old) - ($page_limit-1);
		    $old = array_slice($old, $offset, $page_limit, true);
		}
		$old[$item] = $item;
	    }
	}
	return $old;
    }
    
    public function getProductViewedFromDB($customer_id){
	try{
	    return $this->getCustomerProductViewed($customer_id);
//	    if(!empty($result)){
//		return $result;
//	    }else{
//		$reader = Mage::getSingleton('core/resource')->getConnection('core_read');
//		$sql = "select value from fhs_customer_productviewed where customer_id = ".$customer_id.";";
//		$product_viewed_ids = $reader->fetchRow($sql);
//		if(!empty($product_viewed_ids['value'])){
//		    return unserialize($product_viewed_ids['value']);
//		}
//	    }
	} catch (Exception $ex) {}
	return null;
    }
    
    public function setProductViewedToDB($customer_id, $product_viewed_ids){
	try{
	    $product_viewed_ids = serialize($product_viewed_ids);
	    $writer = Mage::getSingleton('core/resource')->getConnection('core_write');
	    $sql = "INSERT INTO fhs_customer_productviewed (customer_id, value)
		    VALUES (".$customer_id.",'".$product_viewed_ids."')
		    ON DUPLICATE KEY UPDATE value = '".$product_viewed_ids."';";
	    $writer->query($sql);
	} catch (Exception $ex) {}
    }
    
    //Search history
    public function addSearchHistory($keyword){
        $result = array();
        $result['success'] = false;
	$keyword = Mage::helper('fahasa_catalog/product')->xss_clean($keyword);
	try{
	    $keyword = trim($keyword);
	    if(empty($keyword)){
		return $result;
	    }
	    if(Mage::getStoreConfig('search_history_config/config/is_active')){
		$limit = Mage::getStoreConfig('search_history_config/config/store_limit');
		$need_save = true;

		if($this->_isLoggedIn()){
		    $keywords = $this->getCustomerSearchHistory($this->_getCustomerSession()->getCustomerId());
		}else{
		    $search_history = $this->_getCustomerSession()->getSearchHistory();
		    $keywords = $search_history['keywords'];
		}
		
		//add keyword
		if(!empty($keywords)){
		    if(end($keywords) == $keyword){
			$need_save = false;
		    }else{
			if(array_key_exists($keyword, $keywords)){
			    unset($keywords[$keyword]);
			}

			if(sizeof($keywords) >= $limit){
				$offset = sizeof($keywords) - ($limit-1);
				$keywords = array_slice($keywords, $offset, $limit, true);
			}
			$keywords[$keyword] = $keyword;
		    }
		}else{
		    $keywords = [];
		    $keywords[$keyword] = $keyword;
		}
		
		$data = [];
		$data['keywords'] = $keywords;
		
		if($need_save){
		    if($this->_isLoggedIn()){
			$this->setCustomerSearchHistory($this->_getCustomerSession()->getCustomerId(), $keywords);
		    }
		
		    $data['login_saved'] = false;
		    $this->_getCustomerSession()->setSearchHistory($data);
		}
		
		$result['success'] = true;
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function removeSearchHistory(){
        $result = array();
        $result['success'] = false;
	try{
	    if(Mage::getStoreConfig('search_history_config/config/is_active')){
		$search_history = array('keywords'=>[],'login_saved'=>false);
		
		if($this->_isLoggedIn()){
		    $this->setCustomerSearchHistory($this->_getCustomerSession()->getCustomerId(), []);
		}
		$this->_getCustomerSession()->setSearchHistory($search_history);
		$result['success'] = true;
	    }
	} catch (Exception $ex) {}
	return $result;
    }
    
    public function getSearchHistory(){
        $result = array();
        $result['success'] = false;
	$result['result'] = null;
	
	if(Mage::getStoreConfig('search_history_config/config/is_active')){
	    $data = $this->_getCustomerSession()->getSearchHistory();
	    if(empty($data)){
		if($this->_isLoggedIn()){
		    $keywords = $this->getCustomerSearchHistory($this->_getCustomerSession()->getCustomerId());
		}
		
		$data = [];
		$data['login_saved'] = false;
		if(empty($keywords)){
		    $keywords = [];
		}
		$data['keywords'] = $keywords;
		$this->_getCustomerSession()->setSearchHistory($data);
	    }
	    
	    $keywords = [];
	    foreach(array_reverse($data['keywords'], true) as $item){
		array_push($keywords, $item);
	    }
	    $result['success'] = true;
	    $result['result'] = $keywords;
	}
	
	return $result;
    }
    
    public function mergeSearchHistorySessionToDB($customer_id){
	try{
	    if(Mage::getStoreConfig('search_history_config/config/is_active')){
		$search_history = $this->_getCustomerSession()->getSearchHistory();
		$need_save = false;
		
		if(!empty($search_history) && !empty($search_history['keywords'])){
		    if(!$search_history['login_saved']){
			$keywords = $search_history['keywords'];
			$customer_keywords = $this->getCustomerSearchHistory($customer_id);
			
			if(!empty($customer_keywords)){
			    $limit = Mage::getStoreConfig('search_history_config/config/store_limit');
			    $keywords = $this->mergeTwoArray($customer_keywords, $keywords, $limit);
			    $search_history['keywords'] = $keywords;
			}
			$need_save = true;
		    }
		}else if(!empty($search_history)){
		    $this->_getCustomerSession()->unsSearchHistory();
		}
		
		if($need_save){
		    $this->setCustomerSearchHistory($customer_id, $search_history['keywords']);
		    $search_history['login_saved'] = true;
		    $this->_getCustomerSession()->setSearchHistory($search_history);
		}
	    }
	} catch (Exception $ex) {}
    }
    
    public function getKeywordHot(){
        $result = array();
        $result['success'] = false;
	$result['result'] = null;
	
	if(Mage::getStoreConfig('search_history_config/keyword_hot/is_active')){
	    //$version = Mage::getStoreConfig('search_history_config/keyword_hot/version');
	    $version =  $this->getKeywordVersionFromDB();
	    $data = $this->_getCustomerSession()->getKeywordHot();
	    if(empty($data) || $data['version'] != $version){
		$keywords_litmit = Mage::getStoreConfig('search_history_config/keyword_hot/limit');
		$keywords = $this->getKeyHotFromDB($keywords_litmit);
	    }
	    
	    if(!empty($keywords)){
		$data = [];
		$data['keywords'] = $keywords;
		$data['version'] = $version;
		$this->_getCustomerSession()->setKeywordHot($data);
	    }
	    
	    $result['success'] = true;
	    $result['result'] = $data['keywords'];
	}
	
	return $result;
    }
    
    public function getKeywords($search_key = ''){
        $result = array();
        $result['success'] = true;
	$result['history'] = [];
	$result['keyword'] = [];
	if(!empty($search_key)){
	    $this->addSearchHistory($search_key);
	}
	$history = $this->getSearchHistory();
	if(!empty($history['result'])){
	    $result['history'] = $history['result'];
	}
	
	$keyword = $this->getKeywordHot();
	if(!empty($keyword['result'])){
	    $result['keyword'] = $keyword['result'];
	}
	
	return $result;
    }
    
    public function getKeywordVersionFromDB(){
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select value from fhs_core_config_data where `path` = 'search_history_config/keyword_hot/version';";
	    $data = $reader->fetchRow($sql);
	    if(!empty($data['value'])){
		return $data['value'];
	    }
	} catch (Exception $ex) {}
	return 0;
    }
    
    public function setCustomerSearchHistory($customer_id, $keywords){
	try{
	    Mage::helper('fahasa_customer')->setCustomerStore($customer_id, "search_history", serialize($keywords));
	} catch (Exception $ex) {}
    }
    public function getCustomerSearchHistory($customer_id){
	try{
	    $keywords = Mage::helper('fahasa_customer')->getCustomerStore($customer_id, "search_history");
	    if(!empty($keywords)){
		return unserialize($keywords);
	    }
	} catch (Exception $ex) {}
	return null;
    }
    public function setCustomerLastSessionId($customer_id, $old_session_id){
	Mage::helper('fahasa_customer')->setCustomerStore($customer_id, "last_session_id", $old_session_id);
    }
    public function getCustomerLastSessionId($customer_id){
	return Mage::helper('fahasa_customer')->getCustomerStore($customer_id, "last_session_id");
    }
    public function setCustomerProductViewed($customer_id, $product_viewed_ids){
	try{
	    Mage::helper('fahasa_customer')->setCustomerStore($customer_id, "product_viewed", serialize($product_viewed_ids));
	} catch (Exception $ex) {}
    }
    public function getCustomerProductViewed($customer_id){
	try{
	    $product_viewed_ids = Mage::helper('fahasa_customer')->getCustomerStore($customer_id, "product_viewed");
	    if(!empty($product_viewed_ids)){
		return unserialize($product_viewed_ids);
	    }
	} catch (Exception $ex) {}
	return null;
    }
    
    public function getKeyHotFromDB($limit = 5){
	try{
	    $reader = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $sql = "select value, url from fhs_customer_keyword_hot order by sort_order desc limit ".$limit.";";
	    return $reader->fetchAll($sql);
	} catch (Exception $ex) {}
	return null;
    }
    protected function _isLoggedIn() {
        return $this->_getCustomerSession()->isLoggedIn();
    }
    protected function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }
}