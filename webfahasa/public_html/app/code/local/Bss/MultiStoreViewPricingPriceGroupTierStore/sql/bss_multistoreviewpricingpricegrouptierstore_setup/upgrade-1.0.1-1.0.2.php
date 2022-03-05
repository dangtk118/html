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

$installer->run("
	CREATE TABLE IF NOT EXISTS `{$this->getTable('multistoreviewpricingpricegrouptierstore/tierDefault')}` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`product_id` int(11) NOT NULL,
	`status` smallint(6) NOT NULL,
	`store_id` int(11) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `product_id_store_id` (`product_id`,`store_id`),
	KEY `product_id` (`product_id`),
	KEY `store_id` (`store_id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
");

$this->endSetup();