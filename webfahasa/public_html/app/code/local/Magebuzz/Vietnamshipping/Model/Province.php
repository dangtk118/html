<?php
/*
* Copyright (c) 2014 www.magebuzz.com
*/
class Magebuzz_Vietnamshipping_Model_Province extends Mage_Core_Model_Abstract {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        } 
        
	public function _construct() {
		parent::_construct();
		$this->_init($this->getModuleStr() . '/province');
	}
  public function compareDistrictList($newArray,$oldArray,$provinceId){
    $insert = array_diff($newArray, $oldArray);
    $delete = array_diff($oldArray, $newArray);
    if($insert) {
      foreach ($insert as $insertDistrictId) {
        $districtModel = Mage::getModel($this->getModuleStr() . '/district')->load($insertDistrictId);
        $districtModel->setProvinceId($provinceId);
        $districtModel->save();	
      }
    }  
    if($delete) {
      foreach ($delete as $deleteDistrictId) {
        $districtModel = Mage::getModel($this->getModuleStr() . '/district')->load($deleteDistrictId);
        $districtModel->setProvinceId(0);
        $districtModel->save();	
      }
    }  
    if($newArray==null && $oldArray) {
      foreach ($oldArray as $_deleteDistrictId) {
        $districtModel = Mage::getModel($this->getModuleStr() . '/district')->load($_deleteDistrictId);
        $districtModel->setProvinceId(0);
        $districtModel->save();	
      }
    }  
  }
}