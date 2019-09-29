<?php

namespace app\admin\validate;

use app\common\validate\BaseValiFun;
use think\Validate;

class AdminValidate extends Validate
{
    use BaseValiFun;
    /**
     * 定义验证规则
     */	
	protected $rule = [
        'id|管理员ID'=>'number',
        'login_name|登录名'=>'require',
        'role_id|角色'=>'require|array',
        'pwd|密码'=>'length:6,25',
    ];
}