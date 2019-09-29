<?php

namespace app\common\service;

use app\common\exception\CustomException;
use think\Session;

/**
 * 权限管理层
 * Class AuthorityService
 * @package app\common\service
 */
class AuthorityService extends BaseService
{
    /**
     * @var array 权限控制器和名称对应数组
     */
    public static $path_title = [
        'admin' => '系统用户相关',
        'ConfigController' =>'系统配置相关'
    ];

    /**
     * @var string 权限检测标识
     */
    protected static $annotation_tag = 'authCheck';

    /**
     * @var string 菜单标识
     */
    protected static $menu_tag = 'menu_id';

    /**
     * @var array 权限ID和菜单ID对应map
     */
    protected static $auth2menu = [];
    
    /**
     * 扫描所有模块控制器并且将权限列表缓存
     * @throws CustomException
     */
    public static function scanController()
    {
        // 获取app路径
        $app_path = rtrim(ResourcesService::getPath('app_path'),'/');
        $controller_list = self::fileList($app_path,$app_path);

        $controller_list = self::controllerClassList($controller_list);
        if(empty($controller_list)) throw new CustomException('没有任何控制器');
        $action_list = [];
        // 获取控制内的方法列表
        foreach ($controller_list as $controller) {
            $res = self::getControllerPubFunctions($controller);
            empty($res) ?: $action_list[$controller] = $res;
        }
        // 缓存权限
        cache(config('cache.app_cache_prefix.auth_list'),self::makeArray($action_list));
        // 缓存菜单
        cache(config('cache.app_cache_prefix.auth_menu'),self::$auth2menu);
    }

    /**
     * 权限列表缓存数组
     * @param $array
     * @return array
     */
    private static function makeArray($array)
    {
        $res = [];
        $temp = [];
        foreach ($array as $k => $v) {
            $class_name_arr = array_slice(explode('\\',$k),3);
            $count = count($class_name_arr) - 1;
            for ($i = $count; $i >= 0; $i--) {
                $res[$class_name_arr[$i]][] = empty($temp) ? $v : $temp;
                if($i == 0){
                    $temp = [];
                }else{
                    unset($res[$class_name_arr[$i]]);
                }
            }
        }
        return $res;
    }

    /**
     * 遍历文件夹获取文件
     * @param string $path 当前路径
     * @return array 所有带命名空间的控制器类数组
     */
    private static function fileList(string $path)
    {
        $controller_list = [];
        $file = [];
        $temp=scandir($path);
        //遍历文件夹
        foreach($temp as $v){
            $a=$path.'/'.$v;
            if(is_dir($a)){//如果是文件夹则执行
                if($v == '..' || $v == '.') continue;
                $controller_list[$v] = self::fileList($a);
            }else{
                $file[] = $v;
            }
        }
        if(!empty($file)) {
            foreach ($file as $f){
                $controller_list[] = $f;
            }
        }
        return $controller_list;
    }

    /**
     * 将控制器类遍历出来
     * @param array $file_list
     * @param int $l
     * @param bool $in_controller
     * @param string $prefix
     * @return array
     */
    private static function controllerClassList(array $file_list, string $prefix = 'app\\')
    {
        static $controller_list = [];
        //遍历刚才得到的目录树
        foreach($file_list as $key => $val) {
            //如果是个数组，也就代表它是个目录，那么就在它的子文件中加入-|来表示是下一级吧
            if(is_array($file_list[$key])) {
                self::controllerClassList($file_list[$key],$prefix . $key . '\\');
            }else {
                if(substr($val,-14) == 'Controller.php') {
                    $controller_list[] = $prefix . substr($val,0,-4);
                }
            }
        }
        return $controller_list;
    }
    
