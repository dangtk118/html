<?php

class CallbackRequest {

    private $data;
    private $mac;

    public function __construct($data, $mac) {
        $this->data = $data;
        $this->mac = $mac;
    }

    public function getData() {
        return $this->data;
    }

    public function getMac() {
        return $this->mac;
    }

}
