<?php
// includes/api/quiz-session.php

function get_quiz_session() {
    global $pdo;
    
    try {
        // Получаем активную сессию
        $stmt = $pdo->query("SELECT * FROM quiz_session WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $session = $stmt->fetch();
        
        // АВТОМАТИЧЕСКАЯ СМЕНА ФАЗ - ДОБАВЛЯЕМ ЭТУ ЛОГИКУ
        if ($session && $session['phase'] === 'question' && $session['question_start_time']) {
            // Получаем время вопроса
            $stmt = $pdo->prepare("SELECT question_time FROM quiz_questions WHERE id = ?");
            $stmt->execute([$session['current_question_id']]);
            $question_data = $stmt->fetch();
            $question_time = $question_data['question_time'] ?? 30;
            
            // Проверяем, истекло ли время вопроса
            $question_start = strtotime($session['question_start_time']);
            $current_time = time();
            $elapsed_time = $current_time - $question_start;
            
            if ($elapsed_time >= $question_time) {
                // Автоматически переходим к фазе ответов
                $stmt = $pdo->prepare("
                    UPDATE quiz_session SET 
                    phase = 'answers',
                    answers_start_time = NOW()
                    WHERE id = ? AND phase = 'question'
                ");
                $stmt->execute([$session['id']]);
                
                // Обновляем сессию
                $session['phase'] = 'answers';
                $session['answers_start_time'] = date('Y-m-d H:i:s');
                
                error_log("AUTO: Automatically advanced to answers phase for question {$session['current_question_id']}");
            }
        }
        
        if ($session && $session['phase'] === 'answers' && $session['answers_start_time']) {
            // Получаем время показа ответов
            $stmt = $pdo->prepare("SELECT answer_time FROM quiz_questions WHERE id = ?");
            $stmt->execute([$session['current_question_id']]);
            $question_data = $stmt->fetch();
            $answer_time = $question_data['answer_time'] ?? 10;
            
            // Проверяем, истекло ли время показа ответов
            $answers_start = strtotime($session['answers_start_time']);
            $current_time = time();
            $elapsed_time = $current_time - $answers_start;
            
            if ($elapsed_time >= $answer_time) {
                // Автоматически переходим к следующему вопросу
                auto_advance_to_next_question();
                
                // Получаем обновленную сессию
                $stmt = $pdo->query("SELECT * FROM quiz_session WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
                $session = $stmt->fetch();
                
                error_log("AUTO: Automatically advanced to next question");
            }
        }
        
        if (!$session) {
            // Если нет активной сессии, создаем новую
            $stmt = $pdo->prepare("INSERT INTO quiz_session (is_active, phase) VALUES (1, 'waiting')");
            $stmt->execute();
            $session_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("SELECT * FROM quiz_session WHERE id = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch();
        }
        
        $current_question = null;
        if ($session && $session['current_question_id']) {
            // Получаем полную информацию о вопросе с изображением
            $stmt = $pdo->prepare("
                SELECT qq.*, 
                       GROUP_CONCAT(
                           CONCAT_WS('|', qa.id, qa.answer_text, qa.is_correct, qa.points, qa.display_order) 
                           ORDER BY qa.display_order SEPARATOR ';;'
                       ) as answers_data
                FROM quiz_questions qq
                LEFT JOIN quiz_answers qa ON qq.id = qa.quiz_question_id
                WHERE qq.id = ?
                GROUP BY qq.id
            ");
            $stmt->execute([$session['current_question_id']]);
            $current_question = $stmt->fetch();
            
            if ($current_question) {
                // Парсим ответы
                $current_question['answers'] = [];
                if ($current_question['answers_data']) {
                    $answers = explode(';;', $current_question['answers_data']);
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
                unset($current_question['answers_data']);
                
                // ВАЖНО: Исправляем путь к изображению
                if (isset($current_question['image_path']) && $current_question['image_path']) {
                    // Используем относительный путь, который будет корректно обработан на клиенте
                    $current_question['image_url'] = $current_question['image_path'];
                } else {
                    $current_question['image_path'] = null;
                    $current_question['image_url'] = null;
                }
            }
        }
        
        // Рассчитываем оставшееся время
        $time_remaining = null;
        $current_phase = $session['phase'] ?? 'waiting';
        
        if ($current_phase === 'question' && $session['question_start_time']) {
            $question_end = strtotime($session['question_start_time']) + ($current_question['question_time'] ?? 30);
            $time_remaining = max(0, $question_end - time());
        } elseif ($current_phase === 'answers' && $session['answers_start_time']) {
            $answers_end = strtotime($session['answers_start_time']) + ($current_question['answer_time'] ?? 10);
            $time_remaining = max(0, $answers_end - time());
        }
        
        // Получаем полную статистику
        $stats = [];
        
        // 1. Статус мероприятия
        $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1 LIMIT 1");
        $event_state = $stmt->fetch();
        if ($event_state) {
            $stats['event_status'] = $event_state['event_status'] ?? 'not_started';
            $stats['event_mode'] = $event_state['event_mode'] ?? 'quiz';
            $stats['event_name'] = $event_state['event_name'] ?? 'Математический квиз';
        } else {
            $stats['event_status'] = 'not_started';
            $stats['event_mode'] = 'quiz';
            $stats['event_name'] = 'Математический квиз';
        }
        
        // 2. Количество участников (всего зарегистрированных)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM participants");
        $participants_total = $stmt->fetch();
        $stats['participants_total'] = (int)$participants_total['count'];
        
        // 3. Количество активных участников (которые залогинились)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM participants WHERE score >= 0");
        $active_participants = $stmt->fetch();
        $stats['active_participants'] = (int)$active_participants['count'];
        
        // 4. Количество ответов на текущий вопрос
        $current_answers_count = 0;
        if ($session && $session['current_question_id']) {
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT participant_id) as count 
                FROM quiz_participant_answers 
                WHERE quiz_question_id = ?
            ");
            $stmt->execute([$session['current_question_id']]);
            $answers = $stmt->fetch();
            $current_answers_count = (int)$answers['count'];
        }
        $stats['current_answers_count'] = $current_answers_count;
        
        // 5. Активность (количество ответов за последние 5 минут)
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM quiz_participant_answers 
            WHERE answered_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $recent_activity = $stmt->fetch();
        $stats['recent_activity'] = (int)$recent_activity['count'];
        
        // 6. Общее количество вопросов в квизе
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM quiz_questions");
        $total_questions = $stmt->fetch();
        $stats['total_questions'] = (int)$total_questions['count'];
        
        json_response([
            'success' => true,
            'session' => $session,
            'current_question' => $current_question,
            'time_remaining' => $time_remaining,
            'stats' => $stats
        ]);
        
    } catch (PDOException $e) {
        error_log("ERROR in get_quiz_session: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка получения сессии: ' . $e->getMessage()]);
    }
}

function auto_advance_to_next_question() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Получаем текущую сессию
        $stmt = $pdo->query("SELECT * FROM quiz_session WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $session = $stmt->fetch();
        
        if (!$session) {
            $pdo->rollBack();
            return false;
        }
        
        $current_question_id = $session['current_question_id'];
        
        if (!$current_question_id) {
            $pdo->rollBack();
            return false;
        }
        
        // Получаем порядок текущего вопроса
        $stmt = $pdo->prepare("SELECT display_order FROM quiz_questions WHERE id = ?");
        $stmt->execute([$current_question_id]);
        $current_order = $stmt->fetch()['display_order'] ?? 0;
        
        // Получаем следующий вопрос
        $stmt = $pdo->prepare("
            SELECT id, display_order 
            FROM quiz_questions 
            WHERE display_order > ?
            ORDER BY display_order ASC 
            LIMIT 1
        ");
        $stmt->execute([$current_order]);
        $next_question = $stmt->fetch();
        
        if ($next_question) {
            // Есть следующий вопрос
            $next_question_id = $next_question['id'];
            $next_order = $next_question['display_order'];
            
            // Получаем следующий после следующего вопроса
            $stmt = $pdo->prepare("
                SELECT id 
                FROM quiz_questions 
                WHERE display_order > ?
                ORDER BY display_order ASC 
                LIMIT 1
            ");
            $stmt->execute([$next_order]);
            $next_next_question = $stmt->fetch();
            $next_next_id = $next_next_question ? $next_next_question['id'] : null;
            
            // Обновляем сессию
            $stmt = $pdo->prepare("
                UPDATE quiz_session SET 
                current_question_id = ?,
                next_question_id = ?,
                phase = 'question',
                question_start_time = NOW(),
                answers_start_time = NULL
                WHERE id = ?
            ");
            $stmt->execute([$next_question_id, $next_next_id, $session['id']]);
            
            $pdo->commit();
            return true;
            
        } else {
            // Нет следующего вопроса - завершаем квиз
            $stmt = $pdo->prepare("
                UPDATE quiz_session SET 
                phase = 'waiting',
                is_active = 0
                WHERE id = ?
            ");
            $stmt->execute([$session['id']]);
            
            $stmt = $pdo->prepare("UPDATE event_state SET event_status = 'finished' WHERE id = 1");
            $stmt->execute();
            
            $pdo->commit();
            return true;
        }
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Auto advance error: " . $e->getMessage());
        return false;
    }
}

function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    
    if ($base_path === '/') {
        $base_path = '';
    }
    
    return $protocol . $host . $base_path;
}

function start_quiz() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // 1. Обновляем event_state
        $stmt = $pdo->prepare("UPDATE event_state SET event_status = 'running' WHERE id = 1");
        $stmt->execute();
        
        // 2. Получаем первый вопрос (с наименьшим display_order)
        $stmt = $pdo->query("SELECT id, display_order FROM quiz_questions ORDER BY display_order ASC, id ASC LIMIT 1");
        $first_question = $stmt->fetch();
        
        if (!$first_question) {
            throw new Exception('Нет вопросов в квизе. Добавьте вопросы перед началом.');
        }
        
        $first_question_id = $first_question['id'];
        $first_order = $first_question['display_order'];
        
        // 3. Получаем время вопроса
        $stmt = $pdo->prepare("SELECT question_time FROM quiz_questions WHERE id = ?");
        $stmt->execute([$first_question_id]);
        $question_data = $stmt->fetch();
        $question_time = $question_data['question_time'] ?? 30;
        
        // 4. Обновляем или создаем сессию
        // Сначала сбрасываем все активные сессии
        $pdo->exec("UPDATE quiz_session SET is_active = 0 WHERE is_active = 1");
        
        // Создаем новую сессию
        $stmt = $pdo->prepare("
            INSERT INTO quiz_session 
            (is_active, phase, current_question_id, question_start_time) 
            VALUES (1, 'question', ?, NOW())
        ");
        $stmt->execute([$first_question_id]);
        
        $session_id = $pdo->lastInsertId();
        
        // 5. Сбрасываем ответы участников для этого вопроса
        $stmt = $pdo->prepare("DELETE FROM quiz_participant_answers WHERE quiz_question_id = ?");
        $stmt->execute([$first_question_id]);
        
        // 6. Получаем следующий вопрос - ИСПРАВЛЕННЫЙ ЗАПРОС
        $stmt = $pdo->prepare("
            SELECT id 
            FROM quiz_questions 
            WHERE display_order > ? 
            ORDER BY display_order ASC 
            LIMIT 1
        ");
        $stmt->execute([$first_order]);
        $next_question = $stmt->fetch();
        
        if ($next_question) {
            $stmt = $pdo->prepare("UPDATE quiz_session SET next_question_id = ? WHERE id = ?");
            $stmt->execute([$next_question['id'], $session_id]);
        }
        
        $pdo->commit();
        
        json_response([
            'success' => true, 
            'message' => 'Квиз начат! Первый вопрос активирован.',
            'first_question_id' => $first_question_id,
            'question_time' => $question_time
        ]);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("SQL Error in start_quiz: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка начала квиза: ' . $e->getMessage()]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
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
        $pdo->beginTransaction();
        
        // 1. Получаем текущую активную сессию
        $stmt = $pdo->query("SELECT * FROM quiz_session WHERE is_active = 1 LIMIT 1");
        $session = $stmt->fetch();
        
        if (!$session) {
            throw new Exception('Нет активной сессии квиза');
        }
        
        // 2. Если есть next_question_id, переходим к нему
        if ($session['next_question_id']) {
            $next_question_id = $session['next_question_id'];
            
            // Получаем display_order следующего вопроса
            $stmt = $pdo->prepare("SELECT display_order FROM quiz_questions WHERE id = ?");
            $stmt->execute([$next_question_id]);
            $next_question_data = $stmt->fetch();
            $next_order = $next_question_data['display_order'] ?? 0;
            
            // Обновляем сессию
            $stmt = $pdo->prepare("
                UPDATE quiz_session SET 
                current_question_id = ?,
                next_question_id = NULL,
                phase = 'question',
                question_start_time = NOW(),
                answers_start_time = NULL
                WHERE id = ?
            ");
            $stmt->execute([$next_question_id, $session['id']]);
            
            // Получаем следующий вопрос после этого
            $stmt = $pdo->prepare("
                SELECT id 
                FROM quiz_questions 
                WHERE display_order > ? 
                ORDER BY display_order ASC 
                LIMIT 1
            ");
            $stmt->execute([$next_order]);
            $next_next_question = $stmt->fetch();
            
            if ($next_next_question) {
                $stmt = $pdo->prepare("UPDATE quiz_session SET next_question_id = ? WHERE id = ?");
                $stmt->execute([$next_next_question['id'], $session['id']]);
            }
            
            $message = 'Переход к следующему вопросу выполнен.';
            
        } else {
            // 3. Если следующего вопроса нет, завершаем квиз
            $stmt = $pdo->prepare("
                UPDATE quiz_session SET 
                phase = 'waiting',
                question_start_time = NULL,
                answers_start_time = NULL
                WHERE id = ?
            ");
            $stmt->execute([$session['id']]);
            
            // Обновляем event_state
            $stmt = $pdo->prepare("UPDATE event_state SET event_status = 'finished', event_end_time = NOW() WHERE id = 1");
            $stmt->execute();
            
            $message = 'Квиз завершен. Больше вопросов нет.';
        }
        
        $pdo->commit();
        
        json_response(['success' => true, 'message' => $message]);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'error' => 'Ошибка перехода к следующему вопросу: ' . $e->getMessage()]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

function end_quiz() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // 1. Обновляем event_state
        $stmt = $pdo->prepare("
            UPDATE event_state SET 
            event_status = 'finished',
            event_end_time = NOW()
            WHERE id = 1
        ");
        $stmt->execute();
        
        // 2. Обновляем сессию
        $stmt = $pdo->prepare("
            UPDATE quiz_session SET 
            phase = 'waiting',
            question_start_time = NULL,
            answers_start_time = NULL
            WHERE is_active = 1
        ");
        $stmt->execute();
        
        $pdo->commit();
        
        json_response(['success' => true, 'message' => 'Квиз завершен! Все участники видят финальные результаты.']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка завершения квиза: ' . $e->getMessage()]);
    }
}

function reset_quiz() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // 1. Сбрасываем event_state
        $stmt = $pdo->prepare("
            UPDATE event_state SET 
            event_status = 'not_started',
            event_end_time = NULL,
            timer_remaining = NULL 
            WHERE id = 1
        ");
        $stmt->execute();
        
        // 2. Деактивируем все сессии
        $pdo->exec("UPDATE quiz_session SET is_active = 0");
        
        // 3. Создаем новую сессию в режиме ожидания
        $stmt = $pdo->prepare("
            INSERT INTO quiz_session (is_active, phase) 
            VALUES (1, 'waiting')
        ");
        $stmt->execute();
        
        // 4. Сбрасываем ответы участников
        $pdo->exec("DELETE FROM quiz_participant_answers");
        
        // 5. Сбрасываем очки участников
        $pdo->exec("UPDATE participants SET score = 0");
        
        $pdo->commit();
        
        json_response(['success' => true, 'message' => 'Квиз сброшен! Все готово к новому запуску.']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка сброса квиза: ' . $e->getMessage()]);
    }
}

?>