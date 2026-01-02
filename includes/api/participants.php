<?php
require_once __DIR__ . '/../functions.php';

function participant_join($data) {
    global $pdo;
    
    $team = trim($data['team'] ?? '');
    $event_type = $data['event_type'] ?? 'grinder'; // Получаем тип мероприятия
    
    if (empty($team)) {
        json_response(['error' => 'Введите название команды']);
    }
    
    try {
        // Проверяем существование команды для указанного типа мероприятия
        $stmt = $pdo->prepare("SELECT * FROM participants WHERE team = ? AND event_type = ?");
        $stmt->execute([$team, $event_type]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $participant = $existing;
        } else {
            $stmt = $pdo->prepare("INSERT INTO participants (team, event_type) VALUES (?, ?)");
            $stmt->execute([$team, $event_type]);
            $participant_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("SELECT * FROM participants WHERE id = ?");
            $stmt->execute([$participant_id]);
            $participant = $stmt->fetch();
        }
        
        // Получаем вопросы в зависимости от типа мероприятия
        if ($event_type === 'quiz') {
            // Для квиза получаем вопросы из quiz_questions
            $stmt = $pdo->prepare("SELECT * FROM quiz_questions ORDER BY display_order");
            $stmt->execute();
            $questions = $stmt->fetchAll();
        } else {
            // Для мясорубки получаем вопросы из questions с event_type = 'grinder'
            $stmt = $pdo->prepare("SELECT * FROM questions WHERE event_type = ? ORDER BY id");
            $stmt->execute(['grinder']);
            $questions = $stmt->fetchAll();
        }
        
        json_response([
            'success' => true,
            'participant' => $participant,
            'questions' => $questions,
            'event_type' => $event_type
        ]);
        
    } catch (Exception $e) {
        error_log("Participant join error: " . $e->getMessage());
        json_response(['error' => 'Ошибка регистрации команды: ' . $e->getMessage()]);
    }
}

function get_questions_status($data) {
    global $pdo;
    
    $participant_id = $data['participant_id'] ?? 0;
    $event_type = $data['event_type'] ?? 'grinder';
    
    if (empty($participant_id)) {
        json_response(['error' => 'ID участника не указан']);
    }
    
    try {
        // Получаем ПОСЛЕДНИЙ ответ на каждый вопрос
        $stmt = $pdo->prepare("
            SELECT a1.question_id, a1.is_correct, a1.points 
            FROM answers a1
            WHERE a1.participant_id = ? 
              AND a1.event_type = ?
              AND a1.created_at = (
                  SELECT MAX(a2.created_at) 
                  FROM answers a2 
                  WHERE a2.participant_id = a1.participant_id 
                    AND a2.question_id = a1.question_id 
                    AND a2.event_type = a1.event_type
              )
        ");
        $stmt->execute([$participant_id, $event_type]);
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
        error_log("Get questions status error: " . $e->getMessage());
        json_response(['error' => 'Ошибка получения статуса вопросов: ' . $e->getMessage()]);
    }
}

function answer_submit($data) {
    global $pdo;
    
    $participant_id = $data['participant_id'] ?? 0;
    $question_id = $data['question_id'] ?? 0;
    $answer = $data['answer'] ?? '';
    $event_type = $data['event_type'] ?? 'grinder';
    
    if (empty($participant_id) || empty($question_id) || empty($answer)) {
        json_response(['error' => 'Не все данные предоставлены']);
    }
    
    try {
        // Получаем вопрос с проверкой event_type
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND event_type = ?");
        $stmt->execute([$question_id, $event_type]);
        $question = $stmt->fetch();
        
        if (!$question) {
            json_response(['error' => 'Вопрос не найден для указанного типа мероприятия']);
        }
        
        // Проверяем ответ
        $user_answer = strtolower(trim($answer));
        $correct_answer = strtolower(trim($question['answer']));
        $is_correct = ($user_answer === $correct_answer);
        
        // УДАЛИЛИ: Проверку на существующий ответ
        
        $points = $is_correct ? intval($question['points']) : 0;
        $answer_order = null;
        
        if ($is_correct && $question['has_bonus_points']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM answers WHERE question_id = ? AND is_correct = 1 AND event_type = ?");
            $stmt->execute([$question_id, $event_type]);
            $correct_answers_count = $stmt->fetch()['count'];
            $answer_order = $correct_answers_count + 1;
            
            if ($answer_order === 1) {
                $points += intval($question['bonus_first_points']);
            } elseif ($answer_order === 2) {
                $points += intval($question['bonus_second_points']);
            } elseif ($answer_order === 3) {
                $points += intval($question['bonus_third_points']);
            }
        }
        
        // УДАЛИЛИ существующий ответ, если он есть, и записываем новый
        $stmt = $pdo->prepare("DELETE FROM answers WHERE participant_id = ? AND question_id = ? AND event_type = ?");
        $stmt->execute([$participant_id, $question_id, $event_type]);
        
        // Обновляем счет: сначала вычитаем старые баллы
        $stmt = $pdo->prepare("SELECT points FROM answers WHERE participant_id = ? AND question_id = ? AND event_type = ?");
        $stmt->execute([$participant_id, $question_id, $event_type]);
        $old_answer = $stmt->fetch();
        
        if ($old_answer) {
            $stmt = $pdo->prepare("UPDATE participants SET score = score - ? WHERE id = ?");
            $stmt->execute([$old_answer['points'], $participant_id]);
        }
        
        // Сохраняем новый ответ
        $stmt = $pdo->prepare("
            INSERT INTO answers (participant_id, question_id, answer, is_correct, points, answer_order, event_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$participant_id, $question_id, $answer, $is_correct, $points, $answer_order, $event_type]);
        
        // Добавляем новые баллы, если ответ правильный
        if ($is_correct) {
            $stmt = $pdo->prepare("UPDATE participants SET score = score + ? WHERE id = ?");
            $stmt->execute([$points, $participant_id]);
        }
        
        $message = '';
        if ($is_correct) {
            $base_points = intval($question['points']);
            $bonus_points = $points - $base_points;
            
            if ($bonus_points > 0) {
                $place = '';
                if ($answer_order === 1) $place = '🥇 ';
                elseif ($answer_order === 2) $place = '🥈 ';
                elseif ($answer_order === 3) $place = '🥉 ';
                
                $message = "Правильный ответ! {$place}+{$points} баллов ({$base_points} базовых + {$bonus_points} бонусных)";
            } else {
                $message = "Правильный ответ! +{$points} баллов";
            }
        } else {
            $message = 'Неправильный ответ';
        }
        
        json_response([
            'success' => true,
            'is_correct' => $is_correct,
            'points' => $points,
            'answer_order' => $answer_order,
            'message' => $message,
            'debug' => [
                'old_points_subtracted' => $old_answer ? $old_answer['points'] : 0,
                'new_points_added' => $points
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Answer submit error: " . $e->getMessage());
        json_response(['error' => 'Ошибка сохранения ответа: ' . $e->getMessage()]);
    }
}
?>