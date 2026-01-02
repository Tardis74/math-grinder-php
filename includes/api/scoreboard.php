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

function get_quiz_scoreboard() {
    global $pdo;
    
    try {
        // Получаем участников квиза с их результатами
        $stmt = $pdo->query("
            SELECT p.id, p.team, p.score as total_score,
                   COUNT(DISTINCT qpa.quiz_question_id) as questions_answered,
                   SUM(CASE WHEN qpa.points_earned > 0 THEN 1 ELSE 0 END) as correct_answers,
                   SUM(qpa.points_earned) as quiz_score
            FROM participants p
            LEFT JOIN quiz_participant_answers qpa ON p.id = qpa.participant_id
            GROUP BY p.id
            ORDER BY quiz_score DESC, p.team
        ");
        
        $participants = $stmt->fetchAll();
        
        // Получаем вопросы квиза
        $stmt = $pdo->query("
            SELECT qq.*, 
                   GROUP_CONCAT(CONCAT_WS('|', qa.id, qa.answer_text, qa.is_correct) 
                   ORDER BY qa.display_order SEPARATOR ';;') as answers_data
            FROM quiz_questions qq
            LEFT JOIN quiz_answers qa ON qq.id = qa.quiz_question_id
            GROUP BY qq.id
            ORDER BY qq.display_order
        ");
        
        $questions = $stmt->fetchAll();
        
        // Парсим ответы
        foreach ($questions as &$question) {
            $question['answers'] = [];
            if ($question['answers_data']) {
                $answers = explode(';;', $question['answers_data']);
                foreach ($answers as $answer) {
                    list($id, $text, $is_correct) = explode('|', $answer);
                    $question['answers'][] = [
                        'id' => (int)$id,
                        'text' => $text,
                        'is_correct' => (bool)$is_correct
                    ];
                }
            }
            unset($question['answers_data']);
        }
        
        json_response([
            'success' => true,
            'participants' => $participants,
            'questions' => $questions
        ]);
        
    } catch (PDOException $e) {
        json_response(['success' => false, 'error' => 'Ошибка получения результатов квиза: ' . $e->getMessage()]);
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