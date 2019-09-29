<?php
namespace mytools\lib;

class Openssl
{
    // 秘钥
    private static $key = '';
    // 偏移量
    private static $iv = '';

    // 加密
    public static function encrypt($val)
    {
        $encrypt = openssl_encrypt($val, 'AES-256-CBC', self::$key, 0, self::$iv);
        $encrypt = base64_encode($encrypt);
        return $encrypt;
    }

    // 解密
    public static function decrypt($val)
    {
        $decrypt = openssl_decrypt(base64_decode($val), 'AES-256-CBC', self::$key, 0, self::$iv);
        return $decrypt;
    }
}
