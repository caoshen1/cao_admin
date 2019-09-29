<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/8/3
 * Time: 11:02
 */

namespace fastAdmin\command;


use app\common\exception\CustomException;

class FastAdmin
{
    /**
     *  {%controller%}   转小写控制器名
     *  {%module%}       模块名
     *  {%model%}        模型名
     *  {%pk%}           主键名
     *  {%title%}        字段标题
     *  {%name%}         字段对应数据库字段名
     *  {%opstr%}        选项字符串
     *  {%%}             整段替换
     *  {%edit_param%}   控制器接收参数列表
     *  {%key%}          控制器搜索字符串
     *  {%pk_name%}      编辑视图主键标题
     *  {%menu%}         对应菜单
     *  {%namespace%}    控制器命名空间
     *  {%base_controller%} baseController引入
     *  {%route%}        路由规则
     */
    // 列表页类型对应html {__}表示替换内容
    private $list_arr = [
        'text' => '<td>{%%}</td>',
        'switch' => <<<EOF
                <td>
                    <div class="switch">
                        <div class="onoffswitch">
                            <input type="checkbox"  class="onoffswitch-checkbox" data-url="{:url('{%controller%}/status')}" data-id="{\$v.{%pk%}}" id="sw-{\$v.{%pk%}}" {if \$v.status == 1}checked{/if}>
                            <label class="onoffswitch-label" for="sw-{\$v.{%pk%}}">
                                <span class="onoffswitch-inner"></span>
                                <span class="onoffswitch-switch"></span>
                            </label>
                        </div>
                    </div>
                </td>
EOF
        ,
        'image' => '<td><img src="{%%}" style="width: 50px; height: 50px;"></td>'
    ];

    // 编辑页类型对应html
    private $edit_arr = [
        'text' => <<<EOF
               <div class="form-group">
                  <label class="col-sm-2 control-label">{%title%}</label>
                  <div class="col-sm-10">
                    <input type="text" class="form-control" value="{:empty(\$data) ? '__default__' : \$data.{%name%}}" name="{%name%}">
                  </div>
               </div>
EOF
        ,
        'area' => <<<EOF
                <div class="form-group">
                    <label class="col-sm-2 control-label">{%title%}</label>
                    <div class="col-sm-10">
                        <textarea class="form-control form-textarea"  name="{%name%}">{:empty(\$data) ? '__default__' : \$data.{%name%}}</textarea>
                    </div>
                </div>
EOF
        ,
        'file' => <<<EOF
            <div class="form-group">
                <label class="col-sm-2 control-label">{%title%}</label>
                <div class="col-sm-10 file-area">
                    <div class="file-pretty">
                        <input type="file" class="form-control" style="display: none;" up-url="{:url('admin/upload')}">
                        <div class="input-append input-group">
                                <span class="input-group-btn">
                                    <button class="btn btn-white select" type="button">选择文件</button>
                                </span>
                            <input class="input-large form-control" type="text" name="{%name%}"  value="{:empty(\$data) ? '' : \$data.{%name%}}" readonly>
                            <img class="beforeView" src="{:empty(\$data) ? '' : \$data.{%name%}}" style="display: none;">
                        </div>
                    </div>
                    <div class="back-change btn btn-primary">查看图片</div>
                </div>
            </div>
EOF
        ,
        'select' => <<<EOF
            <div class="form-group">
                <label class="col-sm-2 control-label">{%title%}</label>
                <div class="col-sm-10">
                    <select class="form-control inline model-select" name="{%name%}">
                        {%opstr%}
                    </select>
                </div>
            </div>
EOF
        ,
        'radio' => <<<EOF
            <div class="form-group">
                <label class="col-sm-1 control-label">{%title%}</label>
                <div class="col-sm-11">
                    {%opstr%}
                </div>
            </div>
EOF
        ,
        'switch' => <<<EOF
            <div class="form-group">
                 <label class="col-sm-2 control-label">{%title%}</label>
                 <div class="col-sm-10">
                     <div class="switch" style="margin-top: 7px;">
                         <div class="onoffswitch">
                            <input type="checkbox"  class="onoffswitch-checkbox" id="sys-conf-{\$data.{%pk%}}" value="1" name="{%name%}" {if \$data.{%name%} == 1}checked{/if}>
                            <label class="onoffswitch-label" for="sys-conf-{\$data.{%pk%}}">
                                <span class="onoffswitch-inner"></span>
                                <span class="onoffswitch-switch"></span>
                            </label>
                         </div>
                     </div>   
                 </div>    
            </div>
EOF
        ,
        'checkbox' => <<<EOF
            <div class="form-group">
                <label class="col-sm-1 control-label">{%title%}</label>
                <div class="col-sm-11">
                    {%opstr%}
                </div>
            </div>
EOF

    ];

    private $conf = [];

