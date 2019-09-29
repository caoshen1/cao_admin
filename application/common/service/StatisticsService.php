<?php

namespace app\common\service;

use app\common\model\Statistics;

/**
 * 统计服务层
 * Class StatisticsService
 * @package app\common\service
 */
class StatisticsService extends BaseService
{
    // what 支持类型
    public static $what_arr = [
        //['id' => 1, 'title' => '余额'],
    ];

    // type 注释
    public static $type_arr = [
        //['id' => 1, 'title' => '用户'],
    ];


    // 写入统计数据
    public static function create(int $who, string $when, int $what, $value, int $type, $extend = [])
    {
        // 处理时间
        $when = date($when);
        $extend = json_encode($extend, JSON_UNESCAPED_UNICODE);

        // 判断what和type
        $wf = false;
        foreach (self::$what_arr as $v) {
            if($v['id'] == $what) $wf = true;
        }
        $tf = false;
        foreach (self::$type_arr as $v) {
            if($v['id'] == $type) $tf = true;
        }
        if(!$wf || !$tf) dieReturn('写入统计表字段非法');

        $sql = <<<SQL
          INSERT INTO `my_statistics` 
              (`who`,`when`,`what`,`value`,`type`,`extend`)  
                values($who, $when, $what, $value, $type, '$extend') 
                  on  DUPLICATE key update `value` = $value + values(`value`)
SQL;
        return (new Statistics())->query($sql);
    }

    /**
     * 获取一段时间统计记录
     * @param array $params 查询条件 (所有条件都可为空)
     * [
     *      'who' => 查询对象ID或者ID数组,
     *      'what' => 查询的类型ID或者ID数组,
     *      'when' => 查询的时间范围 开始时间字符串|[【开始时间字符串，格式化字符串】,【结束时间字符串，格式化字符串】]
     *      'type' => 查询tp_id或者ID数组
     *      'key'  => Key_id 或者数组
     *      'value' => value 或者数组
     *      'limit' => 每页记录数
     * ]
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getStatistics($params)
    {
        // who when what type key value
        // 构造条件
        $w  = [];
        // 统计对象
        if(!empty($params['who'])) {
            $condition = is_array($params['who']) ? 'in' : '=';
            $w[] = ['who',$condition,$params['who']];
        }
        // 统计时间
        if(!empty($params['when'])) {
            if(is_array($params['when'])) { // 第0个表示开始时间，第1个表示结束时间 [20190126,20190528]
                $time_area = self::transformTime($params['when']);
            }else{ // 字符串
                // 预设字符串
                $preinstall = [
                    // 最近三年
                    '3y' => [
                        date('Ymd',strtotime(date('Ymd') . ' 00:00:00')  - (3600*24 *365 *3)),
                        date('Ymd')
                    ],
                    // 最近一年
                    'y' => [
                        date('Ymd',strtotime(date('Ymd') . ' 00:00:00') - 3600*24 *365),
                        date('Ymd')
                    ],
                    // 最近半年
                    'hy' => [
                        date('Ymd',strtotime(date('Ymd') . ' 00:00:00') - 3600*24 *183),
                        date('Ymd')
                    ],
                    // 最近一季度
                    'q' => [
                        date('Ymd',strtotime(date('Ymd') . ' 00:00:00') - 3600*24 *91),
                        date('Ymd')
                    ],
                    // 最近一个月
                    'm' => [
                        date('Ymd',strtotime(date('Ymd') . ' 00:00:00') - 3600*24 *31),
                        date('Ymd')
                    ],
                    // 最近一周
                    'w' => [
                        date('Ymd',strtotime(date('Ymd') . ' 00:00:00') - 3600*24 *7),
                        date('Ymd')
                    ],
                    // 最近24小时
                    'd' => [
                        date('YmdH',strtotime(date('Ymd') . ' 00:00:00') - 3600*24),
                        date('YmdH')
                    ],
                    // 最近1小时
                    'h' => [
                        date('YmdH',strtotime(date('Ymd') . ' 00:00:00') - 3600),
                        date('YmdH')
                    ],
                ];
                // 如果是预设字符串
                if (array_key_exists($params['when'],$preinstall)) {
                    $time_area = $preinstall[$params['when']];
                }else{
                    // 自己输入字符串，表示开始时间
                    $time_area = self::transformTime($params['when']);
                }
            }
            $w[] = ['when','between',[$time_area[0],$time_area[1]]];
        }
        if(!empty($params['what'])) {
            $condition = is_array($params['what']) ? 'in' : '=';
            $w[] = ['what',$condition,$params['what']];
        }
        if(!empty($params['type'])) {
            $condition = is_array($params['type']) ? 'in' : '=';
            $w[] = ['type',$condition,$params['type']];
        }
        if(!empty($params['key'])) {
            $condition = is_array($params['key']) ? 'in' : '=';
            $w[] = ['key',$condition,$params['key']];
        }
        if(!empty($params['value'])) {
            $condition = is_array($params['value']) ? 'in' : '=';
            $w[] = ['value',$condition,$params['value']];
        }
        // 查询
        if($params['limit']) {
            return Statistics::where($w)
                ->paginate($params['limit'],false,['page' => request()->param('page',1,'intval')])
                ->toArray();
        }
        return Statistics::where($w)->select()->toArray();
    }

    // 转换时间格式
    private static function transformTime($tp)
    {
        if(is_array($tp) && count($tp) == 2) {
            return array_map(function ($v) {
                if(is_array($v)) {
                    return date(empty($v[1]) ? 'Ymd' : $v[1],strtotime($v[0]));
                }
                return date('Ymd',strtotime($v));
            },$tp);
        }
        if(is_array($tp)) {
            return [
                date($tp[1],strtotime($tp[0])),
                date($tp[1]),
            ];
        }
        return [
            date('Ymd',strtotime($tp)),
            date('Ymd'),
        ];
    }

}

