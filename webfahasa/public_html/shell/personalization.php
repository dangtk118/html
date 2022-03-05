<?php
ini_set('memory_limit', '4096M');

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$customer_helper = Mage::helper("fahasa_customer/data");
$result = $customer_helper->copyPersonalizationDataToRedis();

echo var_dump($result);

if($result['result']){
    echo "---------------------------------------------\n";
    echo " SUCCESS ! " . $result['total'] . " products \n";
    echo "---------------------------------------------\n";
}else{
    echo "Error: " . $result['msg'];
}
