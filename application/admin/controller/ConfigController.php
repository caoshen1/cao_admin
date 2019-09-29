<?php

namespace app\admin\controller;

use app\common\model\Config;
use app\common\service\BaseService;
use app\common\service\ConfigService;
use think\Request;

/**
 * 系统配置
 * Class ConfigController
 * @package app\admin\controller
 */
class ConfigController extends BaseController
{
    /**
     * 查看配置表
     * @authCheck true
     * @menu_id 4
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $data = Config::select();
        $data = $data->isEmpty() ? [] : $data;
        $this->title('系统配置表');
        return view('admin@config/index',compact('data'));
    }


    /**
     * 修改配置数据
     * @authCheck true
     * @menu_id 4
     * @param Request $request
     * @return \think\response\Json
     * @throws \app\common\exception\CustomException
     */
    public function update(Request $request)
    {
        $input = BaseService::requestParams([
            'key',
            'value' => '',
            'id' // 兼容switch
        ]);
        if(empty($input)) dieReturn('参数错误');
        if(!empty($input['id'])) $input['key'] = $input['id'];
        return api_response(ConfigService::ConfigSave($input['key'],$input['value']));
    }
}
