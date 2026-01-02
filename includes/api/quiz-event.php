<?php
// includes/api/quiz-event.php

function update_quiz_event_name($data) {
    global $pdo;
    
    // Проверяем авторизацию администратора
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Не авторизован']);
        exit;
    }
    
    $event_name = trim($data['event_name'] ?? '');
    
    if (empty($event_name)) {
        json_response(['success' => false, 'error' => 'Название мероприятия не может быть пустым']);
    }
    
    try {
        // Обновляем название квиза в таблице quiz_events
        $stmt = $pdo->prepare("UPDATE quiz_events SET event_name = ?, updated_at = NOW() WHERE id = 1");
        $stmt->execute([$event_name]);
        
        json_response(['success' => true, 'message' => 'Название квиза обновлено!']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка обновления: ' . $e->getMessage()]);
    }
}
?>