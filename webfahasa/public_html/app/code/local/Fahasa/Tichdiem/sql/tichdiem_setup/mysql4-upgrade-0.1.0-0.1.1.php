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
        array('status' => 'fhs_refund', 'label' => 'Fahasa Refunded (Canceled)')
    )
);

//Insert status map to state
$installer->getConnection()->insertArray(
    $stateTable,
    array('status', 'state', 'is_default'),
    array(
        array('status' => 'fhs_refund',
              'state' => 'canceled',
              'is_default' => '1')
    )
);

$installer->endSetup(); 