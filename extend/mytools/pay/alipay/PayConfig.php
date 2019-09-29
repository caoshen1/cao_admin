<?php
/**
 * Created by PhpStorm.
 * User: line
 * Date: 2019-03-13
 * Time: 16:05
 */
namespace app\admin\channel\alipay;

// 这里的配置参数要读取数据库等自己改。 ---- 返回方式是数组就好了。


class PayConfig
{
    /**
     * 支付宝配置
     * @return array
     */
    public static function AliPay(){
        return [
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
        ];
    }

    /**
     * 微信配置
     * @return array
     */
    public static function WxPay(){
        return [
            //绑定支付的APPID（必须配置，开户邮件中可查看）
            'AppId'                     => '',
            //商户号（必须配置，开户邮件中可查看）
            'MchID'                     => '',
            //支付回调url
            'NotifyUrl'                 => '',
            //签名和验证签名方式， 支持 MD5 和 HMAC-SHA256 方式
            'SignType'                  => 'MD5',
            //上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
            'ReportLevenl'              => '1',
            //KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
            'Key'                       => '',
            //APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置）
            'AppSecret'                 => '',
            //证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载
            'sslCertPath'               => '',
            //API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
            'sslKeyPath'                => '',
            //证书文件不能放在web服务器虚拟目录，应放在有访问权限控制的目录中，防止被他人下载；
            //建议将证书文件名改为复杂且不容易猜测的文件名；
            //商户服务器要做好病毒和木马防护工作，不被非法侵入者窃取证书文件。
        ];
    }
}