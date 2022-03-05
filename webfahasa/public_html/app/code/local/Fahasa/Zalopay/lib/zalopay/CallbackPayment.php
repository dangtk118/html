<?php

class CallbackPayment {

    public static function isValid(CallbackRequest $callbackRequest, $key2) {
        if (!empty($callbackRequest) && !empty($callbackRequest->getData())) {
            $mac = hash_hmac("sha256", $callbackRequest->getData(), $key2);

            if (strcmp($callbackRequest->getMac(), $mac) === 0) {
                return true;
            }
        }

        return false;
    }

    public static function getCallbackData(CallbackRequest $callbackRequest) {

        $callbackData = CallbackDataBuilder::build($callbackRequest);

        return $callbackData;
    }

}
