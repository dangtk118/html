<?php

$installer = $this;

$installer->startSetup();

//Add new tables to check exist facebook Id
$installer->run("
CREATE TABLE IF NOT EXISTS {$this->getTable('facebook_user')} (
    `facebook_id` varchar(20) NOT NULL,
    `email` varchar(256) NOT NULL,
    `created_at` datetime,
    `update_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`facebook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
");

$installer->endSetup();
