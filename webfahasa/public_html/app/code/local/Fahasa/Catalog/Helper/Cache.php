<?php
class Fahasa_Catalog_Helper_Cache extends Mage_Core_Helper_Abstract
{
    public function getBlockId($block_id){
        $result = '';
	if(!empty($block_id)){
	    $key = 'block_'.$block_id;
	    $result = $this->getData($key);
		   
	    if(empty($result)){
		$result = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId($block_id)->toHtml();
		$this->setData($key, $result);
	    }
	}
	return $result;
    }
    
    public function getCategory($cat_id) {
	$result = "";
	if(!empty($cat_id)){
	    $key = 'category_'.$cat_id;
	    $result = $this->getData($key);
	    if(empty($result)){
		$result = Mage::getModel('catalog/category')->load($cat_id);
		$this->setData($key, $result);
	    }
	}
	return $result; 
    }
    
    public function getSeriesDataExtra($type, $ids){
	$result = "";
	if(!empty($ids)){
	    $key = $type.implode("_", $ids);
	    $result = $this->getData($key);
	    if(empty($result)){
		$result = Mage::helper('seriesbook')->getSeriesDataExtraFromDB($type, $ids);
		$this->setData($key, $result);
	    }
	}
	return $result; 
	
    }
    
    public function getPageKeyWord($url){
	$result = "";
	
	if(!empty($url)){
	    $key = 'seo_page_keyword'.$url;
	    try{
		$cache_data = $this->getData($key);
		if(empty($cache_data)){
		    $readConnection = Mage::getSingleton("core/resource")->getConnection("core_read");
		    $query = "select * from fhs_page_keyword_url where 
			    pageUrl = :PageUrl and type in ('supplier', 'author', 'category', 'bookname_from_exist_keysearch');";
		    $binds = array(
			"PageUrl" => $url
		    );
		    $data = $readConnection->fetchRow($query, $binds);
		    $cache_data = array(
			'data' => $data,
			'cached' => true
		    );
		    $this->setData($key, $cache_data);
		}
		$result = $cache_data['data'];
	    }catch(Exception $ex) {
		Mage::log("***[ERROR] getPageKeyWord: url=". $url.", message:".$ex->getMessage(), Zend_Log::ERR, "cache.log");
	    }
	}
	return $result; 
	
    }
    
    public function getData($key){
	$key = $this->urlSafeString($key);
        $result = '';
	if(!empty($key)){
	    $storeId = Mage::app()->getStore()->getId();
	    $queryfier = Mage::getStoreConfig('bubble_queryfier/suffix_js_css/suffix');
	    $cacheId = 'data_'.$key."_".$storeId."_".$queryfier;
	    
	    try{
		if($cache = Mage::app()->getCache()->load($cacheId)) {
		    $result = unserialize($cache);
		}
	    }catch (Exception $ex){
		Mage::log("***[ERROR] getData: key=". $key.", message:".$ex->getMessage(), Zend_Log::ERR, "cache.log");
	    }
	}
	return $result;
    }
    public function setData($key, $data){
	$key = $this->urlSafeString($key);
        $result = false;
	if(!empty($key) && !empty($data)){
	    $storeId = Mage::app()->getStore()->getId();
	    $queryfier = Mage::getStoreConfig('bubble_queryfier/suffix_js_css/suffix');
	    $cacheId = 'data_'.$key."_".$storeId."_".$queryfier;
	    
	    try{
		Mage::app()->getCache()->save(serialize($data), $cacheId, array('block_html'));
		$result = true;
	    }catch (Exception $ex){
		Mage::log("***[ERROR] setData: key=". $key.", message:".$ex->getMessage(), Zend_Log::ERR, "cache.log");
	    }
	}
	return $result;
    }
    public function urlSafeString($str){
	$str = str_replace(' ', '_', $str); // Replaces all spaces with hyphens.
	$str = preg_replace('/[^A-Za-z0-9\-]/', '_', $str); // Removes special chars.
	$str = strtolower($str); // Convert to lowercas
	return $str;
    }
    public function getCacheResult($cache_data){
	if(!empty($cache_data)){
	    if(!empty($cache_data['data'])){
		if(empty($cache_data['model'])){
		    return $cache_data['data'];
		}else{
		    $core_helper = Mage::helper('core');
		    if(!is_array($cache_data['data'])){
			$item = Mage::getModel($cache_data['model'])->setData($core_helper->jsonDecode($cache_data['data']));
			return $this->setAttributeModel($item, $cache_data['model']);
		    }else{
			$result = array();
			foreach($cache_data['data'] as $key=>$item){
			    $item = Mage::getModel($cache_data['model'])->setData($core_helper->jsonDecode($item));
			    $result[$key] = $this->setAttributeModel($item, $cache_data['model']);
			}
			return $result;
		    }
		}
	    }else{
		return null;
	    }
	}
	return null;
    }
    public function setAttributeModel($item, $model){
	switch($model){
	    case 'Mage_Catalog_Model_Resource_Eav_Attribute':
		$item->setCacheTag("EAV_ATTRIBUTE");
		$item->setEventObject("attribute");
		$item->setIdFieldName("attribute_id");
		$item->setResourceName("catalog/attribute");
		$item->setResourceCollectionName("catalog/attribute_collection");
		$item->setEventPrefix("catalog_entity_attribute");
		break;
	    case 'Mana_Seo_Model_Schema':
		$item->setIdFieldName("id");
		$item->setScope("mana_seo/schema/store_flat");
		$item->setResourceName("mana_seo/schema_store_flat");
		$item->setResourceCollectionName("mana_seo/schema_store_flat_collection");
		$item->setEventPrefix("core_abstract");
		$item->setEventObject("object");
		break;
	    case 'Mirasvit_SearchLandingPage_Model_Page':
		$item->setResourceName("searchlandingpage/page");
		$item->setResourceCollectionName("searchlandingpage/page_collection");
		$item->setEventPrefix("core_abstract");
		$item->setEventObject("object");
		break;
	    case 'Mage_Eav_Model_Entity_Type':
		$item->setResourceName("eav/entity_type");
		$item->setResourceCollectionName("eav/entity_type_collection");
		$item->setEventPrefix("core_abstract");
		$item->setEventObject("object");
		break;
	    case 'Mage_Catalog_Model_Resource_Eav_Attribute':
		$item->setCacheTag("EAV_ATTRIBUTE");
		$item->setResourceName("catalog/attribute");
		$item->setResourceCollectionName("catalog/attribute_collection");
		$item->setEventPrefix("catalog_entity_attribute");
		$item->setEventObject("attribute");
		break;
	}
	return $item;
    }
}
?>