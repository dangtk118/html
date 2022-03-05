<?php
/*
* Copyright (c) 2013 www.magebuzz.com
*/
$installer = $this;
$installer->startSetup();
$installer->run("
 DROP TABLE IF EXISTS {$this->getTable('vietnamshipping_shippingweight')};
CREATE TABLE {$this->getTable('vietnamshipping_shippingweight')} (
  `shippingweight_id` int(11) unsigned NOT NULL auto_increment,                                
  `rule_name` varchar(255) NOT NULL default '',
  `from_weight` int(11) unsigned NOT NULL,
  `to_weight` int(11) unsigned NOT NULL,
  `weight_step` int(11) unsigned NULL,
  `price_step` decimal(12,3) default '0',
  `price` decimal(12,3) default '0',
  PRIMARY KEY (`shippingweight_id`)
) 
ENGINE=InnoDB DEFAULT CHARSET=utf8;
 ");
$installer->endSetup(); 