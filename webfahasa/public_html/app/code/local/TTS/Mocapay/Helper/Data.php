<?php

set_include_path(get_include_path() . PATH_SEPARATOR . Mage::getBaseDir('lib') . '/phpseclib');
require_once('Crypt/Hash.php');

class TTS_Mocapay_Helper_Data extends Mage_Core_Helper_Abstract {

    public function generateHMACSignature($partnerId, $partnerSecret, $httpMethod, $requestUrl, $contentType, $requestBody, $timestamp)
    {
        $crypt_hash = new Crypt_Hash();
        $hashedPayload = base64_encode($crypt_hash->_sha256($requestBody));

        $data = array(
            $httpMethod, $contentType, $timestamp, $requestUrl, $hashedPayload
        );
        $rawData = join("", array(join("\n", $data), "\n"));
        $signature = base64_encode(hash_hmac("SHA256", $rawData, $partnerSecret, true));
        return $partnerId . ":" . $signature;
    }

    public function generateRandomString($length)
    {
        $text = '';
        $possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        for ($i = 0; $i < $length; $i++)
        {
            $text .= $possible[rand(0, strlen($possible))];
        }
        return $text;
    }

    function base64url_encode($plainText)
    {
        $base64 = base64_encode($plainText);
        $base64 = trim($base64, "=");
        $base64url = strtr($base64, '+/', '-_');
        return ($base64url);
    }

    function base64URLEncode_pop($str)
    {
//        return rtrim(strtr(($verifier), '+/', '-_'), '=');
        return str_replace(['=', '+', '/'], ['', '-', '_'], ($str));
    }

    function generateCodeVerifier()
    {
        $random = bin2hex(openssl_random_pseudo_bytes(32));
        return $this->base64url_encode(pack('H*', $random));
    }

    public function base64URLEncode($code_verifier)
    {
        return $this->base64url_encode(pack('H*', hash('sha256', $code_verifier)));
    }

    public function cryptHashSha256($str)
    {
        $crypt_hash = new Crypt_Hash();
        return $crypt_hash->_sha256($str);
    }

    public function calculateHMACForPOP1($client_secret, $access_token)
    {

//        $access_token = "eyJhbGciOiJSUzI1NiIsImtpZCI6Il9kZWZhdWx0IiwidHlwIjoiSldUIn0.eyJhdWQiOiIyYTNlOTRiM2VlMzg0ODY4YTIzMjA1NWRiODg0NTA0ZCIsImF1dGhfdGltZSI6MTYwNzk3NTIwMywiZXhwIjoxNjM5NTExMjY5LCJpYXQiOjE2MDc5NzUyNjksImludF9zdmNfYXV0aHpfY3R4IjoiZmIwNTFmYjI1ZmQ2NGZlYWI1YWRiNGM2NmFjMWI4MmYiLCJpc3MiOiJodHRwczovL2lkcC5ncmFiLmNvbSIsImp0aSI6IjZOLWNUVVF5U1ktYl9FTDNubmJhU2ciLCJuYmYiOjE2MDc5NzUwODksInBpZCI6ImFlNmI5ZmMxLTRhYjAtNGY1My1hMGVhLTRkYTIzNWI2YjdlZCIsInNjcCI6IltcIjYwNmViOGIwOTg3ZjQyNDdhOGJiMmYxNDMwOTI5MmM2XCJdIiwic3ViIjoiZDczM2M4YjMtMzgzOC00MjczLWFlYmEtM2Y2ZTk3NWE2NGVjIiwic3ZjIjoiUEFTU0VOR0VSIiwidGtfdHlwZSI6ImFjY2VzcyJ9.nHLispWuYzK8kZUB9LltT0MwE3Xb1FTL7iwNRccd3jojGuL8b18rSVebY99TSDdr2-ijojm-H7V5fZY8rwvDpU3LsIMnNKkbFlgKThv8xgVkTCYPwwQj4LDwsltgMV6h6Qvp28Fk0M3L_cHrUwZjrodx04oRWyKGoC0u3Q8iCUbC3jp_gx-euENjg2TClUHpJ4NViKnSvWFQLS0r9N31lE2KUElvHGOckH6KuwLLmkxJYa8VC3DIMZX0DV4ulRnSEILUBYcWwGqV_LuXqObCvvepc9Jx0MDTlCROCr5Q9zbWci5AWO1WgCCnCNA43wu-jfyTBPyoUcbz3onFDKMYEQ";
//        $unix_time = time();
//        $unix_time = strtotime($date);
        $unix_time = 1608015417;
        Mage::log(" UNIX TIEM 0-- " . $unix_time, null, "mocapay.log");
        $message = $unix_time . $access_token;
//        $words = utf8_decode($message);
//        $utf8 = utf8_encode($words);
//        $utf8 = (mb_convert_encoding($message, "UTF-8"));
//        $utf8 = base64_encode(hash('sha256', $message, true));
        $utf8 = $message;
        $length_test = strlen($utf8);






//        $signature = base64_encode(hash_hmac("SHA256", $utf8, $client_secret, true));
        $signature = base64_encode(hash_hmac("SHA256", $utf8, $client_secret, true));
//        $signature = base64_encode(hash('sha256', $utf8, true));
        $sub = $this->base64URLEncode_pop($signature);

        $payload = array(
            "time_since_epoch" => $unix_time,
            "sig" => $sub
        );
        $payloadBytes = json_encode($payload);
        $result = $this->base64URLEncode_pop(base64_encode($payloadBytes));
        return $result;
    }

    public function calculateHMACForPOP($client_secret, $access_token)
    {
        $unix_time = time();
        $message = $unix_time . $access_token;
        $utf8 = $message;
        $signature = base64_encode(hash_hmac('sha256', $utf8, $client_secret, true));
        $sub = $this->base64URLEncode_pop($signature);

        $payload = [
            "time_since_epoch" => $unix_time,
            "sig" => $sub
        ];
        $payloadBytes = json_encode($payload);
        $result = $this->base64URLEncode_pop(base64_encode($payloadBytes));
        return $result;
    }

}
