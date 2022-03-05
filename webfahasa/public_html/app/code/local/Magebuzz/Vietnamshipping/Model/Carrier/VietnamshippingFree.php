<?php

/*
 * Copyright (c) 2014 www.magebuzz.com
 */

class Magebuzz_Vietnamshipping_Model_Carrier_VietnamshippingFree extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'vietnamshippingfree';
    
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
        if (!Mage::getStoreConfig('carriers/' . $this->_code . '/active') || !Mage::getStoreConfig('vietnamshipping/general/enable_module')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');
        
        //Trung: prevent infinite recursive results from calling getQuote
        static $_getQuoteCallCount = 0;
        $shippingAddress = null;        
        if ($_getQuoteCallCount == 0) {
            $_getQuoteCallCount++;
            $shippingAddress = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress();
            $_getQuoteCallCount--;
        }
        else {
            return false;
        }
        
        $country = $shippingAddress->getCountry();
        $regionId = $shippingAddress->getRegionId();
        $city = $shippingAddress->getCity();

        if ($country != 'VN') {
            return false;
        }

        $modelVietnamshippingRule = Mage::getModel('vietnamshipping/rule');
        $district = Mage::getModel('vietnamshipping/district')->getCollection()
                ->addFieldToFilter('status', '1')
                ->addFieldToFilter('district_name', $city)
                ->getFirstItem();

        $areaId = 0;
        if (isset($district)) {
            $areaId = $district->getAreaId();
        }
        if (!$areaId && $regionId) {
            $query = "
				SELECT vd.area_id FROM " . $this->_getTableName('vietnamshipping_province') . " vd 
				JOIN " . $this->_getTableName('directory_country_region') . " dr 
				ON vd.`province_id`=dr.`province_id` 
				WHERE dr.`region_id`= " . $regionId . "
			";

            $areaId = $this->_getReadConnection()->fetchOne($query);
        }

        $applyCartRules = Mage::helper('vietnamshipping')->getApplyCartRule($shippingAddress);
        $applyItemRules = Mage::helper('vietnamshipping')->getApplyItemRule($request->getAllItems());
        $priceFreeShipping = 0;
        $isFreeShipping = false;
        $continueItemRule = false;
        $appliedRuleIds = array();
        if (count($applyCartRules)) {
            foreach ($applyCartRules as $_cartRule) {
                $areaIds = unserialize($_cartRule->getAreaId());
                if (!empty($areaIds) && !in_array($areaId, $areaIds)) {
                    continue;
                }
                if ($_cartRule->getApplyToShipping() == 'free') {
                    $priceFreeShipping = 0;
                    $appliedRuleIds[] = $_cartRule->getId();
                    //$isFreeShipping = true;
                    $continueItemRule = true;
                    break;
                }
            }
        }

        if (count($appliedRuleIds)) {
            foreach ($applyItemRules as $_itemRule) {
                if (!in_array($_itemRule->getId(), $appliedRuleIds)) {
                    continue;
                }

                $areaIds = unserialize($_itemRule->getAreaId());
                if (!empty($areaIds) && !in_array($areaId, $areaIds)) {
                    continue;
                }
                if ($_itemRule->getApplyToShipping() == 'free') {
                    $priceFreeShipping = 0;
                    $isFreeShipping = true;
                    break;
                }
            }
        }

        if (!$isFreeShipping) {
            return false;
        }
        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier($this->_code);
        $method->setMethod($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethodTitle($this->getConfigData('name'));
        $method->setPrice($priceFreeShipping);
        $method->setCost($this->getConfigData('price'));
        $result->append($method);
        return $result;
    }

    public function getAllowedMethods() {
        return array('vietnamshippingnormal' => $this->getConfigData('name'));
    }

    protected function _getReadConnection() {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    protected function _getTableName($name) {
        return Mage::getSingleton('core/resource')->getTableName($name);
    }

}
