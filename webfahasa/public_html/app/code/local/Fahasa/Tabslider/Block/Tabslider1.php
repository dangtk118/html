<?php

/**
 * // tao them 1 block de khong pha cau truc cua tabslider
 */
class Fahasa_Tabslider_Block_Tabslider1 extends Fahasa_Tabslider_Block_Tabslider {

    private $collectionCacheWS = array();

    public function getCollectionCacheWS() {
        return $this->collectionCacheWS;
    }
    
    /* For data analytics */
    public function getRelatedProductCampaignStr() {
        return "";
    }
    
    public function getTabSliderProductCollection($type, $data, $isMobile , $limit = 0) {
        //{ "sieu-giam":{ "label":"Bão Giá", "list":"28276,","seeAllLink":"sieu-giam-gia","sort_by":"discount", "category_id":"4","block_type":"comingsoon"}}
        //If category_id is given, system will ignore "list", unless it is bestseller
        $sortBy = $data['sort_by'];
        $maxCK = $data['max_ck'];
        $minCK = $data['min_ck'];
        $categoryId = $data['category_id'];
        $blockType = $data['block_type'];   //block_type attribute or not
        $excludeCatId = $data["exclude_catId"];
        $series_id = $data['series_id'];
        
        if(!$blockType && $series_id){
            $blockType = "series";
        }
        
        switch($blockType){
            case "attribute":
                $attribute_code = $data['attribute_code'];
                $attribute_value = $data['attribute_value'];
                $attributeData = $data['attribute_data']; //priority using this over regular attribute_code and attribute_value, as this allow multiple attributes filter
                $is_tab_slider = $data['is_tab_slider'] === false ? false : true;
                $collection = $this->getProductCollectionBaseOnAttribute($attribute_code, $attribute_value, $attributeData, $isMobile, $categoryId, $sortBy, $is_tab_slider, $minCK, $maxCK, $excludeCatId);
            break;
            case "related":
                $product_id = $data['product_id'];
                $collection = $this->getProductCollectionBaseOnRelations($product_id);
            break;
            case "fill":
                $product_id_str = $data['list'];
                $exclude_prod_ids_str = $data['exclude_prod_ids'];
		$limit = $data["limit"];
                $is_tab_slider = isset($data['is_tab_slider']) ? $data['is_tab_slider'] : true;
                $collection = $this->getProductCollectionBaseOnOtherCriteria($product_id_str, $isMobile, $categoryId, $sortBy, $is_tab_slider, $minCK, $maxCK, $excludeCatId, $exclude_prod_ids_str, $limit);
            break;
            case "series":
                $limitSeries = $this->getNumberOfDisplayItem() == 0 ? 30 : $this->getNumberOfDisplayItem();
                $productsSeriesId = Mage::helper('seriesbook')->getProductsBySeriesIdFromDB($series_id,1,$limitSeries);
                if(count($productsSeriesId) > 0) {
                    $product_id_str = implode(",", $productsSeriesId);
                }
                $is_tab_slider = isset($data['is_tab_slider']) ? $data['is_tab_slider'] : true;
                $collection = $this->getProductCollectionBaseOnOtherCriteria($product_id_str, $isMobile, $categoryId, $sortBy, $is_tab_slider, $minCK, $maxCK, $excludeCatId,"", $limit);
            break;
            case "recommended":
                $product_id_str = $data['list'];
                $is_tab_slider = isset($data['is_tab_slider']) ? $data['is_tab_slider'] : true;
                $showSeriesProduct = $isMobile ? false : true; // mobile new version => allways true
                $collection = $this->getProductCollectionBaseOnOtherCriteria($product_id_str, $isMobile, $categoryId, $sortBy, $is_tab_slider, $minCK, $maxCK, $excludeCatId, "", $limit, $showSeriesProduct);
            break;
            default:
                $product_id_str = $data['list'];
                $is_tab_slider = isset($data['is_tab_slider']) ? $data['is_tab_slider'] : true;
                $collection = $this->getProductCollectionBaseOnOtherCriteria($product_id_str, $isMobile, $categoryId, $sortBy, $is_tab_slider, $minCK, $maxCK, $excludeCatId, "", $limit);
        }
        
        $this->collectionCacheWS[] = $collection;
        return $collection;
    }
}
