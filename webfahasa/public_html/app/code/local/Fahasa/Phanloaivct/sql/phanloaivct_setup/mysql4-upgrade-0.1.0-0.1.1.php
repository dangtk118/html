<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$installer = $this;

$installer->startSetup();

$table = $this->getTable('phanloaivct_khuvuc');

$installer->run("ALTER TABLE ".$table." ADD COLUMN express_khuvuc_from TINYINT(1) ");
$installer->run("ALTER TABLE ".$table." ADD COLUMN express_khuvuc_to TINYINT(1) ");

$installer->endSetup();