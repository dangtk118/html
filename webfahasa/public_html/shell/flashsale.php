<?php

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$flashsale_helper = Mage::helper("flashsale/data");
$result = $flashsale_helper->copyDataFromMysqlToRedis();

if($result['result']){
    echo "---------------------------------------------\n";
    echo "DONE ! copied flashsale from mysql to redis  \n";
    echo "---------------------------------------------\n";
}else{
    echo "Error: " . $result['error_type'];
}
