<?php

$installer = $this;

$installer->startSetup();
$installer->run("    
    ALTER TABLE {$this->getTable('vip_level')}
    ADD COLUMN group_id varchar(64),
    ADD COLUMN group_label varchar(64);
");
$installer->endSetup(); 