    /**
     * FastAdmin constructor.
     * @param $conf
     * @throws CustomException
     */
    public function __construct($conf)
    {
        $this->conf = $conf;
        // 生成控制器
        $this->makeController();
        // 生成视图
        $this->makeView();
        // 生成路由
        $this->makeRoute();
    }


    // 生成控制器
    private function makeController()
    {
        // 构建edit_param字符串
        $edit = $this->conf['edit'];
        $edit_param = '';
        foreach ($edit['form'] as $k => $v) {
            $edit_param .= '\'' . $v[0] . '\',' . "\n";
        }
        // 组装命名空间
        $controller_name = $this->conf['controller'];
        $path = '';
        $controller_arr = explode('/',$this->conf['controller']);
        if(count($controller_arr) > 2) { // 需要建子文件夹和修改命名空间
            throw new CustomException('控制器只支持一级目录');
        }
        if(count($controller_arr) > 1) { // 需要建子文件夹和修改命名空间
            $controller_name = $controller_arr[1];
            $path = strtolower($controller_arr[0]);
        }
        $this->conf['controller'] = $controller_name;
        $this->conf['path'] = $path;
        // 读取模板
        $tpl = file_get_contents(__DIR__ . '/../tpl/controller.fast');
        // 替换模板字符串
        $tpl = str_replace('{%controller%}', $controller_name, $tpl);
        $tpl = str_replace('{%min_controller%}', strtolower($controller_name), $tpl);
        $tpl = str_replace('{%model%}', $this->conf['model'], $tpl);
        $tpl = str_replace('{%module%}', $this->conf['module'], $tpl);
        $tpl = str_replace('{%edit_param%}', $edit_param, $tpl);
        $tpl = str_replace('{%key%}',$this->conf['list']['key'],$tpl);
        $tpl = str_replace('{%menu%}',$this->conf['menu_id'],$tpl);
        $tpl = str_replace('{%namespace%}',$path ? '\\'.$path : '',$tpl);
        $tpl = str_replace('{%base_controller%}',$path ? 'use app\admin\controller\BaseController;' : '',$tpl);
        // 检查文件夹
        $path = $this->getAppPath() . 'controller/' . $path;
        if(!is_dir($path)) {
            mkdir($path,0777,true);
        }
        // 写入控制器文件
        if (!file_put_contents($path .'/'. $controller_name . 'Controller.php', $tpl)) {
            throw new CustomException('生成控制器' . $controller_name . '失败！');
        }

    }

