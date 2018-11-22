<?php
/**
 * Created by PhpStorm.
 * User: Alfredo
 * Date: 11/4/18
 * Time: 10:22 PM
 */

namespace magicsoft\select;


class MagicCrypto
{
    CONST ENCRYPT_METHOD = "AES-256-CBC";
    CONST SECRET_KEY = "205bdf05272043d";
    CONST SECRET_IV = "205bdf0512ea37e";

    public static function encrypt($string){

        $key = hash('sha256', self::SECRET_KEY);
        $iv = substr(hash('sha256', self::SECRET_IV), 0, 16);
        $output = openssl_encrypt($string, self::ENCRYPT_METHOD, $key, 0, $iv);

        return base64_encode($output);
    }

    public static function decrypt($string){
        $key = hash('sha256', self::SECRET_KEY);
        $iv = substr(hash('sha256', self::SECRET_IV), 0, 16);

        return openssl_decrypt(base64_decode($string), self::ENCRYPT_METHOD, $key, 0, $iv);
    }
}