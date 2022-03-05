<?php

class Fahasa_Amazonrating_Helper_Data extends Mage_Core_Helper_Abstract {
    
    public function getRating($_sku){
        $result = null;
        try{
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $connection->query("set character_set_results=utf8"); 
            $sql = "select * from book_rating where sku = '".$_sku."';";
            return $connection->fetchAll($sql);
        } catch (Exception $ex) {}
        return $result;
    }
}
