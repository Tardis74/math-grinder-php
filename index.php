<?php
// index.php - Умная переадресация для участников
require_once 'config.php';

// Получаем режим мероприятия
try {
    $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
    $stmt->execute();
    $state = $stmt->fetch();
    $event_mode = $state ? $state['event_mode'] : 'grinder';
    
    // Перенаправляем участника в соответствующий интерфейс
    if ($event_mode === 'quiz') {
        header('Location: quiz-client.php');
    } else {
        header('Location: grinder-client.php');
    }
    exit;
    
} catch (PDOException $e) {
    // При ошибке используем интерфейс мясорубки по умолчанию
    header('Location: grinder-client.php');
    exit;
}
?>