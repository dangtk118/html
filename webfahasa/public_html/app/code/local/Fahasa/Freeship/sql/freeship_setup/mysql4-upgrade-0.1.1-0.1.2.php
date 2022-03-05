<?php

$installer = $this;

$installer->startSetup();

$installer->run("DROP TABLE IF EXISTS fhs_customer_freeship;");

$installer->run("ALTER TABLE fhs_customer_entity ADD COLUMN num_freeship int(11) default 0;");

$installer->endSetup();