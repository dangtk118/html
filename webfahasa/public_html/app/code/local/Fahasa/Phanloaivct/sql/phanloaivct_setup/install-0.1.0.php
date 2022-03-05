<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('phanloaivct_khuvuc')};
CREATE TABLE {$this->getTable('phanloaivct_khuvuc')} (
    `id` int(11) unsigned NOT NULL auto_increment,
  `khuvuc_id` int(11) unsigned NOT NULL,
  `khuvuc_from` int(11) NOT NULL,   
  `khuvuc_to` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup(); 
