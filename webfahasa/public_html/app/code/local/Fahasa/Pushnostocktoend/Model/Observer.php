<?php

class Fahasa_Pushnostocktoend_Model_Observer {
    
    private $in_search_block = false;
    
    public function block_html_before($observer) {
        if (Mage::helper('core')->isModuleEnabled('Fahasa_Pushnostocktoend')) {
            $block = $observer->getBlock();
            $name = $block->getNameInLayout();
            $inTagBlock =  ($block->getParentBlock() != null) && ($block->getParentBlock()->getNameInLayout() == 'tag_products');
            if (($name == "search_result_list") && (!$inTagBlock)) {                
                $this->in_search_block = true;
            }
        }
    }
    
    public function block_html_after($observer) {
        if (Mage::helper('core')->isModuleEnabled('Fahasa_Pushnostocktoend')) {
            $block = $observer->getBlock();
            $name = $block->getNameInLayout();
            if ($name == "search_result_list") {
                $this->in_search_block = false;
            }
        }
    }
    
    public function shouldPushToEnd(){
        // use for app mobile
        if("/search" == $_GET['_url'] && TRUE !== $_GET['in_stock']){
            return FALSE;
        }
        if("/search" == $_GET['_url'] && TRUE === $_GET['in_stock']){
            return true;
        }
        if($this->in_search_block == false){
            return true;
        }
    }
    
    public function catalog_product_collection_load_before($observer) {
        
//        if (Mage::helper('core')->isModuleEnabled('Fahasa_Pushnostocktoend')) {
//            //we only push no stock items to end if it is not for search result.            
//            if ($this->shouldPushToEnd()) {
//                $collection = $observer->getCollection();
//
//                //Should we check if we already join the stock item table?
//                $sel = (string) $collection->getSelect();
//                //Only filter in stock for collection product
//                //For display items in bundle product, we will also display in stock
//                //and out of stock product
//                if(strpos($sel, "fhs_catalog_product_bundle") === false ){ 
//                    if (strpos($sel, 'stock_item') === false && strpos($sel, 'at_qty') === false) {
//                        $collection->getSelect()->joinLeft(
//                                array('at_qty' => $collection->getTable('cataloginventory/stock_item')), "at_qty.product_id = e.entity_id", array('is_in_stock')
//                        );
//                    }
//
//                    // Make sure on_top is the first order directive
//    //                $order = $collection->getSelect()->getPart(Zend_Db_Select::ORDER);
//    //                array_unshift($order, array('at_qty.is_in_stock', 'DESC'));
//    //                $order = $collection->getSelect()->setPart(Zend_Db_Select::ORDER, $order);                
//                    $sel = (string) $collection->getSelect();
//                    if (strpos($sel, 'stock_item') !== false && strpos($sel, 'at_qty') !== false) {
//                        $collection->getSelect()->where('at_qty.is_in_stock = ?', "1");
//                    }
//    //                $where = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
//    //                array_unshift($where, array('at_qty.is_in_stock=1'));
//    //                $collection->getSelect()->setPart(Zend_Db_Select::WHERE, $where);  
//                }
//            }
//        }
    }

}
