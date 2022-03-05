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

DROP TABLE IF EXISTS {$this->getTable('fieldsmanager/orders')};
CREATE TABLE IF NOT EXISTS {$this->getTable('fieldsmanager/orders')} (
  `fieldsmanager_id` int(11) NOT NULL auto_increment,
  `entity_id` int(10) unsigned NOT NULL default '0',
  `attribute_id` smallint(5) unsigned NOT NULL default '0',
  `value` text NULL,
  PRIMARY KEY  (`fieldsmanager_id`),
  UNIQUE KEY `FIELDSMANAGER_ENTITY_ATTRIBUTE` (`entity_id`,`attribute_id`),
  KEY `fieldsmanager_orders_atfk_1` (`attribute_id`),
  KEY `fieldsmanager_orders_enfk_2` (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE {$this->getTable('fieldsmanager/orders')}
  ADD CONSTRAINT `fieldsmanager_orders_atfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `".$installer->getTable('eav/attribute')."` (`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fieldsmanager_orders_enfk_2` FOREIGN KEY (`entity_id`) REFERENCES `".$installer->getTable('sales/order')."` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE;
 ");
$installer->run("

DROP TABLE IF EXISTS {$this->getTable('fieldsmanager/customers')};
CREATE TABLE {$this->getTable('fieldsmanager/customers')} (
    `fieldsmanager_id` int(11) NOT NULL AUTO_INCREMENT,
    `entity_id` int(10) unsigned NOT NULL DEFAULT '0',
    `attribute_id` smallint(5) unsigned NOT NULL DEFAULT '0',
    `value` text NULL,
  PRIMARY KEY (`fieldsmanager_id`),
  UNIQUE KEY `FIELDSMANAGER_CUSTOMER_ENTITY_ATTRIBUTE` (`entity_id`,`attribute_id`),
  KEY `fieldsmanager_customer_atfk_1` (`attribute_id`),
  KEY `fieldsmanager_customer_enfk_2` (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE {$this->getTable('fieldsmanager/customers')}
    ADD CONSTRAINT `fieldsmanager_customer_atfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `".$installer->getTable('eav/attribute')."` (`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT `fieldsmanager_customer_enfk_2` FOREIGN KEY (`entity_id`) REFERENCES `".$installer->getTable('customer/entity')."` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE;
");
    $eavTypeTable = $installer->getTable('eav_entity_type');
    $typeExists = $installer->getConnection()->fetchOne("SELECT count(*) FROM `{$eavTypeTable}` WHERE `entity_type_code`='fme_fieldsmanager'");
    if(!$typeExists)
    {
        $data = $installer->getConnection()->insert($eavTypeTable, array('entity_type_code'=>'fme_fieldsmanager'));
    }

$installer->endSetup(); 