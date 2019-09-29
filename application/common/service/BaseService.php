<?php

namespace app\common\service;

// 基础服务
use app\common\exception\CustomException;
use app\common\validate\BaseValidate;
use fun\Assist;
use fun\GlobalParam;
use think\Exception;
use think\Model;
use think\Request;
use think\Validate;

class BaseService
{
    /**
     * 获取列表 (已废弃)
     * @param array $param  条件数组[where条件,order排序,offset每页记录数,field字段,offset每页记录数,join连表]
     * @param string $model  查询模型
     * @param boolean $is_all  是否查询全部
     * @return array 返回结果
     * @throws \think\exception\DbException
     */
    public static function dataList(array $param, string $model, $is_all = false)
    {
        /*$re = BaseService::dataList([  // 使用示例
            'as' => 'u',
            'join' => [
                [['my_user_identity'=> 'i'],'u.user_id = i.user_id'], // 一对一关联
                [[UserAddress::class=> 'address'],['user_id','user_id',['field'=>'id,name,tel']],'om'], // 一对多关联 [[模型=>别名],[模型外键，主模型主键，[其他条件]]]
            ],
            'where' => [
                //['u.user_id','=',77]
            ],
            'field' => 'u.user_id,headImages,realname'
        ],User::class,true)['data'];*/
        $w = empty($param['where']) ? [] : $param['where'];
        $o = empty($param['order']) ? '' : $param['order'];
        $s = empty($param['offset']) ? config('conf.default_limit') : $param['offset'];
        $f = empty($param['field']) ? '*' : $param['field'];
        $l = empty($param['limit']) ? 999 : $param['limit'];
        // 连表查询一对一
        if(!empty($param['join'])) {
            $make_query = (new $model);
            if(!empty($param['as']))
                $make_query = $make_query->alias($param['as']);
            foreach ($param['join'] as $k => $v) {
                $v[2] = empty($v[2]) ? 'oo' : $v[2];
                if($v[2] == 'oo') { // 一对一关联
                    $make_query = $make_query->leftJoin($v[0],$v[1]);
                }
            }
        }else {
            $make_query = new $model;
        }
        $list = $make_query->where($w)->field($f)->order($o);
        // 是否分页
        if($is_all) {
            $list = $list->limit($l)->select();
        }else {
            $p = empty($param['page']) ? 1 : $param['page'];
            $pp['page'] = $p;
            // 分页额外参数
            if(!empty($param['query'])) {
                $pp['query'] = $param['query'];
            }
            $list = $list->paginate($s,false,$pp)->toArray();
        }
        // 连表查询一对多
        if(!empty($param['join'])) {
            foreach ($param['join'] as $v) {
                if(isset($v[2]) && $v[2] == 'om') { // 一对多关联  [[模型=>别名],[模型外键，主模型主键，[其他条件]]]
                    reset($v[0]);
                    $jm = key($v[0]);
                    if(!isset($v[1][2])) $v[1][2] = [];
                    $ww = empty($v[1][2]['where']) ? [] : $v[1][2]['where'];
                    $oo = empty($v[1][2]['order']) ? '' : $v[1][2]['order'];
                    $ff = empty($v[1][2]['field']) ? '*' : $v[1][2]['field'];
                    $ll = empty($v[1][2]['limit']) ? 999 : $v[1][2]['limit'];
                    if($is_all) {
                        foreach ($list as $kk => $vv) {
                            $list[$kk][$v[0][$jm]] = $jm::where($v[1][0],$vv[$v[1][1]])
                                ->field($ff)
                                ->where($ww)
                                ->order($oo)
                                ->limit($ll)
                                ->select();
                        }
                    }else{
                        foreach ($list['data'] as $kk => $vv) {
                            $list['data'][$kk][$v[0][$jm]] = $jm::where($v[1][0],$vv[$v[1][1]])
                                ->field($ff)
                                ->where($ww)
                                ->order($oo)
                                ->limit($ll)
                                ->select();
                        }
                    }
                }
            }
        }

        return queryStatus(1,$list);
    }

    /**
     * 获取数据列表，按需实现
     * @param $param
     */
    public static function getList($param) {}

