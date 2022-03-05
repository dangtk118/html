<?php 

$installer = $this;

$installer->startSetup();

$installer->run("alter table order_zalopay add zpSystem int(1) default null after `discount`;");
$installer->run("alter table order_zalopay add channel int(4) default null after `zpSystem`;");
$installer->run("alter table order_zalopay modify column bankcode varchar(64);");

$installer->endSetup();