<?php

namespace app\common\model;

use app\common\service\ResourcesService;
use think\Model;

class Config extends Model
{
    protected $pk = 'key';

    // 值获取器
    public function getValueAttr($value, $data)
    {
        switch ($data['type']) {
            case 'image':
                return ResourcesService::staticResource($value);
            default:
                return $value;
        }
    }

    // 值修改器
    public function setValueAttr($value, $data)
    {
        switch ($data['type']) {
            case 'image':
                return ResourcesService::net2Path($value);
            default:
                return $value;
        }
    }
}
