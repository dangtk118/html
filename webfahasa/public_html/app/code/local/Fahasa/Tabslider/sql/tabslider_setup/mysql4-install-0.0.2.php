<?php

$installer = $this;

$installer->startSetup();
$installer->run("
    ALTER TABLE fhs_catalog_product_entity ADD COLUMN discount_percent DECIMAL(12,0);
    CREATE INDEX IDX_FHS_DISCOUNT_PERCENT ON fhs_catalog_product_entity (discount_percent);
");
$installer->endSetup(); 
