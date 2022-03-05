<?php
 
    $installer = $this;
 
    $installer->startSetup();

    $installer->addAttribute(
        'order', 
        'codfee', 
        array(
            'type' => 'float', 
            'grid' => false
        )
    );

    $installer->addAttribute(
        'quote', 
        'codfee', 
        array(
            'type' => 'float',
            'grid' => false
        )
    );
    
    $installer->endSetup();