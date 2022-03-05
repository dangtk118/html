<?php

class Fahasa_Sortprice_Model_Observer {    
    
    public function catalog_product_collection_load_before($observer) {
        if (array_key_exists('order', $_GET)) {
            $sortBy = $_GET['order'];
        }
        if(isset($sortBy) && $sortBy == 'min_price'){    
            $collection = $observer->getCollection();
            $this->sortByMinPrice($collection, $sortBy);
        }
    }
    
    public static function sortByMinPrice($collection, $sortBy){
        if($sortBy == 'min_price'){    
            //Should we check if we already join the stock item table?
            $sel = (string) $collection->getSelect();                
            if (strpos($sel, 'catalog_product_index_price_store') === false) {
                $collection->joinTable('multistoreviewpricingpriceindexer/product_index_price', 'entity_id = entity_id', 
                        array('min_price' => 'min_price'), '{{table}}.store_id='.Mage::app()->getStore()->getId(), 'left');
            }
            $curOrder = $collection->getSelect()->getPart(Zend_Db_Select::ORDER);
            $alreadySortPrice = false;
            foreach($curOrder as &$order ){
                if(is_array($order) && $order[0] == "min_price"){
                    $alreadySortPrice = true;
                    $order[1] = "asc";
                    $collection->getSelect()->setPart(Zend_Db_Select::ORDER, $curOrder);
                    break;
                }
            }
            if(!$alreadySortPrice){
                $collection->getSelect()->order(array("min_price".' ASC'));
            }
        }
//        if($sortBy != null){
//            $collection->getSelect()->order(array("entity_id".' ASC')); 
//        }
    }

}
