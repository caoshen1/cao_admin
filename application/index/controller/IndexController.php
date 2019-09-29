<?php

namespace app\index\controller;

use app\common\service\AuthorityService;
use think\Controller;
use think\Request;

/**
 * 测试控制器
 * Class IndexController
 * @package app\index\controller
 */
class IndexController extends Controller
{
    /**
     * 主控制器
     * @authCheck false
     * @param Request $request
     * @throws \app\common\exception\CustomException
     */
    public function index(Request $request)
    {

    }

}
