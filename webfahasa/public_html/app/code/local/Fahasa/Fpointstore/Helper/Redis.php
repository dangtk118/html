<?php

class Fahasa_Fpointstore_Helper_Redis extends Mage_Core_Helper_Abstract {
    
    public function createRedisClient(){
        $port = Mage::getStoreConfig('fpointstore_config/config/redis_port');
        $host = Mage::getStoreConfig('fpointstore_config/config/redis_host');
        
        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;
    }
}
