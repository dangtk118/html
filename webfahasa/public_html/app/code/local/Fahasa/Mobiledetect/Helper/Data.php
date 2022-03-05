<?php

/**
 * Mobile detect base on USER_AGENT. Our USER_AGENT has been normalized to be 
 * either "Chrome" or "Mobile"
 * @author phamtn8
 */
class Fahasa_Mobiledetect_Helper_Data extends Mage_Core_Helper_Abstract{
    
    public $httpHeaders;
    
    public function __construct(){
        $httpHeaders = $_SERVER;
        // clear existing headers
        $this->httpHeaders = array();

        // Only save HTTP headers. In PHP land, that means only _SERVER vars that
        // start with HTTP_.
        foreach ($httpHeaders as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $this->httpHeaders[$key] = $value;
            }
        }
    }
    
    public function isMobile(){
        foreach($this->httpHeaders as $key => $value){
            if($key == "HTTP_USER_AGENT"){
                if(strtolower($value) == "mobile"){
                    return true;
                }
            }
        }
        return Mage::helper('mobiledetect')->isMobile();
    }
    
    public function getJSForMobile(){
        //if(true){
        if($this->isMobile()){
            //File will be inside root js folder
            return 'js/mobile.js';
        }
    }
    
    public function getCssForMobile(){
        //if(true){
        if($this->isMobile()){
            //File will be inside root js folder
            return 'css/mobile.css';
        }
    }
}
