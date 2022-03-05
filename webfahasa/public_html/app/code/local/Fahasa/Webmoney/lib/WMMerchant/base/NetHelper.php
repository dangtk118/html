<?php
/**
 * @Author: toan.nguyen
 * @Date:   2016-02-25 09:18:45
 * @Last Modified by:   hgiasac
 * @Last Modified time: 2016-02-28 22:35:41
 */

namespace WMMerchant\base;

/**
 * Network helper class
 */
class NetHelper {

    /*
     * Get IP address from client
     */
    public static function getIPAddress()
    {
        //Get User IP Address
        $ip_address = null;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        return $ip_address;
    }

    /**
     * Get base URL of current request
     *
     * @param  boolean $use_forwarded_host Is using forwarded host
     *
     * @return string                      URL string
     */
    public static function getBaseURL($use_forwarded_host = false) {
        $s = $_SERVER;

        $ssl      = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on');
        $sp       = strtolower($s['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port     = $s['SERVER_PORT'];
        $port     = ((!$ssl && $port=='80') || ($ssl && $port == '443') ) ? '' : ':' . $port;
        $host     = ($use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST']) ) ? $s['HTTP_X_FORWARDED_HOST'] : (isset( $s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
        $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
        return $protocol . '://' . $host;
    }

    /**
     * Get full current URL
     *
     * @param  boolean $use_forwarded_host Is using forwarded host
     *
     * @return string                      URL string
     */
    public static function getCurrentURL($use_forwarded_host = false) {
        return self::getBaseURL($use_forwarded_host) . $_SERVER['REQUEST_URI'];
    }
}
