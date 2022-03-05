<?php

$installer = $this;

$installer->startSetup();

$installer->run("ALTER TABLE {$this->getTable('customer/entity')} ADD telephone varchar(255);");
$installer->run("ALTER TABLE {$this->getTable('customer/entity')} ADD refer_code varchar(255);");
$installer->run("ALTER TABLE {$this->getTable('customer/entity')} ADD refer_status int default 0;");
$installer->run("ALTER TABLE {$this->getTable('customer/entity')} ADD refer_rule int;");

$installer->run("DROP TABLE IF EXISTS {$this->getTable('telephone_opt_log')};");
$installer->run("
CREATE TABLE {$this->getTable('telephone_otp_log')} (
    id int unsigned NOT NULL auto_increment,
    customer_id int NOT NULL,
    telephone varchar(64) NOT NULL,
    otp_code varchar(64) NOT NULL,
    expire_otp DATETIME,
    curent_sent int default 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    action varchar(64) NOT NULL DEFAULT 'create otp',
    PRIMARY KEY (id),
    UNIQUE (otp_code)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
");

$installer->endSetup();
