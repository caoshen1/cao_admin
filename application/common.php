<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// 产生随机字符串
function randStr($len = 0) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    $len = $len ? $len : mt_rand(5,10);
    for ( $i = 0; $i < $len; $i++ )
    {
        $str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    return $str;
}

// 终止执行并返回结果
function dieReturn($msg,$code = 0)
{
    json(['code'=>$code,'msg'=>$msg])->send();
    exit();
}

// 返回json
function api_response($res)
{
    if(empty($res)) return json(['data' => null, 'code' => 1]);
    if (!isset($res['query_status']) || $res['query_status'] == 1) {
        $data = isset($res['data']) ? $res['data'] : $res;
        return json(['data' => $data, 'code' => 1]);
    }
    return json(['msg' => $res['msg'], 'code' => 0]);
}

// 密码加密
function encryptPwd($p,$salt = '')
{
    $salt = $salt ? $salt : config('conf.salt');
    return md5($p . $salt);
}

/**
 * 生成Token
 * @param $uid int 用户ID
 * @param $type int 用户类型 1：平台管理员  2：用户
 * @return string token
 */
function makeToken($uid, $type)
{
    $time = time();
    $data = [
        'uid' => $uid,
        'typ' => $type,
        'qft' => $time, // 签发时间
        //'gqt' => $time + 1800, // 过期时间
        'gqt' => $time + (365 * 86400), // 过期时间
    ];

    return \fun\Openssl::encrypt(json_encode($data));
}

// 验证Token
function readToken($token)
{
    $time = time();
    try {
        $data = json_decode(\fun\Openssl::decrypt($token), true);
        if ($data['gqt'] > $time && $time >= $data['qft']) {
            return $data;
        }
        dieReturn('登录已过期，请重新登录',1003);
    } catch (\Exception $e) {
        dieReturn('凭证非法',2);
    }

}

// 返回服务层执行结果
function queryStatus($code,$data = null)
{
    return $code == 1 ? ['query_status'=>$code,'data'=>$data] : ['query_status'=>$code,'msg'=>$data];
}

// 生成日期订单号
function makeOid()
{
    return date('YmdHis').mt_rand(1000,9999); // 18位
}

// 生成订单支付号
function makePayId()
{
    return time() . mt_rand(10000, 99999); // 15位
}

// 时间戳转日期
function Ymd($stamp) {
    return date('Y-m-d', $stamp);
}

function YmdHis($stamp) {
    return date('Y-m-d H:i:s', $stamp);
}

function YmdHi($stamp) {
    return date('Y-m-d H:i', $stamp);
}

if(!function_exists('startEndTime')) {
    /**
     * 根据开始日期和结束日期获取时间戳
     * @param string $date 字符串日期
     * @param string $type start|end
     * @return false|int
     */
    function startEndTime($date,$type) {
        if($type == 'start') {
            $str = ' 00:00:00';
        }else{
            $str = ' 23:59:59';
        }
        return strtotime(date('Y-m-d',strtotime($date) . $str));
    }
}

if(!function_exists('mstime')) {
    /**
     * 返回当前的毫秒时间戳
     * @return float
     */
    function mstime() {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
}


if(!function_exists('sum')) {
    /**
     * 计算元素的和，支持数字和数字数组
     * @param mixed ...$params
     * @return int|string
     * @throws Exception
     */
    function sum(...$params)
    {
        if(count($params) <= 0) throw new \Exception('请传入正确的参数');
        $sum = 0;
        foreach ($params as $param) {
            if(is_array($param)) {
                foreach ($param as $num) {
                    if(!is_numeric($num)) throw new \Exception('请传入正确的参数');
                    $sum += $num;
                }
            }else if(is_numeric($param)) {
                $sum += $param;
            }else{
                throw new \Exception('请传入正确的参数');
            }
        }
        return $sum;
    }
}

if(!function_exists('hash2int')) {
    /**
     * 将字符串哈希成整型
     * @param string $string
     * @return int
     */
    function hash2int($string)
    {
        $hash = 0;
        $len = strlen($string);
        for($i = 0; $i < $len; $i++) {
            $hash += ($hash << 5 ) + ord($string[$i]);
        }
        return $hash % 701819;
    }
}

if(!function_exists('setErrorExtend')) {
    /**
     * 设置CustomException异常扩展信息
     * @param $param
     */
    function setErrorExtend($param)
    {
        cache('error_log_extend',$param);
    }
}

// 引入后台函数文件
include_once __DIR__. '/../extend/fastAdmin/common.php';

