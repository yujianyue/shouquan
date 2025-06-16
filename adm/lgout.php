<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: adm/lgout.php
// 文件大小: 256 字节
/**
 * 本文件功能: 管理员退出
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 清除会话
session_unset();
session_destroy();

// 跳转到登录页
header('Location: admin.php?do=login');
exit;
?>
