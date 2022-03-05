<?php

$installer = $this;

$installer->startSetup();

$table = $this->getTable('available_stock');
$installer->run("ALTER TABLE ".$table." ADD COLUMN notify TINYINT(1) DEFAULT 0");

$installer->endSetup(); 