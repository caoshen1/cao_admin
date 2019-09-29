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
use app\common\model\Config;

/**
 * 配置服务层
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class ConfigService extends BaseService
{
    /*
     * 配置列表，唯一标记作为key
     */
    public static function ConfigList()
    {
        $list = Config::order('sort asc')->select();
        return queryStatus(1,$list);
    }

    /**
     * 配置数据保存
     */
    public static function ConfigSave($key,$value)
    {
        $row = Config::find($key);
        if(!$row) throw new CustomException(0,'未查询到该配置项');
        // 如果是switch，则修改状态
        if($row->getData('type') == 'switch') {
            $status = $row->value == 1 ? 2 : 1;
            $row->value = $status;
        }else{
            // 验证值
            if(!$value = self::checkValue($row->type,$value,$row->value)) throw new CustomException('输入的值非法');
            // 写入值
            $row->value = $value;
        }
        if($row->save()) return queryStatus(1);
        throw new CustomException('保存失败');
    }

    // 验证值
    private static function checkValue($type,$value,$old)
    {
        switch ($type) {
            case 'int':
                return is_numeric($value) ? (int)$value : false;
                break;
            case 'float':
                return is_numeric($value) ? $value : false;
                break;
            case 'url':
                return preg_match("#(http|https)://(.*\.)?.*\..*#i",$value) ? $value : false;
                break;
            case 'image':
                // 验证并上传图片，删除原图，返回图片路径
                $path = ResourcesService::net2Path($value);
                if(!file_exists(ResourcesService::getPath('image') . $path)) {
                    return false;
                }
                // 删除原图
                ResourcesService::deleteFile($old);
                return $path;
                break;
            case 'string':
                return empty($value) ? false : $value;
                break;
            case 'switch':
                if(!in_array($value,[1,2,'on','off'])) return false;
                if($value == 'on') return 1;
                if($value == 'off') return 2;
                return (int)$value;
            case 'pwd':
                return empty($value) ? false : $value;
                break;
            case 'json':
                return empty($value) ? false : $value;
        }
    }

    // 获取配置项值
    public static function getValue($key)
    {
        if(is_array($key)) {
            $data = Config::where('key','in',$key)->field('key,value,type')->select();
            if(!$data->isEmpty()) $data = $data->toArray();
            else return [];
            $res = [];
            foreach ($data as $v) {
                if($v['type'] == 'json') {
                    $res[$v['key']] = explode('|',$v['value']);
                }else{
                    $res[$v['key']] = $v['value'];
                }
            }
            return $res;
        }else{
            $data = Config::find($key);
            if(!$data) return [];
            if($data['type'] == 'json') {
                return explode('|',$data['value']);
            }else{
                return $data['value'];
            }
        }

    }

}