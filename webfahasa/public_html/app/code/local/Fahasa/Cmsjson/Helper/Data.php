<?php
class Fahasa_Cmsjson_Helper_Data extends Mage_Catalog_Helper_Data
{
    public function getBlock($block_id){
	$result = array();
	$result['success'] = true;
	$result['content'] = true;
	
	$block_id = Mage::helper('fahasa_catalog/product')->cleanBug($block_id);
	if(empty($block_id)){return $result;}
	
	$result = Mage::helper('fahasa_catalog')->getContentByBlockId($block_id);
		
        return $result;
    }
}
?>