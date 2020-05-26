<?php namespace App\Libraries;

class GlobalIdentity {

    public static function genGUID() {
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);
        $guid = 
             substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $guid;
    }
}