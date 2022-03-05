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
$table = $installer->getTable('multistoreviewpricingcatalogrule/rule_product_price');
if ($installer->getConnection()->isTableExists($table) == true) {
    $installer->getConnection()->dropTable($table);
}
/**
 * Create table 'multistoreviewpricingcatalogrule/rule_product_price'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('multistoreviewpricingcatalogrule/rule_product_price'))
    ->addColumn('rule_product_price_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Rule Product PriceId')
    ->addColumn('rule_date', Varien_Db_Ddl_Table::TYPE_DATE, null, array(
        'nullable'  => false,
        ), 'Rule Date')
    ->addColumn('customer_group_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Customer Group Id')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Product Id')
    ->addColumn('rule_price', Varien_Db_Ddl_Table::TYPE_DECIMAL, array(12,4), array(
        'nullable'  => false,
        'default'   => '0.0000',
        ), 'Rule Price')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        ), 'Store Id')
    ->addColumn('latest_start_date', Varien_Db_Ddl_Table::TYPE_DATE, null, array(
        ), 'Latest StartDate')
    ->addColumn('earliest_end_date', Varien_Db_Ddl_Table::TYPE_DATE, null, array(
        ), 'Earliest EndDate')

    ->addIndex($installer->getIdxName('multistoreviewpricingcatalogrule/rule_product_price', array('rule_date', 'store_id', 'customer_group_id', 'product_id'), Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE),
        array('rule_date', 'store_id', 'customer_group_id', 'product_id'), array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->addIndex($installer->getIdxName('multistoreviewpricingcatalogrule/rule_product_price', array('customer_group_id')),
        array('customer_group_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingcatalogrule/rule_product_price', array('store_id')),
        array('store_id'))
    ->addIndex($installer->getIdxName('multistoreviewpricingcatalogrule/rule_product_price', array('product_id')),
        array('product_id'))

    ->addForeignKey($installer->getFkName('multistoreviewpricingcatalogrule/rule_product_price', 'product_id', 'catalog/product', 'entity_id'),
        'product_id', $installer->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)

    ->addForeignKey($installer->getFkName('multistoreviewpricingcatalogrule/rule_product_price', 'customer_group_id', 'customer/customer_group', 'customer_group_id'),
        'customer_group_id', $installer->getTable('customer/customer_group'), 'customer_group_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)

    ->addForeignKey($installer->getFkName('multistoreviewpricingcatalogrule/rule_product_price', 'store_id', 'core/store', 'store_id'),
        'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)

    ->setComment('CatalogRule Product Price Store');
$installer->getConnection()->createTable($table);

$this->endSetup();
