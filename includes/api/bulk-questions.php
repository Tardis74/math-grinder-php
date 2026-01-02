<?php
// includes/api/bulk-questions.php

function delete_questions_bulk() {
    global $pdo;
    
    error_log("BULK DELETE: Starting function");
    error_log("BULK DELETE: POST data = " . print_r($_POST, true));
    error_log("BULK DELETE: GET data = " . print_r($_GET, true));
    
    // Получаем данные из POST
    $ids_input = $_POST['ids'] ?? '';
    
    if (empty($ids_input)) {
        // Попробуем получить из raw input
        $raw_input = file_get_contents("php://input");
        error_log("BULK DELETE: Raw input = " . $raw_input);
        
        if (!empty($raw_input)) {
            $decoded = json_decode($raw_input, true);
            if ($decoded && isset($decoded['ids'])) {
                $ids_input = $decoded['ids'];
            }
        }
    }
    
    if (empty($ids_input)) {
        json_response([
            'success' => false, 
            'error' => 'ID вопросов не указаны',
            'debug' => [
                'post' => $_POST,
                'raw_input' => file_get_contents("php://input")
            ]
        ]);
    }
    
    error_log("BULK DELETE: IDs input = " . print_r($ids_input, true));
    
    // Преобразуем в массив
    $ids = [];
    
    if (is_string($ids_input)) {
        // Пробуем декодировать JSON
        $decoded = json_decode($ids_input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $ids = $decoded;
        } else {
            // Разбиваем строку по запятой
            $ids = explode(',', $ids_input);
            // Убираем пробелы
            $ids = array_map('trim', $ids);
        }
    } elseif (is_array($ids_input)) {
        $ids = $ids_input;
    }
    
    // Фильтруем ID
    $filtered_ids = [];
    foreach ($ids as $id) {
        $id_int = intval($id);
        if ($id_int > 0) {
            $filtered_ids[] = $id_int;
        }
    }
    
    if (empty($filtered_ids)) {
        json_response([
            'success' => false, 
            'error' => 'Неверный формат ID',
            'debug' => [
                'original_ids' => $ids,
                'filtered_ids' => $filtered_ids,
                'ids_input_type' => gettype($ids_input)
            ]
        ]);
    }
    
    error_log("BULK DELETE: Filtered IDs = " . print_r($filtered_ids, true));
    
    try {
        $pdo->beginTransaction();
        
        // 1. Получаем изображения для удаления
        $placeholders = implode(',', array_fill(0, count($filtered_ids), '?'));
        $stmt = $pdo->prepare("SELECT id, image_path FROM questions WHERE id IN ($placeholders)");
        $stmt->execute($filtered_ids);
        $questions = $stmt->fetchAll();
        
        // 2. Удаляем файлы изображений
        foreach ($questions as $question) {
            if ($question['image_path'] && $question['image_path'] !== 'null' && $question['image_path'] !== '') {
                $image_path = __DIR__ . '/../..' . $question['image_path'];
                if (file_exists($image_path)) {
                    if (unlink($image_path)) {
                        error_log("BULK DELETE: Deleted image: " . $image_path);
                    } else {
                        error_log("BULK DELETE: Failed to delete image: " . $image_path);
                    }
                }
            }
        }
        
        // 3. Удаляем вопросы из базы
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id IN ($placeholders)");
        $stmt->execute($filtered_ids);
        $deleted_count = $stmt->rowCount();
        
        $pdo->commit();
        
        error_log("BULK DELETE: Successfully deleted $deleted_count questions");
        
        json_response([
            'success' => true, 
            'message' => "Успешно удалено $deleted_count вопросов",
            'deleted' => $deleted_count
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("BULK DELETE: Error: " . $e->getMessage());
        json_response([
            'success' => false, 
            'error' => 'Ошибка удаления вопросов: ' . $e->getMessage()
        ]);
    }
}
?>