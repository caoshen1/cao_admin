<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/6/18
 * Time: 10:10
 */

namespace fun;


class GlobalParam
{
    private static $key = 'GP';

    // 设置
    public static function set($name, $value)
    {
        $GLOBALS[self::$key][$name] = $value;
    }

    // 获取
    public static function get($name)
    {
        return empty($GLOBALS[self::$key][$name]) ? null : $GLOBALS[self::$key][$name];
    }

    // 清除
    public static function clear()
    {
        $GLOBALS[self::$key] = null;
    }
}