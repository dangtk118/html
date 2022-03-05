<?php
/**
 * This is to handle display grid for products id
 */
class Openstream_CustomListing_Block_Products extends Openstream_CustomListing_Block_Abstract {

    private $collectionCacheWS = array();
    private $mapCollection = array();

    public function getCollectionCacheWS() {
        return $this->collectionCacheWS;
    }
    
    protected function _getProductCollection() {
        $isMobile = Mage::helper('fhsmobiledetect')->isMobile();
        $product_ids = explode(',', $this->getValue());        
        if(isset($_GET['order'])) {
            // create_at/num_order : toolbar
            $sortBy = $_GET['order'];
        } else if ($this->getSortBy()) {
            // block config
            $sortBy = $this->getSortBy();            
        }
        if(!$this->mapCollection["collection_pid_sort_by"]){
            $collection = $this->getProductByProductIdsWithSortBy($product_ids, $isMobile, $sortBy);
            $this->collectionCacheWS[] = $collection;
            $this->mapCollection["collection_pid_sort_by"] = $collection;
        }
        return $this->mapCollection["collection_pid_sort_by"];        
    }
}
