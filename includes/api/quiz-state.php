<?php
// includes/api/quiz-state.php

function get_quiz_state() {
    global $pdo;
    
    try {
        // Получаем текущее состояние мероприятия
        $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1");
        $state = $stmt->fetch();
        
        // Получаем текущий активный вопрос с порядком отображения
        $stmt = $pdo->query("
            SELECT cq.*, qq.question_text, qq.question_type, qq.question_time, qq.answer_time, qq.display_order
            FROM current_quiz_question cq
            LEFT JOIN quiz_questions qq ON cq.quiz_question_id = qq.id
            WHERE cq.is_active = 1
            ORDER BY cq.started_at DESC LIMIT 1
        ");
        $current_question = $stmt->fetch();
        
        // Автоматически обновляем фазы на основе времени сервера
        if ($current_question) {
            $now = new DateTime();
            $current_time = $now->format('Y-m-d H:i:s');
            
            // Проверяем и обновляем фазу автоматически
            if ($current_question['question_end_time'] && $current_time >= $current_question['question_end_time'] && $current_question['phase'] === 'question') {
                $stmt = $pdo->prepare("UPDATE current_quiz_question SET phase = 'answers' WHERE id = ?");
                $stmt->execute([$current_question['id']]);
                $current_question['phase'] = 'answers';
            }
            
            if ($current_question['answers_end_time'] && $current_time >= $current_question['answers_end_time'] && $current_question['phase'] === 'answers') {
                $stmt = $pdo->prepare("UPDATE current_quiz_question SET is_active = 0 WHERE id = ?");
                $stmt->execute([$current_question['id']]);
                $current_question = null;
            }
        }
        
        // Вычисляем оставшееся время
        $time_remaining = null;
        $current_phase = null;
        
        if ($current_question) {
            $now = time();
            
            if ($current_question['phase'] === 'question' && $current_question['question_end_time']) {
                $end_time = strtotime($current_question['question_end_time']);
                $time_remaining = max(0, $end_time - $now);
                $current_phase = 'question';
            } elseif ($current_question['phase'] === 'answers' && $current_question['answers_end_time']) {
                $end_time = strtotime($current_question['answers_end_time']);
                $time_remaining = max(0, $end_time - $now);
                $current_phase = 'answers';
            }
            
            // ДОБАВЬТЕ ПРОВЕРКУ НА КОРРЕКТНОСТЬ ВРЕМЕНИ
            if ($time_remaining > 10000) { // Если больше 10,000 секунд - что-то не так
                $time_remaining = null;
            }
        }
        
        // Получаем статистику ответов
        $answers_stats = [
            'total_answers' => 0,
            'correct_answers' => 0,
            'participation_rate' => 0
        ];
        
        if ($current_question) {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM participants");
            $total_participants = $stmt->fetch()['total'];
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_answers,
                       SUM(CASE WHEN points_earned > 0 THEN 1 ELSE 0 END) as correct_answers
                FROM quiz_participant_answers 
                WHERE quiz_question_id = ?
            ");
            $stmt->execute([$current_question['quiz_question_id']]);
            $answers_data = $stmt->fetch();
            
            $answers_stats = [
                'total_answers' => $answers_data['total_answers'] ?? 0,
                'correct_answers' => $answers_data['correct_answers'] ?? 0,
                'participation_rate' => $total_participants > 0 ? 
                    round(($answers_data['total_answers'] / $total_participants) * 100, 1) : 0
            ];
        }
        
        json_response([
            'success' => true,
            'event_state' => $state,
            'current_question' => $current_question,
            'time_remaining' => $time_remaining,
            'current_phase' => $current_phase,
            'answers_stats' => $answers_stats,
            'server_time' => $current_time ?? date('Y-m-d H:i:s')
        ]);
        
    } catch (PDOException $e) {
        json_response([
            'success' => false, 
            'error' => 'Ошибка получения состояния квиза: ' . $e->getMessage()
        ]);
    }
}

function reset_quiz($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // УПРОЩЕННАЯ ВЕРСИЯ БЕЗ ТРАНЗАКЦИЙ - они вызывают проблемы
        error_log("Starting quiz reset (no transactions)...");
        
        // 1. Очищаем таблицы в правильном порядке (из-за внешних ключей)
        $tables = [
            'cheating_attempts',
            'answers', 
            'quiz_participant_answers',
            'waiting_room',
            'participants',
            'current_quiz_question'
        ];
        
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table");
            $stmt->execute();
            $count = $stmt->rowCount();
            error_log("Cleared $table: $count rows");
        }
        
        // 2. Сбрасываем AUTO_INCREMENT для participants
        $stmt = $pdo->prepare("ALTER TABLE participants AUTO_INCREMENT = 1");
        $stmt->execute();
        
        // 3. Сбрасываем статус мероприятия
        $stmt = $pdo->prepare("
            UPDATE event_state SET 
            event_status = 'not_started',
            event_start_time = NULL,
            event_end_time = NULL,
            updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute();
        
        // 4. Получаем итоговое количество участников
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM participants");
        $final_count = $stmt->fetch()['count'];
        
        error_log("Quiz reset completed. Final participants: " . $final_count);
        
        json_response([
            'success' => true, 
            'message' => 'Квиз сброшен! Все участники и данные удалены.',
            'participants_count' => $final_count
        ]);
        
    } catch (PDOException $e) {
        error_log("Quiz reset error: " . $e->getMessage());
        json_response([
            'success' => false, 
            'error' => 'Ошибка сброса квиза: ' . $e->getMessage()
        ]);
    }
}

