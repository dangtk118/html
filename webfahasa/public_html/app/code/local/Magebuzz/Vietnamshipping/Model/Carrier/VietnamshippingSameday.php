<?php

/*
 * Copyright (c) 2014 www.magebuzz.com
 */

class Magebuzz_Vietnamshipping_Model_Carrier_VietnamshippingSameday extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'vietnamshippingsameday';

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
        } else {
            return false;
        }
        
        $country = $shippingAddress->getCountry();
        $regionId = $shippingAddress->getRegionId();

        if ($country != 'VN') {
            return false;
        }
	$expected_delivery_datetime = Mage::getSingleton('customer/session')->getExpectedDeliveryDateTimeSameday();
	if(empty($expected_delivery_datetime)){
            return false;
	}
	
        $modelVietnamshippingRule = Mage::getModel('vietnamshipping/rule');
        $district = Mage::getModel('vietnamshipping/district')->getCollection()
                ->addFieldToFilter('status', '1')
                ->addFieldToFilter('district_name', $shippingAddress->getCity())
                ->getFirstItem();
        if (isset($district)) {
            $areaId = $district->getAreaId();
        }
        if (!$areaId && $regionId) {
            $query = "SELECT vd.area_id FROM " . $this->_getTableName('vietnamshipping_province') . " vd JOIN " . $this->_getTableName('directory_country_region') . " dr ON vd.`province_id`=dr.`province_id` WHERE dr.`region_id`= " . $regionId . "";
            $areaId = $this->_getReadConnection()->fetchOne($query);
        }
        if ($areaId) {
            $areaModel = Mage::getModel('vietnamshipping/area')->load($areaId);
            $shippingSameday = $areaModel->getShippingSameday();
            $shippingSamedayFixedPrice = $areaModel->getShippingSamedayFixedPrice();
            if ($shippingSameday && $shippingSamedayFixedPrice) {
                $priceShippingSameday = $shippingSamedayFixedPrice;

                // check if there is any rule for discounting for shipping cost
                $applyCartRules = Mage::helper('vietnamshipping')->getApplyCartRule($shippingAddress);
                $applyItemRules = Mage::helper('vietnamshipping')->getApplyItemRule($request->getAllItems());
                $appliedRuleIds = array();
                if (count($applyCartRules)) {
                    foreach ($applyCartRules as $_cartRule) {
                        $areaIds = unserialize($_cartRule->getAreaId());
                        if (!empty($areaIds) && !in_array($areaId, $areaIds)) {
                            continue;
                        }
                        $appliedRuleIds[] = $_cartRule->getId();
                    }
                }
                if (count($appliedRuleIds)) {
                    if (count($applyItemRules)) {
                        foreach ($applyItemRules as $_itemRule) {
                            if (!in_array($_itemRule->getId(), $appliedRuleIds)) {
                                continue;
                            }

                            $areaIds = unserialize($_itemRule->getAreaId());
                            if (!empty($areaIds) && !in_array($areaId, $areaIds)) {
                                continue;
                            }
                            if ($_itemRule->getApplyToShipping() == 'free') {
                                return false;
                            }

                            if ($_itemRule->getApplyToShipping() == 'discount_shipping_sameday') {
                                if ($_itemRule->getSimpleAction() == 'by_percent') {
                                    $percent = 100 - ($_itemRule->getDiscountAmount());
                                    $priceShippingSameday = ($priceShippingSameday * $percent) / 100;
                                } else {
                                    $priceShippingSameday = $priceShippingSameday - ($_itemRule->getDiscountAmount());
                                }
                            }
                        }
                    }
                }
                if ($priceShippingSameday <= 0) {
                    $priceShippingSameday = 0;
                }

                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($this->_code);
                $method->setMethod($this->_code);
                $method->setCarrierTitle($this->getConfigData('title'));
//		    $method->setMethodTitle($this->getConfigData('name'));
                //$transLabelVct = Mage::helper("vietnamshipping")->__('from %s to %s days', $kv_from, $kv_to);
                $method->setMethodTitle($expected_delivery_datetime);
                $method->setPrice($priceShippingSameday);
                $method->setCost($this->getConfigData('price'));
                $result->append($method);
                return $result;
            }
        }

        return false;
    }

    public function getAllowedMethods() {
        return array('vietnamshippingsameday' => $this->getConfigData('name'));
    }

    protected function _getReadConnection() {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    protected function _getTableName($name) {
        return Mage::getSingleton('core/resource')->getTableName($name);
    }

}
