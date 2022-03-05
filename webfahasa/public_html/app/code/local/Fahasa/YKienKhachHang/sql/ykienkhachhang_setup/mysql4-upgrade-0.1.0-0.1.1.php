<?php

$installer = $this;

$installer->startSetup();

$installer->run("alter table y_kien_khach_hang add column status varchar(10)  default 'sent'");
$installer->endSetup(); 