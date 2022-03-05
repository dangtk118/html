<?php

class Fahasa_Tabslider_Block_Related extends Fahasa_Tabslider_Block_Tabslider1 {
    
    public function _toHtml()
    {
        $product = Mage::registry('product');
        $id = $product->getId();
        $this->setData('data', '[{"related-products":{"block_type":"related", "product_id":"' . $id . '"}}]');
        return parent::_toHtml(); 
    }
    
    public function getRelatedProductCampaignStr() {
        return "RELATED_PRODUCT";
    }
    
}