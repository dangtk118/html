<?php

class CallbackData {

    private $appId;
    private $appTransId;
    private $appTime;
    private $appUser;
    private $amount;
    private $embedData;
    private $item;
    private $zptransid;
    private $serverTime;
    private $channel;
    private $merchantUserId;
    private $userFeeAmount;
    private $discountAmount;
    private $bankcode;
    private $ccbankcode;
    private $zpSystem;

    public function __construct($appId, $appTransId, $appTime, $appUser, $amount, $embedData, $item, $zptransid, $serverTime, $channel, $merchantUserId, $userFeeAmount, $discountAmount, $bankcode, $ccbankcode, $zpSystem) {
        $this->appId = $appId;
        $this->appTransId = $appTransId;
        $this->appTime = $appTime;
        $this->appUser = $appUser;
        $this->amount = $amount;
        $this->embedData = $embedData;
        $this->item = $item;
        $this->zptransid = $zptransid;
        $this->serverTime = $serverTime;
        $this->channel = $channel;
        $this->merchantUserId = $merchantUserId;
        $this->userFeeAmount = $userFeeAmount;
        $this->discountAmount = $discountAmount;
        $this->bankcode = $bankcode;
        $this->ccbankcode = $ccbankcode;
        $this->zpSystem = $zpSystem;
    }

    public function getAppId() {
        return $this->appId;
    }

    public function getAppTransId() {
        return $this->appTransId;
    }

    public function getAppTime() {
        return $this->appTime;
    }

    public function getAppUser() {
        return $this->appUser;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getEmbedData() {
        return $this->embedData;
    }

    public function getItem() {
        return $this->item;
    }

    public function getZptransid() {
        return $this->zptransid;
    }

    public function getServerTime() {
        return $this->serverTime;
    }

    public function getChannel() {
        return $this->channel;
    }

    public function getMerchantUserId() {
        return $this->merchantUserId;
    }

    public function getUserFeeAmount() {
        return $this->userFeeAmount;
    }

    public function getDiscountAmount() {
        return $this->discountAmount;
    }

    public function getBankcode() {
        return $this->bankcode;
    }
    
    public function getCcbankcode(){
        return $this->ccbankcode;
    }
    
    public function getZpSystem(){
        return $this->zpSystem;
    }

}

class CallbackDataBuilder {

    public static function build(CallbackRequest $callbackRequest) {

        $callbackData = null;
        if (!empty($callbackRequest) && !empty($callbackRequest->getData())) {
            $data = $callbackRequest->getData();
            $json = json_decode($data, true);
            $appId = isset($json['appid']) ? $json['appid'] : 0;
            $appTransId = isset($json['apptransid']) ? $json['apptransid'] : '';
            $appTime = isset($json['apptime']) ? $json['apptime'] : 0;
            $appUser = isset($json['appuser']) ? $json['appuser'] : '';
            $amount = isset($json['amount']) ? $json['amount'] : 0;
            $embedData = isset($json['embeddata']) ? $json['embeddata'] : '';
            $item = isset($json['item']) ? $json['item'] : '';
            $zptransid = isset($json['zptransid']) ? $json['zptransid'] : 0;
            $serverTime = isset($json['servertime']) ? $json['servertime'] : 0;
            $channel = isset($json['channel']) ? $json['channel'] : 0;
            $merchantUserId = isset($json['merchantuserid']) ? $json['merchantuserid'] : '';
            $userFeeAmount = isset($json['userfeeamount']) ? $json['userfeeamount'] : 0;
            $discountAmount = isset($json['discountamount']) ? $json['discountamount'] : 0;
            $bankcode = isset($json['bankcode']) ? $json['bankcode'] : '';
            $ccbankcode = isset($json['ccbankcode']) ? $json['ccbankcode'] : '';
            $zpSystem = isset($json['zpSystem']) ? $json['zpSystem'] : '';

            $callbackData = new CallbackData($appId, $appTransId, $appTime, $appUser, $amount, $embedData, $item, $zptransid, $serverTime, $channel, $merchantUserId, $userFeeAmount, $discountAmount, $bankcode, $ccbankcode, $zpSystem);
        }

        return $callbackData;
    }

}
