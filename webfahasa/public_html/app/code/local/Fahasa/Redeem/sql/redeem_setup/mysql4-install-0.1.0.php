<?php

$installer = $this;
$installer->startSetup();
$sql = <<<SQLTEXT
CREATE TABLE `fhs_redeem` (
    `id` int(11) primary key auto_increment,
    `redeem_code` varchar(16) NOT NULL,
    `fpoint_value` int(11) unsigned DEFAULT '0',
    `freeship_value` int(11) unsigned DEFAULT '0',
    `expired_at` datetime NOT NULL,`active` int(1) DEFAULT '1',
    `description` varchar(255) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `created_by` varchar(255) DEFAULT NULL,
    `is_used` int(1) DEFAULT '0',
    `campaign_id` varchar(255) DEFAULT NULL, 
    UNIQUE KEY `UNQ_FHS_REDEEM` (`redeem_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
		
SQLTEXT;

$installer->run($sql);

$installer->run("ALTER TABLE fhs_purchase_action_log ADD redeem_code varchar(32);");

$installer->endSetup();
