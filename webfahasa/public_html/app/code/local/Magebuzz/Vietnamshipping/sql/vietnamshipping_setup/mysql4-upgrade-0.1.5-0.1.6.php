<?php
/*
* Copyright (c) 2013 www.magebuzz.com
*/

$installer = $this;
$installer->startSetup();
$installer->run("
 ALTER TABLE {$this->getTable('vietnamshipping_shippingweight')} ADD `status` smallint(6) unsigned NULL default '0';
 ");
$installer->endSetup(); 