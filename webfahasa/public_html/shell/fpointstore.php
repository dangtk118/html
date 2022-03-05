<?php

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$fpointstore_helper = Mage::helper("fpointstore/data");
$result = $fpointstore_helper->copyDataFromMysqlToRedis();

if($result['result']){
    echo "---------------------------------------------\n";
    echo "DONE ! copied fpointstore from mysql to redis  \n";
    echo "---------------------------------------------\n";
}else{
    echo "Error: " . $result['error_type'];
}
