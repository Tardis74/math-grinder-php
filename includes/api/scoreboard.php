<?php
require_once __DIR__ . '/../functions.php';

function get_scoreboard() {
    global $pdo;
    
    try {
        // Получаем участников с их счетами
        $stmt = $pdo->query("
            SELECT p.* 
            FROM participants p 
            ORDER BY p.score DESC
        ");
        $participants = $stmt->fetchAll();
        
        // Получаем вопросы
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY id");
        $questions = $stmt->fetchAll();
        
        // Получаем ответы для каждого участника (включая answer_order)
        foreach ($participants as &$participant) {
            $stmt = $pdo->prepare("
                SELECT question_id, is_correct, points, answer_order 
                FROM answers 
                WHERE participant_id = ?
            ");
            $stmt->execute([$participant['id']]);
            $participant['answers'] = $stmt->fetchAll();
        }
        
        json_response([
            'participants' => $participants,
            'questions' => $questions
        ]);
    } catch (PDOException $e) {
        error_log("Get scoreboard error: " . $e->getMessage());
        json_response(['error' => 'Ошибка получения результатов'], 500);
    }
}

function get_detailed_results() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // Получаем детальные результаты
        $stmt = $pdo->query("
            SELECT p.* 
            FROM participants p 
            ORDER BY p.score DESC
        ");
        $participants = $stmt->fetchAll();
        
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY id");
        $questions = $stmt->fetchAll();
        
        $stmt = $pdo->query("
            SELECT a.*, p.team 
            FROM answers a 
            JOIN participants p ON a.participant_id = p.id
        ");
        $answers = $stmt->fetchAll();
        
        json_response([
            'participants' => $participants,
            'questions' => $questions,
            'answers' => $answers
        ]);
    } catch (PDOException $e) {
        error_log("Get detailed results error: " . $e->getMessage());
        json_response(['error' => 'Ошибка получения детальных результатов'], 500);
    }
}
?>