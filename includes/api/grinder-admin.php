<?php
// includes/api/grinder-admin.php
require_once __DIR__ . '/../functions.php';

function start_grinder_event() {
    global $pdo;
    check_admin_auth();
    
    try {
        // Получаем длительность таймера
        $stmt = $pdo->query("SELECT timer_duration FROM grinder_events WHERE id = 1");
        $event = $stmt->fetch();
        $timer_duration = $event['timer_duration'] ?? 3600;
        
        // Устанавливаем время начала и окончания
        $event_start_time = date('Y-m-d H:i:s');
        $event_end_time = date('Y-m-d H:i:s', time() + $timer_duration);
        
        $stmt = $pdo->prepare("
            UPDATE grinder_events SET 
            event_status = 'running',
            event_start_time = ?,
            event_end_time = ?,
            is_accepting_answers = 1,
            updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute([$event_start_time, $event_end_time]);
        
        json_response(['success' => true, 'message' => 'Мясорубка начата!']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка начала мясорубки: ' . $e->getMessage()]);
    }
}

function finish_grinder_event() {
    global $pdo;
    check_admin_auth();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE grinder_events SET 
            event_status = 'finished',
            is_accepting_answers = 0,
            event_end_time = NULL,
            updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute();
        json_response(['success' => true, 'message' => 'Мясорубка завершена!']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка завершения мясорубки: ' . $e->getMessage()]);
    }
}

function reset_grinder_event() {
    global $pdo;
    check_admin_auth();
    
    try {
        $stmt = $pdo->prepare("
            UPDATE grinder_events SET 
            event_status = 'not_started',
            is_accepting_answers = 0,
            is_ranking_frozen = 0,
            event_start_time = NULL,
            event_end_time = NULL,
            updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute();
        json_response(['success' => true, 'message' => 'Мясорубка сброшена!']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка сброса мясорубки: ' . $e->getMessage()]);
    }
}

function stop_grinder_answers() {
    global $pdo;
    check_admin_auth();
    
    try {
        $stmt = $pdo->prepare("UPDATE grinder_events SET is_accepting_answers = 0 WHERE id = 1");
        $stmt->execute();
        json_response(['success' => true, 'message' => 'Прием ответов остановлен']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка остановки приема ответов: ' . $e->getMessage()]);
    }
}

function resume_grinder_answers() {
    global $pdo;
    check_admin_auth();
    
    try {
        $stmt = $pdo->prepare("UPDATE grinder_events SET is_accepting_answers = 1 WHERE id = 1");
        $stmt->execute();
        json_response(['success' => true, 'message' => 'Прием ответов возобновлен']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка возобновления приема ответов: ' . $e->getMessage()]);
    }
}

function freeze_grinder_ranking() {
    global $pdo;
    check_admin_auth();
    
    try {
        $stmt = $pdo->prepare("UPDATE grinder_events SET is_ranking_frozen = 1 WHERE id = 1");
        $stmt->execute();
        json_response(['success' => true, 'message' => 'Рейтинг заморожен']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка заморозки рейтинга: ' . $e->getMessage()]);
    }
}

function unfreeze_grinder_ranking() {
    global $pdo;
    check_admin_auth();
    
    try {
        $stmt = $pdo->prepare("UPDATE grinder_events SET is_ranking_frozen = 0 WHERE id = 1");
        $stmt->execute();
        json_response(['success' => true, 'message' => 'Рейтинг разморожен']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка разморозки рейтинга: ' . $e->getMessage()]);
    }
}
?>