<?php

require_once (dirname(__FILE__).'/../app/Mage.php');
Mage::app();

$buffetcombo_helper = Mage::helper("event/buffetcombo");
$result = $buffetcombo_helper->setTimeUsedExpired();

echo "Result: ". $result['result'] . "\n";
echo "Message: ". $result['msg'] . "\n";
