<?php

class Fahasa_Rediscart_Model_Cart{
    private $id;
    private $customerId;
    
       function getId(){
        return $this->id;
    }
    
    function getCustomerId(){
        return $this->customerId;
    }
    
}