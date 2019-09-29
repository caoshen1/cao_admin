<?php
/**
 * FastAdmin 公共函数
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/8/2
 * Time: 14:44
 */

if(!function_exists('staticResource')) {
    // 加载静态资源
    function staticResource($path) {
        return 'http://'.$_SERVER["HTTP_HOST"].'/cao_admin/' . $path;
    }
}

if(!function_exists('password')) {
    // 密码加密
    function password($pwd,$salt = '') {
        $salt = empty($salt) ? config('conf.salt') : $salt;
        return md5($pwd.$salt);
    }
}

if(!function_exists('api_response')) {
    // 返回json
    function api_response($res)
    {
        if (!isset($res['query_status']) || $res['query_status'] == 1) {
            $data = $res['data'] ?? $res;
            return json(['data' => $data, 'code' => 1]);
        }
        return json(['msg' => $res['msg'], 'code' => 0]);
    }
}

if(!function_exists('dieReturn')) {
    // 终止执行并返回结果
    function dieReturn($msg,$code = 0)
    {
        json(['code'=>$code,'msg'=>$msg])->send();
        exit();
    }
}

if(!function_exists('arr2Str')) {
    // 终止执行并返回结果
    function arr2Str($arr)
    {
        static $str = '[';
        if(empty($arr) || !is_array($arr)) return '[]';
        foreach ($arr as $v) {
            if(is_array($v)) {
                arr2Str($v);
            }
            $str .= $v . ',';
        }
        return rtrim($str,',') . ']';
    }
}
