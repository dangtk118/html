<?php
class Fahasa_Catalog_Block_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{
    
    public function setCollection($collection)
    {   
        parent::setCollection($collection);
        
        if (strpos((string)$this->getCollection()->getSelect(), "stock_status") == false ){
            if ($_GET['in_stock'] == null || $_GET['in_stock'] == "1") {
                $this->getCollection()->joinField('stock_status', 'cataloginventory/stock_status', 'stock_status', 'product_id=entity_id', null, 'left');
                $this->getCollection()->getSelect()->where('at_stock_status.stock_status = ?', "1");
            }
        }
        
        $currentSortBy = $this->getCurrentOrder();
        if ($currentSortBy) {
            if (strpos((string)$this->getCollection()->getSelect(), "searchindex_result_mage_catalog_product") == false) {
                $this->getCollection()->getSelect()->order($currentSortBy . ' desc');
                $this->getCollection()->distinct(false);
            }
            else {
                $this->getCollection()->getSelect()->reset(Zend_Db_Select::ORDER);                
                if ($currentSortBy == 'relevance') {
                    $this->getCollection()->getSelect()->order('relevance desc')->order('entity_id desc');
                }
                else {
                    $this->getCollection()
                         ->getSelect()
                         ->order('relevance desc')
                         ->order($currentSortBy == 'price' ? 'min_price asc' : $currentSortBy . ' desc');                         
                }
            }
        }
        
        return $this;
    }
    
    public function getSortInStock() {
        return array(
            "in stock" => 1, 
            "show all" => 0
            );
    }       
    
    /*
     * set default order = "" 
     */
    
    public function getCurrentOrder()
    {
        $orders = $this->getAvailableOrders();

        $order = $this->getRequest()->getParam($this->getOrderVarName());
        if($order == null && isset($orders[$order])){
            if(strpos($this->getRequest()->getRequestUri(), "/catalog/category/view/id/") === FALSE ){
                return "";
            } else {
                return isset($orders["num_orders"]) ? "num_orders" : "";
            }
        }else{
            return $order;
        }
    }

}
