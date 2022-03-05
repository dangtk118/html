<?php

class ConfigMoca {

    private $BASE_URL;
    private $PARTNER_ID;
    private $PARTNER_SECRET;
    private $CLIENT_ID;
    private $CLIENT_SECRET;
    private $REDIRECT_URL_ANDROID;
    private $REDIRECT_URL_IOS;
    private $MERCHANT_ID;
    private $REDIRECT_URL_FHS;
    private $CLIENT_ID_MOBILE;
    private $CLIENT_SECRET_MOBILE;

    public function __construct()
    {
        
    }
    
    public function  getConfigData($config){
        return Mage::getStoreConfig('payment/mocapay/' . $config);
    }

    public function getBaseUrl()
    {
        if (!$this->BASE_URL)
        {
            $this->BASE_URL = $this->getConfigData('mocapay_url');
        }
        return $this->BASE_URL;
    }

    public function setBaseUrl($BASE_URL)
    {
        $this->BASE_URL = $BASE_URL;
    }

    public function getPartnerId()
    {
        if (!$this->PARTNER_ID)
        {
            $this->PARTNER_ID = $this->getConfigData('partner_id');
        }
        return $this->PARTNER_ID;
    }

    public function setPartnerId($PARTNER_ID)
    {
        return $this->PARTNER_ID = $PARTNER_ID;
    }

    public function getPartnerSecret()
    {
        if (!$this->PARTNER_SECRET)
        {
            $this->PARTNER_SECRET = $this->getConfigData('partner_secret');
        }
        return $this->PARTNER_SECRET;
    }

    public function setPartnerSecret($PARTNER_SECRET)
    {
        return $this->PARTNER_SECRET = $PARTNER_SECRET;
    }

    public function getClientIdWeb()
    {

        if (!$this->CLIENT_ID)
        {
            $this->CLIENT_ID = $this->getConfigData('client_id');
        }
        return $this->CLIENT_ID;
    }

    public function setClientIdWeb($CLIENT_ID)
    {
        return $this->CLIENT_ID = $CLIENT_ID;
    }

    public function getClientSecretWeb()
    {
        if (!$this->CLIENT_SECRET)
        {
            $this->CLIENT_SECRET = $this->getConfigData('client_secret');
        }
        return $this->CLIENT_SECRET;
    }

    public function getRedirectUrlAndroid()
    {
        if (!$this->REDIRECT_URL_ANDROID)
        {
            $this->REDIRECT_URL_ANDROID = $this->getConfigData('redirect_url_android');
        }
        return $this->REDIRECT_URL_ANDROID;
    }

    public function setRedirectUrlAndroid($REDIRECT_URL_ANDROID)
    {
        return $this->REDIRECT_URL_ANDROID = $REDIRECT_URL_ANDROID;
    }

    public function getRedirectUrliOS()
    {
        if (!$this->REDIRECT_URL_IOS)
        {
            $this->REDIRECT_URL_IOS = $this->getConfigData('redirect_url_ios');
        }
        return $this->REDIRECT_URL_IOS;
    }

    public function setRedirectUrliOS($REDIRECT_URL_IOS)
    {
        return $this->REDIRECT_URL_IOS = $REDIRECT_URL_IOS;
    }

    public function getMerchantId()
    {
        if (!$this->MERCHANT_ID)
        {
            $this->MERCHANT_ID = $this->getConfigData('merchant_id');
        }
        return $this->MERCHANT_ID;
    }

    public function setMerchantId($MERCHANT_ID)
    {
        return $this->MERCHANT_ID = $MERCHANT_ID;
    }
    
    public function getRedirectUrlFhsWeb()
    {
        if (!$this->REDIRECT_URL_FHS)
        {
            $this->REDIRECT_URL_FHS = $this->getConfigData('redirect_url_fhs');
        }
        return $this->REDIRECT_URL_FHS;
    }
    
    public function getRedirectUrlFhs($channel){
        if ($channel == "mobile_android"){
            return $this->getRedirectUrlAndroid();
        } else if ($channel == "mobile_ios"){
            return $this->getRedirectUrliOS();
        }
        return $this->getRedirectUrlFhsWeb();
    }
    
    public function setRedirectUrlFhs($REDIRECT_URL_FHS)
    {
        return $this->REDIRECT_URL_FHS = $REDIRECT_URL_FHS;
    }
    
    public function getClientIdMobile(){
        if (!$this->CLIENT_ID_MOBILE){
            $this->CLIENT_ID_MOBILE = $this->getConfigData('client_id_mobile');
        }
        return $this->CLIENT_ID_MOBILE;
    }
    
    public function getClientSecretMobile(){
        if (!$this->CLIENT_SECRET_MOBILE){
            $this->CLIENT_SECRET_MOBILE = $this->getConfigData('client_secret_mobile');
        }
        return $this->CLIENT_SECRET_MOBILE;
    }

    
    public function getClientId($channel)
    {
        if ($channel == "mobile_android" || $channel == "mobile_ios")
        {
            return $this->getClientIdMobile();
        }
        return $this->getClientIdWeb();
    }

    public function getClientSecret($channel)
    {

        if ($channel == "mobile_android" || $channel == "mobile_ios")
        {
            return $this->getClientSecretMobile();
        }
        return $this->getClientSecretWeb();
    }

}
