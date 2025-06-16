<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: inc/pubs.php
// 文件大小: 6175 字节
/**
 * 本文件功能: 公共PHP函数库
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

/**
 * JSON响应输出
 * @param int $code 状态码 0成功 非0失败
 * @param string $msg 提示信息
 * @param array $data 数据
 * @return void
 */
function json_result($code, $msg, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 安全过滤输入
 * @param string $str 输入字符串
 * @return string 过滤后的字符串
 */
function safe_input($str) {
    if (is_array($str)) {
        foreach ($str as $key => $value) {
            $str[$key] = safe_input($value);
        }
    } else {
        $str = trim($str);
        $str = htmlspecialchars($str, ENT_QUOTES);
    }
    return $str;
}

/**
 * 获取GET参数
 * @param string $name 参数名
 * @param mixed $default 默认值
 * @return mixed 参数值
 */
function get_param($name, $default = '') {
    return isset($_GET[$name]) ? safe_input($_GET[$name]) : $default;
}

/**
 * 获取POST参数
 * @param string $name 参数名
 * @param mixed $default 默认值
 * @return mixed 参数值
 */
function post_param($name, $default = '') {
    return isset($_POST[$name]) ? safe_input($_POST[$name]) : $default;
}

/**
 * 检查管理员是否登录
 * @return bool 是否登录
 */
function is_admin_login() {
    return isset($_SESSION['admin_login']) && $_SESSION['admin_login'] === true;
}

/**
 * 管理员登录验证
 * @return void
 */
function check_admin_login() {
    if (!is_admin_login()) {
        header('Location: admin.php?do=login');
        exit;
    }
}

/**
 * 生成随机字符串
 * @param int $length 长度
 * @return string 随机字符串
 */
function random_str($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $str;
}

/**
 * 生成授权码
 * @param string $version_code 版本代码
 * @param string $domain 域名
 * @return string 授权码
 */
function generate_auth_code($version_code, $domain) {
    return md5($version_code . AUTH_KEY . $domain);
}

/**
 * 验证授权码
 * @param string $auth_code 授权码
 * @param string $version_code 版本代码
 * @param string $domain 域名
 * @return bool 是否有效
 */
function verify_auth_code($auth_code, $version_code, $domain) {
    return $auth_code === generate_auth_code($version_code, $domain);
}

/**
 * 验证域名DNS TXT记录
 * @param string $domain 域名
 * @param string $auth_code 授权码
 * @return bool 是否匹配
 */
function verify_dns_txt($domain, $auth_code) {
    $records = dns_get_record($domain, DNS_TXT);
    if (empty($records)) {
        return false;
    }
    
    foreach ($records as $record) {
        if (isset($record['txt']) && $record['txt'] === $auth_code) {
            return true;
        }
    }
    
    return false;
}

/**
 * 读取JSON文件
 * @param string $file 文件路径
 * @return array 解析后的数据
 */
function read_json_file($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

/**
 * 写入JSON文件
 * @param string $file 文件路径
 * @param array $data 数据
 * @return bool 是否成功
 */
function write_json_file($file, $data) {
    $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return file_put_contents($file, $content, LOCK_EX) !== false;
}

/**
 * 读取CSV文件内容
 * @param string $file CSV文件路径
 * @param bool $has_header 是否有标题行
 * @return array 二维数组数据
 */
function read_csv_file($file, $has_header = true) {
    if (!file_exists($file)) {
        return [];
    }
    
    $data = [];
    $handle = fopen($file, 'r');
    
    if ($has_header) {
        $header = fgetcsv($handle);
    }
    
    while (($row = fgetcsv($handle)) !== false) {
        if ($has_header) {
            $item = [];
            foreach ($header as $i => $key) {
                $item[$key] = isset($row[$i]) ? $row[$i] : '';
            }
            $data[] = $item;
        } else {
            $data[] = $row;
        }
    }
    
    fclose($handle);
    return $data;
}

/**
 * 导入CSV数据到数据库
 * @param string $file CSV文件路径
 * @param string $table 表名
 * @param array $fields 字段映射
 * @param object $db 数据库对象
 * @return array 导入结果
 */
function import_csv_to_db($file, $table, $fields, $db) {
    $data = read_csv_file($file);
    if (empty($data)) {
        return ['success' => 0, 'fail' => 0];
    }
    
    $success = 0;
    $fail = 0;
    
    foreach ($data as $row) {
        $insert_data = [];
        foreach ($fields as $db_field => $csv_field) {
            if (isset($row[$csv_field])) {
                $insert_data[$db_field] = $row[$csv_field];
            }
        }
        
        if ($db->insert($table, $insert_data)) {
            $success++;
        } else {
            $fail++;
        }
    }
    
    return ['success' => $success, 'fail' => $fail];
}

/**
 * 检查两次请求的时间间隔
 * @param string $key 请求标识
 * @param int $seconds 间隔秒数
 * @return bool 是否允许请求
 */
function check_request_interval($key, $seconds = 30) {
    $now = time();
    $last_time = isset($_SESSION[$key]) ? $_SESSION[$key] : 0;
    
    if ($now - $last_time < $seconds) {
        return false;
    }
    
    $_SESSION[$key] = $now;
    return true;
}

/**
 * 获取客户端IP
 * @return string IP地址
 */
function get_client_ip() {
    $ip = '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
