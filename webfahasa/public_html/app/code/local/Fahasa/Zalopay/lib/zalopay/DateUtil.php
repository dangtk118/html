<?php

class DateUtil {

    public static function getAppTransIdPrefix() {
        $tz = 'Asia/Ho_Chi_Minh';
        $timestamp = time();
        $dt = new DateTime("now", new DateTimeZone($tz));
        $dt->setTimestamp($timestamp);
        return $dt->format('ymdHis');
    }

}
