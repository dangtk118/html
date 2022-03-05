<?php

$installer = $this;

$installer->startSetup();

//Add two new tables to handle tich diem
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('td_total_score')};
DROP TABLE IF EXISTS {$this->getTable('td_score_transaction')};

CREATE TABLE {$this->getTable('td_total_score')} (
  customer_email varchar(64) NOT NULL,
  total_score int(11) NOT NULL default 0,
  last_updated datetime NOT NULL,
  last_updated_by varchar(64) NOT NULL,
  membership_level int(11) default 0,  
  PRIMARY KEY (customer_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('td_score_transaction')} (
  trans_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  customer_email varchar(64) NOT NULL,
  membership_level int(11) default 0,
  action int(11) default NULL,
  increment_id varchar(64) NOT NULL,
  amount varchar(64) NOT NULL,
  insert_time datetime NOT NULL,
  insert_by varchar(64) NOT NULL,
  score_before int,
  score_after int,
  PRIMARY KEY (trans_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup(); 