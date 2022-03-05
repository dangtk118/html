<?php

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$buffetcombo_helper = Mage::helper("event/buffetcombo");
$result = $buffetcombo_helper->copyDataToRedis();

if($result['result']){
    echo "---------------------------------------------\n";
    echo " SUCCESS ! " . $result['total'] . " products \n";
    echo "---------------------------------------------\n";
}else{
    echo "Error: " . $result['msg'];
}
