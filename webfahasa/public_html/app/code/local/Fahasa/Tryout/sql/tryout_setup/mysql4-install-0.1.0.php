<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('tryout')};
DROP TABLE IF EXISTS {$this->getTable('tryout_campaign')};

CREATE TABLE {$this->getTable('tryout')} (
  tryout_email varchar(64) NOT NULL,
  tryout_money decimal(12,0) NOT NULL,
  last_updated TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_at DATETIME DEFAULT NULL,
  campaign_id int(11) default 0,  
  PRIMARY KEY (tryout_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE {$this->getTable('tryout_campaign')} (
  campaign_id int(11) unsigned NOT NULL AUTO_INCREMENT,
  campaign_name varchar(64) NOT NULL,
  description varchar(64) NOT NULL, 
  created_at DATETIME DEFAULT NULL,
  created_by varchar(64) NOT NULL,
  PRIMARY KEY (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->run("ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `tryout_discount` DECIMAL( 12, 0 ) NOT NULL;");
$installer->run("ALTER TABLE  `".$this->getTable('sales/quote_address')."` ADD  `base_tryout_discount` DECIMAL( 12, 0 ) NOT NULL;");
$installer->run("ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `tryout_discount` DECIMAL( 12, 0 ) NOT NULL;");
$installer->run("ALTER TABLE  `".$this->getTable('sales/order')."` ADD  `base_tryout_discount` DECIMAL( 12, 0 ) NOT NULL;");

$installer->endSetup(); 