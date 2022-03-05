<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/* @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

/**
 * Create table 'multistoreviewpricingpriceindexer/product_price_indexer_final_idx'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('multistoreviewpricingpriceindexer/product_price_indexer_final_idx'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Entity ID')
    ->addColumn('customer_group_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Customer Group ID')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Website ID')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Store ID')
    ->addColumn('tax_class_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'default'   => '0',
        ), 'Tax Class ID')
    ->addColumn('orig_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Original Price')
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Price')
    ->addColumn('min_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Min Price')
    ->addColumn('max_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Max Price')
    ->addColumn('tier_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Tier Price')
    ->addColumn('base_tier', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Base Tier')
    ->addColumn('group_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Group price')
    ->addColumn('base_group_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Base Group price')
    ->setComment('Catalog Product Price Indexer Final Store Index Table');
$installer->getConnection()->createTable($table);

/**
 * Create table 'multistoreviewpricingpriceindexer/product_price_indexer_final_tmp'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('multistoreviewpricingpriceindexer/product_price_indexer_final_tmp'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Entity ID')
    ->addColumn('customer_group_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Customer Group ID')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Website ID')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Store ID')
    ->addColumn('tax_class_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'default'   => '0',
        ), 'Tax Class ID')
    ->addColumn('orig_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Original Price')
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Price')
    ->addColumn('min_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Min Price')
    ->addColumn('max_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Max Price')
    ->addColumn('tier_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Tier Price')
    ->addColumn('base_tier', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Base Tier')
    ->addColumn('group_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Group price')
    ->addColumn('base_group_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Base Group price')
    ->setComment('Catalog Product Price Indexer Final Store Temp Table');
$installer->getConnection()->createTable($table);

$memoryTables = array(
    'multistoreviewpricingpriceindexer/product_price_indexer_final_tmp',
    'multistoreviewpricingpriceindexer/product_price_indexer_tmp',
);

$connection = $installer->getConnection();

foreach ($memoryTables as $table) {
    $connection->changeTableEngine($installer->getTable($table), Varien_Db_Adapter_Pdo_Mysql::ENGINE_MEMORY);
}

$installer->endSetup();
