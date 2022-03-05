<?php

$installer = $this;

$installer->startSetup();

//Add new tables to handle vip_company_domain
$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('vip_company')} (
    `companyId` varchar(64) NOT NULL,
    `companyName` varchar(256) NOT NULL,
    `companyDescription` varchar(512),
    `vip_level` int(11) NOT NULL,
    `group_id` varchar(128) NOT NULL,
    `created_at` timestamp default CURRENT_TIMESTAMP,
    `created_by` varchar(128) NOT NULL,    
    PRIMARY KEY (`companyId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
");

$installer->endSetup();
