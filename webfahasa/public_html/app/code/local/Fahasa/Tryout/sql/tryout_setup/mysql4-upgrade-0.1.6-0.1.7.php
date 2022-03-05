<?php

$installer = $this;

$installer->startSetup();

$installer->run("ALTER TABLE fhs_purchase_action_log ADD COLUMN customer_id int(11) default 0;");
$installer->endSetup();