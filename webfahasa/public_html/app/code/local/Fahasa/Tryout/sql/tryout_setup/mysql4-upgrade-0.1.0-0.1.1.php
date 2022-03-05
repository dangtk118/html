<?php

$installer = $this;

$installer->startSetup();

$installer->run("create table IF NOT EXISTS fhs_tryout_action_log(
    id int(11) primary key auto_increment,
    account varchar(64) not null,
    action varchar(32) not null,
    amount decimal(12,0) not null,
    leftOver decimal(12,0) not null,
    updateBy varchar(64) not null,
    lastUpdated timestamp,
    order_id varchar(64) not null,
    suborder_id varchar(64) not null,
    description varchar(256) default \"\"
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
$installer->endSetup(); 