<?php 

$installer = $this;
$installer->startSetup();

$installer->run("alter table fahasa_events add `fpoint_turn_cost` decimal(12,0) default null after `play_limit`;");
$installer->run("alter table fahasa_events add `revert_turn_time` int(10) unsigned default null after `fpoint_turn_cost`;");

$installer->run("alter table fahasa_event_gift add item_index int(4) unsigned default 0;");

$installer->run(" CREATE TABLE `fahasa_user_event_turn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `revert_qty` int(10) unsigned DEFAULT '0',
  `revert_times_used` int(10) unsigned DEFAULT '0',
  `buy_qty` int(10) unsigned DEFAULT '0',
  `buy_times_used` int(10) unsigned DEFAULT '0',
  `revert_time` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(64) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UC_event_buy_turn` (`event_id`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
 
$installer->endSetup();
