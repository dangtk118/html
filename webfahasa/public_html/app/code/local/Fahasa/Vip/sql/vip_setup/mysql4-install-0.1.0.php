<?php

$installer = $this;

$installer->startSetup();

//Add two new tables to handle tich diem
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('customer_vip')};
DROP TABLE IF EXISTS {$this->getTable('vip_level')};

CREATE TABLE {$this->getTable('customer_vip')} (
  customer_email varchar(64) NOT NULL,
  vip_id varchar(64) NOT NULL,
  group_id varchar(64) NOT NULL,
  last_updated datetime NOT NULL,
  last_updated_by varchar(64) NOT NULL,
  vip_level int(11) default 0,  
  PRIMARY KEY (customer_email,vip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('vip_level')} (
  level_id int(11) unsigned NOT NULL,
  discount_primary varchar(64) NOT NULL,
  discount_increment varchar(64) NOT NULL,  
  PRIMARY KEY (level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup(); 