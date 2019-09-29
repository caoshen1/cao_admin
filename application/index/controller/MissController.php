<?php

namespace app\index\controller;

use think\Controller;

/**
 * 错误路由
 * Class MissController
 * @package app\index\controller
 */
class MissController extends Controller
{
    /**
     * @authCheck false
     * @return \think\response\Json
     */
    public function miss()
    {
        return json(['data' => null, 'msg' => '路由非法', 'code' => 0]);
    }

}