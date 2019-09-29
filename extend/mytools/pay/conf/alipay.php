<?php
return [
    'title'=>'支付宝支付',
    'class'=> '\\app\\channel\\wxpay\\AliPay',
    // 配置项
    'conf'=> [
        //应用ID
        'Appid'                     => '',
        //同步回调地址
        'ReturnUrl'                 => '',
        //异步回调地址
        'NotifyUrl'                 => '',
        //商户私钥 === 这是生成的私钥
        'RsaPrivateKey'             => '',
        //支付宝公钥 === 这是上传后的 旁边有个支付宝公钥
        'alipayPublicKey'           => '',
        // 支付回调地址
        'pay_notify_url'=>'',
        // 退款回调地址
        'refund_notify_url'=>'',
    ],
];