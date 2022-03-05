<?php

$installer = $this;

$installer->startSetup();

//Add two field to handle freeship
$quoteTable = $installer->getTable('sales/quote');
$installer->run("ALTER TABLE {$quoteTable} ADD column freeship_amount decimal(12, 0) default 0");

$orderTable = $installer->getTable('sales/order');
$installer->run("ALTER TABLE {$orderTable} ADD column freeship_amount decimal(12, 0) default 0");
$installer->endSetup();