<?php
// includes/api/questions.php

function delete_question($input) {
    global $pdo;
    
    try {
        $id = intval($input['id'] ?? 0);
        
        if (empty($id)) {
            json_response(['success' => false, 'error' => 'ID вопроса не указан']);
        }
        
        $pdo->beginTransaction();
        
        // Получаем информацию о вопросе для удаления изображения
        $stmt = $pdo->prepare("SELECT image_path FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        $question = $stmt->fetch();
        
        if ($question && $question['image_path']) {
            $image_path = __DIR__ . '/../..' . $question['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Удаляем вопрос
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Вопрос удален']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка удаления вопроса: ' . $e->getMessage()]);
    }
}

function import_questions_from_file($input) {
    global $pdo;
    
    try {
        $file_content = $input['file_content'] ?? '';
        
        if (empty($file_content)) {
            json_response(['success' => false, 'error' => 'Содержимое файла пустое']);
        }
        
        $lines = explode("\n", $file_content);
        $imported = 0;
        $errors = [];
        
        $pdo->beginTransaction();
        
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode('|', $line);
            if (count($parts) < 2) {
                $errors[] = "Строка " . ($line_num + 1) . ": неверный формат";
                continue;
            }
            
            $text = trim($parts[0]);
            $answer = trim($parts[1]);
            $points = isset($parts[2]) ? intval(trim($parts[2])) : 1;
            
            if (empty($text) || empty($answer)) {
                $errors[] = "Строка " . ($line_num + 1) . ": отсутствует текст или ответ";
                continue;
            }
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO questions (text, answer, points) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$text, $answer, $points]);
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Строка " . ($line_num + 1) . ": ошибка БД - " . $e->getMessage();
            }
        }
        
        $pdo->commit();
        
        $message = "Импортировано вопросов: $imported";
        if (!empty($errors)) {
            $message .= ". Ошибок: " . count($errors);
        }
        
        json_response([
            'success' => true, 
            'message' => $message,
            'imported' => $imported,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Ошибка импорта: ' . $e->getMessage()]);
    }
}
?>