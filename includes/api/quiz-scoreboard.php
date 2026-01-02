<?php
// includes/api/quiz-scoreboard.php

function get_quiz_answers_stats($question_id) {
    global $pdo;
    
    try {
        // Получаем статистику ответов на конкретный вопрос
        $stmt = $pdo->prepare("
            SELECT 
                qa.id,
                qa.answer_text,
                COUNT(qpa.id) as answer_count,
                qa.is_correct
            FROM quiz_answers qa
            LEFT JOIN quiz_participant_answers qpa ON qa.id = qpa.quiz_answer_id AND qpa.quiz_question_id = ?
            WHERE qa.quiz_question_id = ?
            GROUP BY qa.id
            ORDER BY qa.display_order
        ");
        $stmt->execute([$question_id, $question_id]);
        $answers_stats = $stmt->fetchAll();
        
        // Получаем общее количество участников
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM participants");
        $total_participants = $stmt->fetch()['total'];
        
        json_response([
            'success' => true,
            'answers_stats' => $answers_stats,
            'total_participants' => $total_participants
        ]);
        
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка получения статистики: ' . $e->getMessage()]);
    }
}