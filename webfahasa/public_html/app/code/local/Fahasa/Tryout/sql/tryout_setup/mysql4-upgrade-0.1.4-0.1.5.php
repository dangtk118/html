<?php

$installer = $this;

$installer->startSetup();

$installer->addAttribute('customer', 'fpoint', array(
    'type'      => 'varchar',
    'label'     => 'Fpoint',
    'input'     => 'text',
    'required'  => false
));

$installer->endSetup();