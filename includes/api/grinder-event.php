<?php
// includes/api/grinder-event.php

function get_grinder_event_state_full() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM grinder_events WHERE id = 1");
        $state = $stmt->fetch();
        
        if (!$state) {
            $state = [
                'event_name' => 'Математическая мясорубка',
                'event_status' => 'not_started',
                'timer_duration' => 3600,
                'timer_remaining' => 3600,
                'is_accepting_answers' => true,
                'is_ranking_frozen' => false
            ];
        }
        
        // Отправляем JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($state, JSON_UNESCAPED_UNICODE);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'Database error: ' . $e->getMessage(),
            'event_name' => 'Математическая мясорубка',
            'event_status' => 'not_started',
            'timer_duration' => 3600,
            'timer_remaining' => 3600,
            'is_accepting_answers' => true,
            'is_ranking_frozen' => false
        ]);
        exit;
    }
}

function update_grinder_event_settings($data) {
    global $pdo;
    
    // ВАЖНО: Проверяем сессию
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Проверяем авторизацию
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        exit;
    }
    
    try {
        $event_name = $data['event_name'] ?? 'Математическая мясорубка';
        $timer_duration = intval($data['timer_duration'] ?? 3600);
        
        if (empty($event_name)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Название мероприятия не может быть пустым']);
            exit;
        }
        
        if ($timer_duration <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Длительность таймера должна быть положительной']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE grinder_events SET 
            event_name = ?,
            timer_duration = ?,
            updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute([$event_name, $timer_duration]);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'Настройки мясорубки обновлены!']);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Ошибка обновления настроек: ' . $e->getMessage()]);
        exit;
    }
}
?>