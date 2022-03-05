<?php

class AppIdToken implements BaseEntity {

    private $appId;
    private $zpTransToken;

    /**
     * 
     * @param int $appId
     * @param string $zpTransToken
     */
    public function __construct($appId, $zpTransToken) {
        $this->appId = $appId;
        $this->zpTransToken = $zpTransToken;
    }

    public function getAppId() {
        return $this->appId;
    }

    public function getZpTransToken() {
        return $this->zpTransToken;
    }

    public function toArray() {
        $arr = array(
            'appid' => $this->appId,
            'zptranstoken' => $this->zpTransToken
        );
        return $arr;
    }

    public function toJson() {
        return JsonUtil::toJson($this);
    }

}
