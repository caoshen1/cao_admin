<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/9/30
 * Time: 10:28
 */

namespace resources;

/**
 * 资源工厂类
 * Class ResourceFactory
 * @package resources
 */
class ResourceFactory
{
    /**
     * 获取一个资源操作类
     * @param string $name
     * @return ResourceInterface
     */
    public static function getResourceObj(string $name) : ResourceInterface
    {
        return new $name();
    }
}