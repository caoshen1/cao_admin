<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    // 指令名 =》完整的类名
    // 快速构建curd
    'fastadmin' 	=>	\fastAdmin\command\FastAdmin::class,
    // 生成服务层
    'make:service'	=>	\fastAdmin\command\ServiceCommand::class,
    // 生成权限列表
    'create:auth'	=>	\fastAdmin\command\CreateAuth::class,
];
