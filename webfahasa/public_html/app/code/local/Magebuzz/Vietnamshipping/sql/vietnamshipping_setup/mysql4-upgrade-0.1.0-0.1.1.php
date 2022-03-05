<?php

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('vietnamshipping_rule')};
CREATE TABLE {$this->getTable('vietnamshipping_rule')} (
  `rule_id` int(11) unsigned NOT NULL auto_increment,
  `rule_name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
	`customer_groups` text NOT NULL,
  `from_date` datetime NULL,
  `to_date` datetime NULL,
  `priority` varchar(255) NOT NULL default '',
  `status` smallint(6) NOT NULL default '0',
  `conditions_serialized` text NOT NULL,
  `actions_serialized` text NOT NULL,
  `apply_to_shipping` varchar(255) NOT NULL default '',
  `simple_action` varchar(255) NOT NULL default '',
  `discount_amount` int(11) unsigned NOT NULL ,
  `area_id` text NOT NULL,
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup(); 