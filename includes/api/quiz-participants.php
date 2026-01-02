<?php
// includes/api/quiz-participants.php

function get_quiz_questions_status($data) {
    global $pdo;
    
    $participant_id = $data['participant_id'] ?? 0;
    
    if (empty($participant_id)) {
        json_response(['error' => 'ID участника не указан']);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                qpa.quiz_question_id as question_id,
                SUM(qpa.points_earned) as points,
                (SUM(qpa.points_earned) > 0) as is_correct
            FROM quiz_participant_answers qpa
            WHERE qpa.participant_id = ?
            GROUP BY qpa.quiz_question_id
        ");
        $stmt->execute([$participant_id]);
        $answers = $stmt->fetchAll();
        
        $formatted_answers = [];
        foreach ($answers as $answer) {
            $formatted_answers[] = [
                'question_id' => $answer['question_id'],
                'is_correct' => (bool)$answer['is_correct'],
                'points' => (int)$answer['points'],
                'answered' => true
            ];
        }
        
        json_response($formatted_answers);
        
    } catch (PDOException $e) {
        error_log("Get quiz questions status error: " . $e->getMessage());
        json_response(['error' => 'Ошибка получения статуса вопросов квиза: ' . $e->getMessage()]);
    }
}

function submit_quiz_answer($data) {
    global $pdo;
    
    try {
        $participant_id = $data['participant_id'] ?? null;
        $quiz_question_id = $data['quiz_question_id'] ?? null;
        $quiz_answer_ids = $data['quiz_answer_ids'] ?? [];
        
        if (!$participant_id || !$quiz_question_id) {
            json_response(['success' => false, 'error' => 'Не указаны обязательные параметры']);
            return;
        }
        
        // Проверяем существование участника
        $stmt = $pdo->prepare("SELECT id FROM participants WHERE id = ?");
        $stmt->execute([$participant_id]);
        $participant = $stmt->fetch();
        
        if (!$participant) {
            json_response(['success' => false, 'error' => 'Участник не найден']);
            return;
        }
        
        // Проверяем существование вопроса
        $stmt = $pdo->prepare("SELECT id FROM quiz_questions WHERE id = ?");
        $stmt->execute([$quiz_question_id]);
        $question = $stmt->fetch();
        
        if (!$question) {
            json_response(['success' => false, 'error' => 'Вопрос не найден']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // Проверяем, не отвечал ли уже участник на этот вопрос
        $stmt = $pdo->prepare("
            SELECT id FROM quiz_participant_answers 
            WHERE participant_id = ? AND quiz_question_id = ?
        ");
        $stmt->execute([$participant_id, $quiz_question_id]);
        
        if ($stmt->fetch()) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Вы уже ответили на этот вопрос']);
            return;
        }
        
        // Получаем информацию о вопросе
        $stmt = $pdo->prepare("SELECT question_type FROM quiz_questions WHERE id = ?");
        $stmt->execute([$quiz_question_id]);
        $question = $stmt->fetch();
        
        $points_earned = 0;
        
        if (!empty($quiz_answer_ids)) {
            // Для одиночного выбора
            if ($question['question_type'] === 'single' && count($quiz_answer_ids) === 1) {
                $quiz_answer_id = $quiz_answer_ids[0];
                
                // Получаем баллы за ответ
                $stmt = $pdo->prepare("SELECT points FROM quiz_answers WHERE id = ? AND quiz_question_id = ?");
                $stmt->execute([$quiz_answer_id, $quiz_question_id]);
                $answer = $stmt->fetch();
                
                if ($answer) {
                    $points_earned = $answer['points']; // Может быть положительным или отрицательным
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO quiz_participant_answers 
                        (participant_id, quiz_question_id, quiz_answer_id, points_earned) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$participant_id, $quiz_question_id, $quiz_answer_id, $points_earned]);
                }
                
            } elseif ($question['question_type'] === 'multiple') {
                // Множественный выбор - ПРОСТАЯ ЛОГИКА
                // Суммируем баллы за все выбранные ответы
                foreach ($quiz_answer_ids as $answer_id) {
                    // Получаем баллы за ответ
                    $stmt = $pdo->prepare("SELECT points FROM quiz_answers WHERE id = ? AND quiz_question_id = ?");
                    $stmt->execute([$answer_id, $quiz_question_id]);
                    $answer = $stmt->fetch();
                    
                    if ($answer) {
                        $points_earned += $answer['points']; // Просто суммируем
                        
                        // Сохраняем каждый выбранный ответ
                        $stmt = $pdo->prepare("
                            INSERT INTO quiz_participant_answers 
                            (participant_id, quiz_question_id, quiz_answer_id, points_earned) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$participant_id, $quiz_question_id, $answer_id, $answer['points']]);
                    }
                }
            }
        }
        
        // Общий счет может быть отрицательным (штраф)
        // Не ограничиваем минимальным значением, чтобы можно было наказывать
        
        // Обновляем общий счет участника
        $stmt = $pdo->prepare("UPDATE participants SET score = score + ? WHERE id = ?");
        $stmt->execute([$points_earned, $participant_id]);
        
        $pdo->commit();
        
        json_response([
            'success' => true,
            'message' => 'Ответ принят',
            'points_earned' => $points_earned,
            'debug' => [
                'participant_id' => $participant_id,
                'question_id' => $quiz_question_id,
                'selected_answers' => $quiz_answer_ids,
                'points_calculated' => $points_earned
            ]
        ]);
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Submit quiz answer error: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка сохранения ответа: ' . $e->getMessage()]);
    }
}

function update_participant_score($data) {
    global $pdo;
    
    try {
        $participant_id = $data['participant_id'] ?? null;
        $score = $data['score'] ?? 0;
        
        if (!$participant_id) {
            json_response(['success' => false, 'error' => 'ID участника не указан']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE participants SET score = ? WHERE id = ?");
        $stmt->execute([$score, $participant_id]);
        
        json_response(['success' => true, 'message' => 'Счет обновлен']);
        
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка обновления счета: ' . $e->getMessage()]);
    }
}

function get_quiz_participants() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT p.*, 
                   COUNT(qpa.id) as answers_count,
                   SUM(qpa.points_earned) as quiz_score
            FROM participants p
            LEFT JOIN quiz_participant_answers qpa ON p.id = qpa.participant_id
            GROUP BY p.id
            ORDER BY quiz_score DESC, p.team
        ");
        
        $participants = $stmt->fetchAll();
        
        json_response(['success' => true, 'participants' => $participants]);
        
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка получения участников: ' . $e->getMessage()]);
    }
}
?>