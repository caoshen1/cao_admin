<?php

namespace fun;


use think\Validate;

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

    public function isNum($num)
    {
        return preg_match('/(^[\-0-9][0-9]*(.[0-9]+)?)$/', $num);
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

    //计算两个坐标间的距离
    public static function calc_distance($gcj02_a, $gcj02_b)
    {
        if (!is_array($gcj02_a)) {
            $gps_a = self::isGcjo2($gcj02_a);
            if (!$gps_a) {
                return null;
            }
        } else {
            $gps_a = $gcj02_a;
        }
        if (!is_array($gcj02_b)) {
            $gps_b = self::isGcjo2($gcj02_b);
            if (!$gps_b) {
                return null;
            }
        } else {
            $gps_b = $gcj02_b;
        }
        return self::nearby_distance($gps_a[0], $gps_a[1], $gps_b[0], $gps_b[1]);
    }

    //计算经纬度两点之间的距离
    private static function nearby_distance($lat1, $lon1, $lat2, $lon2)
    {
        $EARTH_RADIUS = 6372.797;
        $radLat1 = self::rad($lat1);
        $radLat2 = self::rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = self::rad($lon1) - self::rad($lon2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s1 = $s * $EARTH_RADIUS;
        $s2 = round($s1, 2);
        return $s2;
    }

    /**
     * 根据经纬度和半径计算出范围
     * @param string $lat 纬度
     * @param String $lng 经度
     * @param float $radius 半径
     * @return array 范围数组
     */
    public static function calcScope($lat, $lng, $radius)
    {
        $degree = (24901 * 1609) / 360.0;
        $dpmLat = 1 / $degree;

        $radiusLat = $dpmLat * $radius;
        $minLat = $lat - $radiusLat;    // 最小纬度
        $maxLat = $lat + $radiusLat;    // 最大纬度

        $mpdLng = $degree * cos($lat * (3.1415926 / 180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng * $radius;
        $minLng = $lng - $radiusLng;   // 最小经度
        $maxLng = $lng + $radiusLng;   // 最大经度

        /** 返回范围数组 */
        $scope = array(
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLng' => $minLng,
            'maxLng' => $maxLng
        );
        return $scope;
    }

    private static function rad($d)
    {
        return $d * 3.1415926535898 / 180.0;
    }

    /**
     * 判断是否是火星坐标，将字符串坐标转为数组
     * @param string $val 火星坐标
     * @return array|bool
     */
    public static function isGcjo2(string $val)
    {
        if (!preg_match('/^\d{1,3}\.\d+,\d{2,3}\.\d+$/', $val)) {
            return false;
        }
        $gps = explode(',', $val);
        //超出中国范围
        if ($gps[0] < 72.004 || $gps[0] > 137.8347) {
            return false;
        }
        if ($gps[1] < 0.8293 || $gps[1] > 55.8271) {
            return false;
        }
        return $gps;
    }

    /**
     * 调用TP自带验证方法验证数据
     * @param $key
     * @param $value
     * @param $rule
     * @return bool
     */
    public static function checkByRule($key, $value, $rule)
    {
        return Validate::make()
                        ->rule($key, $rule)
                        ->check([$key => $value]);
    }

}
