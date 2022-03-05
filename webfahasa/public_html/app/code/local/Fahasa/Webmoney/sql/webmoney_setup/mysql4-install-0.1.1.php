<?php

$installer = $this;

$installer->startSetup();

//Get status table and state table
$statusTable = $installer->getTable('sales/order_status');
$stateTable = $installer->getTable('sales/order_status_state');

//Insert new status
$installer->getConnection()->insertArray(
    $statusTable,
    array('status','label'),
    array(
        array('status' => 'webmoney_pending_payment', 'label' => 'Đợi Webmoney thanh tóan'),
        array('status' => 'webmoney_payment_success', 'label' => 'Thanh tóan Webmoney thành công')
    )
);

//Insert status map to state
$installer->getConnection()->insertArray(
    $stateTable,
    array('status', 'state', 'is_default'),
    array(
        array('status' => 'webmoney_pending_payment',
              'state' => 'pending_payment',
              'is_default' => '1'),
        array('status' => 'webmoney_payment_success',
              'state' => 'new',
              'is_default' => '1')
    )
);

$installer->endSetup(); 