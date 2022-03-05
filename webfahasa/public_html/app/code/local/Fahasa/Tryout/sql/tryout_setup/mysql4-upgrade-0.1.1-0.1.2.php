<?php

$installer = $this;

$installer->startSetup();

$installer->run("alter table fhs_tryout_action_log add column `amountBefore` decimal(12, 0) default 0; "
        . "alter table fhs_tryout_action_log change `leftOver` `amountAfter` decimal(12, 0); "
        . "alter table fhs_tryout_action_log change `amount` `value` decimal(12, 0);");
$installer->endSetup(); 