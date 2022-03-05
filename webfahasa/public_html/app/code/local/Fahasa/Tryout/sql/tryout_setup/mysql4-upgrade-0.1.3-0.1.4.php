<?php

$installer = $this;

$installer->startSetup();

$installer->run("ALTER TABLE fhs_customer_entity ADD COLUMN fpoint decimal(12,0) default 0;");
$installer->endSetup();