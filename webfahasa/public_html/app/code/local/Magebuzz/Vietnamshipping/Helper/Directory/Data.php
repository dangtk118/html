<?php
/*
* Copyright (c) 2014 www.magebuzz.com 
*/
class Magebuzz_Vietnamshipping_Helper_Directory_Data extends Mage_Directory_Helper_Data {
    
        protected function getModuleStr() { 
            return "vietnamshipping";
        }
  
	protected function _getRegions($storeId) {
			$countryIds = array();

			$countryCollection = $this->getCountryCollection()->loadByStore($storeId);
			foreach ($countryCollection as $country) {
					$countryIds[] = $country->getCountryId();
			}

			/** @var $regionModel Mage_Directory_Model_Region */
			$regionModel = $this->_factory->getModel('directory/region');
			/** @var $collection Mage_Directory_Model_Resource_Region_Collection */
			$collection = $regionModel->getResourceCollection()
					->addCountryFilter($countryIds)
					->load();

			$regions = array(
					'config' => array(
							'show_all_regions' => $this->getShowNonRequiredState(),
							'regions_required' => $this->getCountriesWithStatesRequired()
					)
			);
			foreach ($collection as $region) {
					if (!$region->getRegionId()) {
							continue;
					}
					
					if ($region->getCountryId() == 'VN') {
						$province = Mage::getModel($this->getModuleStr() . '/province')->load($region->getProvinceId());
						if ($province->getStatus() == '2') {
							continue;
						}
					}
					
					$regions[$region->getCountryId()][$region->getRegionId()] = array(
							'code' => $region->getCode(),
							'name' => $this->__($region->getName())
					);
			}
			return $regions;
	}
}