<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/9/30
 * Time: 10:36
 */

namespace resources\driver;

use resources\exception\ResourceException;
use resources\ResourceInterface;

/**
 * 本地文件驱动
 * Class LocalFile
 * @package resources\driver
 */
class LocalFile implements ResourceInterface
{

    /**
     * base64资源上传
     * @param string $file   base64字符串
     * @return mixed|string  相对路径
     * @throws ResourceException
     */
    public static function uploadBase64(string $file)
    {
        // TODO: Implement uploadBase64() method.
        if(preg_match('/^http[s]:\/\/w+$/', $file)){
            return $file;
        }
        // 检查图片类型
        if(!$res = self::isBase64($file)){
            throw new ResourceException('请上传正确的图片');
        }
        $type = $res[2];
        if ($type == "jpeg" || $type == "jepg") {
            $type = "jpg";
        }
        if(!in_array($type,['pjpeg','jpeg','jpg','gif','bmp','png'])) {
            throw new ResourceException('图片格式错误');
        }

        if(empty($savePath)){
            $savePath = config('conf.static_path') . '/image/'.date('Ymd',time()).'/';
        }
        if (!is_dir($savePath)) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($savePath, 0777,true);
        }
        $filename = uniqid() . rand(1000, 9999) . '.' . $type;
        $base64 = str_replace($res[1],'', $file);
        if (!$base64) {
            throw new ResourceException('图片格式错误');
        }
        if (file_put_contents($savePath . $filename, base64_decode($base64))) {
            return '/' . $savePath . $filename;
        } else {
            throw new ResourceException('图片写入失败');
        }
    }

    /**
     * 二进制资源上传
     * @param $file
     * @return mixed
     */
    public static function uploadBin(string $file)
    {
        // TODO: Implement uploadBin() method.
    }

    /**
     * 资源获取
     * @param $name
     * @return mixed
     */
    public static function get($name)
    {
        // TODO: Implement get() method.
    }

    /**
     * 资源删除
     * @param $name
     * @return mixed
     */
    public static function del($name)
    {
        // TODO: Implement del() method.
    }

    /**
     * 判断是否是base64图片
     * @param $data
     * @return bool
     */
    private static function isBase64($data)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data, $result)) {
            return $result;
        } else {
            return false;
        }
    }
}