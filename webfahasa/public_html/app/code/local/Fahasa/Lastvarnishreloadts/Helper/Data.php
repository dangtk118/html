<?php

/**
 *
 * @author Thang Pham
 */
class Fahasa_Lastvarnishreloadts_Helper_Data extends Mage_Core_Helper_Abstract{
    public function handleReloadVarnishCacheTypeTimeStamp($result, $eventCache){
        if($result == true){
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $eventName = $eventCache->getEvent()->getName();
            $cacheId = $eventCache->getType();
            if($cacheId != null){
                //Flush individual cache type
                Mage::log("*** Event Name: " . $eventName . ": " . $cacheId, null, "magento.log");
                $cache = Mage::getModel("lastvarnishreloadts/lastvarnishreloadts")->load($cacheId);
                $cache->setLastReloadTimestamp(date("Y-m-d H:i:s"));
                $cache->save();
            }else if($eventName == "adminhtml_cache_flush_system"){
                //Flush all cache
                Mage::log("*** Event Name: " . $eventName . ": Flush all cache", null, "magento.log");
                $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                $query="update fhs_varnish_cache_reload_timestamp set last_reload_timestamp=now() where cache_type='varnish';";
                $write->query($query);
            }
        }
    }
}
