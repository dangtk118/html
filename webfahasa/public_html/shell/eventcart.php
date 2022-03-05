<?php

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$eventcart_helper = Mage::helper("eventcart/redis");
$result = $eventcart_helper->copyDataFromMysqlToRedis();

if($result['result']){
    echo "---------------------------------------------\n";
    echo "DONE ! copied eventcart from mysql to redis  \n";
    echo "---------------------------------------------\n";
}else{
    echo "Error: " . $result['message']. "\n";
}
