<?php
/*
* Copyright (c) 2013 www.magebuzz.com
*/

$installer = $this;
$installer->startSetup();
$installer->run("
 ALTER TABLE {$this->getTable('vietnamshipping_area')} ADD `shipping_express_price` smallint(6) NOT NULL default '0';
 ALTER TABLE {$this->getTable('vietnamshipping_area')} ADD `shipping_express_fixed_price` decimal(12,3) default '0';
 ");
$installer->endSetup(); 

