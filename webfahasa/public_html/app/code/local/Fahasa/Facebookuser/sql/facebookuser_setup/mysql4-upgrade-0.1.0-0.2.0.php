<?php

$installer = $this;

$installer->startSetup();

$installer->run("
    create table fhs_facebook_confirm(
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`facebook_id` varchar(64) NOT NULL,
	`facebook_key` varchar(64) NOT NULL,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	primary key (`id`),
        UNIQUE (`facebook_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
");

$installer->run("alter table fhs_facebook_user add facebook_email varchar(256) default null;");

$installer->endSetup();
