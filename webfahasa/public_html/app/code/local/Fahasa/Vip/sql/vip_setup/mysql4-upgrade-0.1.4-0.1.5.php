<?php

$installer = $this;

$installer->startSetup();

//Add two new tables to handle tich diem
$quoteTable = $installer->getTable('sales/quote');
$installer->run("ALTER TABLE {$quoteTable} ADD column is_vip int(11) default 0, "
. "ADD column vip_discount_amount decimal(12,0) default 0");

$orderTable = $installer->getTable('sales/order');
$installer->run("ALTER TABLE {$orderTable} ADD column is_vip int(11) default 0, "
. "ADD column vip_discount_amount decimal(12,0) default 0");

$installer->endSetup(); 