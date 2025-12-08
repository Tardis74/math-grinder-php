<?php
// includes/api/quiz-participants.php

function submit_quiz_answer($data) {
    global $pdo;
    
    try {
        $participant_id = $data['participant_id'] ?? null;
        $quiz_question_id = $data['quiz_question_id'] ?? null;
        $quiz_answer_ids = $data['quiz_answer_ids'] ?? []; // Массив для множественного выбора
        $quiz_answer_id = $data['quiz_answer_id'] ?? null; // Для одиночного выбора
        
        if (!$participant_id || !$quiz_question_id) {
            json_response(['success' => false, 'error' => 'Не указаны обязательные параметры']);
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
        
        if (!$question) {
            $pdo->rollBack();
            json_response(['success' => false, 'error' => 'Вопрос не найден']);
            return;
        }
        
        $points_earned = 0;
        
        if ($question['question_type'] === 'single' && $quiz_answer_id) {
            // Одиночный выбор
            $stmt = $pdo->prepare("SELECT points, is_correct FROM quiz_answers WHERE id = ? AND quiz_question_id = ?");
            $stmt->execute([$quiz_answer_id, $quiz_question_id]);
            $answer = $stmt->fetch();
            
            if ($answer) {
                $points_earned = $answer['is_correct'] ? $answer['points'] : 0;
                
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_participant_answers 
                    (participant_id, quiz_question_id, quiz_answer_id, points_earned) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$participant_id, $quiz_question_id, $quiz_answer_id, $points_earned]);
            }
            
        } elseif ($question['question_type'] === 'multiple' && !empty($quiz_answer_ids)) {
            // Множественный выбор
            $correct_answers_count = 0;
            $total_correct_answers = 0;
            $max_points = 0;
            
            // Считаем правильные ответы
            $stmt = $pdo->prepare("SELECT id, points, is_correct FROM quiz_answers WHERE quiz_question_id = ?");
            $stmt->execute([$quiz_question_id]);
            $all_answers = $stmt->fetchAll();
            
            foreach ($all_answers as $answer) {
                if ($answer['is_correct']) {
                    $total_correct_answers++;
                    $max_points += $answer['points'];
                }
            }
            
            // Проверяем выбранные ответы
            foreach ($quiz_answer_ids as $answer_id) {
                $stmt = $pdo->prepare("SELECT points, is_correct FROM quiz_answers WHERE id = ?");
                $stmt->execute([$answer_id]);
                $selected_answer = $stmt->fetch();
                
                if ($selected_answer) {
                    if ($selected_answer['is_correct']) {
                        $correct_answers_count++;
                        $points_earned += $selected_answer['points'];
                    } else {
                        // Штраф за неправильный ответ в множественном выборе
                        $points_earned = max(0, $points_earned - 1);
                    }
                }
            }
            
            // Сохраняем все выбранные ответы
            foreach ($quiz_answer_ids as $answer_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_participant_answers 
                    (participant_id, quiz_question_id, quiz_answer_id, points_earned) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$participant_id, $quiz_question_id, $answer_id, $points_earned]);
            }
        }
        
        // Обновляем общий счет участника
        $stmt = $pdo->prepare("UPDATE participants SET score = score + ? WHERE id = ?");
        $stmt->execute([$points_earned, $participant_id]);
        
        $pdo->commit();
        
        json_response([
            'success' => true,
            'message' => 'Ответ принят',
            'points_earned' => $points_earned,
            'is_correct' => $points_earned > 0
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
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