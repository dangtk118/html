<?php
$installer = $this;
$installer->startSetup();

$installer->addAttribute('order', 'don_vi_ban_hang', array(
                            'type'              =>'varchar', 
                            'visible'           => true, 
                            'required'          => false, 
                            'is_user_defined'   => false, 
                            'note'              => 'Don Vi Ban Hang')
                        );
$installer->addAttribute('order', 'don_vi_giao_hang', array(
                            'type'              =>'varchar', 
                            'visible'           => true, 
                            'required'          => false, 
                            'is_user_defined'   => false, 
                            'note'              => 'Don Vi Giao Hang')
                        );

$installer->getConnection()->addColumn($installer->getTable('sales_flat_order'), 'don_vi_ban_hang','VARCHAR(255) NULL DEFAULT NULL');
$installer->getConnection()->addColumn($installer->getTable('sales_flat_order_grid'), 'don_vi_ban_hang','VARCHAR(255) NULL DEFAULT NULL');
$installer->getConnection()->addColumn($installer->getTable('sales_flat_order'), 'don_vi_giao_hang','VARCHAR(255) NULL DEFAULT NULL');
$installer->getConnection()->addColumn($installer->getTable('sales_flat_order_grid'), 'don_vi_giao_hang','VARCHAR(255) NULL DEFAULT NULL');

$installer->endSetup();