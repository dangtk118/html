<?php 
/**
 * Description of Observer
 *
 * @author hung.lam
 */
class Fahasa_Catalog_Model_Observer{
    
    public function validateBookReleaseDate(Varien_Event_Observer $observer){
	$product = $observer->getEvent()->getProduct();
	if(!empty($product)){
	    if(!empty($product->getBookReleaseDate())){
		if(!empty($product->getExpectedDate())){
		    $book_release_date = date('Y-m-d', strtotime(str_replace('/', '-', $product->getBookReleaseDate())));
		    $expected_date = date('Y-m-d', strtotime(str_replace('/', '-', $product->getExpectedDate())));
		    if($expected_date < $book_release_date){
			$product->setData('expected_date',$product->getBookReleaseDate());
		    }
		}else{
		    $product->setData('expected_date',$product->getBookReleaseDate());
		}
	    }
	}
	return $product;
    }
    
    public function beforeLoadCatalogPage(Varien_Event_Observer $observer){
	$product_helper = Mage::helper('fahasa_catalog/product');
	
	$Controller = $observer->getControllerAction();
	$params = $observer->getParams();
	$category = $observer->getCategory();
	
	$cat_id = $params['id'];
	$currentPage = "1";
	$is_series_type = false;
	
	if(isset($params['p'])){
	    $currentPage = $params['p'];
	}
	if(isset($params['limit'])){
	    $limit = $params['limit'];
	}
	if(isset($params['order'])){
	    $order = $params['order'];
	}
	if(isset($params['series_type'])){
	    $is_series_type = $params['series_type']?true:false;
	}
	if(isset($params['price'])){
	    $priceSelected = $params['price'];
	}
	if($category->getId() == 2){
	    if(!$is_series_type){
		$category->setData('name', $Controller->__('All Category'));
	    }else{
		$category->setData('name', $Controller->__('All Series'));
	    }
	    if (Mage::registry('current_category')) {
		Mage::unregister('current_category');
	    }
	    Mage::register('current_category', $category);
	}
	if(empty($limit)){
	    $limit = $product_helper->getLimit();
	}
	if(empty($order)){
	    $order = $product_helper->getOrder($is_series_type);
	}else{
	    if($is_series_type){
		$order = $product_helper->checkOrderBy('series', $order);
	    }else{
		$order = $product_helper->checkOrderBy('product', $order);
	    }
	}
	$attribute_array = Mage::getStoreConfig('catalog/catalog_cache/attribute_array');
	$attributes_filter = explode(",", $attribute_array);
	$filters_param = [];
	foreach($params as $param_key=>$value){
	    if(in_array($param_key,$attributes_filter)){
		$filters_param[$param_key] = $value;
	    }
	}
	Mage::register('catalog_param', array(
	    'cat_id' => $cat_id,
	    'filters_param' => $filters_param,
	    'limit' => $limit,
	    'currentPage' => $currentPage,
	    'order' => $order,
	    'priceSelected' => !empty($priceSelected)?$priceSelected:null,
	    'is_series_type' => $is_series_type
	));
    }
}
