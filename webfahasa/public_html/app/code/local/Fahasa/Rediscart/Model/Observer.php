<?php

class Fahasa_Rediscart_Model_Observer {

//    public function updateItemsInCart($observer)
//    {
//        $quote = $observer->getCart()->getQuote();
//        Mage::helper("rediscart")->copyCartInRedis($quote);
//    }

//    public function copyCartInRedis($quote)
//    {
//        $quote_id = $quote->getId();
//        $cart_key = "quote:" . $quote_id;
//        /// Start Redis Connection
//        $helper_redis = Mage::helper("flashsale/redis");
//        $redis_client = $helper_redis->createRedisClientCart();
//        if (!$redis_client->isConnected())
//        {
//            return array(
//                "result" => false,
//                "error_type" => Fahasa_Flashsale_Helper_Data_Error::NO_CONNECTION
//            );
//        }
//
//
//        $cartItems = $quote->getAllItems();
//        $cartItems_redis = array();
//        foreach ($cartItems as $item)
//        {
//            $cartItems_redis[] = array(
//                'product_id' => $item['product_id'],
//                'sku' => $item['sku'],
//                'quantity' => $item['qty'],
//                'name' => $item['name'],
//                'price' => $item['price_incl_tax'],
//                "image" => Mage::getBaseUrl('media') . 'catalog/product/' . $item['image']
//            );
//        }
//        $totals = array();
//
//        $redis_value = array(
//            "items" => $cartItems_redis,
//            "totals" => $totals,
//        );
//
//        $redis_json = json_encode($redis_value);
//        $redis_client->set($cart_key, $redis_json);
//
//        $redis_client->close();
//    }

    public function logIn($observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        Mage::helper("rediscart/cart")->mergeGuestItemsToCustomerItems($customer);
    }

}
