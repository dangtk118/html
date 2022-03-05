<?php
require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app('admin')->setUseSessionInUrl(false);                                                                                                                 
#attribute id for supplier_list = 194
#attribute id for publisher_list =   
$attribute_id_to_be_imported = 194;

#Query list of option to be imported

$read = Mage::getSingleton('core/resource')->getConnection('core_read');
$optionSql = "select ncc.nccCode, ncc.name as 'supplierName', sup_list.value
	    from (
		select ncc.value as nccCode, keysearch.name
		from fhs_catalog_product_entity pe
		join fhs_catalog_product_entity_varchar ncc on pe.entity_id = ncc.entity_id and ncc.attribute_id=157 and ncc.value != ''
		join fhs_page_keyword_url keysearch on keysearch.dataId = ncc.value and keysearch.`type` = 'supplier'
		group by keysearch.name
	    ) ncc
	    left join
	    (
		    select opt_value.option_id, opt_value.value
		    from fhs_eav_attribute_option opt 
		    join fhs_eav_attribute_option_value opt_value on opt_value.option_id = opt.option_id
		    where opt.attribute_id = 194
	    ) sup_list on sup_list.value = ncc.name
	    where sup_list.option_id is null;";
$readresults = $read->query($optionSql);
$option_list = $readresults->fetchAll();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
#Import option base on attribute id
foreach($option_list as $o){
    try{
        echo "Process " . $o['supplierName'] . PHP_EOL;
        $option = array();
        $option['attribute_id'] = $attribute_id_to_be_imported;
	    $option['value']['$o["supplierName"]'][0] = $o["supplierName"];
        $setup->addAttributeOption($option);
    }catch(Exception $e){
        echo "Exception when process option " . $o['supplierName'] . " : Error Message is: ".$e->getMessage().PHP_EOL;
    }
}
echo print_r($option);

echo PHP_EOL . "Complete." . PHP_EOL;
