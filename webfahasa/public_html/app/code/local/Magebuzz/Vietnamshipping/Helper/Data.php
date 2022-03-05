<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Helper_Data extends Mage_Core_Helper_Abstract {

    protected $_regionsJson;
    protected $_cityJson;
    protected $_wardJson;
    protected $_wardJson2;

    protected function getModuleStr() { 
      return "vietnamshipping";
    }

    public function removeEmptyItems($array) {
	if($array!='all' && !empty($array)){
	    return $array; 
	}
    }
    public function prepareCharacter($array) {
	if (is_array($array)) { 
	    $array = array_unique($array);      
	    $array = array_filter($array, array($this, 'removeEmptyItems'));
	    $array = @implode(',', $array);
	}  
	return $array;
    }
    public function removeProvinceDuplicate($provinceId) {
	$areas = Mage::getModel($this->getModuleStr() . '/area')->getCollection();
	foreach ($areas as $_area) {
	    $arrayProvinceIds = explode(',',$_area->getProvinceIds());
	    foreach($arrayProvinceIds as $key => $value) {
		if($provinceId==$value) {
		    unset($arrayProvinceIds[$key]);
		    continue;
		}
	    }
	    $_area->setProvinceIds(Mage::helper($this->getModuleStr())->prepareCharacter($arrayProvinceIds));
	    $_area->save();
	}
    }
	
	public function formatDistrictIds($districtIds = array()) {
		if (count($districtIds)) {
			return implode(',', $districtIds);
		}	
		return '';
	}
	
    public function removeDistrictDuplicate($districtId) {
	$areas = Mage::getModel($this->getModuleStr() . '/area')->getCollection();
	foreach ($areas as $_area) {
	    $arrayDistrictIds = explode(',',$_area->getDistrictIds());
	    foreach($arrayDistrictIds as $key => $value) {
		if($districtId==$value) {
		    unset($arrayDistrictIds[$key]);
		    continue;
		}
	    }
	    $_area->setDistrictIds(Mage::helper($this->getModuleStr())->prepareCharacter($arrayDistrictIds));
	    $_area->save();
	}
    }

    public function getApplyCartRule($object) { 
	$currentStoreId = Mage::app()->getStore()->getStoreId();
	$customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
	$ruleCollection = Mage::getModel($this->getModuleStr() . '/rule')->getCollection();
	$ruleCollection->addFieldToFilter('status', 1);
	if(count($ruleCollection)) {
	    $now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, false);
	    foreach($ruleCollection as $rule) {
		$customerGroups = unserialize($rule->getCustomerGroups());
		#$storeIds = Mage::getModel($this->getModuleStr() . '/store')->getCollection()->addFieldToFilter('rule_id',$rule->getRuleId());
		#$arrayStore = $storeIds->getData(); 
		#$_storeIds = array();
		#foreach ($arrayStore as $data) {
		#	$_storeIds[] = $data['store_id'];
		#}
		#if ((!in_array($currentStoreId, $_storeIds) && !in_array(0, $_storeIds))
		#	|| !in_array($customerGroup, $customerGroups)){
		#        $ruleCollection->removeItemByKey($rule->getRuleId());
		#}
		if ($rule->getFromDate() != '' && $rule->getFromDate() != null) {
		    $fromTemp = date('Y-m-d', strtotime($rule->getFromDate())) . " 00:00:00";
		    $from 	= Mage::app()->getLocale()->date($fromTemp, Varien_Date::DATETIME_INTERNAL_FORMAT, null, false);
		    if ($from->compare($now) > 0) 
			$ruleCollection->removeItemByKey($rule->getRuleId());
		}
		if($rule->getToDate() != '' && $rule->getToDate() != null) { 
		    $toTemp = date('Y-m-d', strtotime($rule->getToDate())) . " 23:59:59";
		    $to 	= Mage::app()->getLocale()->date($toTemp, Varien_Date::DATETIME_INTERNAL_FORMAT, null, false);
		    if ($to->compare($now) < 0) 
			$ruleCollection->removeItemByKey($rule->getRuleId());
		}
		if(!$rule->validate($object)) {
		    $ruleCollection->removeItemByKey($rule->getRuleId());				
		}
	    }
	    $ruleCollection->setOrder('priority', 'asc');
	    //$item = $ruleCollection->getFirstItem();

	    if (count($ruleCollection)) 
		return $ruleCollection;
	    else 
		return array();
	}
	else{
	    return array();
	}
    }
	
    public function getApplyItemRule($allItem) {  
	$currentStoreId = Mage::app()->getStore()->getStoreId();
	$customerGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();
	$ruleCollection = Mage::getModel($this->getModuleStr() . '/rule')->getCollection();
	$ruleCollection->addFieldToFilter('status', 1);
	if (count($ruleCollection)) {
	    $now = Mage::app()->getLocale()->date(Mage::getModel('core/date')->date(), Varien_Date::DATETIME_INTERNAL_FORMAT, null, false);
	    foreach($ruleCollection as $rule) {	
		$customerGroups = unserialize($rule->getCustomerGroups());
		#$storeIds = Mage::getModel($this->getModuleStr() . '/store')->getCollection()->addFieldToFilter('rule_id',$rule->getRuleId());
		#$arrayStore = $storeIds->getData(); 
		#$_storeIds = array();
		#foreach ($arrayStore as $data) {
		#	$_storeIds[] = $data['store_id'];
		#}
		#if ((!in_array($currentStoreId, $_storeIds) && !in_array(0, $_storeIds))
		#	|| !in_array($customerGroup, $customerGroups))
		#	$ruleCollection->removeItemByKey($rule->getRuleId());
		if ($rule->getFromDate() != '' && $rule->getFromDate() != null) {
		    $fromTemp = date('Y-m-d', strtotime($rule->getFromDate())) . " 00:00:00";
		    $from 	= Mage::app()->getLocale()->date($fromTemp, Varien_Date::DATETIME_INTERNAL_FORMAT, null, false);
		    if ($from->compare($now) > 0) 
			    $ruleCollection->removeItemByKey($rule->getRuleId());
		}
		if($rule->getToDate() != '' && $rule->getToDate() != null) {
		    $toTemp = date('Y-m-d', strtotime($rule->getToDate())) . " 23:59:59";
		    $to 	= Mage::app()->getLocale()->date($toTemp, Varien_Date::DATETIME_INTERNAL_FORMAT, null, false);
		    if ($to->compare($now) < 0) 
			    $ruleCollection->removeItemByKey($rule->getRuleId());
		}   
		foreach($allItem as $_item) {       
		    $result = $rule->getActions()->validate($_item); 
		    if($result == true) {
			$validate = 1;
			break;
		    }                  
		}
		if(!$validate) {
		    $ruleCollection->removeItemByKey($rule->getRuleId());				
		}
	    }
	    $ruleCollection->setOrder('priority', 'asc');

	    if (count($ruleCollection)) 
		return $ruleCollection;
	    else 
		return array();          
	}else{
	    return array();
	}
    }
    public function getRegionsJson($region = 'VN') {
	Varien_Profiler::start('TEST: '.__METHOD__);
	if (empty($this->_regionsJson)){
	    $storeId = Mage::app()->getStore()->getId();
	    $cacheKey =  strtoupper($this->getModuleStr()) . '_REGIONS_VN_JSON_STORE' . (string)$storeId;
	    //if (Mage::app()->useCache('config')){
		$json = Mage::app()->loadCache($cacheKey);
	    //}
	    if(empty($json)) {
		$json = json_decode(Mage::helper('directory')->getRegionJsonByStore(1), true);
		if(!empty($region)){
		    if($json[$region]){
			$json = $json[$region];
			ksort($json);
		    }
		}
		if($json['VN']){
		    $json = $json['VN'];
		    ksort($json);
		}

		//if (Mage::app()->useCache('config')) {
		    Mage::app()->saveCache($json, $cacheKey, array('config'));
		//}
	    }
	    $this->_regionsJson = $json;
	}

	Varien_Profiler::stop('TEST: ' . __METHOD__);
	return $this->_regionsJson;		
    }

    public function getCityJson() {
	Varien_Profiler::start('TEST: '.__METHOD__);
	if (empty($this->_cityJson)) {
	    $storeId = Mage::app()->getStore()->getId();
	    $cacheKey =  strtoupper($this->getModuleStr()) . '_CITY_JSON_STORE' . (string)$storeId;
	    //if (Mage::app()->useCache('config')) {
		$json = Mage::app()->loadCache($cacheKey);
	    //}
	    if (empty($json)) {
		$cities = $this->_getCities($storeId);
		$helper = Mage::helper('core');
		$json = $helper->jsonEncode($cities);

		//if (Mage::app()->useCache('config')) {
		    Mage::app()->saveCache($json, $cacheKey, array('config'));
		//}
	    }
	    $this->_cityJson = $json;
	}

	Varien_Profiler::stop('TEST: ' . __METHOD__);
	return $this->_cityJson;		
    }

    public function getWardJson() {
	Varien_Profiler::start('TEST: '.__METHOD__);
	if (empty($this->_wardJson)) {
	    $storeId = Mage::app()->getStore()->getId();
	    $cacheKey =  strtoupper($this->getModuleStr()) . '_WARD_JSON_STORE' . (string)$storeId;
	    //if (Mage::app()->useCache('config')) {
		$json = Mage::app()->loadCache($cacheKey);
	    //}
	    if (empty($json)) {
		$wards = $this->_getWards();
		$helper = Mage::helper('core');
		$json = $helper->jsonEncode($wards);

		//if (Mage::app()->useCache('config')) {
		    Mage::app()->saveCache($json, $cacheKey, array('config'));
		//}
	    }
	    $this->_wardJson = $json;
	}

	Varien_Profiler::stop('TEST: ' . __METHOD__);
	return $this->_wardJson;		
    }
    public function getWard2Json() {
	Varien_Profiler::start('TEST: '.__METHOD__);
	if (empty($this->_wardJson2)) {
	    $storeId = Mage::app()->getStore()->getId();
	    $cacheKey =  strtoupper($this->getModuleStr()) . '_WARD2_JSON_STORE' . (string)$storeId;
	    //if (Mage::app()->useCache('config')) {
		$json = Mage::app()->loadCache($cacheKey);
	    //}
	    if (empty($json)) {
		$wards = $this->_getWards2();
		$helper = Mage::helper('core');
		$json = $helper->jsonEncode($wards);

		//if (Mage::app()->useCache('config')) {
			Mage::app()->saveCache($json, $cacheKey, array('config'));
		//}
	    }
	    $this->_wardJson2 = $json;
	}

	Varien_Profiler::stop('TEST: ' . __METHOD__);
	return $this->_wardJson2;		
    }

    public function getCitiesByRegion($regionId) {
	$cities = array();
	$query = "
		SELECT vd.`district_id`, vd.`district_name`, vd.`district_code`, vp.`province_id`, dcr.`region_id` FROM ".$this->_getTableName($this->getModuleStr().'_district')." vd JOIN ".$this->_getTableName($this->getModuleStr().'_province')." vp 
		ON vd.`province_id`=vp.`province_id`
		JOIN ".$this->_getTableName('directory_country_region')." dcr
		ON vp.`province_id` = dcr.`province_id`			
		WHERE vd.`status`=1 AND dcr.`region_id`='".$regionId."'
	";

	$result = $this->_getReadConnection()->fetchAll($query);

	return $result;

	// if (count($result)) {
		// foreach ($result as $_item) {
			// $cities[$_item['region_id']][$_item['district_id']] = array(
				// 'code'	=> $_item['district_code'],
				// 'name'	=> $_item['district_name']
			// );
		// }
	// }
	// return $cities;
    }

    public function isVietnamShippingEnabled() {
	$storeId = Mage::app()->getStore()->getId();
	return (bool)Mage::getStoreConfig($this->getModuleStr().'/general/enable_module', $storeId);
    }

    public function isExistedWardCode($wardCode) {
	$collection = Mage::getModel($this->getModuleStr() .'/ward')->getCollection()
		->addFieldToFilter('ward_code', $wardCode);
	if (count($collection)) {
		return true;
	}
	return false;
    }

    public function isExistedDistrictCode($districtCode) {
	$collection = Mage::getModel($this->getModuleStr() .'/district')->getCollection()
		->addFieldToFilter('district_code', $districtCode);
	if (count($collection)) {
		return true;
	}
	return false;
    }

    public function isExistedProvinceCode($province_code) {
	$collection = Mage::getModel($this->getModuleStr() . '/province')->getCollection()
		->addFieldToFilter('province_code', $province_code);
	if (count($collection)) {
		return true;
	}
	return false;
    }

    public function isExistedAreaCode($area_code) {
	$collection = Mage::getModel($this->getModuleStr() . '/area')->getCollection()
		->addFieldToFilter('area_code', $area_code);
	if (count($collection)) {
		return true;
	}
	return false;
    }
    public function isExistedProvinceCodeEditData($provinceId,$province_code) {
	$collection = Mage::getModel($this->getModuleStr() . '/province')->getCollection()
	    ->addFieldToFilter('province_code', $province_code);

	if (count($collection)) {
	    foreach($collection as $_collection) {
		if($_collection->getProvinceId()!=$provinceId) {
		  return true;
		}
	    }		
	}
      return false;
    }
    public function isExistedDistrictCodeEditData($districtId,$district_code) {
	$collection = Mage::getModel($this->getModuleStr() . '/district')->getCollection()
	    ->addFieldToFilter('district_code', $district_code);
      
	if (count($collection)) {
	    foreach($collection as $_collection) {
		if($_collection->getDistrictId()!=$districtId) {
		  return true;
		}
	    }		
	}
	return false;
    }
    public function isExistedAreaCodeEditData($areaId,$area_code) {
	$collection = Mage::getModel($this->getModuleStr() . '/area')->getCollection()
	    ->addFieldToFilter('area_code', $area_code);
	if (count($collection)) {
	    foreach($collection as $_collection) {
		if($_collection->getAreaId()!=$areaId) {
		    return true;
		}
	    }	
	}
	return false;
    }
    protected function _getCities($storeId) {
	$provinceIds = array();
	// $query = "
		// SELECT dcr.`region_id` FROM ".$this->_getTableName($this->getModuleStr() . '_province')." vp 
		// JOIN ".$this->_getTableName('directory_country_region')." dcr
		// ON vp.`province_id` = dcr.`province_id`
	// ";

	// $result = $this->_getReadConnection()->fetchCol($query);
	$cities = array();
	$query = "
		SELECT vd.`district_id`, vd.`district_name`, vd.`district_code`, vp.`province_id`, dcr.`region_id` FROM ".$this->_getTableName($this->getModuleStr() . '_district')." vd JOIN ".$this->_getTableName($this->getModuleStr() . '_province')." vp 
		ON vd.`province_id`=vp.`province_id`
		JOIN ".$this->_getTableName('directory_country_region')." dcr
		ON vp.`province_id` = dcr.`province_id`			
		WHERE vd.`status`=1
	";

	$result = $this->_getReadConnection()->fetchAll($query);
	if (count($result)) {
	    foreach ($result as $_item) {
		$cities[$_item['region_id']][$_item['district_id']] = array(
		    'code'	=> $_item['district_code'],
		    'name'	=> $_item['district_name'],
		    'id' => $_item['district_id'],
		);
	    }
	}
	return $cities;
    }
	
    protected function _getWards() {
	$wards = array();
	$query = "SELECT vw.ward_id, vw.ward_name, vw.district_id, vp.province_id, dcr.region_id, vd.district_code
		from ".$this->_getTableName($this->getModuleStr() . '_ward')." vw 
		join ".$this->_getTableName($this->getModuleStr() . '_district')." vd on vd.district_id = vw.district_id
		join ".$this->_getTableName($this->getModuleStr() . '_province')." vp on vp.province_id = vd.province_id
		join ".$this->_getTableName('directory_country_region')." dcr on dcr.province_id = vp.province_id
		where vw.status = 1;";

	$result = $this->_getReadConnection()->fetchAll($query);
	if (count($result)) {
	    foreach ($result as $_item) {
		$wards[$_item['district_code']][$_item['ward_id']] = array(
		    'name'	=> $_item['ward_name'],
		    'id' => $_item['ward_id'],
		);
	    }
	}
	return $wards;
    }
	
    protected function _getWards2() {
	$wards = array();
	$query = "SELECT vw.ward_id, vw.ward_name, vw.district_id, vp.province_id, dcr.region_id, vd.district_code
		from ".$this->_getTableName($this->getModuleStr() . '_ward')." vw 
		join ".$this->_getTableName($this->getModuleStr() . '_district')." vd on vd.district_id = vw.district_id
		join ".$this->_getTableName($this->getModuleStr() . '_province')." vp on vp.province_id = vd.province_id
		join ".$this->_getTableName('directory_country_region')." dcr on dcr.province_id = vp.province_id
		where vw.status = 1;";

	$result = $this->_getReadConnection()->fetchAll($query);
	if (count($result)) {
		foreach ($result as $_item) {
			$wards[$_item['district_id']][$_item['ward_id']] = array(
				'name'	=> $_item['ward_name'],
				'id' => $_item['ward_id'],
			);
		}
	}
	return $wards;
    }
	
    protected function _getWriteConnection() {
	return Mage::getSingleton('core/resource')->getConnection('core_write');
    }
  
    protected function _getReadConnection() {
	return Mage::getSingleton('core/resource')->getConnection('core_read');
    }
  
    protected function _getTableName($name) {
	return Mage::getSingleton('core/resource')->getTableName($name);
    }
}
