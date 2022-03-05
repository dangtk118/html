<?php
$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('review_coupon_code')};

CREATE TABLE {$this->getTable('review_coupon_code')} (
    review_id bigint(20) NOT NULL,
    customer_email varchar(128) NOT NULL,
    coupon_code varchar(64) NOT NULL,
    coupon_rule varchar(64) NOT NULL,    
    insert_time datetime NOT NULL,
    approve_by varchar(64) NOT NULL,
    PRIMARY KEY (review_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
