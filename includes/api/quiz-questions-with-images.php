<?php
// includes/api/quiz-questions-with-images.php

function handle_quiz_image_upload() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $upload_dir = __DIR__ . '/../../uploads/questions';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'svg'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Недопустимый формат файла. Разрешены: JPG, PNG, GIF, WebP, ICO, SVG');
    }
    
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Файл слишком большой. Максимальный размер: 10MB');
    }
    
    $filename = uniqid('quiz_') . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file['name']);
    $file_path = $upload_dir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Ошибка загрузки файла');
    }
    
    return '/uploads/questions/' . $filename;
}

function add_quiz_question_with_image() {
    global $pdo;
    
    try {
        // Получаем данные из FormData
        $question_text = $_POST['question_text'] ?? '';
        $question_type = $_POST['question_type'] ?? 'single';
        $question_time = intval($_POST['question_time'] ?? 30);
        $answer_time = intval($_POST['answer_time'] ?? 10);
        
        if (empty($question_text)) {
            json_response(['success' => false, 'error' => 'Заполните текст вопроса']);
        }
        
        $pdo->beginTransaction();
        
        // Обрабатываем изображение
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_path = handle_quiz_image_upload();
        }
        
        // Определяем порядок отображения
        $stmt = $pdo->query("SELECT COALESCE(MAX(display_order), 0) as max_order FROM quiz_questions");
        $max_order = $stmt->fetch()['max_order'];
        
        // Создаем вопрос
        $stmt = $pdo->prepare("
            INSERT INTO quiz_questions (question_text, question_type, question_time, answer_time, display_order, image_path) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $question_text,
            $question_type,
            $question_time,
            $answer_time,
            $max_order + 1,
            $image_path
        ]);
        
        $question_id = $pdo->lastInsertId();
        
        // Обрабатываем ответы
        $answers_data = $_POST['answers'] ?? [];
        if (!empty($answers_data)) {
            $answers = json_decode($answers_data, true);
            
            foreach ($answers as $index => $answer) {
                $answer_text = trim($answer['text'] ?? '');
                if (empty($answer_text)) continue;
                
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_answers (quiz_question_id, answer_text, is_correct, points, display_order) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $question_id,
                    $answer_text,
                    $answer['is_correct'] ? 1 : 0,
                    intval($answer['points'] ?? 0),
                    $index + 1
                ]);
            }
        }
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Вопрос квиза добавлен', 'question_id' => $question_id]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

function update_quiz_question_with_image() {
    global $pdo;
    
    try {
        $question_id = intval($_POST['id'] ?? 0);
        $question_text = $_POST['question_text'] ?? '';
        $question_type = $_POST['question_type'] ?? 'single';
        $question_time = intval($_POST['question_time'] ?? 30);
        $answer_time = intval($_POST['answer_time'] ?? 10);
        
        if (empty($question_id) || empty($question_text)) {
            json_response(['success' => false, 'error' => 'Неверные данные']);
        }
        
        $pdo->beginTransaction();
        
        // Получаем текущий вопрос
        $stmt = $pdo->prepare("SELECT image_path FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $current = $stmt->fetch();
        $image_path = $current['image_path'] ?? null;
        
        // Обработка удаления изображения
        $delete_image = isset($_POST['delete_image']) && $_POST['delete_image'] === 'true';
        if ($delete_image && $image_path) {
            // Удаляем файл с сервера
            if (file_exists(__DIR__ . '/../..' . $image_path)) {
                unlink(__DIR__ . '/../..' . $image_path);
            }
            $image_path = null;
        }
        
        // Обработка нового изображения
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Удаляем старое изображение если есть
            if ($image_path && file_exists(__DIR__ . '/../..' . $image_path)) {
                unlink(__DIR__ . '/../..' . $image_path);
            }
            $image_path = handle_quiz_image_upload();
        }
        
        // Обновляем вопрос
        $stmt = $pdo->prepare("
            UPDATE quiz_questions SET 
            question_text = ?, question_type = ?, question_time = ?, answer_time = ?, image_path = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $question_text, $question_type, $question_time, $answer_time, $image_path, $question_id
        ]);
        
        // Очищаем старые ответы и добавляем новые
        $stmt = $pdo->prepare("DELETE FROM quiz_answers WHERE quiz_question_id = ?");
        $stmt->execute([$question_id]);
        
        // Обрабатываем новые ответы
        $answers_data = $_POST['answers'] ?? [];
        if (!empty($answers_data)) {
            $answers = json_decode($answers_data, true);
            
            foreach ($answers as $index => $answer) {
                $answer_text = trim($answer['text'] ?? '');
                if (empty($answer_text)) continue;
                
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_answers (quiz_question_id, answer_text, is_correct, points, display_order) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $question_id,
                    $answer_text,
                    $answer['is_correct'] ? 1 : 0,
                    intval($answer['points'] ?? 0),
                    $index + 1
                ]);
            }
        }
        
        $pdo->commit();
        
        // Возвращаем обновленные данные
        $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $updated_question = $stmt->fetch();
        
        json_response([
            'success' => true, 
            'message' => 'Вопрос квиза обновлен',
            'question' => $updated_question
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>