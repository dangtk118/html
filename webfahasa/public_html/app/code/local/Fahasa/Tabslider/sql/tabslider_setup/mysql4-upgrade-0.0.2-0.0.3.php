<?php

$installer = $this;

$setup = Mage::getModel( 'eav/entity_setup', 'core_setup' );

$installer->startSetup();

$setup->addAttribute( 'catalog_product', 'discount_percent', array(
    'group'             => 'General',
    'label'             => 'Discount Percent',
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