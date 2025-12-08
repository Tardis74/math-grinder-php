<?php
// answers.php - API для работы с ответами

require_once '../db.php';
require_once '../functions.php';

function answer_submit($data) {
    $participant_id = $data['participant_id'] ?? 0;
    $question_id = $data['question_id'] ?? 0;
    $answer = sanitize_string($data['answer'] ?? '');
    
    if (!$participant_id || !$question_id || empty($answer)) {
        json_response(['error' => 'Все поля обязательны'], 400);
    }
    
    // Проверяем, заморожен ли рейтинг
    if (is_ranking_frozen()) {
        json_response(['error' => 'Рейтинг заморожен, ответы не принимаются'], 403);
    }
    
    // Проверяем, принимаются ли ответы
    if (!is_accepting_answers()) {
        json_response(['error' => 'Прием ответов остановлен'], 403);
    }
    
    // Проверяем, не отвечал ли уже участник на этот вопрос
    $existing_answer = fetchOne("
        SELECT * FROM answers 
        WHERE participant_id = ? AND question_id = ?
    ", [$participant_id, $question_id]);
    
    if ($existing_answer) {
        json_response([
            'is_correct' => (bool)$existing_answer['is_correct'],
            'points' => $existing_answer['points'],
            'message' => 'Вы уже отвечали на этот вопрос'
        ]);
    }
    
    // Получаем вопрос
    $question = fetchOne("SELECT * FROM questions WHERE id = ?", [$question_id]);
    if (!$question) {
        json_response(['error' => 'Вопрос не найден'], 404);
    }
    
    // Проверяем правильность ответа
    $is_correct = strtolower(trim($answer)) === strtolower(trim($question['answer']));
    
    // Определяем количество баллов
    $points = 0;
    if ($is_correct) {
        // Считаем количество правильных ответов на этот вопрос
        $correct_answers_count = fetchOne("
            SELECT COUNT(*) as count 
            FROM answers 
            WHERE question_id = ? AND is_correct = 1
        ", [$question_id]);
        $correct_answers_count = $correct_answers_count['count'];
        
        if ($correct_answers_count === 0) {
            $points = 3;
        } else if ($correct_answers_count === 1) {
            $points = 2;
        } else {
            $points = 1;
        }
        
        // Обновляем счет участника
        executeQuery("
            UPDATE participants 
            SET score = score + ? 
            WHERE id = ?
        ", [$points, $participant_id]);
    }
    
    // Сохраняем ответ
    $answer_id = insert('answers', [
        'participant_id' => $participant_id,
        'question_id' => $question_id,
        'answer' => $answer,
        'is_correct' => $is_correct,
        'points' => $points
    ]);
    
    if (!$answer_id) {
        json_response(['error' => 'Ошибка при сохранении ответа'], 500);
    }
    
    // Логируем действие
    log_action('answer_submit', "Участник $participant_id ответил на вопрос $question_id: $answer");
    
    json_response([
        'is_correct' => $is_correct,
        'points' => $points,
        'message' => $is_correct ? "Верно! +$points баллов" : 'Неверно'
    ]);
}

function get_answers() {
    check_admin_auth();
    
    $answers = fetchAll("
        SELECT a.*, p.team, q.text as question_text 
        FROM answers a 
        JOIN participants p ON a.participant_id = p.id 
        JOIN questions q ON a.question_id = q.id 
        ORDER BY a.created_at DESC
    ");
    
    json_response($answers);
}
?>