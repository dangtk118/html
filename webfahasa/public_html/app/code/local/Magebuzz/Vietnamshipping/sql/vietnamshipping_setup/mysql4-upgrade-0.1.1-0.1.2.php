<?php
/*
* Copyright (c) 2013 www.magebuzz.com
*/
$installer = $this;
$installer->startSetup();
$installer->run("
 DROP TABLE IF EXISTS {$this->getTable('vietnamshipping_store')};
CREATE TABLE {$this->getTable('vietnamshipping_store')} (
  `vietnamshipping_store_id` int(11) unsigned NOT NULL auto_increment,                                
  `rule_id` int(11)  NOT NULL ,
  `store_id` smallint(6) NOT NULL,
  PRIMARY KEY (`vietnamshipping_store_id`),
  CONSTRAINT VIETNAMSHIPING_RULE_ID FOREIGN KEY (`rule_id`) REFERENCES `{$this->getTable('vietnamshipping_rule')}` (`rule_id`) ON UPDATE CASCADE ON DELETE CASCADE
) 
ENGINE=InnoDB DEFAULT CHARSET=utf8;
 ");
$installer->endSetup(); 