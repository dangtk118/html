<?php

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('codfee_province')};
CREATE TABLE {$this->getTable('codfee_province')} LIKE {$this->getTable('vietnamshipping_province')};
INSERT {$this->getTable('codfee_province')} SELECT * FROM {$this->getTable('vietnamshipping_province')};

DROP TABLE IF EXISTS {$this->getTable('codfee_district')};
CREATE TABLE {$this->getTable('codfee_district')} LIKE {$this->getTable('vietnamshipping_district')};
INSERT {$this->getTable('codfee_district')} SELECT * FROM {$this->getTable('vietnamshipping_district')};

DROP TABLE IF EXISTS {$this->getTable('codfee_area')};
CREATE TABLE {$this->getTable('codfee_area')} LIKE {$this->getTable('vietnamshipping_area')};
INSERT {$this->getTable('codfee_area')} SELECT * FROM {$this->getTable('vietnamshipping_area')};
ALTER TABLE {$this->getTable('codfee_area')} MODIFY COLUMN price_shipping_normal DECIMAL(12,3);
UPDATE {$this->getTable('codfee_area')} SET price_shipping_normal = 0.8;

DROP TABLE IF EXISTS {$this->getTable('codfee_rule')};
CREATE TABLE {$this->getTable('codfee_rule')} LIKE {$this->getTable('vietnamshipping_rule')};
ALTER TABLE {$this->getTable('codfee_rule')} MODIFY COLUMN discount_amount DECIMAL(12,3) NOT NULL;

DROP TABLE IF EXISTS {$this->getTable('codfee_store')};
CREATE TABLE {$this->getTable('codfee_store')} (
  `codfee_store_id` int(11) unsigned NOT NULL auto_increment,                                
  `rule_id` int(11)  NOT NULL ,
  `store_id` smallint(6) NOT NULL,
  PRIMARY KEY (`codfee_store_id`),
  CONSTRAINT CODFEE_RULE_ID FOREIGN KEY (`rule_id`) REFERENCES `{$this->getTable('codfee_rule')}` (`rule_id`) ON UPDATE CASCADE ON DELETE CASCADE
) 
ENGINE=InnoDB DEFAULT CHARSET=utf8; 

");

$installer->endSetup();