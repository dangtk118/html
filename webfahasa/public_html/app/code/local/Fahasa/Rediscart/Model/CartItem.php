<?php

class Fahasa_Rediscart_Model_CartItem{
    private $id;
    private $cartId;
    private $productId;
    private $qty;
    
    
    function getId(){
        return $this->id;
    }
    
    function getCartId(){
        return $this->cartId;
    }
    function getProductId(){
        return $this->productId;
    }
    function getQty(){
        return $this->qty;
    }
    
    
}