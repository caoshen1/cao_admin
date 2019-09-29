<?php
/**
 * Created by PhpStorm.
 * User: 曹珅
 * Date: 2019/6/13
 * Time: 20:13
 */


if(!function_exists('dieReturn'))
{
    /**
     * 强制返回错误信息
     * @param string $msg 错误信息
     * @param int $code 响应码
     */
    function dieReturn(string $msg, int $code = 0)
    {
        echo json_encode(['code'=>$code,'msg'=>$msg],JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if(!function_exists('serviceReturn'))
{
    /**
     * 服务层信息返回
     * @param int $status 状态码  1成功
     * @param null $data 返回数据
     * @return array
     */
    function serviceReturn(int $status = 1, $data = null)
    {
        if($status == 1) {
            return ['status'=>1,'data'=>$data];
        }
        return ['status'=>0,'msg'=>$data];
    }
}

if(!function_exists('apiReturn'))
{
    /**
     * @param mixed $data 服务层返回数据
     * @return \think\response\Json
     */
    function apiReturn($data)
    {
        if(isset($data['status']) && $data['status'] == 0) {
            return json(['code'=>0,'msg'=>$data['msg']]);
        }
        return json(['code'=>1,'data'=>$data['data'] ?? $data]);
    }
}

// 获取操作系统
if(!function_exists('getOs'))
{
    function getOS(){
        if(preg_match('/WIN/',PHP_OS)){
            return 'win';
        }
        return 'linux';
    }
}