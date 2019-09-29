<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/7/3
 * Time: 17:53
 */

namespace app\common\exception;

use think\Db;

class ReadException
{
    // 获取数据(放到其他类使用)
    public function readData($id)
    {
        $db = Db::table('my_error_log');
        $data = $db->find($id);
        if(!empty($data['params'])) {
            $data['params'] = json_decode($data['params'],true);
        }
        $data['create_time'] = date('Y-m-d H:i:s',$data['create_time']);
        return $data;
    }
}