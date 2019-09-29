<?php

namespace app\admin\controller;

use app\common\service\AuthorityService;
use think\Controller;

/**
 * 基础控制器
 * Class BaseController
 * @package app\admin\controller
 */
class BaseController extends Controller
{
    /**
     * BaseController constructor.
     * @throws \ReflectionException
     * @throws \app\common\exception\CustomException
     */
    public function __construct()
    {
        parent::__construct();
        if (!session('admin')) {
            $this->redirect('admin/login');
        }
        // 校验权限
        AuthorityService::checkAuth();
    }


    protected function title($title)
    {
        $this->assign('title',$title);
    }

    // 返回上一页
    protected function goBack()
    {
        return view('admin@index/error');
    }
}
