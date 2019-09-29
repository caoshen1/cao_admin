<?php
namespace fun;

class Openssl
{

    public static function encrypt($val)
    {
        $OPENSSL = config('conf.OPENSSL');
        $key = $OPENSSL['key'];
        $iv = $OPENSSL['iv'];
        $encrypt = openssl_encrypt($val, 'AES-256-CBC', $key, 0, $iv);
        $encrypt = base64_encode($encrypt);
        return $encrypt;
    }

    public static function decrypt($val)
    {
        $OPENSSL = config('conf.OPENSSL');
        $key = $OPENSSL['key'];
        $iv = $OPENSSL['iv'];
        $decrypt = openssl_decrypt(base64_decode($val), 'AES-256-CBC', $key, 0, $iv);
        return $decrypt;
    }
}
