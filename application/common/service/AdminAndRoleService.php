<?php

namespace app\common\service;

use app\common\exception\CustomException;
use app\common\model\AdminMenuList;
use app\common\model\AdminRole;

class AdminAndRoleService extends BaseService
{
    /**
     * 根据角色获取权限列表和菜单列表
     * @param array $role
     * @return array
     * @throws CustomException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAuthByRole(array $role) : array
    {
        if(in_array(1001,$role)) {
            $list = AuthorityService::getMenuList();
            return [
                'auth_list' => array_keys($list),
                //'menu_list' => array_unique($list),
                'menu_list' => AdminMenuList::column('id'),
            ];
        }
        $list = AdminRole::where('id','in',$role)
            ->where('status',1)
            ->field('menu_list,auth_list')
            ->select();
        if($list->isEmpty()) {
            throw new CustomException('角色的权限为空');
        }
        $auth_list_merge = [];
        $menu_list_merge = [];
        foreach ($list as $item) {
            $auth_list_merge = array_merge($auth_list_merge,$item->auth_list);
            $menu_list_merge = array_merge($menu_list_merge,$item->menu_list);
        }
        return [
            'auth_list' => array_unique($auth_list_merge),
            'menu_list' => array_unique($menu_list_merge),
        ];
    }

    /**
     * 根据权限列表获取菜单列表
     * @param array $auth
     * @return array
     * @throws CustomException
     */
    public static function getMenuByAuth(array $auth) : array
    {
        $map = AuthorityService::getMenuList();

        $menu = [];

        foreach ($auth as $v) {
            if(!in_array($map[$v],$menu)) {
                $menu[] = $map[$v];
            }
        }
        return $menu;
    }


    /**
     * 将角色ID转为角色字符串
     * @param array $roles 角色列表
     * @param array $role_ids 角色ID
     * @return string
     */
    public static function roleId2Str(array $roles, array $role_ids)
    {
        if(!$role_ids) return '';
        $str = '';
        foreach ($role_ids as $role) {
            $str .= $roles[$role] . ',';
        }
        return rtrim($str,',');
    }

}
