<?php

$installer = $this;

$installer->startSetup();
$installer->run("    
    ALTER TABLE {$this->getTable('vip_level')}
    DROP PRIMARY KEY, ADD id INT PRIMARY KEY AUTO_INCREMENT;
    ALTER TABLE {$this->getTable('vip_level')}
    CHANGE level_id level int;
");
$installer->endSetup(); 