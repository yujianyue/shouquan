<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: adm/lima.php
// 文件大小: 24589 字节
/**
 * 本文件功能: 授权列表管理
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

// 处理Ajax请求
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        // 获取授权列表
        case 'list':
            $page = post_param('page', 1);
            $search = post_param('search', '');
            $page_size = 10;
            
            $where = '';
            if (!empty($search)) {
                $search = $db->escape($search);
                $where = "domain LIKE '%{$search}%' OR auth_code LIKE '%{$search}%' OR mobile LIKE '%{$search}%' OR email LIKE '%{$search}%'";
            }
            
            $result = $db->getPage('auth_codes', $where, 'id DESC', $page, $page_size);
            json_result(0, '获取成功', $result);
            break;
            
        // 获取授权详情
        case 'detail':
            $id = post_param('id');
            if (empty($id)) {
                json_result(1, '参数错误');
            }
            
            $auth = $db->getRow("SELECT * FROM auth_codes WHERE id = {$db->escape($id)}");
            if (!$auth) {
                json_result(1, '授权记录不存在');
            }
            
            // 获取版本信息
            $versions = read_json_file('./inc/banben.json');
            $version_info = [];
            
            foreach ($versions as $version) {
                if ($version['code'] == $auth['version']) {
                    $version_info = $version;
                    break;
                }
            }
            
            $auth['version_info'] = $version_info;
            json_result(0, '获取成功', $auth);
            break;
            
        // 修改域名
        case 'update_domain':
            $id = post_param('id');
            $domain = post_param('domain');
            $version = post_param('version');
            
            if (empty($id) || empty($domain) || empty($version)) {
                json_result(1, '参数错误');
            }
            
            // 查询原记录
            $auth = $db->getRow("SELECT * FROM auth_codes WHERE id = {$db->escape($id)}");
            if (!$auth) {
                json_result(1, '授权记录不存在');
            }
            
            // 检查域名是否已存在
            $exist = $db->getRow("SELECT * FROM auth_codes WHERE domain = '{$db->escape($domain)}' AND id != {$db->escape($id)}");
            if ($exist) {
                json_result(1, '该域名已被其他记录使用');
            }
            
            // 重新生成授权码
            $auth_code = generate_auth_code($version, $domain);
            
            // 更新记录
            $result = $db->update('auth_codes', [
                'domain' => $domain,
                'version' => $version,
                'auth_code' => $auth_code,
                'status' => '待授权' // 更改域名后需要重新授权
            ], "id = {$db->escape($id)}");
            
            if (!$result) {
                json_result(1, '更新失败');
            }
            
            json_result(0, '更新成功', [
                'domain' => $domain,
                'auth_code' => $auth_code
            ]);
            break;
            
        // 更新状态
        case 'update_status':
            $id = post_param('id');
            $status = post_param('status');
            
            if (empty($id) || empty($status)) {
                json_result(1, '参数错误');
            }
            
            $result = $db->update('auth_codes', [
                'status' => $status
            ], "id = {$db->escape($id)}");
            
            if (!$result) {
                json_result(1, '更新失败');
            }
            
            json_result(0, '更新成功');
            break;
            
        // 删除授权
        case 'delete':
            $id = post_param('id');
            if (empty($id)) {
                json_result(1, '参数错误');
            }
            
            $result = $db->delete('auth_codes', "id = {$db->escape($id)}");
            if (!$result) {
                json_result(1, '删除失败');
            }
            
            json_result(0, '删除成功');
            break;
            
        // 批量删除
        case 'batch_delete':
            $ids = post_param('ids');
            if (empty($ids)) {
                json_result(1, '请选择要删除的记录');
            }
            
            $id_arr = explode(',', $ids);
            $id_str = '';
            foreach ($id_arr as $id) {
                $id = intval($id);
                if ($id > 0) {
                    $id_str .= $id . ',';
                }
            }
            $id_str = rtrim($id_str, ',');
            
            if (empty($id_str)) {
                json_result(1, '参数错误');
            }
            
            $result = $db->delete('auth_codes', "id IN ({$id_str})");
            if (!$result) {
                json_result(1, '删除失败');
            }
            
            json_result(0, '删除成功');
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
    <title>授权列表 - <?php echo $site_config['title']; ?></title>
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
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>授权列表</h2>
                    <div class="search-bar">
                        <input type="text" id="search_input" class="search-input" placeholder="搜索域名/授权码/手机号/邮箱">
                        <button id="search_btn" class="search-btn">搜索</button>
                        <button id="batch_delete_btn" class="btn btn-danger" style="margin-left: 10px;">批量删除</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%"><input type="checkbox" id="check_all"></th>
                            <th width="5%">ID</th>
                            <th width="20%">授权码</th>
                            <th width="15%">域名</th>
                            <th width="10%">版本</th>
                            <th width="10%">手机号</th>
                            <th width="10%">状态</th>
                            <th width="10%">时间</th>
                            <th width="15%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="auth_list">
                        <tr>
                            <td colspan="9" style="text-align: center;">加载中...</td>
                        </tr>
                    </tbody>
                </table>
                
                <div id="pagination"></div>
            </div>
        </div>
    </div>
    
    <script>
        // 全局变量
        let authList = [];
        let versions = [];
        
        document.addEventListener('DOMContentLoaded', function() {
            // 加载版本列表
            loadVersions();
            
            // 加载授权列表
            loadAuthList();
            
            // 搜索按钮
            document.getElementById('search_btn').addEventListener('click', function() {
                loadAuthList(1);
            });
            
            // 回车搜索
            document.getElementById('search_input').addEventListener('keydown', function(e) {
                if (e.keyCode === 13) {
                    loadAuthList(1);
                }
            });
            
            // 全选/取消全选
            document.getElementById('check_all').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[name="auth_id"]');
                for (let i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = this.checked;
                }
            });
            
            // 批量删除
            document.getElementById('batch_delete_btn').addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('input[name="auth_id"]:checked');
                if (checkboxes.length === 0) {
                    showToast('请选择要删除的记录');
                    return;
                }
                
                const ids = [];
                for (let i = 0; i < checkboxes.length; i++) {
                    ids.push(checkboxes[i].value);
                }
                
                showOverlay(
                    '<p>确定要删除选中的 ' + checkboxes.length + ' 条记录吗？</p>',
                    '确认删除',
                    [
                        {
                            text: '取消',
                            className: 'btn-default'
                        },
                        {
                            text: '确定删除',
                            className: 'btn-danger',
                            click: function() {
                                batchDelete(ids.join(','));
                                hideOverlay();
                            }
                        }
                    ]
                );
            });
        });
        
        // 加载版本列表
        function loadVersions() {
            ajax({
                url: 'admin.php?do=lima&act=versions',
                success: function(res) {
                    if (res.code === 0) {
                        versions = res.data;
                    }
                }
            });
        }
        
        // 加载授权列表
        function loadAuthList(page) {
            const search = document.getElementById('search_input').value;
            
            ajaxPagination({
                container: 'auth_list',
                paginationContainer: 'pagination',
                url: 'admin.php?do=lima&act=list',
                type: 'POST',
                data: {
                    search: search
                },
                page: page || 1,
                renderContent: function(data) {
                    authList = data;
                    
                    let html = '';
                    if (data.length === 0) {
                        html = '<tr><td colspan="9" style="text-align: center;">暂无数据</td></tr>';
                    } else {
                        for (let i = 0; i < data.length; i++) {
                            const item = data[i];
                            
                            // 获取版本名称
                            let versionName = item.version;
                            for (let j = 0; j < versions.length; j++) {
                                if (versions[j].code === item.version) {
                                    versionName = versions[j].name;
                                    break;
                                }
                            }
                            
                            // 状态样式
                            let statusClass = '';
                            if (item.status === '已授权') {
                                statusClass = 'color: green;';
                            } else if (item.status === '未授权') {
                                statusClass = 'color: red;';
                            }
                            
                            html += '<tr>' +
                                '<td><input type="checkbox" name="auth_id" value="' + item.id + '"></td>' +
                                '<td>' + item.id + '</td>' +
                                '<td title="' + item.auth_code + '">' + item.auth_code.substr(0, 16) + '...</td>' +
                                '<td>' + item.domain + '</td>' +
                                '<td>' + versionName + '</td>' +
                                '<td>' + item.mobile + '</td>' +
                                '<td style="' + statusClass + '">' + item.status + '</td>' +
                                '<td>' + item.created_time.substr(0, 10) + '</td>' +
                                '<td>' +
                                    '<button class="btn btn-sm" onclick="showDetail(' + item.id + ')">详情</button> ' +
                                    '<button class="btn btn-sm btn-danger" onclick="deleteAuth(' + item.id + ')">删除</button>' +
                                '</td>' +
                            '</tr>';
                        }
                    }
                    
                    document.getElementById('auth_list').innerHTML = html;
                }
            });
        }
        
        // 显示详情
        function showDetail(id) {
            showLoading();
            ajax({
                url: 'admin.php?do=lima&act=detail',
                type: 'POST',
                data: {
                    id: id
                },
                success: function(res) {
                    hideLoading();
                    if (res.code === 0) {
                        const auth = res.data;
                        
                        // 生成版本选项
                        let versionOptions = '';
                        for (let i = 0; i < versions.length; i++) {
                            const selected = versions[i].code === auth.version ? 'selected' : '';
                            versionOptions += '<option value="' + versions[i].code + '" ' + selected + '>' + versions[i].name + '</option>';
                        }
                        
                        // 生成状态选项
                        const statusOptions = [
                            '待授权',
                            '未授权',
                            '已授权'
                        ];
                        let statusHtml = '';
                        for (let i = 0; i < statusOptions.length; i++) {
                            const selected = statusOptions[i] === auth.status ? 'selected' : '';
                            statusHtml += '<option value="' + statusOptions[i] + '" ' + selected + '>' + statusOptions[i] + '</option>';
                        }
                        
                        let content = '<div class="form-group">' +
                            '<label class="form-label">ID</label>' +
                            '<input type="text" class="form-control" value="' + auth.id + '" readonly>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label class="form-label">授权码</label>' +
                            '<input type="text" class="form-control" value="' + auth.auth_code + '" readonly>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label class="form-label">域名</label>' +
                            '<input type="text" id="edit_domain" class="form-control" value="' + auth.domain + '">' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label class="form-label">版本</label>' +
                            '<select id="edit_version" class="form-control">' + versionOptions + '</select>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label class="form-label">手机号</label>' +
                            '<input type="text" class="form-control" value="' + auth.mobile + '" readonly>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label class="form-label">邮箱</label>' +
                            '<input type="text" class="form-control" value="' + auth.email + '" readonly>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label class="form-label">状态</label>' +
                            '<select id="edit_status" class="form-control">' + statusHtml + '</select>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label class="form-label">申请时间</label>' +
                            '<input type="text" class="form-control" value="' + auth.created_time + '" readonly>' +
                        '</div>' +
                        '<div class="form-group">' +
                            '<label class="form-label">最后查询时间</label>' +
                            '<input type="text" class="form-control" value="' + auth.last_query_time + '" readonly>' +
                        '</div>';
                        
                        showOverlay(
                            content,
                            '授权详情',
                            [
                                {
                                    text: '关闭',
                                    className: 'btn-default'
                                },
                                {
                                    text: '更新域名',
                                    className: 'btn-primary',
                                    click: function() {
                                        updateDomain(auth.id);
                                    }
                                },
                                {
                                    text: '更新状态',
                                    className: 'btn-success',
                                    click: function() {
                                        updateStatus(auth.id);
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
        }
        
        // 更新域名
        function updateDomain(id) {
            const domain = document.getElementById('edit_domain').value;
            const version = document.getElementById('edit_version').value;
            
            if (!domain) {
                showToast('请输入域名');
                return;
            }
            
            showLoading();
            ajax({
                url: 'admin.php?do=lima&act=update_domain',
                type: 'POST',
                data: {
                    id: id,
                    domain: domain,
                    version: version
                },
                success: function(res) {
                    hideLoading();
                    if (res.code === 0) {
                        showToast('更新成功');
                        hideOverlay();
                        loadAuthList();
                    } else {
                        showToast(res.msg);
                    }
                },
                error: function() {
                    hideLoading();
                    showToast('网络错误，请稍后重试');
                }
            });
        }
        
        // 更新状态
        function updateStatus(id) {
            const status = document.getElementById('edit_status').value;
            
            showLoading();
            ajax({
                url: 'admin.php?do=lima&act=update_status',
                type: 'POST',
                data: {
                    id: id,
                    status: status
                },
                success: function(res) {
                    hideLoading();
                    if (res.code === 0) {
                        showToast('更新成功');
                        hideOverlay();
                        loadAuthList();
                    } else {
                        showToast(res.msg);
                    }
                },
                error: function() {
                    hideLoading();
                    showToast('网络错误，请稍后重试');
                }
            });
        }
        
        // 删除授权
        function deleteAuth(id) {
            showOverlay(
                '<p>确定要删除该授权记录吗？</p>',
                '确认删除',
                [
                    {
                        text: '取消',
                        className: 'btn-default'
                    },
                    {
                        text: '确定删除',
                        className: 'btn-danger',
                        click: function() {
                            showLoading();
                            ajax({
                                url: 'admin.php?do=lima&act=delete',
                                type: 'POST',
                                data: {
                                    id: id
                                },
                                success: function(res) {
                                    hideLoading();
                                    if (res.code === 0) {
                                        showToast('删除成功');
                                        loadAuthList();
                                    } else {
                                        showToast(res.msg);
                                    }
                                },
                                error: function() {
                                    hideLoading();
                                    showToast('网络错误，请稍后重试');
                                }
                            });
                            hideOverlay();
                        }
                    }
                ]
            );
        }
        
        // 批量删除
        function batchDelete(ids) {
            showLoading();
            ajax({
                url: 'admin.php?do=lima&act=batch_delete',
                type: 'POST',
                data: {
                    ids: ids
                },
                success: function(res) {
                    hideLoading();
                    if (res.code === 0) {
                        showToast('删除成功');
                        loadAuthList();
                    } else {
                        showToast(res.msg);
                    }
                },
                error: function() {
                    hideLoading();
                    showToast('网络错误，请稍后重试');
                }
            });
        }
    </script>
</body>
</html>
