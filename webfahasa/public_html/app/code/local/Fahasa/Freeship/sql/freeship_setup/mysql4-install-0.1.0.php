<?php

$installer = $this;

$installer->startSetup();

//Add two new tables to handle tich diem
$installer->run("
CREATE TABLE IF NOT EXISTS fhs_customer_freeship (
  customer_email varchar(64) NOT NULL,
  times int(11) DEFAULT 0,
  created_at DATETIME DEFAULT NULL,
  last_updated TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

//Add two field to handle freeship
$quoteTable = $installer->getTable('sales/quote');
$installer->run("ALTER TABLE {$quoteTable} ADD column is_freeship int(11) default 0");

$orderTable = $installer->getTable('sales/order');
$installer->run("ALTER TABLE {$orderTable} ADD column is_freeship int(11) default 0");

$installer->endSetup();
