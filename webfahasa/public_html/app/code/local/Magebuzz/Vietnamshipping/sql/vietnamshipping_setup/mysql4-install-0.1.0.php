<?php

$installer = $this;

$installer->startSetup();

$installer->run("

 DROP TABLE IF EXISTS {$this->getTable('vietnamshipping_province')};
CREATE TABLE {$this->getTable('vietnamshipping_province')} (
  `province_id` int(11) unsigned NOT NULL auto_increment,
  `province_name` varchar(255) NOT NULL default '',
  `province_code` varchar(255) NOT NULL default '',
  `status` smallint(6) NOT NULL default '0',
  `area_id` int(11) unsigned NULL,
  PRIMARY KEY (`province_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('vietnamshipping_district')};
CREATE TABLE {$this->getTable('vietnamshipping_district')} (
  `district_id` int(11) unsigned NOT NULL auto_increment,
  `district_name` varchar(255) NOT NULL default '',
  `district_code` varchar(255) NOT NULL default '',
  `province_id` int(11) unsigned NOT NULL,
  `status` smallint(6) NOT NULL default '0',
  `area_id` int(11) unsigned NULL,
  PRIMARY KEY (`district_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('vietnamshipping_area')};
CREATE TABLE {$this->getTable('vietnamshipping_area')} (
  `area_id` int(11) unsigned NOT NULL auto_increment,
  `area_name` varchar(255) NOT NULL default '',
  `area_code` varchar(255) NOT NULL default '',
  `price_shipping_normal` int(11) unsigned NOT NULL ,
  `shipping_express` smallint(6) NOT NULL default '0',
  `province_ids` varchar(255) NOT NULL default '',
  `district_ids` varchar(255) NOT NULL default '',
  `status` smallint(6) NOT NULL default '0',
  PRIMARY KEY (`area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup(); 