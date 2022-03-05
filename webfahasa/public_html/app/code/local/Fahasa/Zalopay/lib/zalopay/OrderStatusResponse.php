<?php

class OrderStatusResponse extends DefaultResponse {

    private $isProcessing;
    private $amount;
    private $zpTransid;

    public function __construct($returnCode, $returnMessage, $isprocessing, $amount, $zptransid) {
        parent::__construct($returnCode, $returnMessage);
        $this->isProcessing = $isprocessing;
        $this->amount = $amount;
        $this->zpTransid = $zptransid;
    }

    public function getIsProcessing() {
        return $this->isprocessing;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getZpTransId() {
        return $this->zpTransid;
    }

}
