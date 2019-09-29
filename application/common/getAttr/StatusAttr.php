<?php
/**
 * Created by PhpStorm.
 * User: cs
 * Date: 2019/4/29
 * Time: 9:47
 */

namespace app\common\getAttr;

/**
 * Trait IsEnableAttr  状态获取器
 * @package app\common\getAttr
 */
Trait StatusAttr
{
    /**
     * 状态数组
     * @var array
     */
    protected $status_array = [
        1 => '启用',
        2 => '禁用',
    ];

    public function getStatusAttr($v)
    {
        return $this->status_array[(int)$v];
    }
}