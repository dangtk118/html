<?php

class Fahasa_Checkout_Block_Cart_Minicart extends Mage_Checkout_Block_Cart_Minicart
{
    /**
     * Get shopping cart items qty based on configuration (summary qty or items qty)
     *
     * @return int | float
     */
    public function getSummaryCount()
    {
//        Mage::helper('checkout/data')->getCartFromRedis();
         Mage::log("GET ITEM FROM CHECKOUT SIDE BAR 1111111111111111", null, "coupon.log");
      $count =  Mage::helper("rediscart")->getNumCartItemsFromRedis();
      return $count;
    }
}
