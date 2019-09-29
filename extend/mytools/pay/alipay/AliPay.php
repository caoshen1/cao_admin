<?php
/**
 * Created by PhpStorm.
 * User: line@lineage.com
 * Date: 2018-12-26
 * Time: 16:17
 */
namespace app\admin\channel\alipay;


use app\channel\BaseChannel;

class AliPay
{
    private static $conf = [];
    public function __construct($conf)
    {
        self::$conf = $conf;
    }
    /**
     * 发起电脑网站支付
     * @param string $payAmount                                         支付金额单位: 元
     * @param string $outTradeNo                                        商品订单号
     * @param string $orderName                                         支付标题
     * @return array                                                   返回html需要导入网页
     */
    public function ComputerPay($payAmount = '',$outTradeNo = '',$orderName = ''){
        return self::Pay($payAmount,$outTradeNo,$orderName,'pc');
    }

    /**
     * 发起手机网站支付
     * @param string $payAmount                                         支付金额单位: 元
     * @param string $outTradeNo                                        商品订单号
     * @param string $orderName                                         支付标题
     * @return array                                                    返回html需要导入网页
     */
    public function MobilePay($payAmount = '',$outTradeNo = '',$orderName = ''){
        return self::Pay($payAmount,$outTradeNo,$orderName,'wap');
    }

    /**
     * 发起APP支付
     * @param array $data
     * @return  array
     */
    public function appPay($data){
        return self::Pay($data['amount'],$data['order_no'],$data['desc'],'app');
    }

    /**
     * @param string $payAmount
     * @param string $outTradeNo
     * @param string $orderName
     * @param string $plant
     * @return array
     */
    private static function Pay($payAmount = '',$outTradeNo = '',$orderName = '',$plant = 'app'){
        $aliPay = new AliPayApi();
        $aliPay->setAppid(self::$conf['Appid']);//应用ID
        $aliPay->setReturnUrl(self::$conf['ReturnUrl']);//同步回调地址
        $aliPay->setNotifyUrl(self::$conf['pay_notify_url']);//异步回调地址
        $aliPay->setRsaPrivateKey(self::$conf['RsaPrivateKey']);//商户私钥
        $aliPay->setTotalFee($payAmount);//支付金额 单位 元
        $aliPay->setOutTradeNo($outTradeNo);//商品订单号
        $aliPay->setOrderName($orderName);//支付标题
        switch ($plant) {
            case 'pc':
                $result = $aliPay->doPay_Pc();
                break;
            case 'wap':
                $result = $aliPay->doPay_wap();
                break;
            case 'app':
                $result = $aliPay->doPay_app();
                break;
            default:
                return ['status'=>0,'msg'=>'支付类型错误'];
                break;
        }
        if($result){
            return queryStatus(1,$result);
        }
        return queryStatus(0,$result);
    }

    /**
     * 查询转账订单
     * 商户转账唯一订单号（商户转账唯一订单号、支付宝转账单据号 至少填一个）
     * @param string $outBizBo                                      商户转账唯一订单号
     * @param string $orderId                                       支付宝转账单据号
     * @return array
     */
    public function TransferQueryOrder($outBizBo = '',$orderId = ''){
        $aliPay = new AliPayApi();
        $aliPay->setAppid(self::$conf['Appid']);//应用ID
        $aliPay->setRsaPrivateKey(self::$conf['RsaPrivateKey']);//商户私钥
        $result = $aliPay->doQuery($outBizBo,$orderId);
        $result = $result['alipay_fund_trans_order_query_response'];
        if($result['code'] && $result['code']=='10000'){
            return queryStatus(1,$result);
        }else{
            return queryStatus(0,$result);
        }
    }

    /**
     * 单笔转账到支付宝账户
     * @param string $payAmount                                     单笔转账到支付宝账户
     * @param string $outTradeNo                                    商户转账唯一订单号
     * @param string $account                                       收款方账户（支付宝登录号，支持邮箱和手机号格式。）
     * @param string $realName                                      收款方真实姓名
     * @param string $remark                                        转帐备注
     * @return array
     */
    public function TransferPay($payAmount,$outTradeNo,$account,$realName,$remark){
        $aliPay = new AliPayApi();
        $aliPay->setAppid(self::$conf['Appid']);//应用ID
        $aliPay->setRsaPrivateKey(self::$conf['RsaPrivateKey']);//商户私钥
        $result = $aliPay->doPay_transfer($payAmount,$outTradeNo,$account,$realName,$remark);
        $result = $result['alipay_fund_trans_toaccount_transfer_response'];
        if($result['code'] && $result['code']=='10000'){
            return queryStatus(1,$result);
        }else{
            return queryStatus(0,$result);
        }
    }

    /**
     * 同步回调数据
     * @param $params
     * @return bool
     */
    public function AliPayReturn($params){
        $aliPay = new AliPayApi();
        $aliPay->setAlipayPublicKey(self::$conf['alipayPublicKey']);//支付公钥
        $result = $aliPay->rsaCheck($params);
        if($result === true){
            //同步回调一般不处理业务逻辑，显示一个付款成功的页面，或者跳转到用户的财务记录页面即可。
            return true;
        }
        return false;
    }

