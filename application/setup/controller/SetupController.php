<?php

namespace app\setup\controller;

use app\common\service\BaseService;
use think\Controller;
use think\Db;

class SetupController extends Controller
{
    protected $beforeActionList = [
        'checkDatabase'
    ];

    // 渲染安装页面
    public function index()
    {
        return view('setup@setup/index');
    }

    // 开始安装
    public function run()
    {
        $input = BaseService::requestParams([
            'login_name' => ['','require|chsDash','后台管理员用户名只能是汉字、字母、数字和下划线_及破折号-'],
            'pwd'        => ['','require|length:6,20','登录密码必须为6-20位'],
            'repwd'      => ['','require|length:6,20','登录密码必须为6-20位']
        ]);
        if($input['pwd'] != $input['repwd']) dieReturn('两次密码输入不一致');
        $input['pwd'] = password($input['pwd']);
        $prefix = config('database.prefix');
        // 新建表
        // |--admin表
        $create_admin_sql = <<<EOF
            CREATE TABLE `{$prefix}admin` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `role_id` varchar(255) NOT NULL DEFAULT '' COMMENT '角色json',
              `login_name` varchar(30) NOT NULL DEFAULT '',
              `pwd` varchar(32) NOT NULL DEFAULT '',
              `mobile` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
              `image` varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
              `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
              `create_time` bigint(20) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='后台人员表';
EOF;
        Db::query($create_admin_sql);
        // |--admin_role表
        $create_role_sql = <<<EOF
            CREATE TABLE `{$prefix}admin_role` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `name` varchar(20) NOT NULL DEFAULT '' COMMENT '角色名',
              `auth_list` text NOT NULL COMMENT '权限json',
              `menu_list` text NOT NULL COMMENT '菜单json',
              `create_time` bigint(20) unsigned NOT NULL,
              `update_time` bigint(20) unsigned NOT NULL,
              `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8 COMMENT='后台人员角色表';
EOF;
        Db::query($create_role_sql);
        // |--admin_menu_list表
        $create_menu_sql = <<<EOF
        CREATE TABLE `{$prefix}admin_menu_list` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `pid` int(10) unsigned NOT NULL,
          `title` varchar(20) NOT NULL DEFAULT '' COMMENT '菜单标题',
          `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '字体图标',
          `jump` varchar(50) NOT NULL DEFAULT '' COMMENT '路由',
          `sort` int(10) unsigned NOT NULL DEFAULT '99' COMMENT '排序 小靠前',
          `create_time` bigint(20) unsigned NOT NULL,
          `update_time` bigint(20) unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='后台菜单表';
EOF;
        Db::query($create_menu_sql);
        // |--config表
        $create_conf_sql = <<<EOF
        CREATE TABLE `{$prefix}config` (
          `key` varchar(50) NOT NULL COMMENT '唯一标记名',
          `title` varchar(20) NOT NULL DEFAULT '',
          `value` text COMMENT '值',
          `describe` char(255) NOT NULL DEFAULT '' COMMENT '描述',
          `type` char(30) NOT NULL DEFAULT '' COMMENT 'string,image,json',
          `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
          `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
          PRIMARY KEY (`key`) USING BTREE
        ) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='基本配置参数';
EOF;
        Db::query($create_conf_sql);
        // |--统计表
        $create_statistics_sql = <<<EOF
        CREATE TABLE `my_statistics` (
          `who` bigint(20) unsigned NOT NULL COMMENT '统计谁',
          `when` bigint(18) unsigned NOT NULL COMMENT '统计什么时间',
          `what` int(10) unsigned NOT NULL COMMENT '统计什么',
          `value` bigint(20) NOT NULL COMMENT '统计值',
          `extend` text NOT NULL COMMENT '附加字段',
          `type` int(10) unsigned NOT NULL COMMENT '附加整型字段',
          PRIMARY KEY (`who`,`when`,`what`,`type`),
          KEY `UTKV` (`who`,`when`,`what`,`value`,`type`) USING BTREE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据统计表';
EOF;
        Db::query($create_statistics_sql);
        // 插入管理员数据
        $time = time();
        // |--admin数据
        $insert_admin_sql = "INSERT INTO 
                              `{$prefix}admin`
                                VALUES ('1', '[1001]', '{$input['login_name']}', '{$input['pwd']}', '', '', '1', '{$time}')";
        Db::query($insert_admin_sql);
        // |--admin_role数据
        $role_data = [
            'id' => 1001,
            'name' => '超级管理员',
            'auth_list' => '[]',
            'menu_list' => '[]',
            'create_time' => $time,
            'update_time' => $time,
            'status' => 1
        ];
        Db::name('admin_role')->insert($role_data);
        // |--admin_menu_list数据
        $menu_data = [
            ['id' => 1, 'pid' => 0, 'title' => '系统管理', 'icon' => 'glyphicon glyphicon-cog', 'jump' => '', 'sort' => 98, 'create_time' => $time, 'update_time' => $time],
            ['id' => 2, 'pid' => 5, 'title' => '系统用户', 'icon' => '', 'jump' => 'admin/admin/list', 'sort' => 99, 'create_time' => $time, 'update_time' => $time],
            ['id' => 3, 'pid' => 5, 'title' => '系统角色', 'icon' => '', 'jump' => 'admin/role/list', 'sort' => 99, 'create_time' => $time, 'update_time' => $time],
            ['id' => 4, 'pid' => 1, 'title' => '系统配置', 'icon' => '', 'jump' => 'config/index', 'sort' => 99, 'create_time' => $time, 'update_time' => $time],
            ['id' => 5, 'pid' => 0, 'title' => '后台用户管理', 'icon' => 'glyphicon glyphicon-user', 'jump' => '', 'sort' => 97, 'create_time' => $time, 'update_time' => $time],
        ];
        Db::name('admin_menu_list')->insertAll($menu_data);
        return api_response('');
    }

    // 检查数据库表是否存在
    public function checkDatabase()
    {
        $table_name = config('database.prefix') . 'admin';
        if(!empty(Db::query("SHOW TABLES LIKE '{$table_name}'"))) {
            view('admin@index/login')->send();
            exit;
        }
    }
}
