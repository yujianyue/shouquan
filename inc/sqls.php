<?php

// PHP7+MySQL5.6 查立得源码授权系统DNS验证版 V2025.05.30
// 演示地址: http://shouquan.chalide.cn
// 文件路径: inc/sqls.php
// 文件大小: 6117 字节
/**
 * 本文件功能: 数据库操作类
 * 版权声明: 保留发行权和署名权
 * 作者信息: 15058593138@qq.com
 */

class SqlHelper {
    private $conn;
    private $host;
    private $user;
    private $pass;
    private $dbname;
    private $port;
    private $charset;
    
    /**
     * 构造函数，初始化数据库连接
     * @param array $config 数据库配置数组
     */
    public function __construct($config) {
        $this->host = $config['host'];
        $this->user = $config['user'];
        $this->pass = $config['pass'];
        $this->dbname = $config['name'];
        $this->port = $config['port'];
        $this->charset = $config['charset'];
        $this->connect();
    }
    
    /**
     * 连接数据库
     */
    private function connect() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname, $this->port);
        if ($this->conn->connect_error) {
            die("数据库连接失败: " . $this->conn->connect_error);
        }
        $this->conn->set_charset($this->charset);
    }
    
    /**
     * 执行SQL查询
     * @param string $sql SQL语句
     * @return mysqli_result|bool 查询结果
     */
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    /**
     * 获取单条记录
     * @param string $sql SQL语句
     * @return array|null 查询结果数组
     */
    public function getRow($sql) {
        $result = $this->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    /**
     * 获取多条记录
     * @param string $sql SQL语句
     * @return array 查询结果数组
     */
    public function getAll($sql) {
        $result = $this->query($sql);
        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }
    
    /**
     * 获取记录总数
     * @param string $table 表名
     * @param string $where 条件语句 (可选)
     * @return int 记录数
     */
    public function getCount($table, $where = '') {
        $sql = "SELECT COUNT(*) as total FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $result = $this->getRow($sql);
        return $result ? intval($result['total']) : 0;
    }
    
    /**
     * 插入记录
     * @param string $table 表名
     * @param array $data 数据数组
     * @return int|bool 插入ID或false
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        
        // 预处理字段和值
        $fieldStr = '`' . implode('`, `', $fields) . '`';
        $valueStr = '';
        foreach ($values as $value) {
            $valueStr .= "'" . $this->escape($value) . "', ";
        }
        $valueStr = rtrim($valueStr, ', ');
        
        $sql = "INSERT INTO $table ($fieldStr) VALUES ($valueStr)";
        $result = $this->query($sql);
        
        return $result ? $this->conn->insert_id : false;
    }
    
    /**
     * 更新记录
     * @param string $table 表名
     * @param array $data 数据数组
     * @param string $where 条件语句
     * @return bool 是否成功
     */
    public function update($table, $data, $where) {
        $setStr = '';
        foreach ($data as $key => $value) {
            $setStr .= "`$key` = '" . $this->escape($value) . "', ";
        }
        $setStr = rtrim($setStr, ', ');
        
        $sql = "UPDATE $table SET $setStr WHERE $where";
        return $this->query($sql);
    }
    
    /**
     * 删除记录
     * @param string $table 表名
     * @param string $where 条件语句
     * @return bool 是否成功
     */
    public function delete($table, $where) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql);
    }
    
    /**
     * 转义字符串
     * @param string $str 需要转义的字符串
     * @return string 转义后的字符串
     */
    public function escape($str) {
        return $this->conn->real_escape_string($str);
    }
    
    /**
     * 获取最后一次执行的SQL错误
     * @return string 错误信息
     */
    public function getError() {
        return $this->conn->error;
    }
    
    /**
     * 开始事务
     */
    public function beginTransaction() {
        $this->conn->autocommit(false);
    }
    
    /**
     * 提交事务
     */
    public function commit() {
        $this->conn->commit();
        $this->conn->autocommit(true);
    }
    
    /**
     * 回滚事务
     */
    public function rollback() {
        $this->conn->rollback();
        $this->conn->autocommit(true);
    }
    
    /**
     * 获取分页数据
     * @param string $table 表名
     * @param string $where 条件语句 (可选)
     * @param string $order 排序语句 (可选)
     * @param int $page 当前页码
     * @param int $pageSize 每页记录数
     * @return array 分页数据
     */
    public function getPage($table, $where = '', $order = '', $page = 1, $pageSize = 10) {
        $page = max(1, intval($page));
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT * FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        if (!empty($order)) {
            $sql .= " ORDER BY $order";
        }
        $sql .= " LIMIT $offset, $pageSize";
        
        $data = $this->getAll($sql);
        $total = $this->getCount($table, $where);
        $totalPages = ceil($total / $pageSize);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => $totalPages
        ];
    }
    
    /**
     * 析构函数，关闭数据库连接
     */
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
