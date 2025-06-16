<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: adm/login.php
// 文件大小: 4598 字节
/**
 * 本文件功能: 管理员登录
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理登录请求
if (isset($_GET['act']) && $_GET['act'] == 'login') {
    $username = post_param('username');
    $password = post_param('password');
    
    if (empty($username) || empty($password)) {
        json_result(1, '请输入用户名和密码');
    }
    
    // 读取账号配置
    $account_file = './inc/mima.php';
    if (!file_exists($account_file)) {
        // 创建默认账号文件
        $accounts = [$default_admin];
        write_json_file($account_file, $accounts);
    } else {
        $accounts = read_json_file($account_file);
    }
    
    // 验证账号密码
    $login_success = false;
    foreach ($accounts as $account) {
        if ($account['username'] == $username && $account['password'] == $password) {
            $login_success = true;
            break;
        }
    }
    
    if ($login_success) {
        $_SESSION['admin_login'] = true;
        $_SESSION['admin_username'] = $username;
        json_result(0, '登录成功');
    } else {
        json_result(1, '用户名或密码错误');
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - 源码授权系统</title>
    <link rel="stylesheet" href="inc/pubs.css?v=<?php echo VERSION; ?>">
    <script src="inc/pubs.js?v=<?php echo VERSION; ?>"></script>
    <style>
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .login-title {
            margin-bottom: 20px;
            text-align: center;
            font-size: 24px;
        }
        .login-btn {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">管理员登录</h2>
        <form id="login_form">
            <div class="form-group">
                <label class="form-label">用户名</label>
                <input type="text" name="username" class="form-control" placeholder="请输入用户名">
            </div>
            <div class="form-group">
                <label class="form-label">密码</label>
                <input type="password" name="password" class="form-control" placeholder="请输入密码">
            </div>
            <div class="form-group">
                <button type="button" id="login_btn" class="btn btn-primary login-btn">登录</button>
            </div>
            <div class="form-group" style="text-align: center; margin-top: 20px;">
                <a href="index.php">返回首页</a>
            </div>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('login_btn').addEventListener('click', function() {
                const formData = getFormValues('login_form');
                
                if (!formData.username) {
                    showToast('请输入用户名');
                    return;
                }
                
                if (!formData.password) {
                    showToast('请输入密码');
                    return;
                }
                
                showLoading();
                ajax({
                    url: 'admin.php?do=login&act=login',
                    type: 'POST',
                    data: formData,
                    success: function(res) {
                        hideLoading();
                        if (res.code === 0) {
                            showToast('登录成功');
                            setTimeout(function() {
                                window.location.href = 'admin.php?do=lima';
                            }, 1000);
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
        });
    </script>
</body>
</html>
