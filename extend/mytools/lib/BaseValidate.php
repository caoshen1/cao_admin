<?php
/**
 * Created by PhpStorm.
 * User: 曹珅
 * Date: 2019/6/15
 * Time: 21:54
 */

namespace mytools\lib;


class BaseValidate
{
    public static function check($class, $param, $scene = false)
    {
        $vali = new $class();
        if($scene) {
            $re = $vali->scene($scene)->check($param);
        }else{
            $re = $vali->check($param);
        }
        if(!$re) {
            dieReturn($vali->getError());
        }
    }
}