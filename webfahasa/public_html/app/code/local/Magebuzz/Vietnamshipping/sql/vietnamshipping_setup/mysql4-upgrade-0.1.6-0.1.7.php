<?php
/*
* Copyright (c) 2013 www.magebuzz.com
*/

$installer = $this;
$installer->startSetup();

$attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode('customer_address', 'region');
$attributeModel->setFrontendClass('regions')
	->save();

$installer->endSetup(); 