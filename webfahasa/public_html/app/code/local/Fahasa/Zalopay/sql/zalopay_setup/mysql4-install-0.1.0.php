<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS `order_zalopay`;

CREATE TABLE IF NOT EXISTS `order_zalopay` (
`id` int(11) NOT NULL AUTO_INCREMENT,
 `order_id` int(11) NOT NULL,
 `apptransid` varchar(255) NOT NULL,
 `zptransid` varchar(255) NULL,
 `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `amount` DECIMAL( 10, 2 ) NOT NULL,
 `discount` DECIMAL( 10, 2 ) NULL,
 `bankcode` varchar(10) NOT NULL,
 `status` int(11) NULL DEFAULT '-1000',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
