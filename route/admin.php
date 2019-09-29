<?php

use think\facade\Route;

// 渲染登录页面
Route::get('admin/login', '@admin/index/login')->name('admin/login');
// 获取验证码
Route::get('admin/captcha', '@admin/index/getCaptcha')->name('admin/captcha');
// 登录
Route::post('admin/login', '@admin/index/loginHandle')->name('admin/login');

// 后台路由
Route::group(['name' => 'admin', 'prefix' => 'admin/'], function () {

    // 首页
    Route::get('index', 'index/index')->name('admin/index');
    // 欢迎页面
    Route::get('welcome', 'index/welcome')->name('admin/welcome');
    // 文件异步上传
    Route::post('upload', 'index/uploadFile')->name('admin/upload');

    // 管理员相关
    Route::group('admin',function () {
        // 获取菜单列表
        Route::get('get_menu', 'admin.admin/getMenuList');
        // 获取管理员列表
        Route::get('list', 'admin.admin/index')->name('admin/admin/list');
        // 修改管理员状态
        Route::post('status', 'admin.admin/setStatus')->name('admin/admin/status');
        // 显示修改个人信息页面
        Route::get('info', 'admin.admin/setAdminInfo')->name('admin/admin/info');
        // 修改个人信息
        Route::post('info', 'admin.admin/saveAdminInfo')->name('admin/admin/info');
        // 显示修改密码页面
        Route::get('pwd', 'admin.admin/setPwd')->name('admin/admin/pwd');
        // 修改密码
        Route::post('pwd', 'admin.admin/savePwd')->name('admin/admin/pwd');
        // 退出登录
        Route::post('bye', 'admin.admin/byeBye')->name('admin/admin/bye');
        // 删除管理员
        Route::post('del', 'admin.admin/delete')->name('admin/admin/del');
        // 添加管理员页面
        Route::get('add', 'admin.admin/showAdd')->name('admin/admin/add');
        // 编辑管理员页面
        Route::get('edit/:id', 'admin.admin/showEdit')->name('admin/admin/edit');
        // 保存管理员信息
        Route::post('save', 'admin.admin/save')->name('admin/admin/save');
    });

    // 角色相关
    Route::group('role',function () {
        // 获取角色列表
        Route::get('list', 'admin.adminRole/index')->name('admin/role/list');
        // 修改角色状态
        Route::post('status', 'admin.adminRole/setStatus')->name('admin/role/status');
        // 删除角色
        Route::post('del', 'admin.adminRole/delete')->name('admin/role/del');
        // 添加角色页面
        Route::get('add', 'admin.adminRole/showAdd')->name('admin/role/add');
        // 编辑角色页面
        Route::get('edit/:id', 'admin.adminRole/showEdit')->name('admin/role/edit');
        // 保存角色信息
        Route::post('save', 'admin.adminRole/save')->name('admin/role/save');
        // 刷新系统权限
        Route::post('refresh', 'admin.adminRole/flashAuthList')->name('admin/role/refresh');
    });

    // 系统配置 路由
    Route::group('config',function () {
        // 系统配置列表
        Route::get('index', 'config/index')->name('config/index');
        // 编辑系统配置
        Route::post('save', 'config/update')->name('config/save');
    });

    // 自动后台路由
    Route::group('cao_admin',function () {
        // 显示配置页
        Route::get('page', 'caoadmin.caoAdmin/page')->name('caoAdmin/index');
        // 编辑系统配置
        Route::post('save', 'caoadmin.caoAdmin/update')->name('caoAdmin/save');
        // 获取模型字段
        Route::post('get_fields', 'caoadmin.caoAdmin/getFields')->name('caoAdmin/get_fields');
        // 生成代码
        Route::post('go', 'caoadmin.caoAdmin/makeCode')->name('caoAdmin/go');
    });

    //----next_input_hear

});