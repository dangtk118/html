<?php

ini_set('memory_limit', '4096M');
require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

echo "<------------------------------------------->\n";
echo "<----------------READY---------------------->\n";
echo "<------------------------------------------->\n";
    

if(Mage::getStoreConfig("customer/queue_register/enable") != 1){
    echo "This program is disable  \n";
    echo "---------------------------------------------\n";
    return;
}

echo "------->Wiew log file: customer_register_queue.log \n";

Mage::helper("fahasa_customer/register")->startRegisterOrderQueue();

echo "-------------DONE--------------\n";
return;
