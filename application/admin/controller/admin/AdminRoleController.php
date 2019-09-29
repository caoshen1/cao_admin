<?php

namespace app\admin\controller\admin;



use app\admin\controller\BaseController;
use app\common\model\Admin;
use app\common\model\AdminMenuList;
use app\common\model\AdminRole;
use app\common\service\AuthorityService;
use app\common\service\BaseService;
use think\Request;

/**
 * 后台角色
 * Class AdminRoleController
 * @package app\admin\controller
 */
class AdminRoleController extends BaseController
{
    /**
     * 角色列表
     * @authCheck true
     * @menu_id 3
     * @param Request $request
     * @return \think\response\View
     * @throws \app\common\exception\CustomException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $input = BaseService::requestParams([
            'key',
        ]);
        $w = BaseService::makeWhere($input,[
            ['id|name','like','key'],
        ]);
        // 查询字段赋值
        $key = empty($input['key']) ? '' : $input['key'];
        $query = [
            'page' => $request->get('page',1,'intval'),
            'query' => $request->get(),
        ];
        $data = AdminRole::where($w)->field('id,name,status')->order('id desc')->paginate($request->get('limit',10,'intval'),false,$query);
        $total = $data->total();
        return view('admin@admin/role',compact('total','data','key'));
    }


    /**
     * 刷新权限列表
     * @authCheck true
     * @menu_id 3
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function flashAuthList()
    {
        AuthorityService::scanController();
        return api_response('');
    }

    /**
     * 展示角色新增页面
     * @authCheck true
     * @menu_id 3
     * @return \think\response\View
     * @throws \app\common\exception\CustomException
     */
    public function showAdd()
    {
        // 获取全部权限列表
        $auth_list = AuthorityService::getAuthList();
        // 获取权限标题
        $auth_title = AuthorityService::$path_title;
        return view('admin@Admin/roleform',compact('auth_list','auth_title'));
    }

    /**
     * 添加修改角色
     * @authCheck true
     * @menu_id 3
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save()
    {
        // 接收参数
        $input = BaseService::requestParams([
            'id' => ['','number','ID格式错误'],
            'name' => ['','require','角色名不能为空'],
            'auth_list' => ['','require|array','权限列表信息错误'],
        ]);

        // 如果是新增，检查角色名是否重复
        if(empty($input['id'])) {
            if(AdminRole::where('name',$input['name'])->count('*'))
                dieReturn('该角色已经被创建');
        }else{
            if($input['id'] == 1001) dieReturn('超级管理员不可修改');
        }

        // 整理权限列表和菜单列表
        $menu_cache = AuthorityService::getMenuList();
        if(empty($menu_cache)) dieReturn('系统菜单缓存异常，请执行刷线权限操作');
        $menu_temp = [];
        foreach ($input['auth_list'] as $k => $a) {
            if($input['auth_list'] != 0 && !empty($menu_cache[$a])) {
                $menu_temp[] = (int)$menu_cache[$a];
            }else{
                unset($input['auth_list'][$k]);
            }
        }
        $input['auth_list'] = array_values(array_map(function ($v) {
            return (int)$v;
        },$input['auth_list']));
        $input['menu_list'] = array_unique($menu_temp);
        // 将父菜单加入菜单数组
        $all_menu = AdminMenuList::column('pid','id'); // [id => pid]
        foreach ($input['menu_list'] as $m) {
            if(!in_array($all_menu[$m],$input['menu_list']) && $all_menu[$m])
                $input['menu_list'][] = $all_menu[$m];
        }
        $input['menu_list'] = array_values($input['menu_list']);
        // 保存
        return api_response(BaseService::saveData($input,AdminRole::class));
    }

    /**
     * 显示编辑角色页面
     * @authCheck true
     * @menu_id 3
     * @param $id
     * @return string|\think\response\View
     * @throws \app\common\exception\CustomException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function showEdit($id)
    {
        // 参数错误返回上一页
        if(empty($id) || !$data = AdminRole::find($id)) return $this->goBack();

        // 获取全部权限列表
        $auth_list = AuthorityService::getAuthList();
        // 获取权限标题
        $auth_title = AuthorityService::$path_title;

        return view('admin@Admin/roleform',compact('data','auth_list','auth_title'));
    }

    /**
     * 修改角色状态
     * @authCheck true
     * @menu_id 3
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function setStatus(Request $request)
    {
        $id = $request->post('id',0,'intval');
        if(empty($id)) dieReturn('角色ID为空');
        if($id == 1001) {
            dieReturn('超级管理员不可修改状态');
        }
        if(AdminRole::where('id',$id)->value('status') == 1) {
            if(Admin::where('role_id','like',"%{$id}%")->count('*'))
                dieReturn('该角色下拥有用户，不可禁用');
        }
        return api_response(BaseService::saveStatus($id,AdminRole::class));
    }


    /**
     * 删除角色
     * @authCheck true
     * @menu_id 3
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        if(empty($id)) dieReturn('请选择需要删除的角色');
        if($id == 1001) {
            dieReturn('超级管理员禁止删除');
        }
        if(is_array($id)) {
            foreach ($id as $i) {
                if(Admin::where('role_id','like',"%{$i}%")->count('*'))
                    dieReturn('ID为：<b>'.$i.'</b>的角色下拥有用户，禁止删除');
            }
        }else{
            if(Admin::where('role_id','like',"%{$id}%")->count('*'))
                dieReturn('该角色下拥有用户，禁止删除');
        }

        return api_response(BaseService::deleteData($id,AdminRole::class));
    }
}
