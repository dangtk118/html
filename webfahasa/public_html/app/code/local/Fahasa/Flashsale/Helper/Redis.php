<?php

class Fahasa_Flashsale_Helper_Redis extends Mage_Core_Helper_Abstract {
    
    public function createRedisClient(){
        $port = Mage::getStoreConfig('flashsale_config/config/redis_port');
        $host = Mage::getStoreConfig('flashsale_config/config/redis_host');
        
        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;
    }
    
    public function createRedisClientFlashSale(){
        $port = Mage::getStoreConfig('flashsale_config/config/redis_port_flashsale');
        $host = Mage::getStoreConfig('flashsale_config/config/redis_host_flashsale');
        
        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;
    }
    
    public function createRedisClientEventCart(){
        $port = Mage::getStoreConfig('flashsale_config/config/redis_port_eventcart');
        $host = Mage::getStoreConfig('flashsale_config/config/redis_host_eventcart');
        
        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;
    }
    
    public function createRedisClientCustomerAction()
    {
        $port = Mage::getStoreConfig('flashsale_config/config/redis_port_customeraction');
        $host = Mage::getStoreConfig('flashsale_config/config/redis_host_customeraction');

        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;
    }
    
    public function createRedisClientPublicAction()
    {
        $port = Mage::getStoreConfig('flashsale_config/config/redis_port_publicaction');
        $host = Mage::getStoreConfig('flashsale_config/config/redis_host_publicaction');

        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;
    }

    public function createRedisClientCatalogAction()
    {
        $port = Mage::getStoreConfig('flashsale_config/config/redis_port_catalogaction');
        $host = Mage::getStoreConfig('flashsale_config/config/redis_host_catalogaction');

        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;
    }

    public function createRedisClientProduct()
    {
        $port = Mage::getStoreConfig('flashsale_config/config_product_redis/redis_port_product');
        $host = Mage::getStoreConfig('flashsale_config/config_product_redis/redis_host_product');
       
        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;

    }
    public function createRedisClientCart()
    {
        $port = Mage::getStoreConfig('flashsale_config/config/redis_port_cart');
        $host = Mage::getStoreConfig('flashsale_config/config/redis_host_cart');

        $redisClient = new Redis();
        $redisClient->connect($host, $port);
        return $redisClient;
    }
}
