<?php 

$installer = $this;
$installer->startSetup();

$installer->run("alter table fahasa_events add `data` text default null;");
$installer->run("create table fahasa_event_share_log(
`id` int(11) not null auto_increment,
`event_id` varchar(255) not null,
`email` varchar(255) default null,
`customer_id` int(10) unsigned DEFAULT NULL,
`created_at` datetime DEFAULT CURRENT_TIMESTAMP,
`created_by` varchar(255) DEFAULT NULL,
`channel` varchar(64) default null,
`share_source` varchar(255) default null,
`share_link` varchar(255) default null,
primary key (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8");

$installer->endSetup();
