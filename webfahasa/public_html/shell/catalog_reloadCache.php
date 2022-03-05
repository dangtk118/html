<?php
ini_set('memory_limit', '4096M');
ini_set ('max_execution_time', 18000); 
require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();
Mage::getSingleton('core/translate')->init('Mage_Catelog')->init('frontend');

$catalog_helper = Mage::helper('fahasa_catalog/product');;
$result = $catalog_helper->reloadCache();

if($result['result']){
    echo "---------------------------------------------\n";
    echo "DONE ! RELOAD ALL CACHE CATALOG  \n";
    echo "---------------------------------------------\n";
}else{
    echo "Error: " . $result['error_type'];
}
