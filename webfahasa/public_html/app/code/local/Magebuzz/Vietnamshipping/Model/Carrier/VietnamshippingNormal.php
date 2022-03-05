<?php

/*
 * Copyright (c) 2014 www.magebuzz.com
 */

class Magebuzz_Vietnamshipping_Model_Carrier_VietnamshippingNormal extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'vietnamshippingnormal';
    

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
        $priceShippingNormal = 0;
        $modelVietnamshippingRule = Mage::getModel('vietnamshipping/rule');

        $district = Mage::getModel('vietnamshipping/district')->getCollection()
                ->addFieldToFilter('status', '1')
                ->addFieldToFilter('district_name', $city)
                ->getFirstItem();
        if (isset($district)) {
            $areaId = $district->getAreaId();
        }
        if ($areaId) {
            $priceShippingNormal = Mage::getModel('vietnamshipping/area')->load($areaId)->getPriceShippingNormal();
        } elseif ($regionId) {
            //if cannot get area by District-city, try to find the area by province
            $query = "
			SELECT vd.area_id FROM " . $this->_getTableName('vietnamshipping_province') . " vd 
			JOIN " . $this->_getTableName('directory_country_region') . " dr 
			ON vd.`province_id`=dr.`province_id` 
			WHERE dr.`region_id`= " . $regionId . "
			";
            $areaId = $this->_getReadConnection()->fetchOne($query);

            if ($areaId) {
                $priceShippingNormal = Mage::getModel('vietnamshipping/area')->load($areaId)->getPriceShippingNormal();
            }
        }

        if (!$areaId) {
            return false;
        }

        $applyCartRules = Mage::helper('vietnamshipping')->getApplyCartRule($shippingAddress);
        $applyItemRules = Mage::helper('vietnamshipping')->getApplyItemRule($request->getAllItems());
        $notContinueItemRule = false;
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
        
        //if we want open 2 rule: 1 discount amount rule + 1 increase amount rule
        //rule discount amount: priority's > priority of the discount amount rule because the loop will 
        
        $noApplyCouponAmount = 0;
        $noApplyCouponWholeAmount = 0;
        $noApplyNumFreeshipAmount = 0; //use for calculate use freeship in account
        //the flag to mark whether shipping amount has been discounted or not. we want shipping fee has been discounted 1 times. (It is instead for "break" in behind for loop function)
        //but increasing shipping fee is multiple times
        $hadDiscounted = false;
        $originShippingFee = $priceShippingNormal; //it is used for save the original fee to calculate discount amount by_percent. Ex: 10K hcm, 20K other province.
        
        
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

                    if ($_itemRule->getApplyToShipping() == 'discount_shipping_normal') {
                        if ($_itemRule->getSimpleAction() == 'by_percent') {
                            if (!$hadDiscounted){
                                $percent = ($_itemRule->getDiscountAmount());
                                $priceShippingNormal = $priceShippingNormal - ($originShippingFee * $percent) / 100;
                                $hadDiscounted = true;
                            }
                        } else if($_itemRule->getSimpleAction() == 'cart_fixed_increase'){
                            $priceShippingNormal = $priceShippingNormal + ($_itemRule->getDiscountAmount());
                            
                            if ($_itemRule->getAcceptApplyCoupon() == Magebuzz_Vietnamshipping_Model_Rule::ACCEPT_APPLY_DEFAULT_AMOUNT)
                            {
                                //cho phep ap dung ma giam gia tren phan phi ship mac dinh + num_freeship chi ap dung phi ship mac dinh
                                
                                //khi co rule no_accept_apply_whole_amount: gia tri noApplyCouponAmount khong con y nghia
                                // vi rule no_accept_apply_whole_amount khong cho phep giam gia phi ship mac dinh
                                //vi $noApplyCouponAmount: se cong cong gom boi originShippingFee + $noApplyCouponWholeAmount - applyCouponAmount
                                $noApplyCouponAmount += $_itemRule->getDiscountAmount();
                                $noApplyNumFreeshipAmount += $_itemRule->getDiscountAmount();
                            } else if ($_itemRule->getAcceptApplyCoupon() == Magebuzz_Vietnamshipping_Model_Rule::ACCEPT_APPLY_COUPON_WHOLE_AMOUNT){
                                //cho phep ap dung giam gia cho toan bo phi ship + num_freeship chi ap dung phi ship mac dinh
//                              $noApplyNumFreeshipAmount += $_itemRule->getDiscountAmount();
                            } 
                            else if ($_itemRule->getAcceptApplyCoupon() == Magebuzz_Vietnamshipping_Model_Rule::NO_ACCEPT_APPLY_WHOLE_AMOUNT){
                                //khong cho phep ap dung ma giam gia + num_freeship
                                
                                //khong xet $noApplyCouponWholeAmount = priceShippingNormal + item's discountAmount vi co the co 2 rule cung dieu kien no_accept_apply_whole_amount
                                //$noApplyCouponWholeAmount se duoc cong them priceShippingNormal o doan code ben duoi neu nhu co 1 rule thuoc dieu dien nay xay ra
                                $noApplyCouponWholeAmount += $_itemRule->getDiscountAmount();
                            }
                        } else{
                            if (!$hadDiscounted){
                                $priceShippingNormal = $priceShippingNormal - ($_itemRule->getDiscountAmount());
                                $hadDiscounted = true;
                            }
                        }
//                        break;
                    }
                }
            }
        }
        
        //if $noApplyCouponWholeAmount > 0: there is at least 1 rule which doesn't accept apply whole amount -> so we don't accept apply account's num_freeship
        if ($noApplyCouponWholeAmount > 0){
            $noApplyCouponAmount = $originShippingFee + $noApplyCouponWholeAmount + $noApplyCouponAmount;
            //when there is a rule which not accept apply coupon for whold amount -> we can apply num_freeship for order
            $noApplyNumFreeshipAmount = $priceShippingNormal;
        }  
        
        $session = Mage::getSingleton('checkout/session');
        if ($priceShippingNormal <= 0) {
            $priceShippingNormal = 0;
            $session->unsetData('onestepcheckout_freeship');
            $session->unsetData('onestepcheckout_freeship_amount');
        } elseif ($priceShippingNormal > 0) {
            $is_freeship = $session->getData('onestepcheckout_freeship');
            if ($is_freeship == 1) {
                $numFreeship = Mage::helper('freeship')->getFreeShip();
                if ($numFreeship > 0) {
                    $freeship_amount = $priceShippingNormal - $noApplyNumFreeshipAmount;
                    $session->setData('onestepcheckout_freeship_amount', $priceShippingNormal);
                    $priceShippingNormal = $priceShippingNormal - $freeship_amount;
                } else {
                    $session->unsetData('onestepcheckout_freeship');
                    $session->unsetData('onestepcheckout_freeship_amount');
                }
            }
        }

        $method = Mage::getModel('shipping/rate_result_method');
        $method->setCarrier($this->_code);
        $method->setMethod($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
	$expected_delivery_datetime = Mage::getSingleton('customer/session')->getExpectedDeliveryDateTimeNormal();
	if(!empty($expected_delivery_datetime)){
	    $method->setMethodTitle($expected_delivery_datetime);
	}
        //$method->setMethodTitle($this->getConfigData('name'));
//        $method->setMethodTitle($transLabelVct);
        
        //check quote has any specific product which delivery in specific in province
//        $priceShippingNormal = $this->calculateUrbanDeliveryFee($shippingAddress, $priceShippingNormal);
        if ($noApplyCouponAmount == 0){
            $session->unsNoApplyCouponShippingAmount();
        } else {
            $session->setNoApplyCouponShippingAmount($noApplyCouponAmount);
        }
        
        $method->setPrice($priceShippingNormal);
        $method->setCost($priceShippingNormal);
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

    /**
     * Lay ngay bat dau phan loai van chuyen thuong
     * <pre>
     * KV1: 2-4 ngay
     * KV2: 4-7 ngay
     * KV3-KV4: 6-10 ngay
     * </pre>
     * @param type $areaId la Id khu vuc trong Vietnamshipping model
     * @param type $defaultVctLabel: Label cua van chuyen thuong trong Vietnamshipping model
     * @return string
     */
    public function getLoaiVCTFrom($areaId, $defaultVctLabel, $checkVct) {        
        if ($checkVct == "vietnamshippingnormal") {
        $fr_kv = null;
            $kvModel = Mage::getModel('phanloaivct/khuvuc')->getCollection()->addFieldToFilter('khuvuc_id',$areaId);

            $kv_froms = current($kvModel->getData('khuvuc_from'));
            foreach($kv_froms as $key => $value){
                if($key == 'khuvuc_from'){
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
        if ($checkVct == "vietnamshippingnormal") {
        $to_kv = null;
            $kvModel = Mage::getModel('phanloaivct/khuvuc')->getCollection()->addFieldToFilter('khuvuc_id',$areaId);

            $kv_froms = current($kvModel->getData('khuvuc_to'));
            foreach($kv_froms as $key => $value){
                if($key == 'khuvuc_to'){
                    $to_kv = $value;
                }
            }
        }
        return $to_kv;
    }
    
    public function getShippingFeeNormal($quote) {
        $priceShippingNormal = 0;
        $shippingAddress = $quote->getShippingAddress();
        $regionId = $shippingAddress->getRegionId();
        $city = $shippingAddress->getCity();
        $district = Mage::getModel('vietnamshipping/district')->getCollection()
                ->addFieldToFilter('status', '1')
                ->addFieldToFilter('district_name', $city)
                ->getFirstItem();
        if (isset($district)) {
            $areaId = $district->getAreaId();
        }
        if ($areaId) {
            $priceShippingNormal = Mage::getModel('vietnamshipping/area')->load($areaId)->getPriceShippingNormal();
        } elseif ($regionId) {
            //if cannot get area by District-city, try to find the area by province
            $query = "
			SELECT vd.area_id FROM " . $this->_getTableName('vietnamshipping_province') . " vd 
			JOIN " . $this->_getTableName('directory_country_region') . " dr 
			ON vd.`province_id`=dr.`province_id` 
			WHERE dr.`region_id`= " . $regionId . "
			";
            $areaId = $this->_getReadConnection()->fetchOne($query);

            if ($areaId) {
                $priceShippingNormal = Mage::getModel('vietnamshipping/area')->load($areaId)->getPriceShippingNormal();
            }
        }
        if (!$areaId) {
            return $priceShippingNormal;
        }

        $applyCartRules = Mage::helper('vietnamshipping')->getApplyCartRule($shippingAddress);
        $applyItemRules = Mage::helper('vietnamshipping')->getApplyItemRule($quote->getAllItems());
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

        //if we want open 2 rule discount: we only accept discount 1 rule - the 1'st priority
        //rule discount amount: priority's > priority of the other discount amount rule because the loop has the flag for marking
        
        $noApplyCouponAmount = 0;
        //the flag to mark whether shipping amount has been discounted or not. we want shipping fee has been discounted 1 times. (It is instead for "break" in behind for loop function)
        //but increasing shipping fee is multiple times
        $hadDiscounted = false;
        $originShippingFee = $priceShippingNormal; //it is used for save the original fee to calculate discount amount by_percent. Ex: 10K hcm, 20K other province.
        
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

                    if ($_itemRule->getApplyToShipping() == 'discount_shipping_normal') {
                       if ($_itemRule->getSimpleAction() == 'by_percent') {
                            if (!$hadDiscounted){
                                $percent = ($_itemRule->getDiscountAmount());
                                $priceShippingNormal = $priceShippingNormal - ($originShippingFee * $percent) / 100;
                                $hadDiscounted = true;
                            }
                        } else if($_itemRule->getSimpleAction() == 'cart_fixed_increase'){
                            $priceShippingNormal = $priceShippingNormal + ($_itemRule->getDiscountAmount());
                            //we will calculate shiping fee of rule which doesn't accept apply freeship coupon code
                            if (!$_itemRule->getAcceptApplyCoupon()){
                                $noApplyCouponAmount += $_itemRule->getDiscountAmount();
                            }
                        } else{
                            if (!$hadDiscounted){
                                $priceShippingNormal = $priceShippingNormal - ($_itemRule->getDiscountAmount());
                                $hadDiscounted = true;
                            }
                        }
                    }
                }
            }
        }
        
//        $priceShippingNormal = $this->calculateUrbanDeliveryFee($shippingAddress, $priceShippingNormal);
             
        return $priceShippingNormal;
    }
    
    //function calculate delivery fee for fresh product
    public function calculateUrbanDeliveryFee($shippingAddress, $priceShippingNormal)
    {
        if (Mage::getStoreConfig('vietnamshipping/general/enable_urban_delivery'))
        {
            $quoteItems = $shippingAddress->getQuote()->getAllItems();
            $priceUrbanShipping = Mage::getStoreConfig('vietnamshipping/general/urban_delivery_fee');
            $num_items = count($quoteItems);
            $num_items_specific_delivery = Mage::helper('onestepcheckout')->getNumItemsHasSpecificDelivery($quoteItems);
            if ($num_items_specific_delivery > 0)
            {
                if ($num_items > $num_items_specific_delivery)
                {
                    $priceShippingNormal += $priceUrbanShipping;
                }
                else
                {
                    $priceShippingNormal = $priceUrbanShipping;
                }
            }
        }
        return $priceShippingNormal;
    }
    
    //function get urban shipping fee: 25K. It is different calculateUrbanDeliveryFee
    public function getUrbanDeliveryFee($shippingAddress)
    {
        $priceShippingNormal = 0;
        if (Mage::getStoreConfig('vietnamshipping/general/enable_urban_delivery'))
        {
            $quoteItems = $shippingAddress->getQuote()->getAllItems();
            $priceUrbanShipping = Mage::getStoreConfig('vietnamshipping/general/urban_delivery_fee');
            $num_items_specific_delivery = Mage::helper('onestepcheckout')->getNumItemsHasSpecificDelivery($quoteItems);
            if ($num_items_specific_delivery > 0)
            {
                $priceShippingNormal = $priceUrbanShipping;
            }
        }
        return $priceShippingNormal;
    }

}
