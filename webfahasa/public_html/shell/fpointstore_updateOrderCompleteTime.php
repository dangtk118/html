<?php

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$fpointstore_helper = Mage::helper("fpointstorev2/data");
$result = $fpointstore_helper->updateOrderCompleteTime();

if($result['result']){
    echo "---------------------------------------------\n";
    echo "[DONATE FPOINT INFO] \n";
    echo "---------------------\n";
    echo $result['message_donate']."\n";
    echo "---------------------------------------------\n";
    echo "UPDATE DONE ! \n";
    echo "---------------------------------------------\n";
}else{
    echo "Error: " . $result['message'];
}
