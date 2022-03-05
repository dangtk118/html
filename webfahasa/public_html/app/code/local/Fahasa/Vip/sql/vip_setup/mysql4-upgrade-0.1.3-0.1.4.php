<?php

$installer = $this;

$installer->startSetup();

//Add two new tables to handle tich diem
$installer->run("
CREATE TABLE IF NOT EXISTS `ssc_info` (
        `sscId` varchar(64) NOT NULL,
        `full_name` varchar(256) NULL,
        `gender` varchar(32) NULL,
        `email` varchar(255) NULL,
        `code` varchar(64) NULL,                    
        `created` DATETIME NOT NULL,                    
        `phone` varchar(64) NOT NULL,                                
        PRIMARY KEY (`sscId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
");

$installer->endSetup(); 