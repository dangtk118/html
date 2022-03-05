<?php

class GatewayPayment {

    public static function generateRedirectUrl(Order $order, $gatewayUrl) {

        $dataJson = $order->toJson();
        $base64Encode = base64_encode($dataJson);

        $urlEncode = urlencode($base64Encode);
        $redirectUrl = $gatewayUrl . $urlEncode;

        return $redirectUrl;
    }
    
    public static function generateRedirectUrlByToken(AppIdToken $appIdToken, $gatewayUrl) {
        $dataJson = $appIdToken->toJson();
        $base64Encode = base64_encode($dataJson);

        $urlEncode = urlencode($base64Encode);
        $redirectUrl = $gatewayUrl . $urlEncode;

        return $redirectUrl;
    }

}
