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
namespace app\common\service;

use app\common\exception\CustomException;

/**
 * 资源服务层
 */
class ResourcesService
{

    /**
     * 判断是否是base64图片
     * @param $data
     * @return bool
     */
    public static function isBase64($data)
    {
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data, $result)) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 上传base64图片
     * @param string $data base64编码
     * @param string $savePath 保存路径
     * @param string $type 文件后缀
     * @return array|string
     * @throws CustomException
     */
    public static function saveBase64($data, $savePath = '', $type = 'jpg')
    {
        if(preg_match('/^http[s]:\/\/w+$/', $data)){
            return $data;
        }
        if(!self::isBase64($data)){
            throw new CustomException('请上传正确的图片');
        }
        if ($type == "jpeg" || $type == "jepg") {
            $type = "jpg";
        }
        if(empty($savePath)){
            $savePath = config('conf.static_path') . '/image/'.date('Ymd',time()).'/';
        }
        if (!is_dir($savePath)) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($savePath, 777);
        }
        $filename = uniqid() . rand(1000, 9999) . ".{$type}";
        $base64 = explode(';base64,', $data)[1];
        if (!$base64) {
            throw new CustomException('图片格式错误');
        }
        if (file_put_contents($savePath . $filename, base64_decode($base64))) {
            return '/' . $savePath . $filename;
        } else {
            throw new CustomException('图片写入失败');
        }
    }

    /*
     * [ContentStaticReplace 编辑器中内容的静态资源替换]
     * @param    [string]    $content [在这个字符串中查找进行替换]
     * @param    [string]    $type    [操作类型[get读取额你让, add写入内容](编辑/展示传入get,数据写入数据库传入add)]
     * @return   [string]             [正确返回替换后的内容, 则返回原内容]
     */
    public static function contentStaticReplace($content, $type = 'get')
    {
        // 异步文件上传，返回url
        return $content;
    }

    /*
     *
     * @param    [type]                   $value [description]
     */
    /**
     * 展示静态资源（图片等）
     * @param string $value 服务器地址
     * @return string 网络地址
     */
    public static function staticResource($value)
    {
        if(!empty($value))
        {
            if(strpos($value, 'http') === false)
            {
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                if(strpos($value, config('conf.static_path')) === false) {
                    $value = config('conf.static_path') . $value;
                }
                return $http_type . $_SERVER['HTTP_HOST'] . '/' . $value;
            }
            return $value;
        }
        return '';
    }

    /**
     * 保存网络图片到本地
     * @param string url 资源路径
     * @return string
     */
    public static function downPicSave($url, $filename)
    {
        $ch = curl_init();//初始化一个cURL会话

        curl_setopt($ch,CURLOPT_URL,$url);//抓取url

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//是否显示头信息
        if(strpos($url,'https://') !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $data = curl_exec($ch);// 执行一个cURL会话

        $error = curl_error($ch);//返回一条最近一次cURL操作明确的文本的错误信息。
        curl_close($ch);//关闭一个cURL会话并且释放所有资源
        $file = fopen($filename,"w+");
        fputs($file,$data);//写入文件
        fclose($file);
        return $error ? false : true;
    }

    /**
     * 删除文件
     * @param string $path 文件路径
     * @return bool
     */
    public static function deleteFile($path)
    {
        // 尝试删除原图
        try{
            $old_path = str_replace('\\','/', env('root_path')).'public/'.config('conf.static_path').$path;
            if(file_exists($old_path)) unlink($old_path);
            return true;
        }catch (\Exception $e) {
            return false;
        }
    }
    
    // 上传二进制文件

    /**
     * 上传二进制文件
     * @param mixed $name 字符串为单文件  数组为多文件
     *      [
     *          name,|
     *          name => [
     *              validate=>[                     // 验证数组
     *                  width => ['=',95] | 95,     // 宽px
     *                  height,                     // 高px
     *                  size,                       // 大小 字节
     *                  ext,                        // 文件类型
     *              ]，
     *              path=>路径
     *              array => 1,                     // 是否是同名多文件
     *          ]
     *      ]
     * @return array
     * @throws CustomException
     */
    public static function uploadFile($name)
    {
        if(empty($name)) throw new CustomException('请指明需上传的文件');
        // 单文件
        if(!is_array($name)) {
            $re = self::binUpload($name);
            if($re['query_status'] != 1) throw new CustomException($re['msg']);
            $data= $re['data'];
        }else{ // 多文件
            $data = [];
            foreach ($name as $k => $v) {
                $name = is_int($k) ? $v : $k; // 文件名
                $is_name = empty($v['array']) ? false : true; // 是否同名多文件
                $validate = $v['validate'] ?? []; // 验证数组
                $path = empty($v['path']) ? '' : $v['path'];
                $re = self::binUpload($name, $validate, $is_name, $path);
                if($re['query_status'] == 0) throw new CustomException($re['msg']);
                $data[$name] = $re['data'];
            }
        }
        return $data;
    }

    // 二进制上传
    private static function binUpload($name, $validate = [], $is_name = false,$file_path = '')
    {
        $file = request()->file($name);
        if(!$file) return queryStatus(0,'没有文件被上传');
        // 移动到目录
        $path =  str_replace('\\','/', env('root_path')).'public/'.config('conf.static_path').'/'.$file_path;
        if($is_name && count($file) > 1) { // 单文件名多文件
            $data = [];
            foreach ($file as $item) {
                $re = self::upOneBin($item,$validate,$path,$file_path);
                if($re['query_status'] != 1) { // 上传失败，删除已上传文件
                    if(!empty($data)) {
                        foreach ($data as $v) {
                            self::deleteFile($v);
                        }
                    }
                    return queryStatus(0,$re['msg']);
                };
                $data[] = $re['data'];
            }
            return queryStatus(1,$data);
        }else{
            return self::upOneBin($file,$validate,$path,$file_path);
        }
    }

    // 上传单个二进制
    private static function upOneBin($file,$validate,$path,$file_path)
    {
        // 验证数据
        if(!empty($validate)) {
            if(!$validate['size']) $vali['size'] = $validate['size'];
            if(!$validate['ext']) $vali['ext'] = $validate['ext'];
        }
        // 校验宽高
        if(!empty($validate['width']) || !empty($validate['height'])) {
            $img_size = getimagesize($file->getInfo('tmp_name'));
            // 校验宽度
            if(!empty($validate['width']) && $img_size[0] > $validate['width']) {
                $re = self::checkHW($validate['width'],$img_size[0]);
                if(!$re['re'])
                    return queryStatus(0,'图片宽度应'.$re['msg'].$re['size'].'px');
            }
            // 校验高度
            if(!empty($validate['height']) && $img_size[0] > $validate['height']) {
                $re = self::checkHW($validate['height'],$img_size[0]);
                if(!$re['re'])
                    return queryStatus(0,'图片高度应'.$re['msg'].$re['size'].'px');
            }
        }
        $info = $file->validate($vali)->move($path);
        if($info){
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            return queryStatus(1,$file_path.'/'.str_replace('\\','/', $info->getSaveName()));
        }else{
            return queryStatus(0,$file->getError());
        }
    }

    // 判断图片宽高大小
    private static function checkHW($info,$num)
    {
        if(is_array($info)) {
            switch ($info[0]) {
                case '>':
                    return ['re' => $num > $info[0],'msg'=> '大于','size'=> $info[0]];
                case '>=':
                    return ['re' => $num >= $info[0],'msg'=> '大于等于','size'=> $info[0]];
                case '<':
                    return ['re' => $num < $info[0],'msg'=> '小于','size'=> $info[0]];
                case '<=':
                    return ['re' => $num <= $info[0],'msg'=> '小于等于','size'=> $info[0]];
                case '=':
                    return ['re' => $num == $info[0],'msg'=> '等于','size'=> $info[0]];
            }
        }else {
            return ['re' => $info == $num, 'msg' => '等于','size'=> $info];
        }
    }

    /**
     * 获取环境变量
     * @param string $as
     * @return mixed|string
     */
    public static function getPath(string $as)
    {
        if($as == 'image') {
            return str_replace('\\','/', env('root_path')).'public/'.config('conf.static_path').'/';
        }
        return str_replace('\\','/', env($as));
    }

    // 将服务器上的文件由网络地址转为绝对路径
    public static function net2Path($url)
    {
        return str_replace(request()->root(true),'',$url);
    }
}