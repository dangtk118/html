<?php

$installer = $this;

$setup = Mage::getModel( 'eav/entity_setup', 'core_setup' );

$installer->startSetup();

$setup->addAttribute( 'catalog_product', 'num_orders', array(
    'group'             => 'General',
    'label'             => 'Num Orders:',
    'note'              => '',
    'type'              => 'static',
    'input'             => 'text',
    'backend'           => 'eav/entity_attribute_backend_default',
    'source'            => '',
    'frontend'          => '',
    'required'          => false,
    'filterable'        => true,
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL));
$installer->endSetup(); 