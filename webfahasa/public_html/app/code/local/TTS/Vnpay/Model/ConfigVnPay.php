<?php

class ConfigVnPay { 
    
    private $VNP_TERMINAL;
    private $SECRET_KEY;
    private $BASE_URL;
    
    public function __construct($VNP_TERMINAL,$SECRET_KEY,$BASE_URL) {
       $this->VNP_TERMINAL = $VNP_TERMINAL;
       $this->SECRET_KEY = $SECRET_KEY;
       $this->BASE_URL = $BASE_URL;
    }
    
    public function getVnpTerminal(){
        return $this->VNP_TERMINAL;
    }
    
    public function getBaseUrl(){
        return $this->BASE_URL;
    }
    
    public function getSecretKey(){
        return $this->SECRET_KEY;
    }
}

