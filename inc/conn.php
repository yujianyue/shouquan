<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: inc/conn.php
// 文件大小: 1546 字节
/**
 * 本文件功能: 数据库连接及公共配置
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 数据库连接配置
$db_config = [
    'host' => 'localhost',
    'user' => 'doma2_chalide',
    'pass' => '7CSXkwnAKSWhtYMB',
    'name' => 'doma2_chalide',
    'port' => 3306,
    'charset' => 'utf8mb4'
];

// 系统版本号 - 用于JS和CSS缓存刷新
define('VERSION', '1.0.1');

// 默认管理员账号 未加密存
$default_admin = [
    'chalide' => '<?php exit();?>',
    'username' => 'admin',
    'password' => '123456'
];

// 网站默认设置
$default_site = [  
    'chalide' => '<?php exit();?>',
    'title' => 'PHP7+MySQL5.6 源码授权系统DNS验证版',
    'footer_text' => '© ' . date('Y') . ' DNS验证授权系统',
    'footer_link' => 'https://example.com',
    'captcha' => true
];

// 授权码公共密钥 - 用于生成授权码
define('AUTH_KEY', 'DNSAuthKey20250530');

// 上传文件大小限制 (5MB)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

// 网站菜单配置
$menu_items = [
    ['id' => 'lima', 'name' => '授权列表', 'icon' => 'list'],
    ['id' => 'site', 'name' => '网站设置', 'icon' => 'settings'],
    ['id' => 'pass', 'name' => '修改密码', 'icon' => 'lock']
];

// 错误显示设置
//ini_set('display_errors', 0);
//error_reporting(E_ALL & ~E_NOTICE);

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 会话设置
session_start();

// 数据库连接
require_once 'sqls.php';
$db = new SqlHelper($db_config);
