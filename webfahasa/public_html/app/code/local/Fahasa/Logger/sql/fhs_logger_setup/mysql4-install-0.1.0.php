<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('log_coupon_used')};
DROP TABLE IF EXISTS {$this->getTable('log_coupon_sent')};

CREATE TABLE {$this->getTable('log_coupon_used')} (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  customer_email varchar(64) NOT NULL,
  order_id varchar(64) NOT NULL,
  coupon_code varchar(64) NOT NULL,
  coupon_rule varchar(64) NOT NULL,
  coupon_amt varchar(32) NOT NULL,
  total_amt varchar(32) NOT NULL,
  is_login int(11) default 0,
  insert_time datetime NOT NULL,  
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('log_coupon_sent')} (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  customer_email varchar(64) NOT NULL,
  customer_name varchar(64) NOT NULL,  
  coupon_code varchar(64) NOT NULL,  
  sent_time datetime NOT NULL,  
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup(); 