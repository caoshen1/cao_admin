<?php

namespace app\admin\controller\caoadmin;

use app\common\service\AdminAndRoleService;
use app\common\service\ResourcesService;
use fastAdmin\command\FastAdmin;
use think\Controller;
use think\Db;
use think\Request;

/**
 * 自动生成后台
 * Class JingAdminController
 * @package app\admin\controller
 */
class CaoAdminController extends Controller
{
    protected $beforeActionList = [
        'checkModel'
    ];

    /**
     * 模型文件
     * @var array
     */
    protected $models = [];

    /**
     * 路由文件
     * @var array
     */
    protected $routes = [];

    /**
     * 前置方法，校验开发模式
     */
    public function checkModel()
    {
        if(config('jin_admin.model') != 'dev' || !session('admin'))
            dieReturn('当前不是开发者模式');
    }


    /**
     * 显示页面
     * @return \think\response\View
     */
    public function page()
    {
        // 获取模型文件和路由文件
        $this->scanMAR(1);
        $this->scanMAR(2);
        $models = $this->models;
        $routes = $this->routes;
        return view('admin@cao_admin/index',compact('models','routes'));
    }


    /**
     * 获取所有模型文件或者是路由文件
     * @param int $type 1:模型文件  2路由文件
     * @param string $p 遍历路径
     * @return array
     */
    private function scanMAR(int $type, $p = '')
    {

        if($p) {
            $path = $p;
        }else{
            $path = ResourcesService::getPath('root_path');
            switch ($type) {
                case 1:
                    $path .= 'application/common/model';
                    break;
                case 2:
                    $path .= 'route';
                    break;
            }
        }

        if(is_dir($path)) {
            $temp=scandir($path);
            //遍历文件夹
            foreach($temp as $v){
                $a = $path . '/' . $v;
                if(is_dir($a)){//如果是文件夹则执行
                    if($v == '..' || $v == '.') continue;
                    $this->scanMAR($type, $a);
                }else{
                    $type == 1 ? $this->models[] = $v : $this->routes[] = $v;
                }
            }
        }
    }


    /**
     * 根据选择的模型获取表字段
     * @param Request $request
     * @return \think\response\Json
     */
    public function getFields(Request $request)
    {
        $model = $request->post('model','');
        if(!$model) dieReturn('请选择模型文件');
        // 根据模型文件解析出是哪张表(大驼峰转下划线)
        $table = preg_replace_callback('/([A-Z]{1})/',function($matches){
            return '_'.strtolower($matches[0]);
        },$model);
        // 获取字段
        $table = rtrim(ltrim($table,'_'),'.php');
        $fields = Db::name($table)->getTableFields();
        $pk = Db::name($table)->getPk();
        if(is_array($pk)) {
            $k = '';
            foreach ($pk as $p) {
                $k .= $p . ',';
            }
            $pk = rtrim($k,',');
        }
        return api_response(compact('pk','fields'));
    }

    /**
     * 生成代码
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function makeCode(Request $request)
    {
        $input = $request->post();
        // 组装配置数据
        $conf = [
            // 模块名
            'module' => $input['module'] ? $input['module'] : dieReturn('模块名不能为空'),
            // 对应控制器
            'controller' => $input['controller'] ? ucfirst($input['controller']) : dieReturn('控制器不能为空'),
            // 对应模型
            'model' => $input['model'] ? ucfirst(rtrim($input['model'],'.php')) : dieReturn('请选择模型'),
            // 路由文件名
            'route_file' => $input['route_file'] ? ucfirst(rtrim($input['route_file'],'.php')) : dieReturn('请选择路由文件'),
            // 菜单ID
            'menu_id' => $input['menu_id'] ? $input['menu_id'] : dieReturn('请输入菜单ID'),
        ];
        // 组装列表页配置
        $list = [];
        if(empty($input['field_title']) || empty($input['field_name']) || empty($input['field_style']))
            dieReturn('列表页数据格式错误');
        foreach ($input['field_title'] as $k => $title) {
            $list[$title][] = $input['field_name'][$k] ? $input['field_name'][$k] : dieReturn('列表页数据格式错误');
            $list[$title][] = $input['field_style'][$k] ? $input['field_style'][$k] : dieReturn('列表页数据格式错误');
            // 添加主键标识
            if($input['model_pk'] == $input['field_name'][$k]) {
                $list[$title]['_pk_'] = true;
            }
        }

        // 组装编辑页配置
        $edit = [];
        if(empty($input['edit_title']) || empty($input['edit_name']) || empty($input['edit_style']))
            dieReturn('编辑页数据格式错误');
        foreach ($input['edit_title'] as $k => $title) {
            $edit[$title][] = $input['edit_name'][$k] ? $input['edit_name'][$k] : dieReturn('编辑页数据格式错误');
            $edit[$title][] = $input['edit_style'][$k] ? $input['edit_style'][$k] : dieReturn('编辑页数据格式错误');
            // 如果是有选项的，则添加默认值和待选项
            if(in_array($input['edit_style'][$k],['select','checkbox','radio'])) {
                if(!$input['edit_'.$input['edit_name'][$k].'_option']) dieReturn($edit[$title].'没有待选项');
                $op_arr = explode('|',$input['edit_'.$input['edit_name'][$k].'_option']);
                $options = [];
                $default = '';
                // 解析待选项
                foreach ($op_arr as $op_item) {
                    $temp = explode(':',$op_item);
                    if(empty($temp[0]) || empty($temp[1])) dieReturn($edit[$title].'待选项格式错误');
                    if($default === '') {
                        $default = $temp[1];
                    }
                    $options[$temp[0]] = $temp[1];
                }
                // 将待选项加入数组
                $edit[$title][] = false; // 兼容配置文件
                $edit[$title][] = $default; // 默认值
                $edit[$title][] = $options; // 选项列表
            }
            // 添加主键标识
            if($input['model_pk'] == $input['edit_name'][$k]) {
                if($edit[$title][2] !== false) {
                    $edit[$title][2] = false; // 兼容配置文件
                }
                $edit[$title]['_pk_'] = true;
            }
        }

        $conf['list']['fields'] = $list;
        if(empty($input['search_field'])) dieReturn('请指定列表页搜索字段');
        $conf['list']['key'] = implode('|',$input['search_field']);
        $conf['edit']['form'] = $edit;

        new FastAdmin($conf);
        $this->freshSession();
        return api_response('');
    }

    /**
     * 更新session中的权限和菜单信息
     * @throws \app\common\exception\CustomException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function freshSession()
    {
        $auth_menu = AdminAndRoleService::getAuthByRole(session('admin.role_id'));
        session('admin.menu_list',$auth_menu['menu_list']);
        session('admin.auth_list',$auth_menu['auth_list']);
    }
}
