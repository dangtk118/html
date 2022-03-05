<?php
/**
 * This is to handle display grid for attribute
 */
class Openstream_CustomListing_Block_Attribute extends Openstream_CustomListing_Block_Abstract
{
    private $collectionCacheWS = array();
    
    public function getCollectionCacheWS() {
        return $this->collectionCacheWS;
    }
    
    protected function _getProductCollection()
    {
        $isMobile = Mage::helper('fhsmobiledetect')->isMobile();
        if(isset($_GET['order'])) {
            // create_at/num_order : toolbar
            $sortBy = $_GET['order'];
        } else if ($this->getSortBy()) {
            $sortBy = $this->getSortBy();
        } else{
            $sortBy = "created_at";
        }
        $attribute_code = $this->getAttributeCode();
        $attribute_value = $this->getAttributeValue();
        $attributeData = $this->getAttributeData(); //priority using this over regular attribute_code and attribute_value, as this allow multiple attributes filter
        $aData = json_decode(str_replace("'", "\"", $attributeData), true);
        $categoryId = $this->getCategoryId();
        if(($attribute_code && $attribute_value) || $aData) {
            if (is_null($this->_productCollection)) {
                $this->_productCollection = $this->getProductCollectionBaseOnAttribute($attribute_code, $attribute_value, $aData, $isMobile, $categoryId, $sortBy, false);                
                $this->collectionCacheWS[] = $this->_productCollection;
            }
        }
        return $this->_productCollection;
    }
}
