<?php
/**
 * 生成页面配置信息
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/8/3
 * Time: 10:38
 */

return [
    // 模块名
    'module' => '订单',
    // 对应控制器
    'controller' => 'Order',
    // 对应模型
    'model' => 'Order',
    // 路由文件名
    'route_file' => 'admin',
    // 列表页配置
    'list' => [
        // 列表展示字段
        'fields' => [
            // 标题=>[对应模型字段名，展示风格（text，switch，image）]
            '订单号' => ['order_no','text','_pk_' => true],
            '用户' => ['user_id','text'],
            '状态' => ['status','switch'],
            '订单金额' => ['total_price','text'],
            '最高退款金额' => ['max_reduce','text'],
            '下单时间' => ['create_time','text'],
        ],
        // 搜索字段
        'key' => 'order_no|user_id'
    ],
    // 新增页面
    'edit' => [
        // 表单配置
        'form' => [
            // 标题 => [0字段名，1渲染模式（text,area,file,select,radio,switch），2编辑模式下是否可编辑(无用但是位置先占着)，3默认值,4【[待选值名=>对应值]】]
            // ,_pk_写了则代表是主键，编辑页面不会渲染,只能写在最后面
            //'游戏ID' => ['id','text',false,'','_pk_' => true],
            //'游戏类型' => ['type','radio',false,'2',['固定金额类'=>'1','随机金额类'=>'2']],
            '订单号' => ['order_no','text',false,'_pk_' => true],
            '用户' => ['user_id','text'],
            '状态' => ['status','switch'],
            '订单金额' => ['total_price','text'],
            '最高退款金额' => ['max_reduce','text'],
            '下单时间' => ['create_time','text'],
            '商品信息' => ['goods','text'],
            '收件人信息' => ['user_info','text'],
            '物流信息' => ['express_info','text'],
        ],
    ],
];