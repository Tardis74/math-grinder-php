<?php
require_once __DIR__ . '/../functions.php';

function save_results() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // Получаем детальные результаты
        $stmt = $pdo->query("
            SELECT p.team, p.score, q.text as question_text, 
                   a.answer, a.is_correct, a.points, a.answer_order
            FROM participants p
            LEFT JOIN answers a ON p.id = a.participant_id
            LEFT JOIN questions q ON a.question_id = q.id
            ORDER BY p.score DESC, p.team, q.id
        ");
        $results = $stmt->fetchAll();
        
        // Получаем попытки списывания
        $stmt = $pdo->query("
            SELECT team, question_id, suspicious_activity, detected_at
            FROM cheating_attempts
            ORDER BY team, detected_at
        ");
        $cheating_attempts = $stmt->fetchAll();
        
        // Формируем данные для JSON
        $data = [
            'export_time' => date('Y-m-d H:i:s'),
            'event_name' => 'Математическая мясорубка',
            'participants_count' => count(array_unique(array_column($results, 'team'))),
            'results' => $results,
            'cheating_attempts' => $cheating_attempts,
            'cheating_summary' => [
                'total_attempts' => count($cheating_attempts),
                'teams_with_attempts' => count(array_unique(array_column($cheating_attempts, 'team')))
            ]
        ];
        
        // Возвращаем данные для скачивания на клиенте
        json_response([
            'success' => true, 
            'message' => 'Результаты готовы к скачиванию',
            'file_data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'file_name' => 'results_' . date('Y-m-d_H-i-s') . '.json'
        ]);
        
    } catch (Exception $e) {
        error_log("Save results error: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка сохранения результатов: ' . $e->getMessage()]);
    }
}

function clear_results() {
    global $pdo;
    check_admin_auth();
    
    try {
        $pdo->exec("DELETE FROM answers");
        $pdo->exec("DELETE FROM cheating_attempts");
        $pdo->exec("DELETE FROM participants");
        // УБИРАЕМ is_accepting_answers - оставляем только сброс заморозки рейтинга
        $pdo->exec("UPDATE event_state SET is_ranking_frozen = 0, event_start_time = NULL, event_status = 'not_started'");
        
        json_response([
            'success' => true, 
            'message' => 'Все данные полностью очищены'
        ]);
        
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => 'Ошибка очистки: ' . $e->getMessage()]);
    }
}
?>