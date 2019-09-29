<?php

namespace app\common\model;

use think\Model;

class Statistics extends Model
{
    protected $pk = ['who','when','what','type'];
    protected $json = ['extend'];
    protected $jsonAssoc = true;
}
