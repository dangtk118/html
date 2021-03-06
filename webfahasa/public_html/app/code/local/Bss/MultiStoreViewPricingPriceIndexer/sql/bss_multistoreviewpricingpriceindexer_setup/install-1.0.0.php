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
 * Create table 'multistoreviewpricingpriceindexer/product_index_price'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('multistoreviewpricingpriceindexer/product_index_price'))
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
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Price')
    ->addColumn('final_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Final Price')
    ->addColumn('min_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Min Price')
    ->addColumn('max_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Max Price')
    ->addColumn('tier_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Tier Price')
    ->addColumn('group_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Group Price')
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_index_price', array('customer_group_id')),
        array('customer_group_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_index_price', array('website_id')),
        array('website_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_index_price', array('store_id')),
        array('store_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_index_price', array('min_price')),
        array('min_price'))
    ->addForeignKey(
        $installer->getFkName(
            'multistoreviewpricingpriceindexer/product_index_price',
            'customer_group_id',
            'customer/customer_group',
            'customer_group_id'
        ),
        'customer_group_id', $installer->getTable('customer/customer_group'), 'customer_group_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'multistoreviewpricingpriceindexer/product_index_price',
            'entity_id',
            'catalog/product',
            'entity_id'
        ),
        'entity_id', $installer->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'multistoreviewpricingpriceindexer/product_index_price',
            'website_id',
            'core/website',
            'website_id'
        ),
        'website_id', $installer->getTable('core/website'), 'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'multistoreviewpricingpriceindexer/product_index_price',
            'store_id',
            'core/store',
            'store_id'
        ),
        'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Catalog Product Price Store Index Table');
$installer->getConnection()->createTable($table);

$indexFields = array('website_id', 'customer_group_id', 'min_price', 'store_id');
$installer->getConnection()->addIndex(
    $installer->getTable('multistoreviewpricingpriceindexer/product_index_price'),
    $installer->getIdxName('multistoreviewpricingpriceindexer/product_index_price', $indexFields),
    $indexFields
);

/**
 * Create table 'multistoreviewpricingpriceindexer/product_price_indexer_idx'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('multistoreviewpricingpriceindexer/product_price_indexer_idx'))
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
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Price')
    ->addColumn('final_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Final Price')
    ->addColumn('min_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Min Price')
    ->addColumn('max_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Max Price')
    ->addColumn('tier_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Tier Price')
    ->addColumn('group_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Group Price')
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_price_indexer_idx', array('customer_group_id')),
        array('customer_group_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_price_indexer_idx', array('website_id')),
        array('website_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_price_indexer_idx', array('store_id')),
        array('store_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_price_indexer_idx', array('min_price')),
        array('min_price'))
    ->setComment('Catalog Product Price Indexer Index Store Table');
$installer->getConnection()->createTable($table);

/**
 * Create table 'multistoreviewpricingpriceindexer/product_price_indexer_tmp'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('multistoreviewpricingpriceindexer/product_price_indexer_tmp'))
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
    ->addColumn('price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Price')
    ->addColumn('final_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Final Price')
    ->addColumn('min_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Min Price')
    ->addColumn('max_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Max Price')
    ->addColumn('tier_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Tier Price')
    ->addColumn('group_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Group Price')
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_price_indexer_tmp', array('customer_group_id')),
        array('customer_group_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_price_indexer_tmp', array('website_id')),
        array('website_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_price_indexer_tmp', array('store_id')),
        array('store_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingpriceindexer/product_price_indexer_tmp', array('min_price')),
        array('min_price'))
    ->setComment('Catalog Product Price Indexer Temp Store Table');
$installer->getConnection()->createTable($table);

$installer->endSetup();
