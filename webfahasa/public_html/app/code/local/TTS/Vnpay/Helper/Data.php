<?php

class TTS_Vnpay_Helper_Data extends Mage_Core_Helper_Abstract
{
    public static function getTimeStamp()
    {
        $tz = 'Asia/Ho_Chi_Minh';
        $timestamp = time();
        $dt = new DateTime("now", new DateTimeZone($tz));
        $dt->setTimestamp($timestamp);
        return $dt->format('YmdHis');
    }

}