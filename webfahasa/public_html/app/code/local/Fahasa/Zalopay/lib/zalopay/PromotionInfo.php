<?php

require_once 'BaseEntity.php';
require_once 'JsonUtil.php';

class PromotionInfo implements BaseEntity {

    private $campaignCode;
    private $productInfo;

    /**
     * 
     * @param string $campaignCode
     * @param array $productInfo
     */
    public function __construct($campaignCode, $productInfo) {
        $this->campaignCode = $campaignCode;
        $this->productInfo = $productInfo;
    }

    public function getCampaignCode() {
        return $this->campaignCode;
    }

    public function getProductInfo() {
        return $this->productInfo;
    }

    public function toArray() {
        $arr = array(
            'campaigncode' => $this->campaignCode,
            'productinfo' => $this->productInfo
        );

        return $arr;
    }

    public function toJson() {
        return JsonUtil::toJson($this);
    }

}
