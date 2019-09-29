<?php
/**
 * Created by PhpStorm.
 * User: bzg
 * Date: 2019/6/20
 * Time: 8:47
 */

namespace mytools\lib;


class Authority
{
    // 读取来的原始数据
    private $old_data = [];
    // 组装后的数据
    private $route_data = [];
    // 分组数据
    private $group_data = [];
    // 起始ID
    private $start_id = 10000;
    private $cur_id = 10000;

    public function makeAuthList()
    {
        /*fwrite(STDOUT,"请输入路由目录".PHP_EOL);
        $input = trim(fgets(STDIN));
        $route_path = empty($input) ? ROOT_PATH . '../route/' : $input;*/
        $route_path = str_replace('\\','/',env('root_path')) . 'route/';
        // 引入所有文件1
        $this->fileList($route_path,$this->old_data);
        if(!empty($this->old_data)) {
            // 遍历原数据正则出需要的数据
            foreach ($this->old_data as $v) {
                $re = $this->pregData($v);
                $this->route_data = array_merge($this->route_data,$re['r']);
                $this->group_data = array_merge($this->group_data,$re['g']);
            }
        }
        // 按控制器将所有路由分类
        $controller_route = [];
        if(!empty($this->route_data)){
            foreach ($this->route_data as $v) {
                $path_arr = explode('/',$v['url']);
                $controller_route[$path_arr[1]][] = $v;
            }
            // 添加路由分组 及id
            $index = 1; // 当前为多少组
            foreach ($controller_route as $con => $rou) {
                foreach ($rou as $k => $v) {
                    $controller_route[$con][$k]['pid'] = $this->cur_id;
                    $controller_route[$con][$k]['id'] = $this->cur_id + $k + 1;
                    if(!$v['menu_id']){
                        $controller_route[$con][$k]['menu_id'] = $this->group_data[$con]['menu_id'];
                    }
                }
                array_unshift($controller_route[$con],[
                    'url' => '',
                    'auth_name' => $this->group_data[$con]['name'],
                    'pid' => 0,
                    'id' => $this->cur_id,
                    'menu_id' => $this->group_data[$con]['menu_id'],
                    'is_show' => $this->group_data[$con]['is_show'],
                ]);
                $index++;
                $this->cur_id = $this->start_id * $index;
            }
            // 将数组变为一位数组
            $final_data = [];
            foreach ($controller_route as $v) {
                $final_data = array_merge($final_data,$v);
            }
        }else {
            $final_data = [];
        }
        return $final_data;
    }

    // 遍历文件夹
    public function fileList($dir,&$files)
    {
        //1、首先先读取文件夹
        $temp=scandir($dir);
        //遍历文件夹
        foreach($temp as $v){
            $a=$dir.'/'.$v;
            if(is_dir($a)){//如果是文件夹则执行
                if($v=='.' || $v=='..'){//判断是否为系统隐藏的文件.和..  如果是则跳过否则就继续往下走，防止无限循环再这里。
                    continue;
                }
                $this->fileList($a,$files);//因为是文件夹所以再次调用自己这个函数，把这个文件夹下的文件遍历出来
            }else{
                $files[] = file_get_contents($a);
            }

        }
    }

    // 正则数据
    public function pregData($str)
    {
        $group_data = [];
        $route_data = [];
        // @path:路由  @name:名字
        preg_match_all('/\/\*\*[\S\s]+\*\//U',$str,$zhushi);
        // 在每个备注中正则出路由和名字
        if(!empty($zhushi[0])) {
            foreach ($zhushi[0] as $v) {
                // 名字
                preg_match('/@name[\s\S]+\n/U',$v,$name);
                // 路由
                preg_match('/@path[\s\S]+\n/U',$v,$path);
                // 分组
                preg_match('/@group[\s\S]+\n/U',$v,$group);
                // 是否显示
                preg_match('/@show[\s\S]+\n/U',$v,$show);
                // 菜单id
                preg_match('/@menu[\s\S]+\n/U',$v,$menu);
                if(!empty($group[0])) {
                    $group_data[trim(str_replace('@group','',$group[0]))] = [
                        'name'=>trim(str_replace('@name','',$name[0])),
                        'is_show' => empty($show[0]) ? 1 : trim(str_replace('@show','',$show[0])),
                        'menu_id' => empty($menu[0]) ? 0 : trim(str_replace('@menu','',$menu[0])),
                    ];
                    if(empty($menu[0]))
                        dieReturn($group_data[trim(str_replace('@group','',$group[0]))]['name'].'菜单ID为空');
                }else{
                    $route_data[] = [
                        'url' => trim(str_replace('@path','',$path[0])),
                        'auth_name' => trim(str_replace('@name','',$name[0])),
                        'is_show' => empty($show[0]) ? 1 : trim(str_replace('@show','',$show[0])),
                        'menu_id' => empty($menu[0]) ? 0 : trim(str_replace('@menu','',$menu[0])),
                    ];
                }
            }
        }
        return ['r'=>$route_data,'g'=>$group_data];
    }
}