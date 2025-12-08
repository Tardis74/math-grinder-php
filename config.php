<?php
// config.php - Основной конфигурационный файл

date_default_timezone_set('Europe/Moscow'); // Или ваш часовой пояс

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'math_grinder');
define('DB_USER', 'root');
define('DB_PASS', '');

define('TIME_CORRECTION', 0); // В секундах, если нужно корректировать

// Режимы мероприятия
define('EVENT_MODE_GRINDER', 'grinder');
define('EVENT_MODE_QUIZ', 'quiz');

// Статусы участников в зале ожидания
define('WAITING_STATUS_WAITING', 'waiting');
define('WAITING_STATUS_APPROVED', 'approved');
define('WAITING_STATUS_REJECTED', 'rejected');

// Настройки приложения
define('BASE_URL', 'http://localhost/math-grinder-php');
define('SITE_NAME', 'Математическая мясорубка');

// Настройки сессии
session_start();

// Подключение к базе данных
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Добавьте эту строку для включения автокоммита
    $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
} catch(PDOException $e) {
    error_log("Ошибка подключения к БД: " . $e->getMessage());
    die("Ошибка подключения к базе данных");
}

function is_superadmin() {
    return isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] === true;
}

function check_superadmin_auth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: admin-login.php');
        exit;
    }
    if (!is_superadmin()) {
        header('Location: admin-main.php');
        exit;
    }
}

function update_event_state($data) {
    global $pdo;
    try {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        
        $sql = "UPDATE event_state SET " . implode(', ', $fields) . " WHERE id = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return true;
    } catch (PDOException $e) {
        error_log("Update event state error: " . $e->getMessage());
        return false;
    }
}

function is_ranking_frozen() {
    $state = get_event_state();
    return $state['is_ranking_frozen'] ?? false;
}

function is_accepting_answers() {
    $state = get_event_state();
    return $state['is_accepting_answers'] ?? true;
}
?>