<?php
// config.php - Основной конфигурационный файл

date_default_timezone_set('Asia/Yekaterinburg'); // Или ваш часовой пояс  Europe/Moscow

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

function get_grinder_event_state() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM grinder_events WHERE id = 1");
        $state = $stmt->fetch();
        
        if (!$state) {
            // Создаем запись по умолчанию
            $stmt = $pdo->prepare("
                INSERT INTO grinder_events (id, event_name, timer_duration) 
                VALUES (1, 'Математическая мясорубка', 3600)
            ");
            $stmt->execute();
            $stmt = $pdo->query("SELECT * FROM grinder_events WHERE id = 1");
            $state = $stmt->fetch();
        }
        
        // Вычисляем оставшееся время для мясорубки
        if ($state['event_status'] === 'running' && $state['event_end_time']) {
            $end_time = strtotime($state['event_end_time']);
            $current_time = time();
            $remaining = max(0, $end_time - $current_time);
            $state['timer_remaining'] = $remaining;
            
            if ($remaining <= 0) {
                $stmt = $pdo->prepare("
                    UPDATE grinder_events 
                    SET event_status = 'finished', is_accepting_answers = 0 
                    WHERE id = 1
                ");
                $stmt->execute();
                $state['event_status'] = 'finished';
                $state['is_accepting_answers'] = 0;
                $state['timer_remaining'] = 0;
            }
        } else {
            $state['timer_remaining'] = $state['timer_duration'];
        }
        
        return $state;
    } catch (PDOException $e) {
        error_log("Get grinder event state error: " . $e->getMessage());
        return [
            'event_name' => 'Математическая мясорубка',
            'event_status' => 'not_started',
            'timer_duration' => 3600,
            'timer_remaining' => 3600,
            'is_accepting_answers' => true,
            'is_ranking_frozen' => false
        ];
    }
}

function get_quiz_event_state() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM quiz_events WHERE id = 1");
        $state = $stmt->fetch();
        
        if (!$state) {
            // Создаем запись по умолчанию
            $stmt = $pdo->prepare("
                INSERT INTO quiz_events (id, event_name, timer_duration) 
                VALUES (1, 'Математический квиз', 1800)
            ");
            $stmt->execute();
            $stmt = $pdo->query("SELECT * FROM quiz_events WHERE id = 1");
            $state = $stmt->fetch();
        }
        
        // Вычисляем оставшееся время для квиза
        if ($state['event_status'] === 'running' && $state['event_end_time']) {
            $end_time = strtotime($state['event_end_time']);
            $current_time = time();
            $remaining = max(0, $end_time - $current_time);
            $state['timer_remaining'] = $remaining;
            
            if ($remaining <= 0) {
                $stmt = $pdo->prepare("
                    UPDATE quiz_events 
                    SET event_status = 'finished', is_accepting_answers = 0 
                    WHERE id = 1
                ");
                $stmt->execute();
                $state['event_status'] = 'finished';
                $state['is_accepting_answers'] = 0;
                $state['timer_remaining'] = 0;
            }
        } else {
            $state['timer_remaining'] = $state['timer_duration'];
        }
        
        return $state;
    } catch (PDOException $e) {
        error_log("Get quiz event state error: " . $e->getMessage());
        return [
            'event_name' => 'Математический квиз',
            'event_status' => 'not_started',
            'timer_duration' => 1800,
            'timer_remaining' => 1800,
            'is_accepting_answers' => true,
            'is_ranking_frozen' => false
        ];
    }
}

function update_quiz_event_settings($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            UPDATE quiz_events SET 
            event_name = ?,
            timer_duration = ?,
            updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute([
            $data['event_name'] ?? 'Математический квиз',
            $data['timer_duration'] ?? 1800
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Update quiz event settings error: " . $e->getMessage());
        return false;
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

function get_event_state() {
    // Получаем текущий режим
    $stmt = $pdo->query("SELECT event_mode FROM event_state WHERE id = 1");
    $mode = $stmt->fetchColumn();
    
    // Возвращаем соответствующее состояние
    if ($mode === 'quiz') {
        return get_quiz_event_state();
    } else {
        return get_grinder_event_state();
    }
}


function is_accepting_answers() {
    $state = get_event_state();
    return $state['is_accepting_answers'] ?? true;
}
?>