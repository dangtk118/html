<?php

$installer = $this;

$installer->startSetup();

//Add new tables to handle vip_company_domain
$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('vip_company_domain')} (
        `companyEmailDomain` varchar(256) NULL,
        `vip_level` varchar(32) NULL,
        `group_id` varchar(255) NULL,                             
        PRIMARY KEY (`companyEmailDomain`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
");

$installer->endSetup();