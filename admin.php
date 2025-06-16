<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: admin.php
// 文件大小: 848 字节

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: admin.php
// 文件大小: 679 字节
/**
 * 本文件功能: 管理后台统一入口
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

require_once './inc/conn.php';
require_once './inc/pubs.php';

// 获取要执行的操作
$do = get_param('do', 'lima');

// 不需要登录验证的操作
$no_login_actions = ['login', 'lgout'];

// 验证登录状态
if (!in_array($do, $no_login_actions)) {
    check_admin_login();
}

// 包含对应的处理文件
$file_path = './adm/' . $do . '.php';
if (file_exists($file_path)) {
    require_once $file_path;
} else {
    echo '<script>alert("页面不存在");window.location.href="admin.php?do=lima";</script>';
    exit;
}
?>
