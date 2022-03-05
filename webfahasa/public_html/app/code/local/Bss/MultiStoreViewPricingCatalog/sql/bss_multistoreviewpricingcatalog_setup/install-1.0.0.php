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
/* @var $installer Mage_Catalog_Model_Resource_Setup */

$installer->startSetup();

/**
 * Create table 'multistoreviewpricingcatalog/product_super_attribute_pricing'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('multistoreviewpricingcatalog/product_super_attribute_pricing'))
    ->addColumn('value_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Value ID')
    ->addColumn('product_super_attribute_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Product Super Attribute ID')
    ->addColumn('value_index', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => true,
        'default'   => null,
        ), 'Value Index')
    ->addColumn('is_percent', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'default'   => '0',
        ), 'Is Percent')
    ->addColumn('pricing_value', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Pricing Value')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Website ID')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Store ID')
    ->addIndex($installer->getIdxName('multistoreviewpricingcatalog/product_super_attribute_pricing', array('product_super_attribute_id')),
        array('product_super_attribute_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingcatalog/product_super_attribute_pricing', array('website_id')),
        array('website_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingcatalog/product_super_attribute_pricing', array('store_id')),
        array('store_id'))
    ->addForeignKey(
        $installer->getFkName(
            'multistoreviewpricingcatalog/product_super_attribute_pricing',
            'website_id',
            'core/website',
            'website_id'
        ),
        'website_id', $installer->getTable('core/website'), 'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'multistoreviewpricingcatalog/product_super_attribute_pricing',
            'store_id',
            'core/store',
            'store_id'
        ),
        'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addForeignKey(
        $installer->getFkName(
            'multistoreviewpricingcatalog/product_super_attribute_pricing',
            'product_super_attribute_id',
            'catalog/product_super_attribute',
            'product_super_attribute_id'
        ),
        'product_super_attribute_id',
        $installer->getTable('catalog/product_super_attribute'),
        'product_super_attribute_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Catalog Product Super Attribute Pricing Store Table');
$installer->getConnection()->createTable($table);

// add unique key for product-value-website-store
$installer->getConnection()->addKey(
    $installer->getTable('multistoreviewpricingcatalog/product_super_attribute_pricing'),
    'UNQ_product_super_attribute_id_value_index_website_id_store_id',
    array('product_super_attribute_id', 'value_index', 'website_id', 'store_id'),
    'unique'
);

$installer->endSetup();
