<?php
/**
 * Created by PhpStorm.
 * User: 曹珅
 * Date: 2019/6/16
 * Time: 10:34
 */

namespace mytools\system;


class System
{
    private $currentOs = '';

    public function __construct()
    {
        $this->currentOs = getOS();
    }

    // 获取当前环境变量
    public function getOsPath()
    {
        if($this->currentOs == 'win'){
            $str = 'set path';
        }else{
            $str = 'echo $PATH';
        }
        return shell_exec($str);
    }

    // 判断当前环境变量中是否存在工具路径
    public function hasMe()
    {
        $path = $this->getOsPath();
        if(preg_match('/'.str_replace('/','\\/',ROOT_PATH).'/',$path)) {
            return true;
        }
        return false;
    }

    // 将工具路径加入当前系统变量
    public function addOsPath()
    {
        if($this->currentOs == 'win'){
            $str = ['set path=%path%;'.rtrim(ROOT_PATH,'/')];
        }else{
            $str = [
                'export PATH="'.rtrim(ROOT_PATH,'/').':$PATH" >> /etc/profile',
                'source /etc/profile',
                ];
        }
        $flag = true;
        foreach ($str as $v) {
            $re = shell_exec($v);
            if($re) $flag = false;
        };
        return $flag;
    }

    public function seeYouOnce()
    {
        if(!$this->hasMe()){
            fwrite(STDOUT,"现在将我添加到系统变量，方便以后在任意位置召唤我吗？(y/n):".PHP_EOL);
            if(trim(fgets(STDIN)) == 'y') {
                $re = $this->addOsPath();
                $msg = $re ? '添加成功！我是加了也没用系列' : '添加失败！你可以尝试手动添加一下:(';
                fwrite(STDOUT,$msg.PHP_EOL);
            }
        }
    }

    public function setCharset()
    {
        shell_exec('chcp 65001');
    }
}