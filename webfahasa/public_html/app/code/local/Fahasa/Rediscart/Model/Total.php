<?php

class Fahasa_Rediscart_Model_Total{
    private $code;
    private $price;
    private $title;
    private $area;
    private $as;
    
     public function __construct($code, $price, $title)
    {
        $this->code = $code;
        $this->price = $price;
        $this->title = $title;
    }
    function getCode(){
        return $this->code;
    }
    
    function getTitle(){
        return $this->title;
    }
    function getPrice(){
        return $this->price;
        
    }
    function getArea(){
        return $this->area;
    }
    
    function getAs(){
        return null;
    }
    function getAddress(){
        return null;
    }
}

