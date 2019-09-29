<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/9/29
 * Time: 8:49
 */

use think\facade\Route;

Route::group(['name' => 'setup', 'prefix' =>'@setup/'],function () {
    // 主页
    Route::get('index','setup/index')->name('setup/index');
    // 安装
    Route::post('run','setup/run')->name('setup/run');
});