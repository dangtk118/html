<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    alter table fahasa_events add `play_limit` int(1) default 1;
");

$installer->endSetup();
