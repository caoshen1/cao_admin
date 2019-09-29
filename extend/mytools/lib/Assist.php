<?php

namespace mytools\lib;

class Assist
{
    //校验身份证号码
    public static function isIdcard($idcard)
    {
        return preg_match('/^[1-9]\d{5}[1-9]\d{3}(((0[13578]|1[02])(0[1-9]|[12]\d|3[0-1]))|((0[469]|11)(0[1-9]|[12]\d|30))|(02(0[1-9]|[12]\d)))(\d{4}|\d{3}[xX])$/', $idcard);
    }

    //校验手机号码
    public static function mobile($mobile)
    {
        return preg_match('/^1[3456789][\d]{9}$/', $mobile);
    }

    //校验姓名
    public static function userName($name)
    {
        return preg_match('/^[\x{4e00}-\x{9fa5}]{2,20}$/u', $name);
    }

    //校验支付密码
    public static function isPayPassword($password)
    {
        return preg_match('/^\d{6}$/', $password);
    }


    //银行卡luhm校验
    public static function luhm($s)
    {
        $arr_no = str_split($s);
        $last_n = $arr_no[count($arr_no) - 1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n) {
            if ($i % 2 == 0) {
                $ix = $n * 2;
                if ($ix >= 10) {
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                } else {
                    $total += $ix;
                }
            } else {
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $total *= 9;
        if ($last_n == ($total % 10)) {
            return true;
        } else {
            return false;
        }
    }

    //截取银行卡bin
    public static function isBankAps($bankbin)
    {
        $length = strlen($bankbin);
        if ($length == 7) {
            return substr($bankbin, 0, 3);
        } else {
            return substr($bankbin, 1, 3);
        }
    }

    //校验金额
    public static function isJine($jine)
    {
        return preg_match('/^(([1-9][0-9]*)|(([0]\.\d{1,2}|[1-9][0-9]*\.\d{1,2})))$/', $jine);
    }

}
