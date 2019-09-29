<?php
/**
 * Created by PhpStorm.
 * User: cs
 * Date: 2019/4/19
 * Time: 11:19
 */

namespace app\channel;


use app\common\model\User;
use app\service\UserService;
use think\facade\Log;

class BaseChannel
{
    // 渠道列表
    public static $channel = [
        1 => ['title'=>'微信','href'=>'/conf/wxpay.php'], // 微信
        2 => ['title'=>'支付宝','href'=>'/conf/alipay.php'], // 阿里
    ];

    private $instance;
    // 渠道配置
    private $channel_conf;
    // 基础配置
    private $conf;
    // 支付渠道
    private $channel_key;

    public function __construct($channel_key)
    {
        // 包含配置文件
        try {
            $this->conf = require_once __DIR__ . '/conf/base.php';
            $this->channel_conf = require_once __DIR__ . self::$channel[$channel_key]['href'];
            $this->channel_key = $channel_key;
        }catch (\Exception $e) {
            return ['status'=>0,'msg'=>'包含配置文件失败！'];
        }
        $this->instance = new $this->channel_conf['class']($this->channel_conf['conf']);
    }


    // 支付接口  [desc=>订单描述,order_no=>订单号,total=>订单金额]
    public function pay($data)
    {
        $data['desc'] = $data['desc'] ?? '曙光联盟订单';
        return $this->instance->appPay($data);
    }

    // 退款接口  [order_no=>订单号,refund_no=>退款单号,total=>总金额,desc=>退款描述,refund=>退款金额]
    public function refund($data)
    {
        // 生成流水号
        $pay_id = makePayId();

        $pay_lod_data = [
            'pay_id' => $pay_id,
            'order_id' => $data['order_no'],
            'pay_price' => $data['refund'],
            'payment'=> $this->channel_key,
            'type' => 2
        ];

        if(!PayLog::create($pay_lod_data)) queryStatus(0,'写入支付日志失败');
        return $this->instance->refund($data);
    }

    // 查询退款

    // 查询支付

    // 传入参数转换
    private function makeParams($data)
    {
        $param = [];
        foreach ($this->channel_conf['params'] as $k => $v) {
            if (isset($data[$k])) {
                foreach ($v as $vv) {
                    $param[$vv] = $data[$k];
                }
            }
        }
        return $param;
    }

    // 传出参数转换
    private function outParams($data)
    {
        if($data['status'] == 1) {
            $param = [];
            foreach ($this->channel_conf['out_param'] as $k => $v) {
                if(isset($data['data'][$k])) {
                    $param[$v] = $data['data'][$k];
                }
            }
            return ['status'=>1,'data'=>$param];
        }
        return $data;
    }


    /**
     * @param $info array 支付回调信息 ['tid'=>流水号,'status'=>状态,'total'=>总金额,'trade_no'=>渠道订单号]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function payNotify($info)
    {
        // 修改订单状态
        // 查询订单
        $pay_log = PayLog::find($info['tid']);
        if(!$pay_log) return;
        if($pay_log->status != 2) return;
        $order = Order::find($pay_log->order_id);
        if($order->status != 1) return;
        if($pay_log->pay_price != $info['total']) return;
        if($info['status'] == 1) { // 支付成功
            $order->status = 2;
            $order->save();
            $pay_log->trade_no = $info['trade_no'];
            $pay_log->status = 1;
            $pay_log->pay_time = time();
            $pay_log->save();
            // 扣除积分
            $user = User::find($order->user_id);
            $integral = $order->integral - $order->extend->use_int;
            $user->integral = ['inc', $integral];
            $user->save();
            // 写入积分变动记录
            UserService::addIntegralLog($order->user_id,$order->integral,2,1);
            if($order->extend->use_int > 0) UserService::addIntegralLog($order->user_id,$order->extend->use_int,2,2);
            // 分润
            UserService::buyShare($order->user_id,$info['total']);
            return;
        }
        // 支付失败
        $pay_log->trade_no = $info['trade_no'];
        $pay_log->status = 3;
        $pay_log->pay_time = time();
        $pay_log->save();
        Log::record('订单流水号：'.$info['tid'].' 支付失败'.PHP_EOL);
    }

    // 退款回调  ['tid' =>自己流水号,'oid' =>自己订单号,'trade_no' =>微信退款订单号,'total'=>微信退款金额,'status'退款状态]
    public static function refundNotify($info)
    {
        // 修改订单状态
        // 查询订单
        $pay_log = PayLog::find($info['tid']);
        if(!$pay_log) return;
        if($pay_log->status != 2) return;
        $order = Order::find($pay_log->order_id);
        if($order->status != 6) return;
        if($pay_log->pay_price != $info['total']) return;
        if($info['status'] == 1) { // 支付成功
            $order->status = 2;
            $order->save();
            $pay_log->trade_no = $info['trade_no'];
            $pay_log->status = 1;
            $pay_log->pay_time = time();
            $pay_log->save();
        }
        // 支付失败
        $pay_log->trade_no = $info['trade_no'];
        $pay_log->status = 3;
        $pay_log->pay_time = time();
        $pay_log->save();
        Log::record('订单流水号：'.$info['tid'].' 支付失败'.PHP_EOL);
    }
}