<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: index.php
// 文件大小: 20570 字节

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: index.php
// 文件大小: 20399 字节
/**
 * 本文件功能: 授权查询与提交页面
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

require_once './inc/conn.php';
require_once './inc/pubs.php';

// 处理Ajax请求
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        // 授权查询
        case 'query':
            $domain = post_param('domain');
            if (empty($domain)) {
                json_result(1, '请输入域名');
            }
            
            // 检查查询时间间隔
            if (!check_request_interval('query_time', 30)) {
                json_result(1, '查询过于频繁，请30秒后再试');
            }
            
            // 查询数据库
            $auth = $db->getRow("SELECT * FROM auth_codes WHERE domain = '{$db->escape($domain)}'");
            
            if (!$auth) {
                json_result(1, '该域名未授权');
            }
            
            // 验证DNS TXT记录
            $is_valid = verify_dns_txt($domain, $auth['auth_code']);
            
            // 更新授权状态
            $status = $is_valid ? '已授权' : '未授权';
            $db->update('auth_codes', [
                'status' => $status,
                'last_query_time' => date('Y-m-d H:i:s')
            ], "id = {$auth['id']}");
            
            // 获取版本信息
            $versions = read_json_file('./inc/banben.json');
            $version_info = [];
            
            foreach ($versions as $version) {
                if ($version['code'] == $auth['version']) {
                    $version_info = $version;
                    break;
                }
            }
            
            // 更新下载链接 (仅已授权显示)
            if ($is_valid && !empty($version_info)) {
                $version_info['download'] = "download.php?code={$auth['auth_code']}";
            }
            
            json_result(0, '查询成功', [
                'domain' => $auth['domain'],
                'auth_code' => $auth['auth_code'],
                'status' => $status,
                'created_time' => $auth['created_time'],
                'last_query_time' => date('Y-m-d H:i:s'),
                'version' => $version_info
            ]);
            break;
        
        // 获取授权
        case 'apply':
            $domain = post_param('domain');
            $mobile = post_param('mobile');
            $email = post_param('email');
            $version = post_param('version');
            
            if (empty($domain)) {
                json_result(1, '请输入域名');
            }
            
            if (empty($mobile)) {
                json_result(1, '请输入手机号');
            }
            
            if (empty($email)) {
                json_result(1, '请输入邮箱');
            }
            
            if (empty($version)) {
                json_result(1, '请选择版本');
            }
            
            // 检查域名是否已存在
            $exist = $db->getRow("SELECT * FROM auth_codes WHERE domain = '{$db->escape($domain)}'");
            if ($exist) {
                json_result(1, '该域名已申请授权，请勿重复提交');
            }
            
            // 验证版本是否存在
            $versions = read_json_file('./inc/banben.json');
            $version_exists = false;
            
            foreach ($versions as $v) {
                if ($v['code'] == $version) {
                    $version_exists = true;
                    break;
                }
            }
            
            if (!$version_exists) {
                json_result(1, '选择的版本不存在');
            }
            
            // 生成授权码
            $auth_code = generate_auth_code($version, $domain);
            
            // 保存到数据库
            $result = $db->insert('auth_codes', [
                'auth_code' => $auth_code,
                'domain' => $domain,
                'version' => $version,
                'mobile' => $mobile,
                'email' => $email,
                'status' => '待授权',
                'created_time' => date('Y-m-d H:i:s'),
                'last_query_time' => date('Y-m-d H:i:s')
            ]);
            
            if (!$result) {
                json_result(1, '提交失败，请稍后重试');
            }
            
            json_result(0, '提交成功', [
                'domain' => $domain,
                'auth_code' => $auth_code
            ]);
            break;
            
        // 获取版本列表
        case 'versions':
            $versions = read_json_file('./inc/banben.json');
            json_result(0, '获取成功', $versions);
            break;
            
        default:
            json_result(1, '未知操作');
            break;
    }
    
    exit;
}        
        // 创建默认账号文件
        $account_file = './inc/mima.php';
        if (!file_exists($account_file)) {
            $accounts = [$default_admin];
            write_json_file($account_file, $accounts);
        }
        
        // 创建默认设置文件
        $site_file = './inc/json.php';
        if (!file_exists($site_file)) {
            write_json_file($site_file, $default_site);
        }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>源码授权系统DNS验证版</title>
    <link rel="stylesheet" href="inc/pubs.css?v=<?php echo VERSION; ?>">
    <script src="inc/pubs.js?v=<?php echo VERSION; ?>"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-top">
                <h1 class="site-title">源码授权系统DNS验证版</h1>
            </div>
        </div>
        
        <div class="card" id="tabs">
            <div class="tab-nav">
                <div class="tab-item active">授权查询</div>
                <div class="tab-item">获取授权</div>
            </div>
            
            <!-- 授权查询 -->
            <div class="tab-content active">
                <div class="form-group">
                    <label class="form-label">域名</label>
                    <input type="text" id="query_domain" class="form-control" placeholder="请输入需要查询的域名">
                </div>
                <div class="form-group">
                    <button id="query_btn" class="btn btn-primary">查询授权</button>
                </div>
                
                <div id="query_result" style="display:none;" class="card">
                    <div class="card-header">查询结果</div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th width="30%">域名</th>
                                <td id="result_domain"></td>
                            </tr>
                            <tr>
                                <th>授权码</th>
                                <td id="result_auth_code"></td>
                            </tr>
                            <tr>
                                <th>授权状态</th>
                                <td id="result_status"></td>
                            </tr>
                            <tr>
                                <th>申请时间</th>
                                <td id="result_created_time"></td>
                            </tr>
                            <tr>
                                <th>最后查询时间</th>
                                <td id="result_query_time"></td>
                            </tr>
                            <tr>
                                <th>版本信息</th>
                                <td id="result_version"></td>
                            </tr>
                            <tr id="download_row" style="display:none;">
                                <th>下载链接</th>
                                <td id="result_download"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 获取授权 -->
            <div class="tab-content">
                <form id="apply_form">
                    <div class="form-group">
                        <label class="form-label">域名</label>
                        <input type="text" name="domain" class="form-control" placeholder="请输入申请授权的域名">
                    </div>
                    <div class="form-group">
                        <label class="form-label">手机号</label>
                        <input type="text" name="mobile" class="form-control" placeholder="请输入手机号码">
                    </div>
                    <div class="form-group">
                        <label class="form-label">邮箱</label>
                        <input type="email" name="email" class="form-control" placeholder="请输入邮箱">
                    </div>
                    <div class="form-group">
                        <label class="form-label">选择版本</label>
                        <select name="version" id="version_select" class="form-control">
                            <option value="">请选择版本</option>
                        </select>
                    </div>
                    <div class="form-group" id="version_info" style="display:none;">
                        <div class="card">
                            <div class="card-header">版本信息</div>
                            <div class="card-body">
                                <table class="table">
                                    <tr>
                                        <th width="30%">版本名称</th>
                                        <td id="info_name"></td>
                                    </tr>
                                    <tr>
                                        <th>授权说明</th>
                                        <td id="info_note"></td>
                                    </tr>
                                    <tr>
                                        <th>价格</th>
                                        <td id="info_price"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="button" id="apply_btn" class="btn btn-primary">提交申请</button>
                    </div>
                </form>
                
                <div id="apply_result" style="display:none;" class="card">
                    <div class="card-header">申请结果</div>
                    <div class="card-body">
                        <div class="alert" style="background-color: #f8f8f8; padding: 15px; border-left: 4px solid #5cb85c; margin-bottom: 15px;">
                            <p>您的授权申请已提交成功！请按照以下步骤添加DNS TXT记录完成授权：</p>
                        </div>
                        
                        <table class="table">
                            <tr>
                                <th width="30%">域名</th>
                                <td id="apply_domain"></td>
                            </tr>
                            <tr>
                                <th>授权码</th>
                                <td id="apply_auth_code"></td>
                            </tr>
                            <tr>
                                <th>TXT记录主机记录</th>
                                <td id="apply_host">@</td>
                            </tr>
                            <tr>
                                <th>TXT记录值</th>
                                <td id="apply_txt_value"></td>
                            </tr>
                        </table>
                        
                        <div class="alert" style="background-color: #f8f8f8; padding: 15px; border-left: 4px solid #d9534f; margin-top: 15px;">
                            <p><strong>重要提示：</strong>请将上述TXT记录添加到您的域名DNS解析中，添加后等待解析生效（通常5分钟-24小时不等），然后在"授权查询"中查询验证授权状态。</p>
                             <p> <strong>请勿删除此TXT记录，否则将导致授权失效。</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer" style="text-align: center; margin-top: 20px; padding: 20px 0; border-top: 1px solid #eee;">
            <p>© <?php echo date('Y'); ?> 源码授权系统DNS验证版 - <a href="admin.php" target="_blank">管理入口</a></p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 加载版本列表
            ajax({
                url: 'index.php',
                data: { act: 'versions' },
                success: function(res) {
                    if (res.code === 0 && res.data) {
                        let html = '<option value="">请选择版本</option>';
                        for (let i = 0; i < res.data.length; i++) {
                            html += '<option value="' + res.data[i].code + '">' + res.data[i].name + '</option>';
                        }
                        document.getElementById('version_select').innerHTML = html;
                    }
                }
            });
            
            // 监听版本选择变化
            document.getElementById('version_select').addEventListener('change', function() {
                const versionCode = this.value;
                if (!versionCode) {
                    document.getElementById('version_info').style.display = 'none';
                    return;
                }
                
                ajax({
                    url: 'index.php',
                    data: { act: 'versions' },
                    success: function(res) {
                        if (res.code === 0 && res.data) {
                            let version = null;
                            for (let i = 0; i < res.data.length; i++) {
                                if (res.data[i].code === versionCode) {
                                    version = res.data[i];
                                    break;
                                }
                            }
                            
                            if (version) {
                                document.getElementById('info_name').innerText = version.name;
                                document.getElementById('info_note').innerText = version.note;
                                document.getElementById('info_price').innerText = '¥' + version.price;
                                document.getElementById('version_info').style.display = 'block';
                            }
                        }
                    }
                });
            });
            
            // 授权查询
            document.getElementById('query_btn').addEventListener('click', function() {
                const domain = document.getElementById('query_domain').value.trim();
                if (!domain) {
                    showToast('请输入域名');
                    return;
                }
                
                showLoading();
                ajax({
                    url: 'index.php?act=query',
                    type: 'POST',
                    data: { 
                        act: 'query',
                        domain: domain
                    },
                    success: function(res) {
                        hideLoading();
                        if (res.code === 0) {
                            document.getElementById('result_domain').innerText = res.data.domain;
                            document.getElementById('result_auth_code').innerText = res.data.auth_code;
                            document.getElementById('result_status').innerText = res.data.status;
                            document.getElementById('result_created_time').innerText = res.data.created_time;
                            document.getElementById('result_query_time').innerText = res.data.last_query_time;
                            
                            // 显示版本信息
                            let versionText = '';
                            if (res.data.version) {
                                versionText = res.data.version.name + ' (' + res.data.version.note + ')';
                            }
                            document.getElementById('result_version').innerText = versionText;
                            
                            // 显示下载链接 (仅已授权)
                            if (res.data.status === '已授权' && res.data.version && res.data.version.download) {
                                document.getElementById('result_download').innerHTML = '<a href="' + res.data.version.download + '" class="btn btn-success">下载源码</a>';
                                document.getElementById('download_row').style.display = '';
                            } else {
                                document.getElementById('download_row').style.display = 'none';
                            }
                            
                            document.getElementById('query_result').style.display = 'block';
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
            
            // 获取授权
            document.getElementById('apply_btn').addEventListener('click', function() {
                const formData = getFormValues('apply_form');
                
                if (!formData.domain) {
                    showToast('请输入域名');
                    return;
                }
                
                if (!formData.mobile) {
                    showToast('请输入手机号');
                    return;
                }
                
                if (!formData.email) {
                    showToast('请输入邮箱');
                    return;
                }
                
                if (!formData.version) {
                    showToast('请选择版本');
                    return;
                }
                
                showLoading();
                ajax({
                    url: 'index.php?act=apply',
                    type: 'POST',
                    data: {
                        act: 'apply',
                        domain: formData.domain,
                        mobile: formData.mobile,
                        email: formData.email,
                        version: formData.version
                    },
                    success: function(res) {
                        hideLoading();
                        if (res.code === 0) {
                            document.getElementById('apply_domain').innerText = res.data.domain;
                            document.getElementById('apply_auth_code').innerText = res.data.auth_code;
                            document.getElementById('apply_txt_value').innerText = res.data.auth_code;
                            
                            document.getElementById('apply_form').style.display = 'none';
                            document.getElementById('apply_result').style.display = 'block';
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
