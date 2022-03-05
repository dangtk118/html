<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE fahasa_user_event_log add customer_id int(10) unsigned default null;
    ALTER TABLE fahasa_user_event_log ADD attend_int int(10) unsigned default null;
    ALTER TABLE fahasa_user_event_log DROP INDEX `UC_Person`;
    ALTER TABLE fahasa_user_event_log  ADD UNIQUE KEY `UC_event_user` (`event_id`,`email`,`attend_code`, `attend_int`);
");

$installer->endSetup();
