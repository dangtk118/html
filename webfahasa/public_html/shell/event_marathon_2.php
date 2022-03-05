<?php

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$marathon_helper = Mage::helper("event/marathon");
$data = $marathon_helper->updateMarathon2();

echo "Result: ". $data['result'] . "\n";
echo "Message: ". $data['msg'] . "\n";