    // 生成试图
    private function makeView()
    {
        // 建立试图文件夹
        $list = $this->conf['list'];
        $table_head = '';
        $table_body = '';
        $pk = ''; // 主键
        $pk_name = '';
        // 构造两个字符串
        foreach($list['fields'] as $key => $v) {
            if(isset($v['_pk_']) && $v['_pk_'] === true) { // 不渲染主键
                $pk = $v[0];
            }
            $table_head .= '<th>' . $key . '</th>' . "\n";
            $table_body .= str_replace('{%%}', '{$v.' . $v[0] . '}', $this->list_arr[$v[1]]) . "\n";
        }
        if(empty($pk)) {
            throw new CustomException('配置文件没有指定主键');
        }
        // 创建文件夹
        if (!is_dir($this->getAppPath() . 'view/' . strtolower($this->conf['controller']))) {
            mkdir($this->getAppPath() . 'view/' . strtolower($this->conf['controller']), 0777,true);
        }
        // 写入两个文件
        $index = file_get_contents(__DIR__ . '/../tpl/index.view.fast');
        $index = str_replace('{%table_head%}',$table_head,$index);
        $index = str_replace('{%table_body%}',$table_body,$index);
        $index = str_replace('{%controller%}',strtolower($this->conf['controller']),$index);
        $index = str_replace('{%module%}',$this->conf['module'],$index);
        $index = str_replace('{%pk%}',$pk,$index);
        if(!file_put_contents($this->getAppPath() . 'view/'.strtolower($this->conf['controller']) . '/index.html',$index)){
            throw new CustomException('写入'.$this->conf['controller'].'主页视图失败！');
        }

        // {%form_body%} 新增页面表单内容
        $form = $this->conf['edit'];
        $form_body = '';
        $pk = '';
        foreach ($form['form'] as $k => $v) {
            if (isset($v['_pk_']) && $v['_pk_'] === true) { // 不渲染主键
                $pk = $v[0];
                $pk_name = $k;
                continue;
            }

            // 如果是select或者radio则渲染选项
            if(in_array($v[1],['select','radio','checkbox'])) {
                $op_str = '';
                switch ($v[1]) {
                    case 'select':
                        foreach ($v[4] as $op_title => $op_value) {
                            if(!empty($v[3]) && $op_value == $v[3]) {
                                $op_str .= '<option value="'.$op_value.'" selected >'.$op_title.'</option>' . "\n";
                            }else{
                                $op_str .= '<option value="'.$op_value.'">'.$op_title.'</option>' . "\n";
                            }
                        }
                        break;
                    case 'radio':
                        foreach ($v[4] as $op_title => $op_value) {
                            if(!empty($v[3]) && $op_value == $v[3]) {
                                $op_str .= '<label class="checkbox-inline">
                                                <input type="radio" class="i-checks" value="'.$op_value.'" name="{%name%}" checked>
                                                '.$op_title.'
                                            </label>'. "\n";
                            }else{
                                $op_str .= '<label class="checkbox-inline">
                                                <input type="radio" class="i-checks" value="'.$op_value.'" name="{%name%}">
                                                '.$op_title.'
                                            </label>'. "\n";
                            }
                        }
                        break;
                    case 'checkbox':
                        foreach ($v[4] as $op_title => $op_value) {
                            if(!empty($v[3]) && $op_value == $v[3]) {
                                $op_str .= '<label class="checkbox-inline">
                                                <input type="checkbox" class="i-checks" value="'.$op_value.'" name="{%name%}[]" checked>
                                                '.$op_title.'
                                            </label>'. "\n";
                            }else{
                                $op_str .= '<label class="checkbox-inline">
                                                <input type="checkbox" class="i-checks" value="'.$op_value.'" name="{%name%}[]">
                                                '.$op_title.'
                                            </label>'. "\n";
                            }
                        }
                        break;
                    default:

                        break;
                }

            }
            if(isset($op_str)) {
                $str = str_replace('{%opstr%}', $op_str, $this->edit_arr[$v[1]]);
                $str = str_replace('{%title%}', $k, $str);
            }else{
                $str = str_replace('{%title%}', $k, $this->edit_arr[$v[1]]);
            }
            // 默认值
            if(isset($v[3]) && !empty($v[3])) {
                $str = str_replace('__default__', $v[3], $str);
            }else{
                $str = str_replace('__default__', '', $str);
            }
            $str = str_replace('{%name%}', $v[0], $str);
            $form_body .= $str . "\n";
        }

        $show_add = file_get_contents(__DIR__ . '/../tpl/showAdd.view.fast');
        $show_add = str_replace('{%form_body%}',$form_body,$show_add);
        $show_add = str_replace('{%pk%}', $pk, $show_add);
        $show_add = str_replace('{%pk_name%}', $pk_name, $show_add);
        $show_add = str_replace('{%controller%}',strtolower($this->conf['controller']),$show_add);
        $show_add = str_replace('{%module%}',$this->conf['module'],$show_add);
        if (!file_put_contents($this->getAppPath() . 'view/' . strtolower($this->conf['controller']) . '/info.html', $show_add)) {
            throw new CustomException('写入'.$this->conf['controller'].'编辑视图失败！');
        }
    }

    // 生成路由
    private function makeRoute()
    {
        $route_str = <<<EOF
// {%module%} 路由
    Route::group('{%controller%}',function () {
        // {%module%}列表
        Route::get('index', '{%route%}/index')->name('{%controller%}/list');
        // 展示新增页面
        Route::get('add', '{%route%}/showAdd')->name('{%controller%}/add');
        // 保存 {%module%}
        Route::post('save', '{%route%}/save')->name('{%controller%}/save');
        // 展示编辑
        Route::get('edit/:id', '{%route%}/showEdit')->name('{%controller%}/edit')->pattern(['id' => '\d+']);
        // 修改{%module%}状态
        Route::post('status', '{%route%}/setStatus')->name('{%controller%}/status');
        // 删除{%module%}
        Route::post('del', '{%route%}/delete')->name('{%controller%}/del');
    });

    //----next_input_hear
EOF;
        // 替换路由字符串中的标记
        $controller = $this->conf['path'] ? $this->conf['path'] . '.' . strtolower($this->conf['controller']) : strtolower($this->conf['controller']);
        $route_str = str_replace('{%route%}',  $controller, $route_str);
        $route_str = str_replace('{%controller%}',  strtolower($this->conf['controller']), $route_str);
        $route_str = str_replace('{%module%}',strtolower($this->conf['module']),$route_str);
        // 读取当前路由文件，将字符串替换到标记处
        // |--先复制一份当前路由文件，以防失败
        $route_path = str_replace('\\', '/', env('route_path'));
        copy($route_path . $this->conf['route_file'] . '.php', $route_path . $this->conf['route_file'] . '_cp.php');
        $route_file_str = file_get_contents($route_path . $this->conf['route_file'] . '.php');
        $route_file_str = str_replace('//----next_input_hear', $route_str, $route_file_str);
        // 再写入路由文件
        if (!file_put_contents($route_path . $this->conf['route_file'] . '.php', $route_file_str)) {
            throw new CustomException('写入' . $this->conf['controller'] . '路由失败！');
        }
        // 成功后删除备份文件
        unlink($route_path . $this->conf['route_file'] . '_cp.php');
    }

    // 获取app文件夹路径
    private function getAppPath()
    {
        return str_replace('\\', '/', env('app_path') . 'admin/');
    }
}