<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$installer = $this;

$installer->startSetup();

$table = $this->getTable('phieudangky');

$installer->run("ALTER TABLE ".$table." ADD COLUMN note text ");

$installer->endSetup();