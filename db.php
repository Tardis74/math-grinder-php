<?php
// db.php - Подключение к базе данных и базовые функции

class Database {
    private $host = 'localhost';
    private $db_name = 'math_grinder';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Ошибка подключения к БД: " . $exception->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }
        return $this->conn;
    }
}

// Создание экземпляра базы данных
$database = new Database();
try {
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Базовые функции для работы с БД
function executeQuery($sql, $params = []) {
    global $db;
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Ошибка выполнения запроса: " . $e->getMessage());
        return false;
    }
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
}

function insert($table, $data) {
    global $db;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Ошибка вставки данных: " . $e->getMessage());
        return false;
    }
}

function update($table, $data, $where) {
    global $db;
    
    $set = [];
    foreach ($data as $key => $value) {
        $set[] = "$key = :$key";
    }
    $set = implode(', ', $set);
    
    $where_conditions = [];
    $where_params = [];
    foreach ($where as $key => $value) {
        $where_conditions[] = "$key = :where_$key";
        $where_params["where_$key"] = $value;
    }
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "UPDATE $table SET $set WHERE $where_clause";
    $params = array_merge($data, $where_params);
    
    try {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Ошибка обновления данных: " . $e->getMessage());
        return false;
    }
}

function delete($table, $where) {
    global $db;
    
    $where_conditions = [];
    $params = [];
    foreach ($where as $key => $value) {
        $where_conditions[] = "$key = :$key";
        $params[$key] = $value;
    }
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "DELETE FROM $table WHERE $where_clause";
    
    try {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Ошибка удаления данных: " . $e->getMessage());
        return false;
    }
}
?>