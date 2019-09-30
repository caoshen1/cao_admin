<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/9/30
 * Time: 10:22
 */

namespace resources;

/**
 * 资源上层接口
 * Interface ResourceInterface
 * @package resources
 */
interface ResourceInterface
{
    /**
     * base64资源上传
     * @param $file
     * @return mixed
     */
    public static function uploadBase64(string $file);

    /**
     * 二进制资源上传
     * @param $file
     * @return mixed
     */
    public static function uploadBin(string $file);

    /**
     * 资源获取
     * @param $name
     * @return mixed
     */
    public static function get($name);

    /**
     * 资源删除
     * @param $name
     * @return mixed
     */
    public static function del($name);
}