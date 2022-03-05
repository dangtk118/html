<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('salesrule/coupon'), 'sent','INT DEFAULT 0');

$installer->endSetup(); 