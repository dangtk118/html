<?php

class JsonUtil {

    /**
     * 
     * @param object $obj
     * @return string Json
     */
    public static function toJson($obj) {
        $arr = self::objectToArray($obj);
        return json_encode($arr);
    }

    private static function objectToArray($obj) {
        if ($obj instanceof BaseEntity) {
            $obj = $obj->toArray();
        }

        $new = array();
        if (is_array($obj)) {
            foreach ($obj as $key => $val) {
                $new[$key] = JsonUtil::objectToArray($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }

}