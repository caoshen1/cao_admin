<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2019 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace mytools\lib;


/**
 * 资源服务层
 */
class Resources
{

    // 检查是否是base64
    public static function isBase64($data)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data, $result)) {
            return serviceReturn(1,$result);
        } else {
            return serviceReturn(0,'请上传正确的图片');
        }
    }

    // 上传base64
    public static function saveBase64($data, $savePath = '', $type = 'jpg')
    {
        if(preg_match('/^\/w+\/w+/', $data)){
            return serviceReturn(1,$data);
        }
        if(self::isBase64($data)['status'] == 0){
            return serviceReturn(0,'请上传正确的图片');
        }
        if ($type == "jpeg" || $type == "jepg") {
            $type = "jpg";
        }
        if(empty($savePath)){
            $savePath = 'image/'.date('Ymd',time()).'/';
        }
        if (!is_dir($savePath)) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($savePath, 0774);
        }
        $filename = uniqid() . rand(1000, 9999) . ".{$type}";
        $base64 = explode(';base64,', $data)[1];
        if (!$base64) {
            return serviceReturn(0,'图片格式错误');
        }
        if (file_put_contents($savePath . $filename, base64_decode($base64))) {
            return serviceReturn(1,'/' . $savePath . $filename);
        } else {
            return serviceReturn(0,'图片写入失败');
        }
    }


    // 批量上传图片 [文件名，文件名=>[width,height,size,ext,path,old_path]]
    public static function uploadMore(array $file_arr = [])
    {
        if ($file = request()->file()) {
            $data = []; // 保存最后路径
            foreach ($file_arr as $key => $fn) {
                if($icon_file = is_array($fn) ? $file[$key] : $file[$fn]) {
                    $vali = []; // 校验字段
                    $path = 'image'; // 储存路径
                    $old_path = ''; // 原文件
                    if(is_array($fn)) {
                        $image = \think\Image::open($icon_file);
                        if(!empty($fn['width']) && $image->width() != $fn['width']) {
                            dieReturn('图片宽度必须为'.$fn['width'].'px');
                        }
                        if(!empty($fn['height']) && $image->height() != $fn['height']) {
                            dieReturn('图片高度必须为'.$fn['height'].'px');
                        }
                        if(!empty($fn['size'])) {
                            $vali['size'] = $fn['size'];
                        }
                        if(!empty($fn['ext'])) {
                            $vali['ext'] = $fn['ext'];
                        }
                        if(!empty($fn['path'])) {
                            $path = $fn['path'];
                        }
                        if(!empty($fn['old_path'])) {
                            $old_path = $fn['old_path'];
                        }
                    }

                    $icon_info = $icon_file->validate($vali)->move($path);
                    if ($icon_info) {
                        // 删除源文件
                        try {
                            if (!empty($old_path)) {
                                $old_path = env('root_path') . 'public/' . $old_path;
                                if (file_exists($old_path)) unlink($old_path);
                            }
                        } catch (\Exception $e) {

                        }
                        $data[is_array($fn) ? $key : $fn] = $icon_info->getSaveName();
                    } else {
                        // 上传失败获取错误信息
                        return dieReturn($icon_file->getError());
                    }
                }
            }
        }
    }
    
}