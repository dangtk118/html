<?php

$installer = $this;

$installer->startSetup();

$quoteTable = $installer->getTable('sales/quote');
$installer->run("ALTER TABLE {$quoteTable} ADD column vip_id varchar(64) NULL;");

$orderTable = $installer->getTable('sales/order');
$installer->run("ALTER TABLE {$orderTable} ADD column vip_id varchar(64) NULL;");

$installer->endSetup(); 