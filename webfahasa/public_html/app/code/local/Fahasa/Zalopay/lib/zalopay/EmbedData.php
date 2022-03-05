<?php

class EmbedData implements BaseEntity {

    private $promotionInfo;
    private $merchantInfo;
    private $bankgroup;
    /**
     * 
     * @param string $promotionInfo
     * @param object $merchantInfo
     */
    public function __construct($promotionInfo, $merchantInfo, $bankgroup) {
        $this->promotionInfo = $promotionInfo;
        $this->merchantInfo = $merchantInfo;
        $this->bankgroup = $bankgroup;
    }

    public function getPromotionInfo() {
        return $this->promotionInfo;
    }

    public function getMerchantInfo() {
        return $this->merchantInfo;
    }
    
    public function getBankgroup(){
        return $this->bankgroup;
    }

    public function toArray() {
        $arr = array(
            'promotioninfo' => $this->promotionInfo,
            'merchantinfo' => $this->merchantInfo,
            'bankgroup' => $this->bankgroup
        );

        return $arr;
    }

    public function toJson() {
        return JsonUtil::toJson($this);
    }

}
