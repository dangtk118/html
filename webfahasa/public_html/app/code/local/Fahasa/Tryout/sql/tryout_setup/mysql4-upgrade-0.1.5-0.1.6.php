<?php

$installer = $this;

$installer->startSetup();

$installer->run("ALTER TABLE fhs_customer_entity ADD COLUMN vip_level int(11) default 0;");
$installer->endSetup();