<?php

class Fahasa_Checkout_Block_Cart_Sidebar extends Mage_Checkout_Block_Cart_Sidebar {

    public function getRecentItems($count = null)
    {
        if (!$count){
            $count = 4;
        }
        $cart = Mage::helper("rediscart")->getCartItemsFromRedis();
	if(!empty($cart)){
	    return array_slice(array_reverse($cart), 0, $count);
	}else{
	    return null;
	}
    }

    public function getSummaryCount()
    {
        $count = Mage::helper("rediscart")->getNumCartItemsFromRedis();
        return $count;
    }
    
    public function getTotals()
    {
        $count = Mage::helper("rediscart")->getCartTotalsFromRedis();
        return $count;
    }

    public function getVirtualSubTotal()
    {
        $cart = Mage::helper("rediscart")->getCartItemsFromRedis();
        $total = 0;
        foreach ($cart as $item)
        {
            $total += $item['row_total'];
        }
        return $total;
    }

}
