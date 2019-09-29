<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/6/15
 * Time: 17:52
 */

// 显示能任务列表
function showList($arr){
    foreach ($arr as $k => $v) {
        fwrite(STDOUT,'     '.$k.'、'.$v['title'].PHP_EOL);  //标准输出
    }
}

// 退出
function exitTool(){
    fwrite(STDOUT,"那我先退下啦！".PHP_EOL);
    exit();
}

// 帮助，显示命令列表
function help($c_list){
    ksort($c_list);
    foreach ($c_list as $k => $v) {
        fwrite(STDOUT,'     '.$k.str_repeat(' ',20-strlen($k)).$v[1].PHP_EOL);  //标准输出
    }
}

// 显示操作系统
function showOs() {
    fwrite(STDOUT,getOs().PHP_EOL);
}

// 清理屏幕
if(!function_exists('clear'))
{
    function clear(){
        for ($i = 0; $i < 40; $i++) {
            fwrite(STDOUT,PHP_EOL);  //标准输出
        }
    }
}