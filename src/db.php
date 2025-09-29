<?php

class Database {
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;
    private $pdo;
    
    public function __construct() {
        $this->host = 'localhost';
        $this->database = 'apple_clone';
        $this->username = 'root';  
        $this->password = '';      
        $this->charset = 'utf8mb4';
        
        $this->connect();
    }
    
    private function connect() {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database query error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }
    
    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('Database insert error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function update($table, $data, $where) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
        }
        $fields = implode(', ', $fields);
        
        $whereClause = [];
        foreach ($where as $key => $value) {
            $whereClause[] = "{$key} = :where_{$key}";
        }
        $whereClause = implode(' AND ', $whereClause);
        
        $sql = "UPDATE {$table} SET {$fields} WHERE {$whereClause}";
        
        $params = $data;
        foreach ($where as $key => $value) {
            $params["where_{$key}"] = $value;
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Database update error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($table, $where) {
        $whereClause = [];
        foreach ($where as $key => $value) {
            $whereClause[] = "{$key} = :{$key}";
        }
        $whereClause = implode(' AND ', $whereClause);
        
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($where);
        } catch (PDOException $e) {
            error_log('Database delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
}

$db = new Database();
?>