<?php
// includes/api/quiz-session.php

function get_quiz_session() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM quiz_session WHERE id = 1");
        $session = $stmt->fetch();
        
        if (!$session) {
            $stmt = $pdo->prepare("INSERT INTO quiz_session (id, is_active, phase) VALUES (1, 0, 'waiting')");
            $stmt->execute();
            $stmt = $pdo->query("SELECT * FROM quiz_session WHERE id = 1");
            $session = $stmt->fetch();
        }
        
        $current_question = null;
        if ($session['current_question_id']) {
            $stmt = $pdo->prepare("
                SELECT qq.*, 
                       GROUP_CONCAT(CONCAT_WS('|', qa.id, qa.answer_text, qa.is_correct, qa.points, qa.display_order) 
                       ORDER BY qa.display_order SEPARATOR ';;') as answers_data
                FROM quiz_questions qq
                LEFT JOIN quiz_answers qa ON qq.id = qa.quiz_question_id
                WHERE qq.id = ?
                GROUP BY qq.id
            ");
            $stmt->execute([$session['current_question_id']]);
            $question_data = $stmt->fetch();
            
            if ($question_data) {
                $current_question = [
                    'id' => $question_data['id'],
                    'quiz_question_id' => $question_data['id'],
                    'question_text' => $question_data['question_text'],
                    'question_type' => $question_data['question_type'],
                    'question_time' => $question_data['question_time'],
                    'answer_time' => $question_data['answer_time'],
                    'display_order' => $question_data['display_order'],
                    'answers' => []
                ];
                
                if ($question_data['answers_data']) {
                    $answers = explode(';;', $question_data['answers_data']);
                    foreach ($answers as $answer) {
                        list($id, $text, $is_correct, $points, $order) = explode('|', $answer);
                        $current_question['answers'][] = [
                            'id' => (int)$id,
                            'answer_text' => $text,
                            'is_correct' => (bool)$is_correct,
                            'points' => (int)$points,
                            'display_order' => (int)$order
                        ];
                    }
                }
            }
        }
        
        // ИСПРАВЛЕНИЕ: используем единое время PHP для всех расчетов
        $now = time();
        $time_remaining = null;
        $should_advance = false;
        $next_action = null;
        
        if ($session['phase'] === 'question' && $session['question_start_time']) {
            // Преобразуем MySQL время в timestamp
            $start_time = strtotime($session['question_start_time']);
            
            // Корректируем часовой пояс если нужно (убираем жесткую коррекцию)
            $time_diff = $start_time - $now;
            if (abs($time_diff) > 7200) { // Если разница больше 2 часов
                // Используем текущее время как точку отсчета
                $start_time = $now - 1; // Начинаем с 1 секунды назад
                // Обновляем время в БД для корректности
                $update_stmt = $pdo->prepare("UPDATE quiz_session SET question_start_time = NOW() WHERE id = 1");
                $update_stmt->execute();
            }
            
            $end_time = $start_time + ($current_question['question_time'] ?? 30);
            $time_remaining = max(0, $end_time - $now);
            
            if ($time_remaining <= 0) {
                $should_advance = true;
                $next_action = 'answers';
            }
            
        } elseif ($session['phase'] === 'answers' && $session['answers_start_time']) {
            $start_time = strtotime($session['answers_start_time']);
            
            // Корректируем часовой пояс если нужно
            $time_diff = $start_time - $now;
            if (abs($time_diff) > 7200) {
                $start_time = $now - 1;
                $update_stmt = $pdo->prepare("UPDATE quiz_session SET answers_start_time = NOW() WHERE id = 1");
                $update_stmt->execute();
            }
            
            $end_time = $start_time + ($current_question['answer_time'] ?? 10);
            $time_remaining = max(0, $end_time - $now);
            
            if ($time_remaining <= 0) {
                $should_advance = true;
                $next_action = 'next_question';
            }
        }
        
        // Автоматическое продвижение фаз
        if ($should_advance) {
            if ($next_action === 'answers') {
                advance_to_answers_phase();
            } elseif ($next_action === 'next_question') {
                advance_to_next_question();
            }
            // Получаем обновленные данные
            return get_quiz_session();
        }
        
        // Получаем статистику
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM participants");
        $participants_count = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM quiz_questions");
        $questions_count = $stmt->fetch()['count'];
        
        $event_stmt = $pdo->query("SELECT event_status FROM event_state WHERE id = 1");
        $event_state = $event_stmt->fetch();
        
        // Получаем количество ответов на текущий вопрос
        $current_answers_count = 0;
        if ($session['current_question_id']) {
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT participant_id) as answer_count 
                FROM quiz_participant_answers 
                WHERE quiz_question_id = ?
            ");
            $stmt->execute([$session['current_question_id']]);
            $answers_data = $stmt->fetch();
            $current_answers_count = $answers_data['answer_count'] ?? 0;
        }
        
        json_response([
            'success' => true,
            'session' => $session,
            'current_question' => $current_question,
            'time_remaining' => $time_remaining,
            'stats' => [
                'participants_count' => $participants_count,
                'questions_count' => $questions_count,
                'current_answers_count' => $current_answers_count,
                'event_status' => $event_state ? $event_state['event_status'] : 'not_started'
            ],
            'server_time' => $now,
            'debug_info' => [
                'phase' => $session['phase'],
                'should_advance' => $should_advance,
                'next_action' => $next_action
            ]
        ]);
        
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка получения сессии: ' . $e->getMessage()]);
    }
}

