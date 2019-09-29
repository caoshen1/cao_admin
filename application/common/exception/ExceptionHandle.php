<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/7/3
 * Time: 15:22
 */

namespace app\common\exception;

use Exception;
use think\Db;
use think\exception\Handle;

class ExceptionHandle extends Handle
{
    public function render(Exception $e)
    {
        // 参数验证错误
        if ($e instanceof CustomException) {
            $msg = $e->getMessage();

            if (config('error.show') === true) {
                // 不存在表则新建表
                if (!Db::query("show tables like 'my_error_log'")) {
                    $this->createTable();
                }
                $id = $this->writeData(config('error.code'), $e->getMessage(), $e->getFile(), $e->getLine(),cache('error_log_extend'));
                cache('error_log_extend',null);
                $msg .= '#' . $id;
            }
            $error['msg'] = $msg;
            $error['code'] = config('error.code');
            return json($error);
        }

        // 其他错误交给系统处理
        return parent::render($e);
    }

    // 生成数据表
    private function createTable()
    {
        $sql = <<<doc
        CREATE TABLE `my_error_log` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `code` int(11) NOT NULL DEFAULT '0' COMMENT '错误码',
          `file` varchar(255) NOT NULL DEFAULT '' COMMENT '错误文件',
          `msg` varchar(255) NOT NULL DEFAULT '' COMMENT '错误信息',
          `line` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '错误行号',
          `params` text COMMENT '请求参数',
          `extend` text COMMENT '辅助数据',
          `create_time` int(10) unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
doc;
        return Db::query($sql);
    }

    // 写入数据
    private function writeData($code, $msg, $file, $line,$extend)
    {
        $db = Db::table('my_error_log');
        $row = $db->count('*');
        if ($row > 5000) {
            $db->limit(2500)->delete();
        }
        $params = request()->param();
        $this->PrintfData($params);
        $data = [
            'code' => $code,
            'file' => $file,
            'msg' => $msg,
            'line' => $line,
            'params' => json_encode($params),
            'extend' => json_encode($extend),
            'create_time' => time()
        ];
        return $db->insertGetId($data);
    }

    private function PrintfData(&$data)
    {
        if (is_array($data)) {
            foreach ($data as $v) {
                $this->PrintfData($v);
            }
        } else {
            return mb_substr($data, 0, 255, 'utf-8');
        }
    }

}