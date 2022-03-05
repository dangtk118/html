<?php

class Fahasa_Almostcart_Block_Almostcart extends Mage_Core_Block_Template {
    public function obtainAlmostCartCollection(){
        $sqlQuery = "select al.*, image.value as image_url 
from fhs_almostcart_gift al
join fhs_catalog_product_entity_varchar image on al.gift_product_id = image.entity_id and image.attribute_id=85 
where al.status=1 
order by item_order asc;";
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $readResults = $read->query($sqlQuery);        
        return $readResults;
    }
}
