<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Area extends Mage_Core_Model_Abstract {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }         
    
	public function _construct() {
		parent::_construct();
		$this->_init($this->getModuleStr() . '/area');
	}
  public function compareProvinceList($newArray,$oldArray,$areaId){
    $insert = array_diff($newArray, $oldArray);
    $delete = array_diff($oldArray, $newArray);
    if($insert) {
      foreach ($insert as $insertProvinceId) {
        $provinceModel = Mage::getModel($this->getModuleStr() . '/province')->load($insertProvinceId);
        $provinceModel->setAreaId($areaId);
        $provinceModel->save();	
      }
    }  
    //Zend_Debug::dump($newArray);die('xxxxxxx');
    if($delete) {
      foreach ($delete as $deleteProvinceId) {
        $provinceModel = Mage::getModel($this->getModuleStr() . '/province')->load($deleteProvinceId);
        $provinceModel->setAreaId(0);
        $provinceModel->save();	
      }
    }
    if($newArray ==null && $oldArray)  {
      foreach ($oldArray as $_deleteProvinceId) {
        //Zend_Debug::dump($_deleteProvinceId);die('xxxxxxx');
        $provinceModel = Mage::getModel($this->getModuleStr() . '/province')->load($_deleteProvinceId);
        $provinceModel->setAreaId(0);
        $provinceModel->save();	
      }
    }
  }
  public function compareDistrictList($newArray,$oldArray,$areaId){
    $insert = array_diff($newArray, $oldArray);
    $delete = array_diff($oldArray, $newArray);
    if($insert) {
      foreach ($insert as $insertDistrictId) {
        $districtModel = Mage::getModel($this->getModuleStr() . '/district')->load($insertDistrictId);
        $districtModel->setAreaId($areaId);
        $districtModel->save();	
      }
    }  
    if($delete) {
      foreach ($delete as $deleteDistrictId) {
        $districtModel = Mage::getModel($this->getModuleStr() . '/district')->load($deleteDistrictId);
        $districtModel->setAreaId(0);
        $districtModel->save();	
      }
    }
    if($newArray ==null && $oldArray)  {
      foreach ($oldArray as $_deleteDistrictId) {
        $districtModel = Mage::getModel($this->getModuleStr() . '/district')->load($_deleteDistrictId);
        $districtModel->setAreaId(0);
        $districtModel->save();	
      }
    }  
  }
}