<?php

class Fahasa_Rediscart_Helper_Data extends Mage_Core_Helper_Abstract {

    //this function should be load from redis. It is different from getCartFromRedisInCheckout function (getCartFromRedisInCheckout get from database)
    public function getCartFromRedis()
    {
        $redis_cart = Mage::registry('redis_cart');
        if (!$redis_cart)
        {
            $helper_redis = Mage::helper("flashsale/redis");
            $redis_client = $helper_redis->createRedisClientCart();
            if (!$redis_client->isConnected())
            {
                return array(
                    "result" => false,
                    "error_type" => Fahasa_Flashsale_Helper_Data_Error::NO_CONNECTION
                );
            }

            $session = Mage::getSingleton('checkout/session');
            $redis_cart_helper = Mage::helper("rediscart/cart");
            //?????????????IMPORTANT NEED TO INSERT CART fhs_cart => MUST TO DO
            $cart_id = $session->getRedisCartId();
            //Case 1. session is existed
            //Case 2. session is new
            if ($cart_id)
            {
                $cart_key = "quote:" . $session->getRedisCartId();
                $cart_value = $redis_client->get($cart_key);
                $redis_client->close();

                //check cart existed in redis
                //cart_id existed in session -> cart value does not existed in redis -> reason: cart redis is clear and cart_id is existed in redis and database
                if (!$cart_value || empty($cart_value))
                {
                    //TEST_OK
                    //load cart_data from fhs_cart table in database
                    $cart_items = $redis_cart_helper->getItemsByCartId($cart_id);
                    $cart_value = $this->copyCartInRedis($cart_id, $cart_items);
                    $redis_cart = array(
                        "items" => $cart_items
                    );
                }
                else
                {
                    //TEST_OK
                    $redis_cart = unserialize($cart_value);
                }

                Mage::register('redis_cart', $redis_cart);
            }
            else
            {
                $redis_client->close();
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                //customer has login
                if ($customer->getId())
                {
                    //??? TEST OK
                    $lastest_cart_id = $redis_cart_helper->getLastestCartIdByCustomerId($customer->getId());
                    if ($lastest_cart_id != -1)
                    {
                        $cart_items = $redis_cart_helper->getItemsByCartId($lastest_cart_id);

                        //customer has already gotten cart by redis cart id before 
                        //count = 0: customer has not gotten cart by redis cart id before => will check fhs_sales_flat_quote to copy item in cart
                        if (count($cart_items) > 0)
                        {
                            Mage::helper("rediscart")->copyCartInRedis($lastest_cart_id, $cart_items);
                            $redis_cart = array(
                                "items" => $cart_items
                            );
                            Mage::register('redis_cart', $redis_cart);
                            $session->setRedisCartId($lastest_cart_id);
                        }
                        else
                        {
                            //cart is empty => create empty item for redis cart for this function not to run check + query database
                            $redis_cart = array(
                                "items" => array()
                            );
                            Mage::register('redis_cart', $redis_cart);
                            $session->setRedisCartId($lastest_cart_id);
                        }
                    }
                    else
                    {
                        //Step 1: customer is login and does not have entry in fhs_cart (customer have never add cart before)
                        //Step2: check customer has quote_item in fhs_sales_flat_quote_item. If customer has items in quote 
                        //-> we will copy item to fhs_cart_item to save in redis
                        $quote_id = $session->getQuoteId();
                        if ($quote_id)
                        {
                            //??? TEST OK
                            $quote_num_items = $redis_cart_helper->getNumItemsInQuote($quote_id);
                            if ($quote_num_items > 0)
                            {
                                $lastest_cart_id = Mage::helper("rediscart/cart")->copyQuoteToRedisCart($quote_id, $customer);
                                $cart_items = $redis_cart_helper->getItemsByCartId($lastest_cart_id);
                                Mage::helper("rediscart")->copyCartInRedis($lastest_cart_id, $cart_items);
                                $redis_cart = array(
                                    "items" => $cart_items
                                );
                                Mage::register('redis_cart', $redis_cart);
                                $session->setRedisCartId($lastest_cart_id);
                            }
                            else
                            {
                                //cart is empty => OK
                                //cart is empty => create empty item for redis cart for this function not to run check + query database
                                $redis_cart = array(
                                    "items" => array()
                                );
                                Mage::register('redis_cart', $redis_cart);
                                $session->setRedisCartId($lastest_cart_id);
                            }
                        }
                        else
                        {
                            //customer has never added cart before => cart is empty => OK
                        }
                    }
                }
                else
                {
                    //customer is guest, and no have cart_id so they have never added cart before
                    //customer is guest, has item in fhs_sales_flat_quote_item
                    $quote_id = $session->getQuoteId();
                    if ($quote_id)
                    {
                        //??? TEST OK
                        $quote_num_items = $redis_cart_helper->getNumItemsInQuote($quote_id);
                        if ($quote_num_items > 0)
                        {
                            $lastest_cart_id = Mage::helper("rediscart/cart")->copyQuoteToRedisCart($quote_id, $customer);
                            $cart_items = $redis_cart_helper->getItemsByCartId($lastest_cart_id);
                            Mage::helper("rediscart")->copyCartInRedis($lastest_cart_id, $cart_items);
                            $redis_cart = array(
                                "items" => $cart_items
                            );
                            Mage::register('redis_cart', $redis_cart);
                            $session->setRedisCartId($lastest_cart_id);
                        }
                        else
                        {
                            //cart is empty => OK
                            //cart is empty => create empty item for redis cart for this function not to run check + query database
                            $redis_cart = array(
                                "items" => array()
                            );
                            Mage::register('redis_cart', $redis_cart);
                            $session->setRedisCartId($lastest_cart_id);
                        }
                    }
                    else
                    {
                        //customer has never added cart before => cart is empty => OK
                    }
                }
            }
        }

        return $redis_cart;
    }

    //get cart_items array from redis with key element is product_id
    public function getCartFromRedisWithKey()
    {
        $redis_cart = Mage::helper('rediscart')->getCartFromRedis();
        $redis_cart_items = $redis_cart['items'];
        $result = array();
        foreach ($redis_cart_items as $item)
        {
            $result[$item['product_id']] = $item;
        }
        return $result;
    }


    public function getCartTotalsFromRedis()
    {
        $cart = $this->getCartFromRedis();
        if ($cart)
        {
            return $cart['totals'];
        }
        return null;
    }
    
     public function getCartItemsFromRedis()
    {
        $cart = $this->getCartFromRedis();
        if ($cart)
        {
            return $cart['items'];
        }
        return null;
    }

    public function getNumCartItemsFromRedis()
    {
        $cart = $this->getCartFromRedis();
        if ($cart)
        {
            return count($cart['items']);
        }
        return 0;
    }

    public function copyCartInRedis($cart_id, $cart_items)
    {
        $cart_key = "quote:" . $cart_id;
        /// Start Redis Connection
        $helper_redis = Mage::helper("flashsale/redis");
        $redis_client = $helper_redis->createRedisClientCart();
        if (!$redis_client->isConnected())
        {
            return array(
                "result" => false,
                "error_type" => Fahasa_Flashsale_Helper_Data_Error::NO_CONNECTION
            );
        }

        $cartItems = $cart_items;

        $redis_value = array(
            "items" => $cartItems,
        );

        $redis_json = serialize($redis_value);
        $redis_client->set($cart_key, $redis_json);

        $redis_client->close();
        return $redis_json;
    }

}
