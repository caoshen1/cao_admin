<?php

namespace app\common\model;

use app\common\getAttr\ImageAttr;
use think\Model;

class Admin extends Model
{
    use ImageAttr;

    protected $json = ['role_id'];
    protected $jsonAssoc = true;

}
