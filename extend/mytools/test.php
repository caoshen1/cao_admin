<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/6/15
 * Time: 16:33
 */

namespace mytools;


class test
{
    private $a = 0;
    private $b = 0;
    private $m = '';

    public function index()
    {
        fwrite(STDOUT,"请输入第一个数字".PHP_EOL);
        $this->a = trim(fgets(STDIN));
        fwrite(STDOUT,"请输入第二个数字".PHP_EOL);
        $this->b = trim(fgets(STDIN));
        fwrite(STDOUT,"请输入计算符号".PHP_EOL);
        $this->m = trim(fgets(STDIN));
        $str = '计算结果为：';
        switch ($this->m) {
            case "+":
                return $str . ($this->a + $this->b);
            case '-':
                return $str . ($this->a - $this->b);
            case '*':
                return $str . ($this->a * $this->b);
            case '/':
                return $str . ($this->a / $this->b);
        }
    }

    public function test()
    {
        for($i = 0; $i <= 10; $i++) {
            $str = '['.str_repeat('=',$i) . '>'.str_repeat(' ',10 - $i) . ']';
            fwrite(STDOUT,$str.PHP_EOL);
            sleep(1);
        }
    }
}