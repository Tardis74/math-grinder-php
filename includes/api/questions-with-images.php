<?php
function handle_image_upload() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $upload_dir = __DIR__ . '/../../uploads/questions';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('Недопустимый формат файла');
    }
    
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Файл слишком большой');
    }
    
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file['name']);
    $file_path = $upload_dir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Ошибка загрузки файла');
    }
    
    return '/uploads/questions/' . $filename;
}

function add_question() {
    global $pdo;
    
    try {
        // Получаем данные из $_POST (FormData)
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
        
        // Обрабатываем изображение
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_path = handle_image_upload();
        }
        
        // Создаем вопрос
        $stmt = $pdo->prepare("
            INSERT INTO questions (text, answer, points, image_path, has_bonus_points, bonus_first_points, bonus_second_points, bonus_third_points) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $text, $answer, $points, $image_path,
            $has_bonus_points, $bonus_first_points, $bonus_second_points, $bonus_third_points
        ]);
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Вопрос добавлен']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}

function update_question() {
    global $pdo;
    
    try {
        $id = intval($_POST['id'] ?? 0);
        $text = $_POST['text'] ?? '';
        $answer = $_POST['answer'] ?? '';
        $points = intval($_POST['points'] ?? 1);
        
        // ПРАВИЛЬНО обрабатываем чекбокс
        $has_bonus_points = isset($_POST['has_bonus_points']) ? intval($_POST['has_bonus_points']) : 0;
        $bonus_first_points = intval($_POST['bonus_first_points'] ?? 0);
        $bonus_second_points = intval($_POST['bonus_second_points'] ?? 0);
        $bonus_third_points = intval($_POST['bonus_third_points'] ?? 0);
        
        error_log("Update question: id=$id, has_bonus_points=$has_bonus_points");
        
        if (empty($id) || empty($text) || empty($answer)) {
            json_response(['success' => false, 'error' => 'Неверные данные']);
        }
        
        $pdo->beginTransaction();
        
        // Получаем текущий вопрос
        $stmt = $pdo->prepare("SELECT image_path FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        
        $image_path = $current['image_path'] ?? null;
        
        $delete_image = isset($_POST['delete_image']) && $_POST['delete_image'] === 'true';
        if ($delete_image && $image_path) {
            error_log("Deleting image: " . $image_path);
            
            // Удаляем файл с сервера
            if (file_exists(__DIR__ . '/../..' . $image_path)) {
                unlink(__DIR__ . '/../..' . $image_path);
                error_log("Deleted image file: " . $image_path);
            }
            
            $image_path = null; // Устанавливаем image_path в NULL в БД
        }

        // Обрабатываем новое изображение
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            error_log("Processing new image upload");
            
            // Удаляем старое изображение если есть
            if ($image_path && file_exists(__DIR__ . '/../..' . $image_path)) {
                unlink(__DIR__ . '/../..' . $image_path);
                error_log("Deleted old image: " . $image_path);
            }
            
            $image_path = handle_image_upload();
            error_log("New image path: " . $image_path);
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
            throw new Exception('Ошибка выполнения SQL запроса');
        }
        
        $pdo->commit();
        
        // Возвращаем обновленные данные
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $updated_question = $stmt->fetch();
        
        json_response([
            'success' => true, 
            'message' => 'Вопрос обновлен',
            'question' => $updated_question
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Update question error: " . $e->getMessage());
        json_response(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>