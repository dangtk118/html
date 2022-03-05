<?php

/*
 * Copyright (c) 2014 www.magebuzz.com
 */

class Magebuzz_Vietnamshipping_Model_Carrier_VietnamshippingExpress extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'vietnamshippingexpress';

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
            $shippingExpress = $areaModel->getShippingExpress();
            $shippingExpressPrice = $areaModel->getShippingExpressPrice();
            $shippingExpressFixedPrice = $areaModel->getShippingExpressFixedPrice();
            if ($shippingExpress) {
                if ($shippingExpressPrice && $shippingExpressFixedPrice) {
                    $priceShippingExpress = $shippingExpressFixedPrice;
                } else {
                    if ($request->getAllItems()) {
                        $totalWeight = 0;
                        foreach ($request->getAllItems() as $item) {
                            if (!$item->getProduct()->isVirtual()) {
                                $totalWeight = $totalWeight + $item->getWeight() * $item->getQty();
                            }
                        }
                        $shippingWeights = Mage::getModel('vietnamshipping/shippingweight')->getCollection()
                                ->addFieldToFilter('status', 1);

                        if (!count($shippingWeights)) {
                            return false;
                        }

                        $notContinue = 0;
                        foreach ($shippingWeights as $shippingWeight) {
                            $fromWeight = $shippingWeight->getFromWeight();
                            $toWeight = $shippingWeight->getToWeight();
                            if ($toWeight) {
                                if ($totalWeight >= $fromWeight && $totalWeight <= $toWeight) {
                                    $priceShippingExpress = $shippingWeight->getPrice();
                                    $notContinue = 1;
                                    break;
                                }
                            }
                        }
                        // weight not range in collection
                        if (!$notContinue) {
                            foreach ($shippingWeights as $_shippingWeight) {
                                $weightStep = $_shippingWeight->getWeightStep();
                                $priceStep = $_shippingWeight->getPriceStep();
                                if ($weightStep > 0 && $priceStep > 0) {
                                    $_fromWeight = $_shippingWeight->getFromWeight();
                                    $rangeWeightMore = $totalWeight - $_fromWeight;
                                    $priceMore = floor($rangeWeightMore / $weightStep) * $priceStep;
                                }
                            }
                            // result Price max
                            $priceMax = 0;
                            foreach ($shippingWeights as $_shippingWeightFindPriceMax) {
                                if ($_shippingWeightFindPriceMax->getPrice() > $priceMax) {
                                    $priceMax = $_shippingWeightFindPriceMax->getPrice();
                                }
                            }
                            $priceShippingExpress = $priceMore + $priceMax;
                        }
                    }
                }

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

                            if ($_itemRule->getApplyToShipping() == 'discount_shipping_express') {
                                if ($_itemRule->getSimpleAction() == 'by_percent') {
                                    $percent = 100 - ($_itemRule->getDiscountAmount());
                                    $priceShippingExpress = ($priceShippingExpress * $percent) / 100;
                                } else {
                                    $priceShippingExpress = $priceShippingExpress - ($_itemRule->getDiscountAmount());
                                }
                            }
                        }
                    }
                }
                if ($priceShippingExpress <= 0) {
                    $priceShippingExpress = 0;
                }

                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($this->_code);
                $method->setMethod($this->_code);
                $method->setCarrierTitle($this->getConfigData('title'));
//		    $method->setMethodTitle($this->getConfigData('name'));
                $kv_from = $this->getLoaiVCTFrom($areaId, $this->getConfigData('name'), $this->_code);
                $kv_to = $this->getLoaiVCTTo($areaId, $this->getConfigData('name'), $this->_code);
                $transLabelVct = Mage::helper("vietnamshipping")->__('from %s to %s days', $kv_from, $kv_to);
                $method->setMethodTitle($transLabelVct);
                $method->setPrice($priceShippingExpress);
                $method->setCost($this->getConfigData('price'));
                $result->append($method);
                return $result;
            }
        }

        return false;
    }

    public function getAllowedMethods() {
        return array('vietnamshippingexpress' => $this->getConfigData('name'));
    }

    protected function _getReadConnection() {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    protected function _getTableName($name) {
        return Mage::getSingleton('core/resource')->getTableName($name);
    }

    public function getLoaiVCTFrom($areaId, $defaultVctLabel, $checkVct) {
        if ($checkVct == "vietnamshippingexpress") {
            $fr_kv = null;
            $kvModel = Mage::getModel('phanloaivct/khuvuc')->getCollection()->addFieldToFilter('khuvuc_id', $areaId);

            $kv_froms = current($kvModel->getData('express_khuvuc_from'));
            foreach ($kv_froms as $key => $value) {
                if ($key == 'express_khuvuc_from') {
                    $fr_kv = $value;
                }
            }
        }
        return $fr_kv;
    }

    /**
     * Lay ngay ket thuc phan loai van chuyen thuong
     * @param type $areaId
     * @param type $defaultVctLabel
     * @param type $checkVct
     * @return type
     */
    public function getLoaiVCTTo($areaId, $defaultVctLabel, $checkVct) {
        if ($checkVct == "vietnamshippingexpress") {
            $to_kv = null;
            $kvModel = Mage::getModel('phanloaivct/khuvuc')->getCollection()->addFieldToFilter('khuvuc_id', $areaId);

            $kv_froms = current($kvModel->getData('express_khuvuc_to'));
            foreach ($kv_froms as $key => $value) {
                if ($key == 'express_khuvuc_to') {
                    $to_kv = $value;
                }
            }
        }
        return $to_kv;
    }

}
