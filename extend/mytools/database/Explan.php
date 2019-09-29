<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/7/16
 * Time: 14:40
 */

namespace mytools\database;


class Explan
{
    // 数据库对象
    private $db;
    // 执行的sql语句
    private $sql;

    // 执行计划原始结果
    private $base_result;

    // 执行计划排序后结果
    private $sort_result;

    // 语言包
    private $lang = [
        'select_type' => [
            'SIMPLE'=>'简单查询',
        ],

        'type' => [
            'ALL' => '全表扫描',
        ],

        'Extra' => [
            'Using where' => '在where条件中使用了索引',
            'Using join buffer' => '使用了连表缓存'
        ],
    ];

    // 建议
    private $suggest = [];

    // 连接参数
    private $conf = [
        'host' => '127.0.0.1',
        'database' => 'test',
        'username' => 'root',
        'password' => '123456',
        'charset' => 'utf8'
    ];

    public function __construct()
    {
        try{
            $db = new \PDO("mysql:host={$this->conf['host']};dbname={$this->conf['database']}", $this->conf['username'], $this->conf['password']);
            $db->exec("set names ".$this->conf['charset']);
        }catch (\PDOException $e) {
            die ("Error!: " . $e->getMessage() . "<br/>");
        }
        $this->db = $db;
    }

    // 执行查询计划
    public function explain($sql)
    {
        $this->sql = 'explain ' . $sql;
        $result = $this->db->query($this->sql);
        $this->base_result = $result->fetchAll(\PDO::FETCH_ASSOC);
        $this->sortResult();
    }

    // 排序查询计划结果
    public function sortResult()
    {
        $this->sort_result = [];
        if(!empty($this->base_result)) {
            foreach ($this->base_result as $v) {
                $this->sort_result[$v['id']][] = $v;
            }
        }
        krsort($this->sort_result);
    }

    // 查询参与的表格索引
    public function queryIndex()
    {
        // 从sql语句中找出参与的表格
        //$tables =
    }

}