<?php

namespace app\admin\controller\admin;

use app\admin\controller\BaseController;
use app\admin\validate\AdminValidate;
use app\common\model\Admin;
use app\common\model\AdminRole;
use app\common\service\AdminAndRoleService;
use app\common\service\BaseService;
use app\common\validate\BaseValidate;
use think\Request;

/**
 * 后台用户
 * Class AdminController
 * @package app\admin\controller
 */
class AdminController extends BaseController
{
    /**
     * 管理员列表
     * @authCheck true
     * @menu_id 2
     */
    public function index(Request $request)
    {
        $input = BaseService::requestParams([
            'key',
            'role' => ['','number','角色格式错误'],
        ]);
        $w = BaseService::makeWhere($input,[
            ['login_name|mobile','like','key'],
            ['role_id','like','role'],
        ]);
        // 查询字段赋值
        $role_check = empty($input['role']) ? 0 : $input['role']; // 当前选中的角色
        $key = empty($input['key']) ? '' : $input['key'];
        $query = [
            'page' => $request->get('page',1,'intval'),
            'query' => $request->get(),
        ];
        // 查询所有角色
        $roles = AdminRole::column('name','id');
        $data = Admin::where($w)
            ->withAttr('role_id',function ($v) use ($roles) {
                return AdminAndRoleService::roleId2Str($roles,$v);
            })
            ->paginate($request->get('limit',10,'intval'),false,$query);
        $total = $data->total();
        return view('admin@admin/list',compact('total','data','roles','role_check','key'));
    }

    /**
     * 展示管理员新增页面
     * @authCheck true
     * @menu_id 2
     * @return \think\response\View
     * @throws \app\common\exception\CustomException
     */
    public function showAdd()
    {
        $roles = AdminRole::where('status',1)->column('name','id'); // 角色列表
        $this->title('添加管理员');
        return view('admin@Admin/adminform',compact('roles'));
    }

    /**
     * 添加修改管理员
     * @authCheck true
     * @menu_id 2
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
            'id',
            'login_name',
            'role_id',
            'pwd',
            'mobile' => ['','require|mobile','请输入正确的手机号']
        ]);
        // 校验参数
        BaseValidate::check(AdminValidate::class,$input);

        // 去处重复的不存在的角色ID
        $role_all = AdminRole::where('status',1)->column('id');
        $input['role_id'] = array_unique($input['role_id']);
        foreach ($input['role_id'] as &$v) {
            if(!in_array($v,$role_all)) unset($v);
            $v = (int)$v;
        }

        // 判断密码
        if(empty($input['id'])) {
            if(empty($input['pwd'])) dieReturn('请输入登录密码');
        }else{
            if(empty($input['pwd'])) unset($input['pwd']);
        }

        // 处理密码
        if(!empty($input['pwd'])) {
            $input['pwd'] = password($input['pwd']);
        }

        // 保存
        return api_response(BaseService::saveData($input,Admin::class));
    }

    /**
     * 显示编辑管理员页面
     * @authCheck true
     * @menu_id 2
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
        if(empty($id) || !$data = Admin::find($id)) return $this->goBack();

        $roles = AdminRole::where('status',1)->column('name','id'); // 角色列表
        $data->role = AdminAndRoleService::roleId2Str($roles,$data->role_id);

        return view('admin@admin/adminform',compact('data','roles'));
    }

    /**
     * 修改管理员状态
     * @authCheck true
     * @menu_id 2
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function setStatus(Request $request)
    {
        $id = $request->post('id',0,'intval');
        if($id == session('admin.id')) dieReturn('别想不开啊，小老弟');
        return api_response(BaseService::saveStatus($id,Admin::class));
    }


    /**
     * 删除管理员
     * @authCheck true
     * @menu_id 2
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function delete(Request $request)
    {
        $id = $request->post('id');
        if(is_array($id) && in_array(session('admin.id'),$id)) {
            dieReturn('别想不开啊，小老弟');
        }else{
            if($id == session('admin.id')) dieReturn('别想不开啊，小老弟');
        }
        return api_response(BaseService::deleteData($id,Admin::class));
    }

    // 显示修改密码页面
    public function setPwd()
    {
        $this->title('修改密码');
        return view('admin@admin/password');
    }

    /**
     * 修改密码
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function savePwd()
    {
        $input = BaseService::requestParams([
            'old_pwd|原密码' => ['','require|length:6,25'],
            'password|新密码' => ['','require'],
            'repassword|确认新密码' => ['','require'],
        ]);
        if($input['password'] != $input['repassword']) dieReturn('两次密码输入不一致');
        $admin = session('admin');
        if(password($input['old_pwd']) != $admin['pwd']) dieReturn('原密码输入错误');
        if(password($input['password']) == $admin['pwd']) dieReturn('新密码不能和原密码相同');
        if(!Admin::update([
            'id' => $admin['id'],
            'pwd' => password($input['password'])
        ])) {
            dieReturn('修改密码失败');
        }
        session('admin',null);
        return api_response('');
    }


    // 显示修改个人信息页面
    public function setAdminInfo()
    {
        $this->title('修改个人信息');
        return view('admin@admin/info');
    }

    /**
     * 修改个人信息
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function saveAdminInfo()
    {
        $input = BaseService::requestParams([
            'mobile' => ['','require|mobile','请输入正确的手机号'],
            'hand_img' => ['','require','请上传头像']
        ]);
        // 保存信息
        $admin = session('admin');
        if(!Admin::update([
            'id' => $admin['id'],
            'mobile' => $input['mobile'],
            'image' => str_replace(request()->root(true),'',$input['hand_img']),
        ])){
            dieReturn('修改信息失败');
        }
        // 修改session信息
        session('admin.mobile',$input['mobile']);
        session('admin.image',$input['hand_img']);
        return api_response('');
    }

    // 退出登录
    public function byeBye()
    {
        session('admin',null);
        return api_response('');
    }

}
