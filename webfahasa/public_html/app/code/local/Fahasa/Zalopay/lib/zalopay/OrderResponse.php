<?php

class OrderResponse extends DefaultResponse {

    private $zpTransToken;

    public function __construct($returnCode, $returnMessage, $zpTransToken) {
        parent::__construct($returnCode, $returnMessage);
        $this->zpTransToken = $zpTransToken;
    }

    public function getZpTransToken() {
        return $this->zpTransToken;
    }

}
