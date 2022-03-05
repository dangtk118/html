<?php

set_include_path(get_include_path() . PATH_SEPARATOR . Mage::getBaseDir('lib') . '/phpseclib');
require_once('Crypt/RSA.php');

class TTS_Momopay_Helper_Data extends Mage_Core_Helper_Abstract {

    public static function encryptRSA(array $rawData, $publicKey) {
        $rawJson = json_encode($rawData, JSON_UNESCAPED_UNICODE);

        $rsa = new Crypt_RSA();
        $rsa->loadKey($publicKey);
        $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);

        $cipher = $rsa->encrypt($rawJson);
        return base64_encode($cipher);
    }

}
