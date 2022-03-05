<?php

class Fahasa_Codfee_Helper_Data extends Magebuzz_Vietnamshipping_Helper_Data {

    protected function getModuleStr() {
        return "codfee";
    }
    
    public function formatFee($amount){
        return Mage::helper('codfee')->__('COD Fee');
    }
    
    public function calculateCodFee($shippingAddress, $totalAmount) {
        $country = $shippingAddress->getCountry();
        $regionId = $shippingAddress->getRegionId();
        $city = $shippingAddress->getCity();

        if ($country != 'VN') {
            return false;
        }
        
        $codFee = 0; //we reuse the shipping normal db column

        //Figure out if there is a COD area matching the shipping address
        $district = Mage::getModel($this->getModuleStr() . '/district')->getCollection()
                ->addFieldToFilter('status', '1')
                ->addFieldToFilter('district_name', $city)
                ->getFirstItem();
        if (isset($district)) {
            $areaId = $district->getAreaId();
        }
        if ($areaId) {
            $codFee = $totalAmount / 100 * 
                    Mage::getModel($this->getModuleStr() . '/area')->load($areaId)->getPriceShippingNormal();
        } elseif ($regionId) {
            //if cannot get area by District-city, try to find the area by province
            $query = "
			SELECT vd.area_id FROM " . $this->_getTableName($this->getModuleStr() . '_province') . " vd 
			JOIN " . $this->_getTableName('directory_country_region') . " dr 
			ON vd.`province_id`=dr.`province_id` 
			WHERE dr.`region_id`= " . $regionId . "
			";
            $areaId = $this->_getReadConnection()->fetchOne($query);

            if ($areaId) {
                $codFee = $totalAmount / 100 * 
                        Mage::getModel($this->getModuleStr() . '/area')->load($areaId)->getPriceShippingNormal();
            }
        }        
        if (!$areaId) {
            return false;
        }
        
        //Find and apply rules        
        $applyCartRules = Mage::helper($this->getModuleStr())->getApplyCartRule($shippingAddress);        
        if (count($applyCartRules)) {
            foreach ($applyCartRules as $_cartRule) {
                $areaIds = unserialize($_cartRule->getAreaId());
                if (!empty($areaIds) && !in_array($areaId, $areaIds)) {
                    continue;
                }
                if ($_cartRule->getApplyToShipping() == 'discount_shipping_normal') {
                    if ($_cartRule->getSimpleAction() == 'by_percent') {
                        $codFee = $totalAmount /100 * $_cartRule->getDiscountAmount();
                    }
                    else {
                        $codFee = $_cartRule->getDiscountAmount();
                    }
                }
            }
        }
        
        return $codFee;
    }

}
