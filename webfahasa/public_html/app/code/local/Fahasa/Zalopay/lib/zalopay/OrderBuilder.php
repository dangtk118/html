<?php

class OrderBuilder {

    private $appId;
    private $appUser;
    private $appTime;
    private $amount;
    private $appTransId;
    private $embedData;
    private $items;
    private $description;
    private $phone;
    private $email;
    private $address;
    private $bankcode;
    private $key1;

    public function __construct() {
        
    }

    public function setAppId($appId) {
        $this->appId = $appId;
        return $this;
    }

    public function setAppUser($appUser) {
        $this->appUser = $appUser;
        return $this;
    }

    public function setAppTime($appTime) {
        $this->appTime = $appTime;
    }

    public function setAmount($amount) {
        $this->amount = $amount;
        return $this;
    }

    public function setAppTransId($appTransId) {
        $this->appTransId = $appTransId;
        return $this;
    }

    public function setEmbedData(EmbedData $embedData) {
        $this->embedData = $embedData;
        return $this;
    }

    public function setItems($items) {
        $this->items = $items;
        return $this;
    }

    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    public function setPhone($phone) {
        $this->phone = $phone;
        return $this;
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function setAddress($address) {
        $this->address = $address;
        return $this;
    }

    public function setBankcode($bankcode) {
        $this->bankcode = $bankcode;
        return $this;
    }

    public function setKey1($key1) {
        $this->key1 = $key1;
        return $this;
    }

    public function createOrder() {
        $strEmbedData = $this->embedData->toJson();
        $strItem = JsonUtil::toJson($this->items);

        $hmacInput = sprintf("%s|%s|%s|%s|%s|%s|%s", $this->appId, $this->appTransId, $this->appUser, $this->amount, $this->appTime, $strEmbedData, $strItem);
        $mac = hash_hmac("sha256", $hmacInput, $this->key1);

        if ($this->isGateway()) {
            return new OrderGateway($this->appId, $this->appUser, $this->appTime, $this->amount, $this->appTransId, $strEmbedData, $strItem, $this->description, $mac, $this->phone, $this->email, $this->address, $this->bankcode);
        } else {
            return new Order($this->appId, $this->appUser, $this->appTime, $this->amount, $this->appTransId, $strEmbedData, $strItem, $this->description, $mac);
        }
    }

    private function isGateway() {
        if (!empty($this->email) || !empty($this->address) || !empty($this->bankcode)) {
            return true;
        }

        return false;
    }

}
