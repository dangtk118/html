<?php
/*////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\  FME Fieldsmanager extension  \\\\\\\\\\\\\\\\\\\\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\\\\\\\\\\\\\\\\\\\\\\\\ NOTICE OF LICENSE\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                                                                   ///////
 \\\\\\\ This source file is subject to the Open Software License (OSL 3.0)\\\\\\\
 ///////   that is bundled with this package in the file LICENSE.txt.      ///////
 \\\\\\\   It is also available through the world-wide-web at this URL:    \\\\\\\
 ///////          http://opensource.org/licenses/osl-3.0.php               ///////
 \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
 ///////                      * @category   FME                            ///////
 \\\\\\\                      * @package    FME_Fieldsmanager              \\\\\\\
 ///////    * @author     Malik Tahir Mehmood <malik.tahir786@gmail.com>   ///////
 \\\\\\\                                                                   \\\\\\\
 /////////////////////////////////////////////////////////////////////////////////
 \\* @copyright  Copyright 2010 © free-magentoextensions.com All right reserved\\\
 /////////////////////////////////////////////////////////////////////////////////
 */


$installer = $this;

$installer->startSetup();
$installer->run("

    DROP TABLE IF EXISTS {$this->getTable('fieldsmanager/store')};
    CREATE TABLE IF NOT EXISTS {$this->getTable('fieldsmanager/store')} (
        `attribute_id` int(10) unsigned NOT NULL,                   
        `store_id` smallint(5) unsigned NOT NULL,                  
        PRIMARY KEY  (`attribute_id`,`store_id`),                   
        KEY `fm_attribute_store_id` (`store_id`)  
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; 

    DROP TABLE IF EXISTS {$this->getTable('fieldsmanager/customer_group')};
    CREATE TABLE IF NOT EXISTS {$this->getTable('fieldsmanager/customer_group')} (
        `attribute_id` int(10) unsigned NOT NULL,                   
        `group_id` smallint(5) unsigned NOT NULL,                  
        PRIMARY KEY  (`attribute_id`,`group_id`),                   
        KEY `fm_attribute_group_id` (`group_id`)  
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    DROP TABLE IF EXISTS {$this->getTable('fieldsmanager/products')};
    CREATE TABLE IF NOT EXISTS {$this->getTable('fieldsmanager/products')} (
        `attribute_id` int(10) unsigned NOT NULL,                   
        `products_id` smallint(5) unsigned NOT NULL,                  
        PRIMARY KEY  (`attribute_id`,`products_id`),                   
        KEY `fm_attribute_products_id` (`products_id`)  
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8; 
        
    DROP TABLE IF EXISTS {$this->getTable('fieldsmanager/category')};
    CREATE TABLE IF NOT EXISTS {$this->getTable('fieldsmanager/category')} (
        `attribute_id` int(10) unsigned NOT NULL,                   
        `category_id` smallint(5) unsigned NOT NULL,                  
        PRIMARY KEY  (`attribute_id`,`category_id`),                   
        KEY `fm_attribute_category_id` (`category_id`)  
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
");

$installer->endSetup(); 