<?php

namespace mytools\lib;

// 基础服务
use think\Model;
use think\Request;
use think\Validate;

class BaseService
{
    /**
     * 获取列表
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
        $s = empty($param['offset']) ? 10 : $param['offset'];
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
            $list = $list->paginate($s,false,['page'=>$p])->toArray();
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
     * 构造条件列表
     * @param array $request 请求参数数组
     * @param array $field 条件字段数组 [ [field=>字段名，type=>对比条件] ]
     * @param string $as 表别名
     * @return array 条件数组
     */
    public static function makeWhere(array $request,array $field, string $as = '')
    {
        $w = [];
        if(!empty($field) && !empty($request)) {
            foreach ($field as $v) {
                if (!empty($request[$v[0]])) {
                    if ($as) $f = $as . '.' . $v[0];
                    else $f = $v[0];
                    $w[] = [$f, $v[1], $request[$v[0]]];
                }
                if ($v[1] == 'like'){
                    if (!empty($request['key'])) {
                         $w[] = [$v[0], $v[1], '%' . $request['key'] . '%'];
                    }
                }
                if($v[1] == 'bt') {
                    if ($as) $f = $as . '.' . $v[0];
                    else $f = $v[0];
                    $rule = empty($v[2]) ? 'Y-m-d' : $v[2];
                    if(!empty($request['start_time']) && (new Validate())->dateFormat($request['start_time'],$rule))
                        $w[] = [$f,'>',strtotime($request['start_time'])];
                    if(!empty($request['end_time']) && (new Validate())->dateFormat($request['end_time'],$rule))
                        $w[] = [$f,'<',strtotime($request['end_time'])];
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
     * @param boolean $check 是否编辑验证
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return array
     */
    public static function saveData(array $param, string $model, $validate = '', $check = false, $u = [])
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
            if($check && !empty($u)) {
                self::check($model,$param[$pk],$u);
            }
            $re = $model::update($param);
        }


        return $re ? queryStatus(1) : dieReturn('操作失败');
    }

    /**
     * @param int $id 记录ID
     * @param string $model 操作模型
     * @param string|array $field 修改的字段 (为字符串则是修改的字段名，默认1和2切换，为数组则指定字段指定值['字段'=>值,])
     * @param boolean $check 是否编辑验证
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return array
     */
    public static function saveStatus($id, string $model,$field = 'status', $check = false, $u = [])
    {
        if(!$id) dieReturn('请传入记录ID');
        if(!$row = $model::find($id)) dieReturn('未查询到该条记录');
        if($check && !empty($u)) {
            self::check($model,$id,$u);
        }
        if(is_array($field)) { // 自定义状态值
            foreach ($field as $k=>$v) {
                $row[$k] = $v;
            }
        }else{
            $row[$field] = $row[$field] == 1 ? 2 : 1;
        }
        return $row->save() ? queryStatus(1) : dieReturn('修改失败');
    }

    /**
     * 删除数据
     * @param int|array $id  记录ID或者数组
     * @param string $model 操作模型
     * @param boolean $check 是否编辑验证
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return array
     */
    public static function deleteData($id,string $model, $check = false, $u = [])
    {
        if(empty($id)) dieReturn('请传入记录ID');
        if($check && !empty($u)) {
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
     * @param boolean $check 是否编辑验证
     * @param array $u 验证条件 ['需验证的字段'=>'值',]
     * @return array
     */
    public static function rowDetail($id,string $model,$field = '*', $check = false, $u = [])
    {
        if(empty($id)) dieReturn('请传入记录ID');
        if(!$detail = $model::field($field)->find($id)) dieReturn('未查询到该条记录');
        if($check && !empty($u)) {
            foreach ($u as $k => $v) {
                if($v != $detail[$k]) {
                    dieReturn('操作非法');
                }
            }
        }
        return queryStatus(1,$detail);
    }

    // 处理请求参数
    public static function requestParams(Request $request,array $param)
    {
        $arr = [];
        foreach ($param as $k => $v) {
            if(is_int($k)) {
                $arr[$v] = $request->param($v);
            }else {
                $arr[$k] = empty($request->param($k)) ? $v : $request->param($k);
            }
        }
        return $arr;
    }

    // 验证修改是否非法
    private static function check($model, $pk, $u)
    {
        $old = $model::find($pk);
        if(!$old) dieReturn('操作的数据不存在');
        foreach ($u as $k => $v) {
            if($v != $old[$k]) {
                dieReturn('操作非法');
            }
        }
    }
}