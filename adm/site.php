<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: adm/site.php
// 文件大小: 5729 字节
/**
 * 本文件功能: 网站设置
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理设置保存请求
if (isset($_GET['act']) && $_GET['act'] == 'save') {
    $title = post_param('title');
    $footer_text = post_param('footer_text');
    $footer_link = post_param('footer_link');
    $captcha = post_param('captcha') == '1' ? true : false;
    
    if (empty($title)) {
        json_result(1, '网站标题不能为空');
    }
    
    // 保存设置
    $settings = [
        'title' => $title,
        'footer_text' => $footer_text,
        'footer_link' => $footer_link,
        'captcha' => $captcha
    ];
    
    $site_file = './inc/json.php';
    if (write_json_file($site_file, $settings)) {
        json_result(0, '设置保存成功');
    } else {
        json_result(1, '设置保存失败，请检查文件权限');
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
    <title>网站设置 - <?php echo $site_config['title']; ?></title>
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
                <h2>网站设置</h2>
            </div>
            <div class="card-body">
                <form id="site_form">
                    <div class="form-group">
                        <label class="form-label">网站标题</label>
                        <input type="text" name="title" class="form-control" value="<?php echo $site_config['title']; ?>" placeholder="请输入网站标题">
                    </div>
                    <div class="form-group">
                        <label class="form-label">底部文字</label>
                        <input type="text" name="footer_text" class="form-control" value="<?php echo $site_config['footer_text']; ?>" placeholder="请输入底部文字">
                    </div>
                    <div class="form-group">
                        <label class="form-label">底部链接</label>
                        <input type="text" name="footer_link" class="form-control" value="<?php echo $site_config['footer_link']; ?>" placeholder="请输入底部链接">
                    </div>
                    <div class="form-group">
                        <label class="form-label">验证码开关</label>
                        <div>
                            <label style="margin-right: 15px;">
                                <input type="radio" name="captcha" value="1" <?php echo $site_config['captcha'] ? 'checked' : ''; ?>> 开启
                            </label>
                            <label>
                                <input type="radio" name="captcha" value="0" <?php echo !$site_config['captcha'] ? 'checked' : ''; ?>> 关闭
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="button" id="submit_btn" class="btn btn-primary">保存设置</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('submit_btn').addEventListener('click', function() {
                const formData = getFormValues('site_form');
                
                if (!formData.title) {
                    showToast('请输入网站标题');
                    return;
                }
                
                showLoading();
                ajax({
                    url: 'admin.php?do=site&act=save',
                    type: 'POST',
                    data: formData,
                    success: function(res) {
                        hideLoading();
                        if (res.code === 0) {
                            showToast('设置保存成功');
                            // 刷新页面以应用新设置
                            setTimeout(function() {
                                window.location.reload();
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
