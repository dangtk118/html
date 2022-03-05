<?php

namespace WMMerchant\base;

/**
 * Security helper methods
 * Used for encrypting checksum
 */
class Security {

    /**
     * Join all properties values from model as string
     *
     * @param  Model $model     Model to be hashed
     * @return string           Result Hash string
     */
    public static function joinHashText($model) {
        $plainString = '';
        $attributes = $model->hashAttributes();

        foreach ($attributes as $key) {
            $plainString = $plainString . $model->$key;
        }

        return $plainString;
    }

    /**
     * Hash checksum from string, with secret key as salt
     * Concatenates all property, except checksum, then uses hash_hmac with secret key
     *
     * @param  string $text     Input text
     * @param  string $secret   Secret key
     * @return string           Result Hash string
     */
    public static function hashChecksum($text, $secret) {
        $hash =  hash_hmac('sha1', $text, $secret);

        return strtoupper($hash);
    }

    /**
     * Validates checksum
     *
     * @param  string $checksum Checksum hash
     * @param  string $text     The plain text to validate
     * @param  string $secret   The secret key which is provided by webmoney
     * @return boolean          Is checksum valid
     */
    public static function validateChecksum($checksum, $text, $secret) {
        $value = self::hashChecksum($text, $secret);
        return $checksum === $value;
    }
}
