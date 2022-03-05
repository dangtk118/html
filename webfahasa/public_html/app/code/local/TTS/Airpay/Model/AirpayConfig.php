<?php

class AirpayConfig {

    private $APP_ID;
    private $APP_KEY;
    private $BASE_URL;

    public function __construct($APP_ID, $APP_KEY, $BASE_URL)
    {
        $this->APP_ID = $APP_ID;
        $this->APP_KEY = $APP_KEY;
        $this->BASE_URL = $BASE_URL;
    }

    public function getAppId()
    {
        return (int) $this->APP_ID;
    }

    public function getAppKey()
    {
        return $this->APP_KEY;
    }

    public function getBaseUrl()
    {
        return $this->BASE_URL;
    }

}
