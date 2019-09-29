<?php

namespace app\common\validate;


use app\common\model\Admin;
use fun\Assist;
use fun\GlobalParam;

trait BaseValiFun
{
    // 校验身份证号码
    protected function checkIDcard($card)
    {
        return Assist::isIdcard($card) ? true : false;
    }

    // 校验火星坐标
    protected function checkGcjo2($v)
    {
        return Assist::isGcjo2($v) ? true : false;
    }

    // 校验银行卡
    protected function checkBankCard($card)
    {
        return Assist::luhm($card) ? true : false;
    }

    protected function checkName($name)
    {
        return Assist::userName($name) ? true : false;
    }

    // 校验邮箱
    protected function checkEmail($v)
    {
        return preg_match('/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/u', $v) ? true : false;
    }

    //校验手机号码
    protected function checkMobile($mobile)
    {
        return Assist::mobile($mobile) ? true : false;
    }

    // 校验金额
    protected function checkJe($v)
    {
        return Assist::isJine($v) ? true : false;
    }

    // 校验URL
    protected function checkUrl($v)
    {
        return preg_match('/^http[s]?:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/u', $v) ? true : false;
    }

    // 校验颜色
    protected function checkColor($v)
    {
        return preg_match('/^(#([a-fA-F0-9]{6}|[a-fA-F0-9]{3}))?$/', $v) ? true : false;
    }

}
