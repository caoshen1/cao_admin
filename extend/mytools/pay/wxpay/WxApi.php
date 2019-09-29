<?php

namespace app\channel\wxpay;

use app\api\controller\OrderController;
use app\channel\BaseChannel;
use fun\Openssl;

class WxApi
{
    private $config = [];

    public function __construct($config = [])
    {
        $this->config = $config;
    }

    /**
     * 微信支付请求接口(POST)
     * @param array $data [desc=>订单描述,order_no=>订单号,total=>订单金额,notify_url=>回调地址,trade_type=>支付类型,openid=>openID]
     * @return array
     */
    public function appPay($data)
    {
        $config = $this->config;

        //统一下单参数构造
        $unifiedorder = [
            'appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::getNonceStr(),
            'body' => $data['desc'] ?? '数融云支付订单', // 订单描述
            'out_trade_no' => $data['order_no'], //商户系统内部订单号
            'total_fee' => round($data['total'] * 100), // 金额
            'spbill_create_ip' => self::getip(),
            'notify_url' => $data['notify_url'] ?? $this->config['pay_notify_url'], // 回调
            'trade_type' => $data['trade_type'] ?? 'JSAPI', // 支付方式
        ];

        if (isset($data['openid']) && !empty($data['openid'])) {
            $unifiedorder['openid'] = $data['openid'];
        }
        $unifiedorder['sign'] = self::makeSign($unifiedorder);

        //请求数据,统一下单
        $xmldata = self::array2xml($unifiedorder);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $res = $this->curl_post_ssl($url, $xmldata);
        if (!$res) {
            return ['status' => 0, 'msg' => "无法连接到微信支付服务器"];
        }
        $PayResults = self::xml2array($res);
        if (strval($PayResults['result_code']) == 'FAIL') {
            return ['status' => 0, 'msg' => strval($PayResults['err_code']) . ':' . strval($PayResults['err_code_des'])];
        }
        if (strval($PayResults['return_code']) == 'FAIL') {

            return ['status' => 0, 'msg' => strval($PayResults['return_msg'])];
        }

        switch ($data['trade_type']) {
            case "APP":
                $prePayParams = array();
                $prePayParams['appid'] = $PayResults['appid'];
                $prePayParams['partnerid'] = $PayResults['mch_id'];
                $prePayParams['prepayid'] = $PayResults['prepay_id'];
                $prePayParams['noncestr'] = $PayResults['nonce_str'];
                $prePayParams['package'] = 'Sign=WXPay';
                $prePayParams['timestamp'] = time();
                $prePayParams['sign'] = self::makeSign($prePayParams);
                return ['status' => 1, 'data' => $prePayParams];
                break;
            case "NATIVE":
                return ['status' => 1, 'data' => $PayResults];
                break;
            case "JSAPI":
                $time = time();
                settype($time, "string");        //jsapi支付界面,时间戳必须为字符串格式
                $prePayParams = array(
                    'appId' => strval($PayResults['appid']),
                    'nonceStr' => strval($PayResults['nonce_str']),
                    'package' => 'prepay_id=' . strval($PayResults['prepay_id']),
                    'signType' => 'MD5',
                    'timeStamp' => $time
                );
                $prePayParams['paySign'] = self::makeSign($prePayParams);
                return ["status" => 1, 'data' => $prePayParams];
                break;
            default:
                return ['status' => 0, 'msg' => "非法请求类型"];
                break;
        }

    }


    /**
     * 微信退款(POST)
     * @param array $data [order_no=>订单号,refund_no=>退款单号,total=>总金额,desc=>退款描述,refund=>退款金额,notify_url=>回调地址]
     * @return array                        xml格式的数据
     */
    public function refund($data)
    {
        $config = $this->config;
        //退款参数
        $refundorder = array(
            'appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::getNonceStr(),
            'out_trade_no' => $data['order_no'],
            'out_refund_no' => $data['refund_no'],
            'total_fee' => round($data['total'] * 100),
            'refund_desc' => $data['desc'],
            'refund_fee' => round($data['refund'] * 100),
            'notify_url' => $data['notify_url'] ?? $this->config['refund_notify_url'], // 回调,
        );
        $refundorder['sign'] = self::makeSign($refundorder);
        //请求数据,进行退款
        $xmldata = self::array2xml($refundorder);
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $res = self::curl_post_ssl($url, $xmldata, true);
        if (!$res) {
            return ['status' => 0, 'msg' => "连接微信服务器失败"];
        }

        $content = self::xml2array($res);
        if (strval($content['result_code']) == 'FAIL') {
            return ['status' => 0, 'msg' => strval($content['err_code']) . ':' . strval($content['err_code_des'])];
        }
        if (strval($content['return_code']) == 'FAIL') {
            return ['status' => 0, 'msg' => strval($content['return_msg'])];
        }
        return ["status" => 1, 'data' => $content];

    }

