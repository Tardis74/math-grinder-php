<?php
// includes/api/quiz-questions.php
function get_quiz_questions_with_images() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT qq.*, 
                   GROUP_CONCAT(CONCAT_WS('|', qa.id, qa.answer_text, qa.is_correct, qa.points, qa.display_order) 
                   ORDER BY qa.display_order SEPARATOR ';;') as answers_data
            FROM quiz_questions qq
            LEFT JOIN quiz_answers qa ON qq.id = qa.quiz_question_id
            GROUP BY qq.id
            ORDER BY qq.display_order, qq.id
        ");
        
        $questions = $stmt->fetchAll();
        
        // Парсим ответы
        foreach ($questions as &$question) {
            $question['answers'] = [];
            if ($question['answers_data']) {
                $answers = explode(';;', $question['answers_data']);
                foreach ($answers as $answer) {
                    list($id, $text, $is_correct, $points, $order) = explode('|', $answer);
                    $question['answers'][] = [
                        'id' => (int)$id,
                        'answer_text' => $text,
                        'is_correct' => (bool)$is_correct,
                        'points' => (int)$points,
                        'display_order' => (int)$order
                    ];
                }
            }
            unset($question['answers_data']);
            
            // Добавляем полный URL для изображения
            if ($question['image_path']) {
                $question['image_url'] = BASE_URL . $question['image_path'];
            }
        }
        
        json_response($questions);
        
    } catch (PDOException $e) {
        json_response(['error' => 'Ошибка получения вопросов: ' . $e->getMessage()]);
    }
}

function get_quiz_questions() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT qq.*, 
                   GROUP_CONCAT(CONCAT_WS('|', qa.id, qa.answer_text, qa.is_correct, qa.points, qa.display_order) 
                   ORDER BY qa.display_order SEPARATOR ';;') as answers_data
            FROM quiz_questions qq
            LEFT JOIN quiz_answers qa ON qq.id = qa.quiz_question_id
            GROUP BY qq.id
            ORDER BY qq.display_order, qq.id
        ");
        
        $questions = $stmt->fetchAll();
        
        foreach ($questions as &$question) {
            $question['answers'] = [];
            if ($question['answers_data']) {
                $answers = explode(';;', $question['answers_data']);
                foreach ($answers as $answer) {
                    list($id, $text, $is_correct, $points, $order) = explode('|', $answer);
                    $question['answers'][] = [
                        'id' => (int)$id,
                        'answer_text' => $text,
                        'is_correct' => (bool)$is_correct,
                        'points' => (int)$points,
                        'display_order' => (int)$order
                    ];
                }
            }
            unset($question['answers_data']);
            
            // ИСПРАВЛЯЕМ путь к изображению
            if ($question['image_path'] && $question['image_path'] !== 'null' && $question['image_path'] !== '') {
                // Если путь уже абсолютный (начинается с /), используем как есть
                if (strpos($question['image_path'], '/') === 0) {
                    $question['image_url'] = 'http://localhost' . $question['image_path'];
                } else {
                    // Иначе добавляем базовый путь
                    $question['image_url'] = 'http://localhost/math-grinder-php/uploads/questions/' . $question['image_path'];
                }
                
                // Также сохраняем относительный путь
                $question['image_path_relative'] = $question['image_path'];
            } else {
                $question['image_path'] = null;
                $question['image_url'] = null;
                $question['image_path_relative'] = null;
            }
        }
        
        json_response($questions);
        
    } catch (PDOException $e) {
        json_response(['error' => 'Ошибка получения вопросов: ' . $e->getMessage()]);
    }
}

function add_quiz_question($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // Определяем порядок отображения
        $stmt = $pdo->query("SELECT COALESCE(MAX(display_order), 0) as max_order FROM quiz_questions");
        $max_order = $stmt->fetch()['max_order'];
        
        // Обрабатываем изображение, если передано в FormData
        $image_path = null;
        if (isset($data['image_path'])) {
            $image_path = $data['image_path'];
        }
        
        // Вставляем вопрос
        $stmt = $pdo->prepare("
            INSERT INTO quiz_questions (question_text, question_type, question_time, answer_time, display_order, image_path) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['question_text'],
            $data['question_type'],
            $data['question_time'],
            $data['answer_time'],
            $max_order + 1,
            $image_path
        ]);
        
        $question_id = $pdo->lastInsertId();
        
        // Вставляем ответы
        foreach ($data['answers'] as $index => $answer) {
            $stmt = $pdo->prepare("
                INSERT INTO quiz_answers (quiz_question_id, answer_text, is_correct, points, display_order) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $question_id,
                $answer['text'],
                $answer['is_correct'] ? 1 : 0,
                $answer['points'],
                $index + 1
            ]);
        }
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Вопрос добавлен']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка добавления вопроса: ' . $e->getMessage()]);
    }
}

