<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/8/23
 * Time: 9:46
 */

namespace app\common\getAttr;


trait StartEndTimeAttr
{
    /**
     * @var string 格式化规则
     */
    protected $time_format = 'Y-m-d H:i:s';

    /**
     * 开始时间修改器
     * @param string $str
     * @return false|int
     */
    public function setStartTimeAttr(string $str)
    {
        return strtotime($str);
    }

    /**
     * 结束时间修改器
     * @param string $str
     * @return false|int
     */
    public function setEndTimeAttr(string $str)
    {
        return strtotime($str . ' 23:59:59');
    }

    /**
     * 开始时间获取器
     * @param int $time
     * @return false|string
     */
    public function getStartTimeAttr(int $time)
    {
        return date($this->time_format,$time);
    }

    /**
     * 结束时间获取器
     * @param int $time
     * @return false|string
     */
    public function getEndTimeAttr(int $time)
    {
        return date($this->time_format,$time);
    }

}