    /**
     * 微信支付回调验证
     * 此方法写在 支付统一下单设定的 支付回调链接(notify_url参数)对应的方法里边
     * 比如你的支付回调链接是:http://yourhost/notify.php   这个方法就是放在该链接对应的方法里边执行
     * 此方法在回调方法里边,接收微信支付服务器返回的数据,并判断支付结果
     */
    public function notify()
    {
        //$xml = $GLOBALS['HTTP_RAW_POST_DATA']; //获取微信支付服务器返回的数据
        // 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
        $xml = file_get_contents("php://input");
        if (!$xml) {
            exit("非法请求！");
        }
        //将服务器返回的XML数据转化为数组
        $data = self::xml2array($xml);
        // 保存微信服务器返回的签名sign
        $data_sign = $data['sign'];
        if(!$data_sign){
            exit("非法请求！");
        }
        // sign不参与签名算法
        unset($data['sign']);
        $sign = self::makeSign($data);
        if ($sign === $data_sign) {
            $result = $data;
            // 组装数据调用回调方法 [pid=>流水号，total=>金额  status=>状态  msg=>消息  channel=>渠道]
            $notify_data = [
                'pid' => $data['out_trade_no'],
                'total' => $data['total_fee'],
                'status' => $data['result_code'] == 'SUCCESS' ? 1 : 2,
                'msg' => $data['err_code_des'],
                'channel'=> 1
            ];
            (new OrderController())->orderNotify($notify_data);
        }else {
            $result = false;
        }
        // 返回状态给微信服务器
        if ($result) {
            $str = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        } else {
            $str = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
    }

    // 退款回调结果解析
    public function refundNotify()
    {
        $xml = file_get_contents("php://input");
        if (!$xml) {
            exit("非法请求！");
        }
        //将服务器返回的XML数据转化为数组
        $data = self::xml2array($xml);
        // 解密
        try {
            $req_info = base64_decode($data['req_info']);
            $key = md5($this->config['pay_apikey']);
            $req_data = openssl_decrypt($req_info, 'AES-256-ECB', $key, 0);
        }catch (\Exception $e) {
            return;
        }
        // 组装返回数据
        $arr = [
            'tid' =>$req_data['out_refund_no'], // 自己流水号
            'oid' =>$req_data['out_trade_no'], // 自己订单号
            'trade_no' =>$req_data['refund_id'], // 微信退款订单号
            'total' =>$req_data['refund_fee'] * 100, // 微信退款金额
            'status' =>$req_data['refund_status'] == 'SUCCESS' ? 1 : 2, // 退款状态
        ];
        // 调用统一退款回调
        BaseChannel::refundNotify($arr);
    }

    /**
     * 将一个数组转换为 XML 结构的字符串
     * @param array $arr 要转换的数组
     * @param int $level 节点层级, 1 为 Root.
     * @return string XML 结构的字符串
     */
    protected function array2xml($arr, $level = 1)
    {
        $s = $level == 1 ? "<xml>" : '';
        foreach ($arr as $tagname => $value) {
            if (is_numeric($tagname)) {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if (!is_array($value)) {
                $s .= "<{$tagname}>" . (!is_numeric($value) ? '<![CDATA[' : '') . $value . (!is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
            } else {
                $s .= "<{$tagname}>" . $this->array2xml($value, $level + 1) . "</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s . "</xml>" : $s;
    }

    /**
     * 将xml转为array
     * @param  string $xml xml字符串
     * @return array    转换得到的数组
     */
    protected function xml2array($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return string 产生的随机字符串
     */
    protected function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 生成签名
     * @return  string 签名
     */
    protected function makeSign($data)
    {
        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = http_build_query($data);
        $string = urldecode($string);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->config['pay_apikey'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 获取IP地址
     * @return [String] [ip地址]
     */
    protected function getip()
    {
        static $ip = '';
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        return $ip;
    }

//    /**
//     * 微信支付发起请求
//     */
//    protected function curl_post_ssl($url, $xmldata, $second = 30)
//    {
//        $ch = curl_init();
//        //超时时间
//        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格校验
//        //设置header
//        curl_setopt($ch, CURLOPT_HEADER, false);
//        //要求结果为字符串且输出到屏幕上
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        //post提交方式
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmldata);
//
//        $data = curl_exec($ch);
//        //dump($data);
//        curl_close($ch);
//        if ($data) {
//            return $data;
//        } else {
//            return false;
//        }
//    }
    /**
     * 微信支付发起请求
     */
    protected function curl_post_ssl($url, $xmldata, $useCert = false)
    {
        $config = $this->config;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);//超时时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);

        if ($useCert) {
            //默认格式为PEM，可以注释
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $config['api_cert']);
            //默认格式为PEM，可以注释
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $config['api_key']);
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmldata);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data) {
            return $data;
        } else {
            return false;
        }
    }

}
