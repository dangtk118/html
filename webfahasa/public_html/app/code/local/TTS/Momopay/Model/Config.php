<?php

class Config {
    private $PARTNER_CODE;
    private $PUBLIC_KEY;
    private $BASE_URL;
    private $SECRET_KEY;
    
    public function __construct($PARTNER_CODE, $PUBLIC_KEY, $BASE_URL, $SECRET_KEY) {
       $this->PARTNER_CODE = $PARTNER_CODE;
       $this->PUBLIC_KEY = $PUBLIC_KEY;
       $this->BASE_URL = $BASE_URL;
       $this->SECRET_KEY = $SECRET_KEY;
    }
    
    public function getPartnerCode(){
        return $this->PARTNER_CODE;
    }
    
    public function getPublicKey(){
        return $this->PUBLIC_KEY;
    }
    
    public function getBaseUrl(){
        return $this->BASE_URL;
    }
    
    public function getSecretKey(){
        return $this->SECRET_KEY;
    }
}