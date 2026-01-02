<?php
require_once __DIR__ . '/../functions.php';

function save_results() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // Определяем тип мероприятия
        $event_type = $_POST['event_type'] ?? 'grinder';
        
        if ($event_type === 'grinder') {
            // Результаты для мясорубки
            $stmt = $pdo->query("
                SELECT p.team, p.score, q.text as question_text, 
                       a.answer, a.is_correct, a.points, a.answer_order
                FROM participants p
                LEFT JOIN answers a ON p.id = a.participant_id
                LEFT JOIN questions q ON a.question_id = q.id
                WHERE p.event_type = 'grinder' 
                AND (a.event_type = 'grinder' OR a.event_type IS NULL)
                AND (q.event_type = 'grinder' OR q.event_type IS NULL)
                ORDER BY p.score DESC, p.team, q.id
            ");
            $results = $stmt->fetchAll();
            
            // Получаем попытки списывания
            $stmt = $pdo->query("
                SELECT ca.*, p.team
                FROM cheating_attempts ca
                LEFT JOIN participants p ON ca.participant_id = p.id
                WHERE p.event_type = 'grinder'
                ORDER BY p.team, ca.detected_at
            ");
            $cheating_attempts = $stmt->fetchAll();
            
            $event_name = 'Математическая мясорубка';
        } else {
            // Результаты для квиза
            $stmt = $pdo->query("
                SELECT p.team, p.score as total_score,
                       COUNT(DISTINCT qpa.quiz_question_id) as questions_answered,
                       SUM(CASE WHEN qpa.points_earned > 0 THEN 1 ELSE 0 END) as correct_answers,
                       SUM(qpa.points_earned) as quiz_score
                FROM participants p
                LEFT JOIN quiz_participant_answers qpa ON p.id = qpa.participant_id
                WHERE p.event_type = 'quiz'
                GROUP BY p.id
                ORDER BY quiz_score DESC, p.team
            ");
            $results = $stmt->fetchAll();
            
            // Получаем детали ответов на квиз
            $stmt = $pdo->query("
                SELECT p.team, qq.question_text, qa.answer_text, 
                       qpa.answered_at, qpa.points_earned
                FROM quiz_participant_answers qpa
                JOIN participants p ON qpa.participant_id = p.id
                JOIN quiz_questions qq ON qpa.quiz_question_id = qq.id
                LEFT JOIN quiz_answers qa ON qpa.quiz_answer_id = qa.id
                WHERE p.event_type = 'quiz'
                ORDER BY p.team, qq.display_order
            ");
            $detailed_answers = $stmt->fetchAll();
            
            $cheating_attempts = [];
            $event_name = 'Математический квиз';
        }
        
        // Формируем данные для JSON
        $data = [
            'export_time' => date('Y-m-d H:i:s'),
            'event_name' => $event_name,
            'event_type' => $event_type,
            'participants_count' => count($results),
            'results' => $results,
            'cheating_attempts' => $cheating_attempts,
            'cheating_summary' => [
                'total_attempts' => count($cheating_attempts),
                'teams_with_attempts' => count(array_unique(array_column($cheating_attempts, 'team')))
            ]
        ];
        
        if ($event_type === 'quiz' && isset($detailed_answers)) {
            $data['detailed_answers'] = $detailed_answers;
        }
        
        // Возвращаем данные для скачивания на клиенте
        json_response([
            'success' => true, 
            'message' => 'Результаты готовы к скачиванию',
            'file_data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'file_name' => 'results_' . $event_type . '_' . date('Y-m-d_H-i-s') . '.json'
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
        // Определяем тип мероприятия
        $event_type = $_POST['event_type'] ?? 'grinder';
        
        if ($event_type === 'grinder') {
            // УДАЛЯЕМ участников мясорубки
            $pdo->exec("DELETE FROM answers WHERE event_type = 'grinder'");
            $pdo->exec("DELETE FROM cheating_attempts WHERE participant_id IN (SELECT id FROM participants WHERE event_type = 'grinder')");
            $pdo->exec("DELETE FROM participants WHERE event_type = 'grinder'");
            
            // Сбрасываем состояние мясорубки
            $stmt = $pdo->prepare("
                UPDATE grinder_events SET 
                event_status = 'not_started',
                is_accepting_answers = 1,
                is_ranking_frozen = 0,
                event_start_time = NULL,
                event_end_time = NULL,
                updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute();
            
            json_response([
                'success' => true, 
                'message' => 'Данные мясорубки очищены'
            ]);
            
        } else {
            // УДАЛЯЕМ участников квиза
            $pdo->exec("DELETE FROM quiz_participant_answers");
            $pdo->exec("DELETE FROM participants WHERE event_type = 'quiz'");
            $pdo->exec("DELETE FROM current_quiz_question");
            $pdo->exec("DELETE FROM quiz_session");
            
            // Сбрасываем состояние квиза
            $stmt = $pdo->prepare("
                UPDATE quiz_events SET 
                event_status = 'not_started',
                is_accepting_answers = 1,
                is_ranking_frozen = 0,
                event_start_time = NULL,
                event_end_time = NULL,
                updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute();
            
            // Также сбрасываем состояние в event_state
            $stmt = $pdo->prepare("
                UPDATE event_state SET 
                event_status = 'not_started',
                is_accepting_answers = 1,
                is_ranking_frozen = 0,
                event_start_time = NULL,
                event_end_time = NULL,
                updated_at = NOW()
                WHERE id = 1
            ");
            $stmt->execute();
            
            json_response([
                'success' => true, 
                'message' => 'Данные квиза очищены'
            ]);
        }
        
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => 'Ошибка очистки: ' . $e->getMessage()]);
    }
}
?>