<?php

class DefaultResponse {

    private $returnCode;
    private $returnMessage;

    /**
     * 
     * @param int $returnCode
     * @param string $returnMessage
     */
    public function __construct($returnCode, $returnMessage) {
        $this->returnCode = $returnCode;
        $this->returnMessage = $returnMessage;
    }

    public function getReturnCode() {
        return $this->returnCode;
    }

    public function getReturnMessage() {
        return $this->returnMessage;
    }

}
