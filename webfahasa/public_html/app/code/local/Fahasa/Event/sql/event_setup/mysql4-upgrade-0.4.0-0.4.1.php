<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    create table fhs_vote_stat(
    id int(11) not  null auto_increment,
    event_id varchar(255) not null,
    product_id int(10) unsigned,
    num_real_voted int(10) unsigned,
    offset int(10) unsigned,
    total int(10) unsigned,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_updated timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    primary key (id),
    unique key UC_VOTE_STAT(event_id, product_id)
    )
");

$installer->endSetup();
