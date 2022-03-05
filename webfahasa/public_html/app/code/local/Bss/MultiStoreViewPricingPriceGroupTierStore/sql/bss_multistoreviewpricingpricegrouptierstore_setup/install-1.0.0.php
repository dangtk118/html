<?php
/**
* BSS Commerce Co.
*
* NOTICE OF LICENSE
*
* This source file is subject to the EULA
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://bsscommerce.com/Bss-Commerce-License.txt
*
* =================================================================
*                 MAGENTO EDITION USAGE NOTICE
* =================================================================
* This package designed for Magento COMMUNITY edition
* BSS Commerce does not guarantee correct work of this extension
* on any other Magento edition except Magento COMMUNITY edition.
* BSS Commerce does not provide extension support in case of
* incorrect edition usage.
* =================================================================
*
* @category   BSS
* @package    Bss_MultiStoreViewPricing
* @author     Extension Team
* @copyright  Copyright (c) 2015-2016 BSS Commerce Co. ( http://bsscommerce.com )
* @license    http://bsscommerce.com/Bss-Commerce-License.txt
*/
$installer = $this;

$this->startSetup();

$installer->removeAttribute('catalog_product', 'group_price_for_store');

$entityTypeId   = $installer->getEntityTypeId('catalog_product');
$attributeCode  = 'group_price_for_store';
$this->addAttribute('catalog_product', $attributeCode, array(
	'type'                       => 'decimal',
	'label'                      => 'Group Price For Store View',
	'input'                      => 'text',
	'backend'                    => 'multistoreviewpricingpricegrouptierstore/product_attribute_backend_groupprice',
	'required'                   => false,
	'sort_order'                 => 6,
	'global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'apply_to'                   => 'simple,configurable,virtual,bundle,downloadable',
	'group'                      => 'Prices',
	));

$table = $installer->getTable('multistoreviewpricingpricegrouptierstore/group_price');
if ($installer->getConnection()->isTableExists($table) != true) {
	$installer->run("
		CREATE TABLE `{$this->getTable('multistoreviewpricingpricegrouptierstore/group_price')}` (
		`value_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Value ID',
		`entity_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Entity ID',
		`all_groups` smallint(5) unsigned NOT NULL DEFAULT '1' COMMENT 'Is Applicable To All Customer Groups',
		`customer_group_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Customer Group ID',
		`value` decimal(12,4) NOT NULL DEFAULT '0.0000' COMMENT 'Value',
		`store_id` smallint(5) unsigned NOT NULL COMMENT 'Store ID',
		`is_percent` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Is Percent',
		PRIMARY KEY (`value_id`),
		UNIQUE KEY `group_qty_store` (`entity_id`,`all_groups`,`customer_group_id`,`store_id`),
		KEY `IDX_CATALOG_PRODUCT_ENTITY_GROUP_PRICE_ENTITY_ID` (`entity_id`),
		KEY `IDX_CATALOG_PRODUCT_ENTITY_GROUP_PRICE_CUSTOMER_GROUP_ID` (`customer_group_id`),
		KEY `IDX_CATALOG_PRODUCT_ENTITY_GROUP_PRICE_STORE_ID` (`store_id`),
		CONSTRAINT `FK_MSP_GR__CUS_GR_ID` FOREIGN KEY (`customer_group_id`) REFERENCES `{$this->getTable('customer_group')}` (`customer_group_id`) ON DELETE CASCADE ON UPDATE CASCADE,
		CONSTRAINT `FK_MSP_GR_CAT_PRD_ENTT_TIER_PRICE_ENTT_ID_CAT_PRD_ENTT_ENTT_ID` FOREIGN KEY (`entity_id`) REFERENCES `{$this->getTable('catalog_product_entity')}` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
		CONSTRAINT `FK_MSP_GR_CAT_PRD_ENTT_TIER_PRICE_ST_ID_CORE_ST_ST_ID` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core_store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
	)
	COLLATE='utf8_general_ci'
	ENGINE=InnoDB;
	");
}

$this->endSetup();
