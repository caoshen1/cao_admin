<?php
/**
 * Created by PhpStorm.
 * User: 曹珅
 * Date: 2019/6/15
 * Time: 21:24
 */

return [
    // 显示任务列表
    'ls' => [function() use ($arr){
        showList($arr);
    },'显示任务列表'],
    // 退出
    'exit' => [function(){
        exitTool();
    },'退出'],
    // 获取操作系统
    'os' => [function(){
        showOs();
    },'获取当前操作系统'],
    // 清理屏幕
    'clear' => [function(){
        clear();
    },'清理屏幕'],
];