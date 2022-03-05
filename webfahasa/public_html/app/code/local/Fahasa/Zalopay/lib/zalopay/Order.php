<?php

class Order implements BaseEntity {

    protected $appId;
    protected $appUser;
    protected $appTime;
    protected $amount;
    protected $appTransId;
    protected $embedData;
    protected $item;
    protected $description;
    protected $mac;

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
     */
    public function __construct($appId, $appUser, $appTime, $amount, $appTransId, $embedData, $item, $description, $mac) {
        $this->appId = $appId;
        $this->appUser = $appUser;
        $this->appTime = $appTime;
        $this->amount = $amount;
        $this->appTransId = $appTransId;
        $this->embedData = $embedData;
        $this->item = $item;
        $this->description = $description;
        $this->mac = $mac;
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
            'mac' => $this->mac
        );

        return $arr;
    }

    public function toJson() {
        return JsonUtil::toJson($this);
    }

}