    /**
     * 构造条件列表
     * @param array $request 请求参数数组
     * @param array $field 条件字段数组
     *  [
     *      [
     *          数据库字段名,
     *          对比条件,
     *          前端传来的键名 || 当对比条件为bt时： [[开始键名，结束键名]||开始键名，验证规则] || 开始键名
     *      ]
     *  ]
     * @param string $as 表别名
     * @return array 条件数组
     */
    public static function makeWhere(array $request,array $field, string $as = '')
    {
        $w = [];
        if(!empty($field) && !empty($request)) {
            foreach ($field as $v) {

                // 兼容【表别名.字段名写法】
                $field_arr = explode('.',$v[0]);
                if(count($field_arr) == 2) {
                    $v[0] = $field_arr[1];
                    $as = $field_arr[0];
                }
                // 判断前端给的键名
                $key = empty($v[2]) ? $v[0] : $v[2];

                if (!empty($request[$key]) || $v[1] == 'like') {
                    // 将字段名组装为 表明.字段名
                    if ($as) $f = $as . '.' . $v[0];
                    else $f = $v[0];

                    // 相等字段
                    if(empty($v[1]) || $v[1] == '=') {
                        $v[1] = empty($v[1]) ? '=' : $v[1];
                        $w[] = [$f, $v[1], $request[$key]];
                    }

                    // like字段
                    if ($v[1] == 'like'){
                        $key = empty($v[2]) ? 'key' : $v[2];
                        if (!empty($request[$key])) {
                            $w[] = [$v[0], $v[1], '%' . $request[$key] . '%'];
                        }
                    }

                    // 两者之间字段 默认为start_time和end_time，规则为Y-m-d
                    if($v[1] == 'bt') {
                        // 给默认值
                        if(empty($v[2])) {
                            $v[2] = [['start_time','end_time'],'Y-m-d'];
                        }
                        // 解析规则
                        if(is_array($v[2])) {
                            // 第一个元素是数组
                            if(is_array($v[2][0])) {
                                // 验证数据
                                if(!empty($request[$v[2][1]])) {
                                    foreach ($request[$v[2][1]] as $vvv) {
                                        if(!(new Validate())->dateFormat($vvv,$request[$v[2][1]]))
                                            continue;
                                    }
                                }
                                $w[] = [$f,'>=',$request[$v[2][0][0]]];
                                $w[] = [$f,'<',$request[$v[2][0][1]]];
                                continue;
                            }elseif(is_string($v[2][0])){
                                // 验证数据
                                if(!empty($request[$v[2][1]])) {
                                    if(!(new Validate())->dateFormat($request[$v[2][0]],$request[$v[2][1]]))
                                        continue;
                                }
                                $w[] = [$f,'>',$request[$v[2][0]]];
                            }else{
                                continue;
                            }
                        }elseif(is_string($v[2])){
                            $w[] = [$f,'>=',$request[$v[2]]];
                        }else{
                            continue;
                        }
                    }
                }
            }
        }
        return $w;
    }

    /**
     * 新增修改
     * @param array $param 数据参数
     * @param string $model 操作模型
     * @param string $validate 验证器名
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return array
     * @throws CustomException
     */
    public static function saveData(array $param, string $model, $validate = '', $u = [])
    {
        // 校验数据
        $pk = (new $model)->getPk();
        if(!empty($validate)) {
            if(empty($param[$pk]))
                BaseValidate::check($validate,$param);
            else
                BaseValidate::check($validate,$param,'edit');
        }
        // 保存入库
        if(empty($param[$pk])){
            $re = $model::create($param);
        } else {
            if(!empty($u)) {
                self::check($model,$param[$pk],$u);
            }
            $re = $model::update($param);
        }


        if($re) return queryStatus(1,$re);
        throw new CustomException('操作失败');
    }

    /**
     * 修改状态
     * @param int $id 记录ID
     * @param string $model 操作模型
     * @param string|array $field 修改的字段 (为字符串则是修改的字段名，默认1和2切换，为数组则指定字段指定值['字段'=>值,])
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return array
     * @throws CustomException
     */
    public static function saveStatus($id, string $model,$field = 'status', $u = [])
    {
        if(!$id) throw new CustomException('请传入记录ID');
        if(!$row = $model::find($id)) throw new CustomException('未查询到该条记录');
        if(!empty($u)) {
            self::check($model,$id,$u);
        }
        if(is_array($field)) { // 自定义状态值
            foreach ($field as $k=>$v) {
                $row[$k] = $v;
            }
        }else{
            $row[$field] = $row[$field] == 1 ? 2 : 1;
        }
        if($row->save()) return queryStatus(1);
        throw new CustomException('修改失败');
    }

