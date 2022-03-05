<?php

class Fahasa_Tabslider_Block_Recommendedapi extends Fahasa_Tabslider_Block_Tabslider1 {
    
    public function _toHtml()
    {
//        $product = Mage::registry('product');
//        $id = $product->getId();
//        $this->setData('data', '[{"recommmeded-API:{"block_type":"related", "product_id":""}}]');
        return parent::_toHtml(); 
    }
    
    public function getRelatedProductCampaignStr() {
        return "RELATED_PRODUCT_2";
    }
    
}