    // 获取控制器类的所有public方法
    public static function getControllerPubFunctions(string $class_name)
    {
        $class = new \ReflectionClass($class_name);

        $methods = $class->getMethods();
        // 如果类里面没有方法，则返回空数组
        if(empty($methods)) return [];
        $pub_methods = []; // 保存public方法
        foreach ($methods as $method) {
            if($method->isPublic()) {
                // 获取注释文档
                /**
                 * 权限名称
                 * @check true|false 是否校验权限
                 */

                $doc = $method->getDocComment();
                preg_match_all('/@\w+\s+[\w\s]+\\n/',$doc,$check);
                $doc_arr = [];
                if(!empty($check[0])) {
                    foreach ($check[0] as $str) {
                        $temp = explode(' ',trim($str,"@\n"));
                        $doc_arr[$temp[0]] = trim($temp[1]);
                    }
                }
                // 如果check为true，则需要输出权限
                if(!empty($doc_arr[self::$annotation_tag]) && $doc_arr[self::$annotation_tag] == 'true') {
                    // 正则出权限名
                    $no_sp_str = str_replace(' ', '', $doc);
                    preg_match('/\\n\*[{4e00}-\x{9fa5}A-Za-z0-9_]+/u',$no_sp_str,$titles);
                    if(empty($titles)) {
                        throw new CustomException($class_name . '/' . $method->name . '方法没有权限名');
                    }
                    $title = trim($titles[0],"\n*");

                    // 获取类的注释
                    $class_doc = $class->getDocComment();
                    // 获取类的标题
                    $no_sp_str = str_replace(' ', '', $class_doc);
                    preg_match('/\\n\*[{4e00}-\x{9fa5}A-Za-z0-9_]+/u',$no_sp_str,$titles);
                    if(empty($titles)) {
                        throw new CustomException($class_name . '类没有类标题');
                    }
                    $class_title = trim($titles[0],"\n*");

                    // 获取绑定的菜单ID
                    if(empty($doc_arr[self::$menu_tag])) {
                        throw new CustomException($class_name . '/' . $method->name . '方法没有绑定菜单');
                    }
                    $hash = hash2int(md5($class_name . '\\' . $method->name));
                    $pub_methods[] = [
                        'id' => $hash,
                        'name' => $title,
                        'menu_id' => $doc_arr[self::$menu_tag]
                    ];
                    // 放入菜单对应关系MAP
                    self::$auth2menu[$hash] = $doc_arr[self::$menu_tag];
                }
            }
        }
        if(!empty($pub_methods)) {
            return [
                'id' => hash2int($class_name),
                'name' => $class_title,
                'item' => $pub_methods,
            ];
        }
        return [];
    }

    /**
     * 校验是否有访问权限
     * @throws CustomException
     * @throws \ReflectionException
     */
    public static function checkAuth()
    {
        // 获取当前管理员权限表
        $auth_list = session('admin.auth_list');
        // 超级管理员不校验权限
        if(in_array(1001,session('admin.role_id'))) {
            return true;
        }
        // 获取当前路由的调度信息
        $dispatch = request()->dispatch()->getDispatch();
        $controller_suffix = config('app.controller_suffix');
        // 组装当前类名
        $class = 'app\\'
            .$dispatch[0]
            .'\controller\\'
            .ucfirst($dispatch[1])
            .($controller_suffix ? $controller_suffix : '');
        // 获取反射类
        $cur = new \ReflectionClass($class);
        // 获取当前方法
        $doc = $cur->getMethod($dispatch[2])->getDocComment();
        // 解析注解中的校验信息
        preg_match_all('/@\w+\s+[\w\s]+\\n/',$doc,$check);
        $doc_arr = [];
        if(!empty($check[0])) {
            foreach ($check[0] as $str) {
                $temp = explode(' ',trim($str,"@\n"));
                $doc_arr[$temp[0]] = $temp[1];
            }
        }
        // 校验权限
        if(!empty($doc_arr) && !empty($doc_arr[self::$annotation_tag]) && $doc_arr[self::$annotation_tag] == 'true') {
            // 哈希路由
            $hash = hash2int(md5(rtrim($class . $dispatch[2])));
            if(!in_array($hash,$auth_list)) {
                throw new CustomException('权限非法');
            }
        }
    }

    /**
     * 获取权限列表
     * @return mixed
     * @throws CustomException
     */
    public static function getAuthList()
    {
        $list = cache(config('cache.app_cache_prefix.auth_list'));
        if(empty($list)) {
            self::scanController();
            $list = cache(config('cache.app_cache_prefix.auth_list'));
        }
        return $list;
    }

    /**
     * 获取权限菜单对应表
     * @return mixed
     * @throws CustomException
     */
    public static function getMenuList()
    {
        $list = cache(config('cache.app_cache_prefix.auth_menu'));
        if(empty($list)) {
            self::scanController();
            $list = cache(config('cache.app_cache_prefix.auth_menu'));
        }
        return $list;
    }
}
