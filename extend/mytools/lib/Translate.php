<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/7/19
 * Time: 11:42
 */

namespace mytools\lib;


class Translate
{
    const API_URL = 'http://fanyi.youdao.com/translate?&doctype=json&type=AUTO&i=';

    public function getName()
    {
        while (true) {
            fwrite(STDOUT,"请输入请输入变量名：");
            $str = trim(fgets(STDIN));
            if(empty($str)) {
                continue;
            }
            if($str == 'exit') {
                break;
            }
            $result = $this->sendGet($str);
            if(!empty($result['translateResult'][0][0]['tgt'])) {
                $re_str = $result['translateResult'][0][0]['tgt'];
                $param_name = '';
                $param_name .= '大驼峰：'. $this->bigTF($re_str) . PHP_EOL;
                $param_name .= '小驼峰：'. $this->minTF($re_str) . PHP_EOL;
                $param_name .= '下划线：'. $this->botLine($re_str) . PHP_EOL;
                fwrite(STDOUT, $param_name);
            }else{
                fwrite(STDOUT, 'NULL');
            }
        }
    }

    public function sendGet($data)
    {
        return json_decode(trim(file_get_contents(self::API_URL.$data)),true);
    }

    // 转大驼峰
    public function bigTF($str)
    {
        $str2arr = explode(' ',$str);
        $s = '';
        foreach ($str2arr as $v) {
            $s .= ucfirst($v);
        }
        return $s;
    }

    // 转小驼峰
    public function minTF($str)
    {
        $str2arr = explode(' ',$str);
        $s = '';
        foreach ($str2arr as $k => $v) {
            if($k == 0) {
                $s .= strtolower($v);
            }else{
                $s .= ucfirst($v);
            }
        }
        return $s;
    }

    // 转下划线
    public function botLine($str)
    {
        $str2arr = explode(' ',$str);
        $s = '';
        foreach ($str2arr as $k => $v) {
            if($k == 0) {
                $s .= strtolower($v);
            }else{
                $s .= '_'.strtolower($v);
            }
        }
        return $s;
    }
}