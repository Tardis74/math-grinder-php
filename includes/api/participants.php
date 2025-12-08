<?php
require_once __DIR__ . '/../functions.php';

function participant_join($data) {
    global $pdo;
    
    $team = trim($data['team'] ?? '');
    
    if (empty($team)) {
        json_response(['error' => 'Введите название команды']);
    }
    
    try {
        // Проверяем существование команды
        $stmt = $pdo->prepare("SELECT * FROM participants WHERE team = ?");
        $stmt->execute([$team]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Команда уже существует
            $participant = $existing;
        } else {
            // Создаем новую команду
            $stmt = $pdo->prepare("INSERT INTO participants (team) VALUES (?)");
            $stmt->execute([$team]);
            $participant_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("SELECT * FROM participants WHERE id = ?");
            $stmt->execute([$participant_id]);
            $participant = $stmt->fetch();
        }
        
        // Получаем вопросы
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY id");
        $questions = $stmt->fetchAll();
        
        json_response([
            'success' => true,
            'participant' => $participant,
            'questions' => $questions
        ]);
        
    } catch (Exception $e) {
        error_log("Participant join error: " . $e->getMessage());
        json_response(['error' => 'Ошибка регистрации команды: ' . $e->getMessage()]);
    }
}

function get_questions_status($data) {
    global $pdo;
    
    $participant_id = $data['participant_id'] ?? 0;
    
    if (empty($participant_id)) {
        json_response(['error' => 'ID участника не указан']);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT question_id, is_correct, points 
            FROM answers 
            WHERE participant_id = ?
        ");
        $stmt->execute([$participant_id]);
        $answers = $stmt->fetchAll();
        
        // Преобразуем данные в правильный формат
        $formatted_answers = [];
        foreach ($answers as $answer) {
            $formatted_answers[] = [
                'question_id' => $answer['question_id'],
                'is_correct' => (bool)$answer['is_correct'],
                'points' => (int)$answer['points'],
                'answered' => true // Добавляем поле answered
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
    
    if (empty($participant_id) || empty($question_id) || empty($answer)) {
        json_response(['error' => 'Не все данные предоставлены']);
    }
    
    try {
        // Получаем вопрос
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch();
        
        if (!$question) {
            json_response(['error' => 'Вопрос не найден']);
        }
        
        // Проверяем ответ
        $user_answer = strtolower(trim($answer));
        $correct_answer = strtolower(trim($question['answer']));
        $is_correct = ($user_answer === $correct_answer);
        
        // Проверяем, не отвечал ли уже участник
        $stmt = $pdo->prepare("SELECT * FROM answers WHERE participant_id = ? AND question_id = ?");
        $stmt->execute([$participant_id, $question_id]);
        $existing_answer = $stmt->fetch();
        
        if ($existing_answer) {
            json_response(['error' => 'Вы уже отвечали на этот вопрос']);
        }
        
        // ПРОСТАЯ ЛОГИКА: Используем ТОЛЬКО баллы из настроек вопроса
        $points = $is_correct ? intval($question['points']) : 0;
        $answer_order = null;
        
        // Если есть бонусные баллы, учитываем порядок ответа
        if ($is_correct && $question['has_bonus_points']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM answers WHERE question_id = ? AND is_correct = 1");
            $stmt->execute([$question_id]);
            $correct_answers_count = $stmt->fetch()['count'];
            $answer_order = $correct_answers_count + 1;
            
            // Добавляем бонусные баллы
            if ($answer_order === 1) {
                $points += intval($question['bonus_first_points']);
            } elseif ($answer_order === 2) {
                $points += intval($question['bonus_second_points']);
            } elseif ($answer_order === 3) {
                $points += intval($question['bonus_third_points']);
            }
        }
        
        // Сохраняем ответ
        $stmt = $pdo->prepare("INSERT INTO answers (participant_id, question_id, answer, is_correct, points, answer_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$participant_id, $question_id, $answer, $is_correct, $points, $answer_order]);
        
        // Обновляем счет если ответ правильный
        if ($is_correct) {
            $stmt = $pdo->prepare("UPDATE participants SET score = score + ? WHERE id = ?");
            $stmt->execute([$points, $participant_id]);
        }
        
        // Формируем сообщение
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
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        error_log("Answer submit error: " . $e->getMessage());
        json_response(['error' => 'Ошибка сохранения ответа: ' . $e->getMessage()]);
    }
}
?>