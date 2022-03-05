<?php

class ZalopayConfig {

    private $APPID;
    private $KEY1;
    private $KEY2;
    private $ZALOPAY_BASE_API;
    private $ZALOPAY_GATEWAY_API;

    public function __construct($APPID, $KEY1, $KEY2, $ZALOPAY_BASE_API, $ZALOPAY_GATEWAY_API) {
        $this->APPID = $APPID;
        $this->KEY1 = $KEY1;
        $this->KEY2 = $KEY2;
        $this->ZALOPAY_BASE_API = $ZALOPAY_BASE_API;
        $this->ZALOPAY_GATEWAY_API = $ZALOPAY_GATEWAY_API;
    }

    public function getAPPID() {
        return $this->APPID;
    }

    public function getKEY1() {
        return $this->KEY1;
    }

    public function getKEY2() {
        return $this->KEY2;
    }

    public function getZALOPAY_BASE_API() {
        return $this->ZALOPAY_BASE_API;
    }

    public function getZALOPAY_GATEWAY_API() {
        return $this->ZALOPAY_GATEWAY_API;
    }

}
