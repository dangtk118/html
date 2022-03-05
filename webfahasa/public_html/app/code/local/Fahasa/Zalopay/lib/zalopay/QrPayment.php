<?php

class QrPayment {

    public static function genereateQRByOrder(Order $order, $imageExtension) {

        $dataJson = $order->toJson();
        return self::generateQR($dataJson, $imageExtension);
    }

    public static function generateQRByZPToken(AppIdToken $appIdToken, $imageExtension) {
        return self::generateQR($appIdToken->toJson(), $imageExtension);
    }

    private static function generateQR($data, $imageExtension) {

        if (strcmp($imageExtension, 'png') === 0) {
            return QRcode::png($data);
        }

        return QRcode::png($data);
    }

}
