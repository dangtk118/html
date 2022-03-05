<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('reviews_action')};

CREATE TABLE {$this->getTable('reviews_action')} (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  review_id int(11) NOT NULL,
  customer_email varchar(64) NOT NULL, 
  created_at DATETIME DEFAULT NULL,
  type varchar(64) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup(); 