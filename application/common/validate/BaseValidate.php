<?php

namespace app\common\validate;


class BaseValidate
{
    public static function check($valiname,$data,$scene = false)
    {
        // 实例化验证器对象验证
        $vali_obj = new $valiname;
        if($scene){
            $re = $vali_obj->scene($scene)->check($data);
        }else{
            $re = $vali_obj->check($data);
        }
        if(!$re){
            json(['msg'=>$vali_obj->getError(),'code'=>0])->send();
            exit;
        }
    }

}