function start_quiz() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // Получаем первый вопрос
        $stmt = $pdo->query("SELECT * FROM quiz_questions ORDER BY display_order LIMIT 1");
        $first_question = $stmt->fetch();
        
        if (!$first_question) {
            throw new Exception('Нет доступных вопросов');
        }
        
        // Получаем следующий вопрос
        $stmt = $pdo->prepare("
            SELECT * FROM quiz_questions 
            WHERE display_order > ? 
            ORDER BY display_order 
            LIMIT 1
        ");
        $stmt->execute([$first_question['display_order']]);
        $next_question = $stmt->fetch();
        
        // Начинаем квиз с первого вопроса
        $stmt = $pdo->prepare("
            UPDATE quiz_session SET 
            current_question_id = ?,
            next_question_id = ?,
            phase = 'question',
            question_start_time = NOW(),
            is_active = 1
            WHERE id = 1
        ");
        $stmt->execute([
            $first_question['id'],
            $next_question ? $next_question['id'] : null
        ]);
        
        // Обновляем статус мероприятия
        $stmt = $pdo->prepare("UPDATE event_state SET event_status = 'running' WHERE id = 1");
        $stmt->execute();
        
        $pdo->commit();
        
        json_response(['success' => true, 'message' => 'Квиз начат!']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

function advance_to_answers_phase() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE quiz_session SET 
            phase = 'answers',
            answers_start_time = NOW()
            WHERE id = 1 AND phase = 'question'
        ");
        $result = $stmt->execute();
        
        error_log("Advanced to answers phase: " . ($result ? 'success' : 'failed'));
        return $result;
    } catch (PDOException $e) {
        error_log("Error advancing to answers: " . $e->getMessage());
        return false;
    }
}

function advance_to_next_question() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Получаем текущую сессию
        $stmt = $pdo->query("SELECT * FROM quiz_session WHERE id = 1");
        $session = $stmt->fetch();
        
        if (!$session['next_question_id']) {
            // Нет следующего вопроса - завершаем квиз
            $stmt = $pdo->prepare("
                UPDATE quiz_session SET 
                current_question_id = NULL,
                next_question_id = NULL,
                phase = 'waiting',
                is_active = 0
                WHERE id = 1
            ");
            $stmt->execute();
            
            $stmt = $pdo->prepare("UPDATE event_state SET event_status = 'finished' WHERE id = 1");
            $stmt->execute();
        } else {
            // Переходим к следующему вопросу
            $next_question_id = $session['next_question_id'];
            
            // Получаем следующий после следующего вопроса
            $stmt = $pdo->prepare("
                SELECT qq1.*, 
                (SELECT qq2.id FROM quiz_questions qq2 
                 WHERE qq2.display_order > qq1.display_order 
                 ORDER BY qq2.display_order LIMIT 1) as next_next_id
                FROM quiz_questions qq1
                WHERE qq1.id = ?
            ");
            $stmt->execute([$next_question_id]);
            $next_data = $stmt->fetch();
            
            $stmt = $pdo->prepare("
                UPDATE quiz_session SET 
                current_question_id = ?,
                next_question_id = ?,
                phase = 'question',
                question_start_time = NOW(),
                answers_start_time = NULL
                WHERE id = 1
            ");
            $stmt->execute([
                $next_question_id,
                $next_data['next_next_id'] ?? null
            ]);
        }
        
        $pdo->commit();
        error_log("Advanced to next question successfully");
        return true;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error advancing to next question: " . $e->getMessage());
        return false;
    }
}

function show_answers() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        advance_to_answers_phase();
        json_response(['success' => true, 'message' => 'Показ ответов активирован']);
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

function next_question() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        advance_to_next_question();
        json_response(['success' => true, 'message' => 'Следующий вопрос активирован']);
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

function reset_quiz() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // Очищаем сессию
        $stmt = $pdo->prepare("
            UPDATE quiz_session SET 
            current_question_id = NULL,
            next_question_id = NULL,
            phase = 'waiting',
            question_start_time = NULL,
            answers_start_time = NULL,
            is_active = 0
            WHERE id = 1
        ");
        $stmt->execute();
        
        // Очищаем данные
        $tables = ['quiz_participant_answers', 'participants'];
        foreach ($tables as $table) {
            $pdo->exec("DELETE FROM $table");
        }
        
        // Сбрасываем статус мероприятия
        $stmt = $pdo->prepare("UPDATE event_state SET event_status = 'not_started' WHERE id = 1");
        $stmt->execute();
        
        $pdo->commit();
        
        json_response(['success' => true, 'message' => 'Квиз сброшен!']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка сброса: ' . $e->getMessage()]);
    }
}
?>