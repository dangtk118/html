<?php

$installer = $this;

$installer->startSetup();

$installer->run("RENAME TABLE fhs_tryout_action_log TO fhs_purchase_action_log;"
        . "ALTER TABLE fhs_purchase_action_log ADD COLUMN type varchar(64) default 'tryout';");
$installer->endSetup();