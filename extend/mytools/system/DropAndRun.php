<?php
/**
 * Created by PhpStorm.
 * User: 曹珅
 * Date: 2019/6/16
 * Time: 10:34
 */

namespace mytools\system;

class DropAndRun
{
    public function index()
    {
        $i = 1;
        while (true) {
            fwrite(STDOUT,'你'.str_repeat('真的',$i).'要删库并且跑路吗?(y/n)'.PHP_EOL);
            if(trim(fgets(STDIN)) == 'n') {
                fwrite(STDOUT,'删库有风险，跑路需谨慎！悬崖勒马，回头是岸！'.PHP_EOL);
                break;
            }
            $i++;
        }

    }
}