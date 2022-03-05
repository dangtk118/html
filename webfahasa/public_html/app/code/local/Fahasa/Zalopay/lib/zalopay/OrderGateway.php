<?php

class OrderGateway extends Order {

    private $phone;
    private $email;
    private $address;
    private $bankcode;

    /**
     * 
     * @param int $appId
     * @param string $appUser
     * @param long $appTime
     * @param long $amount
     * @param string $appTransId
     * @param string $embedData
     * @param string $item
     * @param string $description
     * @param string $mac
     * @param string $phone
     * @param string $email
     * @param string $address
     * @param string $bankcode
     */
    public function __construct($appId, $appUser, $appTime, $amount, $appTransId, $embedData, $item, $description, $mac, $phone, $email, $address, $bankcode) {
        parent::__construct($appId, $appUser, $appTime, $amount, $appTransId, $embedData, $item, $description, $mac);
        $this->phone = $phone;
        $this->email = $email;
        $this->address = $address;
        $this->bankcode = $bankcode;
    }

    public function toArray() {
        $arr = array(
            'appid' => $this->appId,
            'appuser' => $this->appUser,
            'apptime' => $this->appTime,
            'amount' => $this->amount,
            'apptransid' => $this->appTransId,
            'embeddata' => $this->embedData,
            'item' => $this->item,
            'description' => $this->description,
            'mac' => $this->mac,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'bankcode' => $this->bankcode
        );
        return $arr;
    }

    public function toJson() {
        return JsonUtil::toJson($this);
    }

}
