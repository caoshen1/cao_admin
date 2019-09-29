<?php
return [
    'title'=>'微信支付',

    // 退款回调地址
    'refund_notify_url'=>'',
    // 配置项
    'conf'=> [
        'mch_id' => '1524809771',//微信支付商户号
        'appid' => 'wx81a67ed3c93d71f9',//微信公众平台APPID
        'pay_apikey' => 'E5X38opQDW67AwgPqIF0nJbfL1VhSTeR',//微信支付API密钥
        'api_cert' => env('root_path') . 'application/channel/wxpay/apiclient_cert.pem',
        'api_key' => env('root_path') . 'application/channel/wxpay/apiclient_key.pem',
        'class'=> '\\app\\channel\\wxpay\\WxApi',
        // 支付回调地址
        'pay_notify_url'=>'pay/wx/notify',
    ],
];