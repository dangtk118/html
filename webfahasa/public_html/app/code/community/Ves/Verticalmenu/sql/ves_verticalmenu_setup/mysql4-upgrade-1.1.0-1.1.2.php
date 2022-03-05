<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();
if (!$installer->getConnection()->tableColumnExists($this->getTable('ves_verticalmenu/verticalmenu'), "menu_icon_class")) {
	$installer->run("
		ALTER TABLE `{$this->getTable('ves_verticalmenu/verticalmenu')}` ADD COLUMN `menu_icon_class` varchar(150) NULL;
		");
}
