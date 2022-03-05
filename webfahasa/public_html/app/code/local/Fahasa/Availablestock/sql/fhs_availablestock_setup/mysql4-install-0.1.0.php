<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('available_stock')};

CREATE TABLE {$this->getTable('available_stock')} (
    id int(11) unsigned NOT NULL AUTO_INCREMENT,
    customer_email varchar(64) NULL,
    isbn varchar(64) NOT NULL,
    insert_time datetime NOT NULL,  
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup(); 