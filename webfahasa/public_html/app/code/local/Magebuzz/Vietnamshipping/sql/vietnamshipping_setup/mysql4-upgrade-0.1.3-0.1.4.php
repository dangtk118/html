<?php
/*
* Copyright (c) 2013 www.magebuzz.com
*/

$installer = $this;
$installer->startSetup();
$installer->run("
 ALTER TABLE {$this->getTable('directory_country_region')} ADD `province_id` int(11) unsigned NULL;
 ALTER TABLE {$this->getTable('directory_country_region')} ADD CONSTRAINT `FK_PROVINCE_ID_VIETNAMSHIPPING_PROVINCE_ID` FOREIGN KEY (`province_id`) REFERENCES `{$this->getTable('vietnamshipping_province')}` (`province_id`) ON DELETE CASCADE ON UPDATE CASCADE;
 ");
$installer->endSetup(); 