function show_quiz_answers($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // Принудительно переключаем на фазу ответов
        $stmt = $pdo->prepare("
            UPDATE current_quiz_question 
            SET phase = 'answers',
                question_end_time = NOW(),
                answers_end_time = DATE_ADD(NOW(), INTERVAL 15 SECOND)
            WHERE is_active = 1
        ");
        $stmt->execute();
        
        json_response([
            'success' => true, 
            'message' => 'Показ ответов активирован'
        ]);
        
    } catch (PDOException $e) {
        json_response([
            'success' => false, 
            'error' => 'Ошибка активации показа ответов: ' . $e->getMessage()
        ]);
    }
}

function pause_quiz($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // Обновляем статус мероприятия на "not_started" (пауза)
        $stmt = $pdo->prepare("
            UPDATE event_state SET 
            event_status = 'not_started',
            updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute();
        
        json_response([
            'success' => true, 
            'message' => 'Квиз на паузе'
        ]);
        
    } catch (PDOException $e) {
        json_response([
            'success' => false, 
            'error' => 'Ошибка паузы квиза: ' . $e->getMessage()
        ]);
    }
}

function end_quiz($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // Деактивируем текущий активный вопрос
        $stmt = $pdo->prepare("UPDATE current_quiz_question SET is_active = 0 WHERE is_active = 1");
        $stmt->execute();
        
        // Обновляем статус мероприятия на "завершено"
        $stmt = $pdo->prepare("
            UPDATE event_state SET 
            event_status = 'finished',
            event_end_time = NULL,
            updated_at = NOW()
            WHERE id = 1
        ");
        $stmt->execute();
        
        $pdo->commit();
        
        json_response([
            'success' => true, 
            'message' => 'Квиз завершен! Все участники видят финальные результаты.'
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response([
            'success' => false, 
            'error' => 'Ошибка завершения квиза: ' . $e->getMessage()
        ]);
    }
}

function start_quiz_question($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        $question_id = $data['question_id'] ?? null;
        
        if (!$question_id) {
            throw new Exception('ID вопроса не указан');
        }
        
        // Получаем информацию о вопросе
        $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch();
        
        if (!$question) {
            throw new Exception('Вопрос не найден');
        }
        
        // Деактивируем предыдущий активный вопрос
        $stmt = $pdo->prepare("UPDATE current_quiz_question SET is_active = 0 WHERE is_active = 1");
        $stmt->execute();
        
        // Вычисляем временные метки
        $now = new DateTime();
        $question_end_time = clone $now;
        $question_end_time->modify("+{$question['question_time']} seconds");
        
        $answers_end_time = clone $question_end_time;
        $answers_end_time->modify("+{$question['answer_time']} seconds");
        
        $next_question_time = clone $answers_end_time;
        $next_question_time->modify("+5 seconds"); // Пауза между вопросами
        
        // Активируем новый вопрос
        $stmt = $pdo->prepare("
            INSERT INTO current_quiz_question 
            (quiz_question_id, is_active, started_at, phase, question_end_time, answers_end_time, next_question_time) 
            VALUES (?, 1, NOW(), 'question', ?, ?, ?)
        ");
        $stmt->execute([
            $question_id,
            $question_end_time->format('Y-m-d H:i:s'),
            $answers_end_time->format('Y-m-d H:i:s'),
            $next_question_time->format('Y-m-d H:i:s')
        ]);
        
        // Обновляем статус мероприятия
        $stmt = $pdo->prepare("UPDATE event_state SET event_status = 'running' WHERE id = 1");
        $stmt->execute();
        
        $pdo->commit();
        
        json_response([
            'success' => true, 
            'message' => 'Вопрос активирован',
            'timing' => [
                'question_end' => $question_end_time->format('Y-m-d H:i:s'),
                'answers_end' => $answers_end_time->format('Y-m-d H:i:s'),
                'next_question' => $next_question_time->format('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response([
            'success' => false, 
            'error' => 'Ошибка активации вопроса: ' . $e->getMessage()
        ]);
    }
}

function next_quiz_question($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // Получаем текущий активный вопрос
        $stmt = $pdo->query("
            SELECT cq.*, qq.display_order as current_order 
            FROM current_quiz_question cq
            LEFT JOIN quiz_questions qq ON cq.quiz_question_id = qq.id
            WHERE cq.is_active = 1 
            ORDER BY cq.started_at DESC 
            LIMIT 1
        ");
        $current_question = $stmt->fetch();
        
        $current_order = $current_question ? $current_question['current_order'] : 0;
        
        // Получаем следующий вопрос по порядку отображения
        $stmt = $pdo->prepare("
            SELECT * FROM quiz_questions 
            WHERE display_order > ?
            ORDER BY display_order 
            LIMIT 1
        ");
        $stmt->execute([$current_order]);
        $next_question = $stmt->fetch();
        
        if (!$next_question) {
            // Нет следующего вопроса - завершаем квиз
            $stmt = $pdo->prepare("UPDATE current_quiz_question SET is_active = 0 WHERE is_active = 1");
            $stmt->execute();
            
            $stmt = $pdo->prepare("UPDATE event_state SET event_status = 'finished' WHERE id = 1");
            $stmt->execute();
            
            $pdo->commit();
            
            json_response([
                'success' => true, 
                'message' => 'Квиз завершен! Это был последний вопрос.',
                'quiz_finished' => true
            ]);
            return;
        }
        
        // Вычисляем временные метки для следующего вопроса
        $now = new DateTime();
        
        $question_end_time = clone $now;
        $question_end_time->modify("+{$next_question['question_time']} seconds");
        
        $answers_end_time = clone $question_end_time;
        $answers_end_time->modify("+{$next_question['answer_time']} seconds");
        
        $next_question_time = clone $answers_end_time;
        $next_question_time->modify("+5 seconds");
        
        // Деактивируем текущий вопрос
        $stmt = $pdo->prepare("UPDATE current_quiz_question SET is_active = 0");
        $stmt->execute();
        
        // Активируем следующий вопрос
        $stmt = $pdo->prepare("
            INSERT INTO current_quiz_question 
            (quiz_question_id, is_active, started_at, phase, question_end_time, answers_end_time, next_question_time) 
            VALUES (?, 1, NOW(), 'question', ?, ?, ?)
        ");
        $stmt->execute([
            $next_question['id'],
            $question_end_time->format('Y-m-d H:i:s'),
            $answers_end_time->format('Y-m-d H:i:s'),
            $next_question_time->format('Y-m-d H:i:s')
        ]);
        
        $pdo->commit();
        
        json_response([
            'success' => true, 
            'message' => 'Следующий вопрос активирован',
            'quiz_finished' => false,
            'next_question_id' => $next_question['id']
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response([
            'success' => false, 
            'error' => 'Ошибка перехода к следующему вопросу: ' . $e->getMessage()
        ]);
    }
}

function is_last_question($question_id) {
    global $pdo;
    
    try {
        // Получаем максимальный порядок отображения
        $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM quiz_questions");
        $max_order = $stmt->fetch()['max_order'];
        
        // Получаем порядок текущего вопроса
        $stmt = $pdo->prepare("SELECT display_order FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $current_order = $stmt->fetch()['display_order'];
        
        return $current_order == $max_order;
        
    } catch (PDOException $e) {
        error_log("Error checking last question: " . $e->getMessage());
        return false;
    }
}

function get_current_question() {
    global $pdo;
    
    try {
        // Получаем текущий активный вопрос с вариантами ответов
        $stmt = $pdo->query("
            SELECT cq.*, qq.question_text, qq.question_type, qq.question_time, qq.answer_time,
                   qa.id as answer_id, qa.answer_text, qa.is_correct, qa.points, qa.display_order
            FROM current_quiz_question cq
            LEFT JOIN quiz_questions qq ON cq.quiz_question_id = qq.id
            LEFT JOIN quiz_answers qa ON qq.id = qa.quiz_question_id
            WHERE cq.is_active = 1
            ORDER BY qa.display_order
        ");
        
        $result = $stmt->fetchAll();
        
        if (empty($result)) {
            json_response(['success' => true, 'current_question' => null]);
            return;
        }
        
        // Форматируем данные вопроса
        $question = [
            'id' => $result[0]['quiz_question_id'],
            'question_text' => $result[0]['question_text'],
            'question_type' => $result[0]['question_type'],
            'question_time' => $result[0]['question_time'],
            'answer_time' => $result[0]['answer_time'],
            'phase' => $result[0]['phase'],
            'started_at' => $result[0]['started_at'],
            'answers' => []
        ];
        
        foreach ($result as $row) {
            if ($row['answer_id']) {
                $question['answers'][] = [
                    'id' => $row['answer_id'],
                    'answer_text' => $row['answer_text'],
                    'is_correct' => (bool)$row['is_correct'],
                    'points' => $row['points'],
                    'display_order' => $row['display_order']
                ];
            }
        }
        
        json_response(['success' => true, 'current_question' => $question]);
        
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка получения текущего вопроса: ' . $e->getMessage()]);
    }
}
?>