    /**
     * 异步回调数据
     * @param $params
     */
    public function AliPayNotify($params){
        $aliPay = new AliPayApi();
        $aliPay->setAlipayPublicKey(self::$conf['alipayPublicKey']);//支付公钥
        $result = $aliPay->rsaCheck($params);
        if($result === true){
            //处理你的逻辑，例如获取订单号$_POST['out_trade_no']，订单金额$_POST['total_amount']等
            //程序执行完后必须打印输出“success”（不包含引号）。如果商户反馈给支付宝的字符不是success这7个字符，支付宝服务器会不断重发通知，
            //直到超过24小时22分钟。一般情况下，25小时以内完成8次通知（通知的间隔频率一般是：4m,10m,10m,1h,2h,6h,15h）
            // 组装返回数据
            $arr = [
                'tid' =>$params['out_refund_no'], // 自己流水号
                'oid' =>$params['out_trade_no'], // 自己订单号
                'trade_no' =>$params['refund_id'], // 微信退款订单号
                'total' =>$params['refund_fee'] * 100, // 微信退款金额
                'status' =>$params['refund_status'] == 'SUCCESS' ? 1 : 2, // 退款状态
            ];
            // 调用统一退款回调
            BaseChannel::refundNotify($arr);
            echo 'success';exit();
        }
        echo 'error';exit();
    }

    /**
     * 发起退款
     * @param array $data
     * @return array
     */
    public function refund($data){
        $aliPay = new AliPayApi();
        $aliPay->setAppid(self::$conf['Appid']);
        $aliPay->setRsaPrivateKey(self::$conf['RsaPrivateKey']);
        $aliPay->setTradeNo($data['refund_no']);//在支付宝系统中的交易流水号。最短 16 位，最长 64 位。和out_trade_no不能同时为空
        $aliPay->setOutTradeNo($data['order_no']);//订单支付时传入的商户订单号,和支付宝交易号不能同时为空。
        $aliPay->setRefundAmount($data['refund']);//需要退款的金额，该金额不能大于订单金额,单位为元，支持两位小数
        $result = $aliPay->doRefund();
        $result = $result['alipay_trade_refund_response'];
        if($result['code'] && $result['code']=='10000'){
            return queryStatus(1,$result);
        }else{
            return queryStatus(0,$result);
        }
    }

    /**
     * 查询订单状态
     * @param string $outTradeNo                        要查询的商户订单号。注：商户订单号与支付宝交易号不能同时为空
     * @param string $tradeNo                           要查询的支付宝交易号。注：商户订单号与支付宝交易号不能同时为空
     * @return array
     */
    public function QueryStaticOrder($outTradeNo,$tradeNo){
        $aliPay = new AliPayApi();
        $aliPay->setAppid(self::$conf['Appid']);
        $aliPay->setRsaPrivateKey(self::$conf['RsaPrivateKey']);
        $aliPay->setOutTradeNo($outTradeNo);//要查询的商户订单号。注：商户订单号与支付宝交易号不能同时为空
        $aliPay->setTradeNo($tradeNo);//要查询的支付宝交易号。注：商户订单号与支付宝交易号不能同时为空
        $result = $aliPay->doQuery();
        if($result['alipay_trade_query_response']['code']!='10000'){
            return [false,$result];
        }else{
            switch($result['alipay_trade_query_response']['trade_status']){
                case 'WAIT_BUYER_PAY':
                    $result['alipay_trade_query_response']['trade_status_text']='交易创建，等待买家付款';
                    break;
                case 'TRADE_CLOSED':
                    $result['alipay_trade_query_response']['trade_status_text']='未付款交易超时关闭，或支付完成后全额退款';
                    break;
                case 'TRADE_SUCCESS':
                    $result['alipay_trade_query_response']['trade_status_text']='交易支付成功';
                    break;
                case 'TRADE_FINISHED':
                    $result['alipay_trade_query_response']['trade_status_text']='交易结束，不可退款';
                    break;
                default:
                    $result['alipay_trade_query_response']['trade_status_text']='未知状态';
                    break;
            }
            return queryStatus(1,$result);
        }
    }

    /**
     * 当面付 （扫码支付）
     * @param string $payAmount                             支付金额 元
     * @param string $outTradeNo                            商户订单号
     * @param string $orderName                             支付信息
     * @return array
     */
    public function QrCodePay($payAmount = '',$outTradeNo = '',$orderName = ''){
        $aliPay = new AliPayApi();
        $aliPay->setAppid(self::$conf['Appid']);
        $aliPay->setNotifyUrl(self::$conf['NotifyUrl']);
        $aliPay->setRsaPrivateKey(self::$conf['RsaPrivateKey']);
        $aliPay->setTotalFee($payAmount);
        $aliPay->setOutTradeNo($outTradeNo);
        $aliPay->setOrderName($orderName);
        $result = $aliPay->QrCodePay();
        $result = $result['alipay_trade_precreate_response'];
        if($result['code'] && $result['code']=='10000'){
            return queryStatus(1,$result);
        }else{
            return queryStatus(0,$result);
        }
    }

    /**
     * 交易关闭接口
     * @param string $tradeNo 在支付宝系统中的交易流水号。最短 16 位，最长 64 位。和out_trade_no不能同时为空
     * @param string $outTradeNo 订单支付时传入的商户订单号,和支付宝交易号不能同时为空。
     * @return array
     */
    public function DoCloseOrder($tradeNo,$outTradeNo){
        $aliPay = new AliPayApi();
        $aliPay->setAppid(self::$conf['Appid']);
        $aliPay->setRsaPrivateKey(self::$conf['RsaPrivateKey']);
        $aliPay->setTradeNo($tradeNo);
        $aliPay->setOutTradeNo($outTradeNo);
        $result = $aliPay->CloseOrder();
        $result = $result['alipay_trade_close_response'];
        if($result['code'] && $result['code']=='10000'){
            return queryStatus(1,$result);
        }else{
            return queryStatus(0,$result);
        }
    }


}