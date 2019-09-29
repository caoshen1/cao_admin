# 氧化钙-admin

#### 介绍
基于TP5.1和H+UI的简单的快开框架

#### 软件架构
application/admin为后台模块
application/setup为安装模块
基于注解的权限管理


#### 安装教程

1. 克隆并调试好本地环境
2. 修改config目录下的database文件，填入正确的数据库参数
3. 浏览器访问 域名/admin/index即可进入安装页面

#### 使用说明

1. 在application/admin/config/jin_admin.php文件中 module为dev且为超级管理员账号登录时，有快速开发菜单
2. BaseService服务类可干基础的活
