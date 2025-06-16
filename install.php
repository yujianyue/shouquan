<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: install.php
// 文件大小: 13010 字节

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: install.php
// 文件大小: 12837 字节
/**
 * 本文件功能: 系统安装脚本
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

require_once './inc/conn.php';
require_once './inc/pubs.php';

// 处理安装请求
if (isset($_GET['act']) && $_GET['act'] == 'install') {
    $import_demo = isset($_POST['import_demo']) ? intval($_POST['import_demo']) : 0;
    
    try {
        // 创建授权表
        $db->query("
            CREATE TABLE IF NOT EXISTS `auth_codes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `auth_code` varchar(32) NOT NULL COMMENT '授权码',
              `domain` varchar(64) NOT NULL COMMENT '域名',
              `version` varchar(50) NOT NULL COMMENT '版本代码',
              `mobile` varchar(20) NOT NULL COMMENT '手机号',
              `email` varchar(100) NOT NULL COMMENT '邮箱',
              `status` varchar(20) NOT NULL DEFAULT '待授权' COMMENT '授权状态',
              `created_time` datetime NOT NULL COMMENT '创建时间',
              `last_query_time` datetime NOT NULL COMMENT '最后查询时间',
              PRIMARY KEY (`id`),
              UNIQUE KEY `domain` (`domain`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='授权码表';
        ");
        
        // 导入演示数据
        if ($import_demo) {
            // 准备演示数据
            $domains = [
                'example.com', 'demo.com', 'test.com', 'sample.org', 'mysite.net',
                'yoursite.com', 'coolsite.com', 'newsite.org', 'bestsite.net', 'topsite.com',
                'webdemo.com', 'codesite.org', 'devsite.net', 'appsite.com', 'techsite.org',
                'oursite.net', 'theirsite.com', 'anysite.org', 'allsite.net', 'somesite.com',
                'onesite.org', 'twosite.net', 'threesite.com', 'foursite.org', 'fivesite.net',
                'sixsite.com', 'sevensite.org', 'eightsite.net', 'ninesite.com', 'tensite.org'
            ];
            
            $versions = ['basic', 'standard', 'premium', 'enterprise'];
            $statuses = ['待授权', '未授权', '已授权'];
            
            // 插入演示数据
            for ($i = 0; $i < 30; $i++) {
                $domain = $domains[$i];
                $version = $versions[array_rand($versions)];
                $status = $statuses[array_rand($statuses)];
                $auth_code = generate_auth_code($version, $domain);
                $mobile = '1' . rand(3, 9) . rand(100000000, 999999999);
                $email = 'user' . ($i + 1) . '@example.com';
                
                // 随机日期，1个月内
                $days = rand(0, 30);
                $hours = rand(0, 23);
                $minutes = rand(0, 59);
                $seconds = rand(0, 59);
                $created_time = date('Y-m-d H:i:s', strtotime("-$days days -$hours hours -$minutes minutes -$seconds seconds"));
                
                // 最后查询时间（比创建时间晚）
                $query_days = rand(0, $days);
                $query_hours = rand(0, $hours);
                $query_minutes = rand(0, $minutes);
                $query_seconds = rand(0, $seconds);
                $last_query_time = date('Y-m-d H:i:s', strtotime("-$query_days days -$query_hours hours -$query_minutes minutes -$query_seconds seconds"));
                
                $db->query("
                    INSERT INTO `auth_codes` (`auth_code`, `domain`, `version`, `mobile`, `email`, `status`, `created_time`, `last_query_time`) 
                    VALUES ('$auth_code', '$domain', '$version', '$mobile', '$email', '$status', '$created_time', '$last_query_time')
                ");
            }
        }
       
        json_result(0, '安装成功，请删除安装文件 install.php 以确保系统安全。');
    } catch (Exception $e) {
        json_result(1, '安装失败：' . $e->getMessage());
    }
    
    exit;
}

// 环境检查
$php_version = PHP_VERSION;
$php_version_ok = version_compare($php_version, '7.1.0', '>=');

$mysqli_ok = extension_loaded('mysqli');
$json_ok = extension_loaded('json');

// 尝试连接数据库
$db_connect_ok = true;
try {
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name'], $db_config['port']);
    if ($conn->connect_error) {
        $db_connect_ok = false;
        $db_error = $conn->connect_error;
    }
    $conn->close();
} catch (Exception $e) {
    $db_connect_ok = false;
    $db_error = $e->getMessage();
}

// 检查文件权限
$inc_writable = is_writable('./inc') || @mkdir('./inc', 0755, true);
$adm_writable = is_writable('./adm') || @mkdir('./adm', 0755, true);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装 - PHP7+MySQL5.6 源码授权系统DNS验证版</title>
    <link rel="stylesheet" href="inc/pubs.css?v=<?php echo VERSION; ?>">
    <script src="inc/pubs.js?v=<?php echo VERSION; ?>"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <h1 class="site-title">PHP7+MySQL5.6 源码授权系统DNS验证版 - 安装</h1>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>系统环境检查</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th width="30%">项目</th>
                        <th width="30%">要求</th>
                        <th width="20%">当前</th>
                        <th width="20%">状态</th>
                    </tr>
                    <tr>
                        <td>PHP版本</td>
                        <td>>= 7.1.0</td>
                        <td><?php echo $php_version; ?></td>
                        <td>
                            <?php if ($php_version_ok): ?>
                                <span style="color: green;">通过</span>
                            <?php else: ?>
                                <span style="color: red;">不通过</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>MySQLi扩展</td>
                        <td>开启</td>
                        <td><?php echo $mysqli_ok ? '已开启' : '未开启'; ?></td>
                        <td>
                            <?php if ($mysqli_ok): ?>
                                <span style="color: green;">通过</span>
                            <?php else: ?>
                                <span style="color: red;">不通过</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>JSON扩展</td>
                        <td>开启</td>
                        <td><?php echo $json_ok ? '已开启' : '未开启'; ?></td>
                        <td>
                            <?php if ($json_ok): ?>
                                <span style="color: green;">通过</span>
                            <?php else: ?>
                                <span style="color: red;">不通过</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>数据库连接</td>
                        <td>正常</td>
                        <td><?php echo $db_connect_ok ? '正常' : '失败'; ?></td>
                        <td>
                            <?php if ($db_connect_ok): ?>
                                <span style="color: green;">通过</span>
                            <?php else: ?>
                                <span style="color: red;">不通过</span>
                                <div style="font-size: 12px;"><?php echo $db_error; ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>inc目录权限</td>
                        <td>可写</td>
                        <td><?php echo $inc_writable ? '可写' : '不可写'; ?></td>
                        <td>
                            <?php if ($inc_writable): ?>
                                <span style="color: green;">通过</span>
                            <?php else: ?>
                                <span style="color: red;">不通过</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>adm目录权限</td>
                        <td>可写</td>
                        <td><?php echo $adm_writable ? '可写' : '不可写'; ?></td>
                        <td>
                            <?php if ($adm_writable): ?>
                                <span style="color: green;">通过</span>
                            <?php else: ?>
                                <span style="color: red;">不通过</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php if ($php_version_ok && $mysqli_ok && $json_ok && $db_connect_ok && $inc_writable && $adm_writable): ?>
                    <div style="margin-top: 20px;">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="import_demo" value="1"> 导入演示数据（30条授权记录）
                            </label>
                        </div>
                        <div class="form-group">
                            <button id="install_btn" class="btn btn-primary">开始安装</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 20px; color: red;">
                        环境检查不通过，请修复上述问题后再次尝试安装。
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const installBtn = document.getElementById('install_btn');
            if (installBtn) {
                installBtn.addEventListener('click', function() {
                    const importDemo = document.getElementById('import_demo').checked ? 1 : 0;
                    
                    showLoading();
                    ajax({
                        url: 'install.php?act=install',
                        type: 'POST',
                        data: {
                            import_demo: importDemo
                        },
                        success: function(res) {
                            hideLoading();
                            if (res.code === 0) {
                                showOverlay(
                                    '<p>' + res.msg + '</p>' +
                                    '<p>系统默认管理员账号: admin</p>' +
                                    '<p>系统默认管理员密码: 123456</p>' +
                                    '<p>请及时修改默认密码以确保系统安全。</p>',
                                    '安装成功',
                                    [
                                        {
                                            text: '进入首页',
                                            className: 'btn-primary',
                                            click: function() {
                                                window.location.href = 'index.php';
                                            }
                                        },
                                        {
                                            text: '进入后台',
                                            className: 'btn-success',
                                            click: function() {
                                                window.location.href = 'admin.php';
                                            }
                                        }
                                    ]
                                );
                            } else {
                                showToast(res.msg);
                            }
                        },
                        error: function() {
                            hideLoading();
                            showToast('网络错误，请稍后重试');
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
