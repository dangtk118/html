<?php

$installer = $this;

$installer->startSetup();

$installer->removeAttribute('customer', 'membership_id');
$installer->run("DROP TABLE IF EXISTS {$this->getTable('customer_membership')};");
$installer->run("DROP TABLE IF EXISTS {$this->getTable('membership_level')};");
$installer->run("DROP TABLE IF EXISTS {$this->getTable('customer_vip')};");
$installer->run("DROP TABLE IF EXISTS {$this->getTable('customer_vip_campaign')};");

$installer->endSetup(); 