function update_quiz_question($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        $question_id = $data['id'];
        
        // Обработка изображения
        $image_path = $data['image_path'] ?? null;
        $delete_image = isset($data['delete_image']) && $data['delete_image'] === true;
        
        if ($delete_image) {
            // Получаем текущий путь к изображению
            $stmt = $pdo->prepare("SELECT image_path FROM quiz_questions WHERE id = ?");
            $stmt->execute([$question_id]);
            $current = $stmt->fetch();
            
            // Удаляем файл с сервера
            if ($current['image_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $current['image_path'])) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $current['image_path']);
            }
            $image_path = null;
        }
        
        // 1. Обновляем вопрос с изображением
        $stmt = $pdo->prepare("
            UPDATE quiz_questions SET 
            question_text = ?, question_type = ?, question_time = ?, answer_time = ?, image_path = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $data['question_text'],
            $data['question_type'],
            $data['question_time'],
            $data['answer_time'],
            $image_path,
            $question_id
        ]);
        
        // 2. Очищаем старые ответы
        $stmt = $pdo->prepare("DELETE FROM quiz_answers WHERE quiz_question_id = ?");
        $stmt->execute([$question_id]);
        
        // 3. Вставляем новые ответы
        foreach ($data['answers'] as $index => $answer) {
            // Проверяем, есть ли текст ответа
            $answer_text = trim($answer['text'] ?? $answer['answer_text'] ?? '');
            if (empty($answer_text)) {
                continue; // Пропускаем пустые ответы
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO quiz_answers (quiz_question_id, answer_text, is_correct, points, display_order) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $question_id,
                $answer_text,
                $answer['is_correct'] ? 1 : 0,
                $answer['points'] ?? 0,
                $index + 1
            ]);
        }
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Вопрос обновлен']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка обновления вопроса: ' . $e->getMessage()]);
    }
}

function delete_quiz_question($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        $question_id = $data['id'];
        
        // Удаляем ответы
        $stmt = $pdo->prepare("DELETE FROM quiz_answers WHERE quiz_question_id = ?");
        $stmt->execute([$question_id]);
        
        // Удаляем вопрос
        $stmt = $pdo->prepare("DELETE FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        
        // Обновляем порядок оставшихся вопросов
        $stmt = $pdo->query("SET @row_number = 0");
        $stmt = $pdo->query("
            UPDATE quiz_questions 
            SET display_order = (@row_number:=@row_number + 1) 
            ORDER BY display_order
        ");
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Вопрос удален']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка удаления вопроса: ' . $e->getMessage()]);
    }
}

function move_quiz_question_up($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        $question_id = $data['id'];
        
        // Получаем текущий порядок
        $stmt = $pdo->prepare("SELECT display_order FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $current_order = $stmt->fetch()['display_order'];
        
        if ($current_order > 1) {
            // Меняем местами с предыдущим вопросом
            $stmt = $pdo->prepare("
                UPDATE quiz_questions 
                SET display_order = CASE 
                    WHEN id = ? THEN ? - 1 
                    WHEN display_order = ? - 1 THEN ? 
                END 
                WHERE id = ? OR display_order = ? - 1
            ");
            $stmt->execute([
                $question_id, $current_order,
                $current_order, $current_order,
                $question_id, $current_order
            ]);
        }
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Порядок обновлен']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка изменения порядка: ' . $e->getMessage()]);
    }
}

function move_quiz_question_down($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        $question_id = $data['id'];
        
        // Получаем текущий порядок и максимальный порядок
        $stmt = $pdo->prepare("SELECT display_order FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $current_order = $stmt->fetch()['display_order'];
        
        $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM quiz_questions");
        $max_order = $stmt->fetch()['max_order'];
        
        if ($current_order < $max_order) {
            // Меняем местами со следующим вопросом
            $stmt = $pdo->prepare("
                UPDATE quiz_questions 
                SET display_order = CASE 
                    WHEN id = ? THEN ? + 1 
                    WHEN display_order = ? + 1 THEN ? 
                END 
                WHERE id = ? OR display_order = ? + 1
            ");
            $stmt->execute([
                $question_id, $current_order,
                $current_order, $current_order,
                $question_id, $current_order
            ]);
        }
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Порядок обновлен']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка изменения порядка: ' . $e->getMessage()]);
    }
}

function duplicate_quiz_question($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        $question_id = $data['id'];
        
        // Получаем оригинальный вопрос
        $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $original_question = $stmt->fetch();
        
        if (!$original_question) {
            throw new Exception('Вопрос не найден');
        }
        
        // Получаем максимальный порядок
        $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM quiz_questions");
        $max_order = $stmt->fetch()['max_order'];
        
        // Создаем копию вопроса
        $stmt = $pdo->prepare("
            INSERT INTO quiz_questions (question_text, question_type, question_time, answer_time, display_order) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $original_question['question_text'] . ' (копия)',
            $original_question['question_type'],
            $original_question['question_time'],
            $original_question['answer_time'],
            $max_order + 1
        ]);
        
        $new_question_id = $pdo->lastInsertId();
        
        // Копируем ответы
        $stmt = $pdo->prepare("SELECT * FROM quiz_answers WHERE quiz_question_id = ? ORDER BY display_order");
        $stmt->execute([$question_id]);
        $answers = $stmt->fetchAll();
        
        foreach ($answers as $answer) {
            $stmt = $pdo->prepare("
                INSERT INTO quiz_answers (quiz_question_id, answer_text, is_correct, points, display_order) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $new_question_id,
                $answer['answer_text'],
                $answer['is_correct'],
                $answer['points'],
                $answer['display_order']
            ]);
        }
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Вопрос скопирован']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка копирования вопроса: ' . $e->getMessage()]);
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

function clear_all_quiz_questions($data) {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $pdo->beginTransaction();
        
        // Удаляем все ответы
        $pdo->exec("DELETE FROM quiz_answers");
        
        // Удаляем все вопросы
        $pdo->exec("DELETE FROM quiz_questions");
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Все вопросы удалены']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка очистки вопросов: ' . $e->getMessage()]);
    }
}
?>