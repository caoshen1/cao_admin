<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/9/2
 * Time: 15:55
 */

return [
    'official_account' => [//微信公众平台
        'token'   => '',          // Token
        'aes_key' => '',   // EncodingAESKey，兼容与安全模式下请一定要填写！！！
        'app_id' => '',         // AppID
        'secret' => '',     // AppSecret
//        'token' => '',          // Token
//        'aes_key' => '',   // EncodingAESKey，兼容与安全模式下请一定要填写！！！
    ],
    'mini_program' => [//微信小程序
        'app_id' => '',         // AppID
        'secret' => '',     // AppSecret

        // 下面为可选项
        // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
        'response_type' => 'array',

        'log' => [
            'level' => 'debug',
            'file' => __DIR__.'/../runtime/log/wechat.log',
        ],
    ],
    'payment' => [//微信支付
        'mch_id' => '',
        'key' => '',   // API 密钥
    ],
    /*'oauth' => [
        'scopes'   => ['snsapi_userinfo'],
        'callback' => 'https://app.168jshphy.cn/api/h5Login',
    ],*/
];