<?php
require_once __DIR__ . '/../functions.php';

function export_to_excel() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // Получаем данные участников
        $stmt = $pdo->query("
            SELECT p.team, p.score, 
                   GROUP_CONCAT(CONCAT('Q', a.question_id, ': ', a.points) ORDER BY a.question_id SEPARATOR '; ') as answers,
                   COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) as correct_answers,
                   COUNT(a.id) as total_answers
            FROM participants p
            LEFT JOIN answers a ON p.id = a.participant_id
            GROUP BY p.id, p.team, p.score
            ORDER BY p.score DESC
        ");
        $participants = $stmt->fetchAll();
        
        // Формируем CSV данные
        $csv_data = "Команда;Общий балл;Правильные ответы;Всего ответов;Детали по вопросам\n";
        
        foreach ($participants as $participant) {
            $team = $participant['team'];
            $score = $participant['score'];
            $correct_answers = $participant['correct_answers'];
            $total_answers = $participant['total_answers'];
            $answers_details = $participant['answers'] ?? 'Нет ответов';
            
            // Экранируем данные для CSV
            $team = str_replace('"', '""', $team);
            $answers_details = str_replace('"', '""', $answers_details);
            
            $csv_data .= "\"$team\";$score;$correct_answers;$total_answers;\"$answers_details\"\n";
        }
        
        // Возвращаем данные для скачивания на клиенте
        json_response([
            'success' => true,
            'message' => 'Данные для Excel готовы',
            'file_data' => $csv_data,
            'file_name' => 'results_excel_' . date('Y-m-d_H-i-s') . '.csv'
        ]);
        
    } catch (Exception $e) {
        error_log("Export to Excel error: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка экспорта: ' . $e->getMessage()]);
    }
}

function export_to_csv() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // Получаем детальные результаты
        $stmt = $pdo->query("
            SELECT 
                p.team,
                p.score as total_score,
                q.id as question_id,
                q.text as question_text,
                a.answer as participant_answer,
                a.is_correct,
                a.points,
                a.answer_order,
                CASE 
                    WHEN a.answer_order = 1 THEN 'Первый (+20)'
                    WHEN a.answer_order = 2 THEN 'Второй (+15)' 
                    WHEN a.answer_order > 2 THEN 'Последующий (+10)'
                    ELSE 'Неправильный'
                END as answer_position
            FROM participants p
            LEFT JOIN answers a ON p.id = a.participant_id
            LEFT JOIN questions q ON a.question_id = q.id
            ORDER BY p.score DESC, p.team, q.id
        ");
        $results = $stmt->fetchAll();
        
        // Формируем детальный CSV
        $csv_data = "Команда;Общий балл;Номер вопроса;Текст вопроса;Ответ участника;Правильность;Баллы;Позиция ответа\n";
        
        foreach ($results as $row) {
            $team = $row['team'];
            $total_score = $row['total_score'];
            $question_id = $row['question_id'] ?? 'Н/Д';
            $question_text = $row['question_text'] ?? 'Н/Д';
            $participant_answer = $row['participant_answer'] ?? 'Нет ответа';
            $is_correct = $row['is_correct'] ? 'Правильно' : 'Неправильно';
            $points = $row['points'] ?? '0';
            $answer_position = $row['answer_position'] ?? 'Н/Д';
            
            // Экранируем данные для CSV
            $team = str_replace('"', '""', $team);
            $question_text = str_replace('"', '""', $question_text);
            $participant_answer = str_replace('"', '""', $participant_answer);
            
            $csv_data .= "\"$team\";$total_score;$question_id;\"$question_text\";\"$participant_answer\";$is_correct;$points;\"$answer_position\"\n";
        }
        
        // Получаем статистику по попыткам списывания
        $stmt = $pdo->query("
            SELECT 
                COALESCE(p.team, 'Неизвестная команда') as team,
                COUNT(*) as cheating_count
            FROM cheating_attempts c
            LEFT JOIN participants p ON c.participant_id = p.id
            GROUP BY p.team
            ORDER BY cheating_count DESC
        ");
        $cheating_stats = $stmt->fetchAll();
        
        // Добавляем статистику списывания в CSV
        $csv_data .= "\n;;;Статистика списывания;;;\n";
        $csv_data .= "Команда;Количество нарушений\n";
        
        foreach ($cheating_stats as $stat) {
            $team = str_replace('"', '""', $stat['team']);
            $count = $stat['cheating_count'];
            $csv_data .= "\"$team\";$count\n";
        }
        
        // Возвращаем данные для скачивания на клиенте
        json_response([
            'success' => true,
            'message' => 'Детальные данные для CSV готовы',
            'file_data' => $csv_data,
            'file_name' => 'results_detailed_' . date('Y-m-d_H-i-s') . '.csv'
        ]);
        
    } catch (Exception $e) {
        error_log("Export to CSV error: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка экспорта: ' . $e->getMessage()]);
    }
}
?>