<?php

namespace app\common\model;

use think\Model;

class AdminRole extends Model
{
    protected $json = ['auth_list','menu_list'];
    protected $jsonAssoc = true;
}
