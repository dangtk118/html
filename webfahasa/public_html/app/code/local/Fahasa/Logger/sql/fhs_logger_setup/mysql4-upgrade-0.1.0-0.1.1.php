<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('logger/logcouponsent'),
    'rule_id',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length' => 11,
        'nullable'  => true,
        'comment' => 'rule_id that match with fhs_salesrule_coupon'
    ));
$installer->endSetup();
