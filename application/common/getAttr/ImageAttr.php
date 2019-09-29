<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/8/23
 * Time: 17:35
 */

namespace app\common\getAttr;


use app\common\service\ResourcesService;

trait ImageAttr
{
    public function getImageAttr($v)
    {
        return ResourcesService::staticResource($v);
    }
}