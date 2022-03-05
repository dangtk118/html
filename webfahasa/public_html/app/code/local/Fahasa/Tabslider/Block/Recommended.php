<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Fahasa_Tabslider_Block_Recommended extends Fahasa_Tabslider_Block_Tabslider1 {
    const CONTENT_BASED = 'content_based';
    const COLLABORATIVE_FILTERING = 'collaborative_filtering';
    const SAME_AUTHOR = 'same_author';
    const MAX_ITEM = 61;
    
    protected function _beforeToHtml() {         
        $data = $this->getData();
        if ($data == null) {
            $this->setData($this->getDataString());
        }
        else {
            if ($this->getData('data') == null) {
                $this->setData('data', $this->getDataString());
            }
        }
        return parent::_beforeToHtml();
    }
    
    public function _toHtml()
    {
        $data = $this->getData('data');
        if (!$data || $data == '[]') {
            return '';
        }
        else {
            return parent::_toHtml(); 
        }
    }
    
    public function getRelatedProductCampaignStr() {
        return "RELATED_PRODUCT_2";
    }
    
    private function getDataString(){
        $product = Mage::registry('product');
        $tabsliderHelper = Mage::helper('tabslider/data');
//        $idList = $tabsliderHelper->getProductIdArray($product->getId());
        
        $seriesStr = '';
        $helperSeries = Mage::helper('seriesbook');
        $idProduct = $product->getId();
        $series_id = $helperSeries->getSeriesByProductIdFromDB($idProduct);
        if ($series_id) {
            $productsidSeries = $helperSeries->getProductsBySeriesIdFromDB($series_id,1,16);
            if ($productsidSeries && count($productsidSeries) > 0) {
                $product_id_str = implode(",", $productsidSeries);
                $seriesStr = '{"series-bo-' . $product->getId() . '": {'
                        . '       "label": "Series bộ",'
                        . '       "label_mobile": "Series bộ",'
                        . '       "list": "' . $product_id_str . '",'
                        . '       "fhsCampaign": "?fhs_campaign='.$helperSeries->getFhsCampaignSeriProductRelated().'",'
                        . '       "shouldRandom": "false",'
                        . '       "seeAllLink": "seriesbook/index/series/id/' . $series_id . '?fhs_campaign='.$helperSeries->getFhsCampaignSeriPage().'"'
                        . '}}';
            }
        }
        $idList = $tabsliderHelper->getProductIdArray($product->getId(), $seriesStr);

        return $idList;
    }

}
