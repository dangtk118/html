<?php

class Api {

    public static function createOrder($apiUrl, Order $order) {
        $orderResponse = NULL;
        try {
            $response = self::postCurl($apiUrl, $order->toArray());
            if (!empty($response)) {
                $json = json_decode($response, true);
                if (!empty($json)) {
                    $returnCode = isset($json['returncode']) ? $json['returncode'] : 0;
                    $returnMessage = isset($json['returnmessage']) ? $json['returnmessage'] : 'Exception';
                    $zpTransToken = isset($json['zptranstoken']) ? $json['zptranstoken'] : '';
                    $orderResponse = new OrderResponse($returnCode, $returnMessage, $zpTransToken);
                }
            }
        } catch (Exception $ex) {
            throw $ex;
        }

        return $orderResponse;
    }

    private static function postCurl($url, $params, $second = 30) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error, 0);
        }
    }

    public static function statusOrder($apiUrl) {
        $orderStatusResponse = NULL;
        try {
            $response = self::getCurl($apiUrl);
            if (!empty($response)) {
                $json = json_decode($response, true);
                if (!empty($json)) {
                    $returnCode = isset($json['returncode']) ? $json['returncode'] : 0;
                    $returnMessage = isset($json['returnmessage']) ? $json['returnmessage'] : 'Exception';
                    $isprocessing = isset($json['isprocessing']) ? $json['isprocessing'] : '';
                    $amount = isset($json['amount']) ? $json['amount'] : '';
                    $zptransid = isset($json['zptransid']) ? $json['zptransid'] : '';
                    $orderStatusResponse = new OrderStatusResponse($returnCode, $returnMessage, $isprocessing, $amount, $zptransid);
                }
            }
        } catch (Exception $ex) {
            throw $ex;
        }

        return $orderStatusResponse;
    }

    private static function getCurl($url, $second = 30) {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, $second);
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, FALSE);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curlHandle);
        if ($response) {
            curl_close($ch);
            return $response;
        } else {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error, 0);
        }
    }

}
