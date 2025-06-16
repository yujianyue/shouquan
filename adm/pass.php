<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: adm/pass.php
// 文件大小: 6263 字节
/**
 * 本文件功能: 修改管理员密码
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理修改密码请求
if (isset($_GET['act']) && $_GET['act'] == 'change') {
    $old_password = post_param('old_password');
    $new_password = post_param('new_password');
    $confirm_password = post_param('confirm_password');
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        json_result(1, '请填写完整信息');
    }
    
    if ($new_password !== $confirm_password) {
        json_result(1, '两次输入的新密码不一致');
    }
    
    if (strlen($new_password) < 6) {
        json_result(1, '新密码长度不能少于6位');
    }
    
    // 读取账号配置
    $account_file = './inc/mima.php';
    if (!file_exists($account_file)) {
        json_result(1, '账号文件不存在');
    }
    
    $accounts = read_json_file($account_file);
    $username = $_SESSION['admin_username'];
    $found = false;
    
    // 验证旧密码并更新
    foreach ($accounts as $key => $account) {
        if ($account['username'] == $username && $account['password'] == $old_password) {
            $accounts[$key]['password'] = $new_password;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        json_result(1, '旧密码不正确');
    }
    
    // 保存更新后的账号
    if (write_json_file($account_file, $accounts)) {
        json_result(0, '密码修改成功');
    } else {
        json_result(1, '密码修改失败，请检查文件权限');
    }
    
    exit;
}

// 获取网站设置
$site_file = './inc/json.php';
if (file_exists($site_file)) {
    $site_config = read_json_file($site_file);
} else {
    $site_config = $default_site;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - <?php echo $site_config['title']; ?></title>
    <link rel="stylesheet" href="inc/pubs.css?v=<?php echo VERSION; ?>">
    <script src="inc/pubs.js?v=<?php echo VERSION; ?>"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <h1 class="site-title"><?php echo $site_config['title']; ?></h1>
                <div class="user-info">
                    <span>欢迎，<?php echo $_SESSION['admin_username']; ?></span>
                    <a href="admin.php?do=lgout" class="btn btn-sm">退出</a>
                </div>
            </div>
            <ul class="nav">
                <?php foreach ($menu_items as $item): ?>
                <li class="nav-item">
                    <a href="admin.php?do=<?php echo $item['id']; ?>" class="nav-link <?php echo $do == $item['id'] ? 'active' : ''; ?>">
                        <?php echo $item['name']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>修改密码</h2>
            </div>
            <div class="card-body">
                <form id="password_form">
                    <div class="form-group">
                        <label class="form-label">旧密码</label>
                        <input type="password" name="old_password" class="form-control" placeholder="请输入旧密码">
                    </div>
                    <div class="form-group">
                        <label class="form-label">新密码</label>
                        <input type="password" name="new_password" class="form-control" placeholder="请输入新密码">
                    </div>
                    <div class="form-group">
                        <label class="form-label">确认新密码</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="请再次输入新密码">
                    </div>
                    <div class="form-group">
                        <button type="button" id="submit_btn" class="btn btn-primary">修改密码</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('submit_btn').addEventListener('click', function() {
                const formData = getFormValues('password_form');
                
                if (!formData.old_password) {
                    showToast('请输入旧密码');
                    return;
                }
                
                if (!formData.new_password) {
                    showToast('请输入新密码');
                    return;
                }
                
                if (!formData.confirm_password) {
                    showToast('请输入确认密码');
                    return;
                }
                
                if (formData.new_password !== formData.confirm_password) {
                    showToast('两次输入的新密码不一致');
                    return;
                }
                
                if (formData.new_password.length < 6) {
                    showToast('新密码长度不能少于6位');
                    return;
                }
                
                showLoading();
                ajax({
                    url: 'admin.php?do=pass&act=change',
                    type: 'POST',
                    data: formData,
                    success: function(res) {
                        hideLoading();
                        if (res.code === 0) {
                            showToast('密码修改成功');
                            document.getElementById('password_form').reset();
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
