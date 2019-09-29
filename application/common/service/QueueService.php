<?php

namespace app\common\service;

use think\Queue;

class QueueService extends BaseService
{
    // 队列名称和类对应表
    private static $queue = [
        //'order_then'=>OrderThen::class, //处理支付后订单
    ];

    /*
     * @param $que  添加到的队列名称
     * @param $data 需添加的数据
     * @return bool 是否添加成功
     */
    public static function add($que,$data)
    {
        // 1.当前任务将由哪个类来负责处理。
        //   当轮到该任务时，系统将生成一个该类的实例，并调用其 fire 方法
        $jobHandlerClassName  = (self::$queue)[$que]; //'app\test\controller\TestController';

        // 2.当前任务归属的队列名称，如果为新队列，会自动创建
        $jobQueueName  	  = 'listenJobs';

        // 3.当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
        //   ( jobData 为对象时，存储其public属性的键值对 )

        $jobData       	  = $data;

        // 4.将该任务推送到消息队列，等待对应的消费者去执行
        $isPushed = Queue::push( $jobHandlerClassName , $jobData ,$jobQueueName);

        // database 驱动时，返回值为 1|false  ;   redis 驱动时，返回值为 随机字符串|false
        if( $isPushed !== false ){
            return true;
        }else{
            return false;
        }
    }
}
