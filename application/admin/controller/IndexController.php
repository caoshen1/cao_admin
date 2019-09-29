<?php

namespace app\admin\controller;

use app\common\model\Admin;
use app\common\model\AdminMenuList;
use app\common\model\AdminRole;
use app\common\service\AdminAndRoleService;
use app\common\service\BaseService;
use app\common\service\ResourcesService;
use think\captcha\Captcha;
use think\Controller;
use think\Db;

class IndexController extends Controller
{
    /**
     * 管理员登录
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function loginHandle()
    {
        $input = BaseService::requestParams([
            'admin_name' => ['','require','请输入登录名'],
            'password' => ['','require','请输入密码'],
            'captcha' => ['','require|captcha','验证码错误']
        ]);
        $w = [
            'login_name' => $input['admin_name'],
            'pwd' => password($input['password'])
        ];

        $admin = Admin::where($w)->find();
        if(!$admin) dieReturn('用户名或密码错误');
        if($admin->getData('status') != 1) dieReturn('该用户已被禁用，请联系管理员');
        // 获取用户权限和菜单
        $auth_menu = AdminAndRoleService::getAuthByRole($admin->role_id);
        $admin = $admin->toArray();
        $admin['menu_list'] = $auth_menu['menu_list'];
        $admin['auth_list'] = $auth_menu['auth_list'];
        // 查询角色
        $roles = AdminRole::column('name','id');
        $str = '';
        foreach ($admin['role_id'] as $v) {
            $str .= $roles[$v] . ',';
        }
        $admin['role'] = rtrim($str,',');
        // 将用户信息写入session
        session('admin',$admin);
        // 渲染主页面
        return api_response('');
    }

    // 渲染登录页面
    public function login()
    {
        $table_name = config('database.prefix') . 'admin';
        if(!Db::query("SHOW TABLES LIKE '{$table_name}'")) {
            return view('setup@setup/index');
        }
        $project_name = config('app.app_name');
        return view('admin@index/login',compact('project_name'));
    }

    // 渲染主页
    public function index()
    {
        // 渲染主页面
        if (!session('admin')) {
            $this->redirect('admin/login');
        }
        $project_name = config('app.app_name');
        $this->assign('title',$project_name.'后台管理系统');
        $menu_list = $this->getMenuList();
        return view('admin@index/index',compact('project_name','menu_list'));
    }
    
    // 生成验证码
    public function getCaptcha()
    {
        $config =    [
            // 验证码字体大小
            'fontSize'    =>    35,
            // 验证码位数
            'length'      =>    4,
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    // 欢迎页
    public function welcome()
    {
        $this->assign('title','欢迎页');
        return view('admin@home/welcome');
    }

    /**
     * 获取管理员菜单列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenuList()
    {
        if (!session('admin')) {
            $this->redirect('admin/login');
        }
        $menus = AdminMenuList::where('id','in',session('admin.menu_list'))
            ->order('sort asc')
            ->select()
            ->toArray();
        // 如果是超级管理员，且为开发模式，则增加一个开发菜单
        if(in_array(1001,session('admin.role_id')) && config('jin_admin.model') == 'dev') {
            $menus[] = [
                'id' => 1531273950,
                'pid' => 0,
                'title' => '快速开发',
                'icon' => 'fa fa-space-shuttle',
                'sort' => 999
            ];
            $menus[] = [
                'id' => 1531273951,
                'pid' => 1531273950,
                'title' => '开发页面',
                'jump' => 'admin/cao_admin/page',
                'sort' => 999
            ];
        }
        return $this->asseMenuList($menus);
    }

    // 将菜单列表组合成规定格式
    private function asseMenuList($menus)
    {
        $temp = [];
        foreach ($menus as $v) {
            if($v['pid'] == 0) {
                $temp[$v['id']] = $v;
                foreach ($menus as $vv) {
                    if($vv['pid'] == $v['id']) {
                        $temp[$v['id']]['list'][] = $vv;
                    }
                }
            }
        }
        return array_values($temp);
    }

    /**
     * 文件异步上传
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function uploadFile()
    {
        if (!session('admin')) {
            $this->redirect('admin/login');
        }
        return api_response(ResourcesService::staticResource(ResourcesService::uploadFile('upload_file')));
    }
}
