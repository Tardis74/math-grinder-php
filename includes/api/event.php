<?php
require_once __DIR__ . '/../functions.php';
function freeze_ranking() {
    global $pdo;
    
    // Простая и надежная версия
    $stmt = $pdo->prepare("UPDATE event_state SET is_ranking_frozen = 1 WHERE id = 1");
    $result = $stmt->execute();
    
    json_response([
        'success' => true, 
        'message' => 'Рейтинг заморожен',
        'affected_rows' => $stmt->rowCount()
    ]);
}

function unfreeze_ranking() {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE event_state SET is_ranking_frozen = 0 WHERE id = 1");
    $result = $stmt->execute();
    
    json_response([
        'success' => true, 
        'message' => 'Рейтинг разморожен',
        'affected_rows' => $stmt->rowCount()
    ]);
}

function start_timer() {
    global $pdo;
    
    $start_time = time() * 1000;
    $stmt = $pdo->prepare("UPDATE event_state SET event_start_time = ? WHERE id = 1");
    $result = $stmt->execute([$start_time]);
    
    json_response(['success' => true, 'message' => 'Таймер запущен']);
}

function stop_timer() {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE event_state SET event_start_time = NULL WHERE id = 1");
    $result = $stmt->execute();
    
    json_response(['success' => true, 'message' => 'Таймер остановлен']);
}

function stop_answers() {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE event_state SET is_accepting_answers = 0 WHERE id = 1");
    $result = $stmt->execute();
    
    json_response(['success' => true, 'message' => 'Прием ответов остановлен']);
}

function resume_answers() {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE event_state SET is_accepting_answers = 1 WHERE id = 1");
    $result = $stmt->execute();
    
    json_response(['success' => true, 'message' => 'Прием ответов возобновлен']);
}

function get_event_state() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1");
    $state = $stmt->fetch();
    
    if (!$state) {
        // Создаем запись если не существует
        $stmt = $pdo->prepare("INSERT INTO event_state (id, is_ranking_frozen, is_accepting_answers) VALUES (1, 0, 1)");
        $stmt->execute();
        $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1");
        $state = $stmt->fetch();
    }
    
    json_response($state);
}
?>