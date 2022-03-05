<?php

require_once '../app/Mage.php';
umask(0);
Mage::app('default');
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

try {
    $allTypes = Mage::app()->useCache();
    foreach($allTypes as $type => $value) {
        if($type == "block_html" || $type === "layout" || 
            $type == "turpentine_esi_blocks" || $type == "turpentine_pages"){
            Mage::app()->getCacheInstance()->cleanType($type);
            Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $type));
            echo "Clearing Cache: {$type}\n";
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
