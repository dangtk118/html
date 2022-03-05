<?php

$installer = $this;

$installer->startSetup();

$installer->addAttribute('customer', 'company_id', array(
    'type'      => 'varchar',
    'label'     => 'Company Id',
    'input'     => 'text',
    'required'  => false,
));
$attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'company_id');
$attribute->setData('used_in_forms', array(
    'adminhtml_customer',
    'checkout_register',
    'customer_account_create',
    'customer_account_edit',
));
$attribute->save();

$installer->endSetup();