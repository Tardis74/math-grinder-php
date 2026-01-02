// includes/api/questions-extended.php
<?php

function handle_image_upload($question_id = null) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $upload_dir = __DIR__ . '/../../uploads/questions';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Недопустимый формат файла. Разрешены: JPG, PNG, GIF, WebP');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        throw new Exception('Файл слишком большой. Максимальный размер: 5MB');
    }
    
    // Генерируем уникальное имя файла
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file['name']);
    $file_path = $upload_dir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Ошибка загрузки файла');
    }
    
    // Сохраняем информацию о файле в БД
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO question_images (filename, original_name, file_path, file_size, mime_type, question_id, is_used) 
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $filename,
        $file['name'],
        $file_path,
        $file['size'],
        $file['type'],
        $question_id
    ]);
    
    // Возвращаем относительный путь для отображения
    return '/uploads/questions/' . $filename;
}

function add_question_with_image() {
    global $pdo;
    
    try {
        $text = $_POST['text'] ?? '';
        $answer = $_POST['answer'] ?? '';
        $points = intval($_POST['points'] ?? 1);
        $has_bonus_points = isset($_POST['has_bonus_points']) ? 1 : 0;
        $bonus_first_points = intval($_POST['bonus_first_points'] ?? 0);
        $bonus_second_points = intval($_POST['bonus_second_points'] ?? 0);
        $bonus_third_points = intval($_POST['bonus_third_points'] ?? 0);
        
        if (empty($text) || empty($answer)) {
            json_response(['success' => false, 'error' => 'Заполните текст вопроса и ответ']);
        }
        
        $pdo->beginTransaction();
        
        // Сначала создаем вопрос без изображения
        $stmt = $pdo->prepare("
            INSERT INTO questions (text, answer, points, has_bonus_points, bonus_first_points, bonus_second_points, bonus_third_points) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$text, $answer, $points, $has_bonus_points, $bonus_first_points, $bonus_second_points, $bonus_third_points]);
        
        $question_id = $pdo->lastInsertId();
        $image_path = null;
        
        // Затем обрабатываем изображение
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_path = handle_image_upload($question_id);
            
            if ($image_path) {
                $stmt = $pdo->prepare("UPDATE questions SET image_path = ? WHERE id = ?");
                $stmt->execute([$image_path, $question_id]);
            }
        }
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Вопрос добавлен']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

function update_question_with_image() {
    global $pdo;
    
    // Включаем вывод ошибок для отладки
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    try {
        // Проверяем метод
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Метод не поддерживается');
        }
        
        $id = intval($_POST['id'] ?? 0);
        $text = $_POST['text'] ?? '';
        $answer = $_POST['answer'] ?? '';
        $points = intval($_POST['points'] ?? 1);
        $has_bonus_points = isset($_POST['has_bonus_points']) ? 1 : 0;
        $bonus_first_points = intval($_POST['bonus_first_points'] ?? 0);
        $bonus_second_points = intval($_POST['bonus_second_points'] ?? 0);
        $bonus_third_points = intval($_POST['bonus_third_points'] ?? 0);
        
        if (empty($id) || empty($text) || empty($answer)) {
            throw new Exception('Неверные данные: ID, текст вопроса и ответ обязательны');
        }
        
        $pdo->beginTransaction();
        
        // Получаем текущий вопрос
        $stmt = $pdo->prepare("SELECT image_path FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $current_question = $stmt->fetch();
        
        if (!$current_question) {
            throw new Exception('Вопрос не найден');
        }
        
        $image_path = $current_question['image_path'] ?? null;
        
        // Обрабатываем новое изображение
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            error_log("Processing new image upload");
            
            // Удаляем старое изображение если есть
            if ($image_path && file_exists(__DIR__ . '/../..' . $image_path)) {
                unlink(__DIR__ . '/../..' . $image_path);
                
                // Удаляем запись о старом изображении
                $stmt = $pdo->prepare("DELETE FROM question_images WHERE file_path LIKE ?");
                $stmt->execute(['%' . basename($image_path)]);
            }
            
            $image_path = handle_image_upload($id);
            error_log("New image path: " . $image_path);
        } else {
            error_log("No new image or upload error: " . ($_FILES['image']['error'] ?? 'no file'));
        }
        
        // Обновляем вопрос
        $stmt = $pdo->prepare("
            UPDATE questions SET 
            text = ?, answer = ?, points = ?, image_path = ?, 
            has_bonus_points = ?, bonus_first_points = ?, bonus_second_points = ?, bonus_third_points = ?,
            updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $text, $answer, $points, $image_path,
            $has_bonus_points, $bonus_first_points, $bonus_second_points, $bonus_third_points, $id
        ]);
        
        if (!$result) {
            throw new Exception('Ошибка обновления вопроса в БД');
        }
        
        $pdo->commit();
        
        error_log("Question updated successfully");
        json_response(['success' => true, 'message' => 'Вопрос обновлен']);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Update question error: " . $e->getMessage());
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>