    /**
     * 删除数据
     * @param int|array $id  记录ID或者数组
     * @param string $model 操作模型
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return array
     * @throws CustomException
     */
    public static function deleteData($id,string $model, $u = [])
    {
        if(empty($id)) throw new CustomException('请传入记录ID');
        if(!empty($u)) {
            if (is_array($id)) {
                foreach ($id as $d) {
                    self::check($model, $d, $u);
                }
            }else{
                self::check($model,$id,$u);
            }
        }
        $model::destroy($id);
        return queryStatus(1);
    }


    /**
     * 获取详情
     * @param $id int 主键ID
     * @param string $model 操作模型
     * @param string $field 字段
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return array
     * @throws CustomException
     */
    public static function rowDetail($id,string $model,$field = '*', $u = [])
    {
        if(empty($id)) dieReturn('请传入记录ID');
        if(!$detail = $model::field($field)->find($id)) throw new CustomException('未查询到该条记录');
        if(!empty($u)) {
            foreach ($u as $k => $v) {
                if($v != $detail[$k]) {
                    throw new CustomException('操作非法');
                }
            }
        }
        return queryStatus(1,$detail);
    }

    /**
     * 获取请求参数并验证
     * @param array $param
     *      [
     *          变量名，
     *          变量名 => 默认值，
     *          变量名|备注 => [默认值,验证规则|模型名,错误提示]， // 为模型名则为验证该主键是否在模型中存在
     *      ]
     * @return array
     * @throws CustomException
     */
    public static function requestParams(array $param)
    {
        $request = request();
        $arr = [];
        foreach ($param as $k => $v) {
            if(is_int($k)) { // 只有值
                $arr[$v] = $request->param($v);
            }else { // 键值对
                if (!is_array($v)) { // 值直接为默认值
                    $arr[$k] = empty($request->param($k)) ? $v : $request->param($k);
                }else{ // 值为数组，需要验证
                    // 取变量名和备注
                    $pnr = explode('|',$k);
                    $pn = $pnr[0]; // 变量名
                    $pr = $pnr[1] ?? ''; // 备注
                    $value = $request->param($pn); // 值
                    $msg = $pr . '验证失败！';
                    if(!empty($v[0])) {
                        $value = $value ? $value : $v;
                    }
                    if(!empty($v[1])) { // 有验证规则
                        if(class_exists($v[1]) && (new $v[1] instanceof Model)) { // 如果是模型对象则验证主键是否存在
                            if(empty($value) || !preg_match('/^\d+$/',$value) || !$cur_model = $v[1]::find($value)){
                                throw new CustomException(empty($v[2]) ? $msg : $v[2]);
                            }
                            GlobalParam::set($pn.'_model',$cur_model); // 将查询到的模型对象放入全局变量，方便在控制器中调用
                        } else if(is_string($v[1])) { // 使用TP内置规则验证
                            if(!Assist::checkByRule($pn,$value,$v[1])) {
                                throw new CustomException(empty($v[2]) ? $msg : $v[2]);
                            }
                        } else if(is_array($v[1])) { // 如果是数组，则调用类的方法验证 第一个元素为类，第二个元素为方法
                            if(class_exists($v[1][0])) {
                                $fun = $v[1][1] ? $v[1][1] : 'index';
                                if(!(new $v[1][0])->$fun($value)) {
                                    throw new CustomException(empty($v[2]) ? $msg : $v[2]);
                                }
                            }
                            throw new CustomException('验证类没有找到');
                        }else {
                            throw new CustomException('验证规则错误');
                        }
                    }
                    $arr[$pn] = $value;
                }
            }
        }
        return $arr;
    }

    /**
     * 验证修改是否非法
     * @param $model
     * @param $pk
     * @param $u
     * @throws CustomException
     */
    private static function check($model, $pk, $u)
    {
        $old = $model::find($pk);
        if(!$old) throw new CustomException('操作的数据不存在');
        foreach ($u as $k => $v) {
            if(is_array($v)) {
                if(!in_array($old[$k],$v)) {
                    throw new CustomException('操作非法');
                }
            }else{
                if($v != $old[$k]) {
                    throw new CustomException('操作非法');
                }
            }
        }
    }


}