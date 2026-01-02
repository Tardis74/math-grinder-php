<?php
// includes/api/import-enhanced.php

function import_questions_simple($file_content) {
    global $pdo;
    
    try {
        $lines = explode("\n", $file_content);
        $imported = 0;
        $errors = [];
        
        $pdo->beginTransaction();
        
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            
            // Пропускаем пустые строки и комментарии
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Разбираем строку: Вопрос | Ответ
            $parts = explode('|', $line);
            if (count($parts) < 2) {
                $errors[] = "Строка " . ($line_num + 1) . ": неверный формат";
                continue;
            }
            
            $text = trim($parts[0]);
            $answer = trim($parts[1]);
            
            if (empty($text) || empty($answer)) {
                $errors[] = "Строка " . ($line_num + 1) . ": вопрос и ответ не могут быть пустыми";
                continue;
            }
            
            // Добавляем вопрос с базовыми настройками
            $stmt = $pdo->prepare("
                INSERT INTO questions (text, answer, points, has_bonus_points) 
                VALUES (?, ?, 1, 0)
            ");
            $stmt->execute([$text, $answer]);
            
            $imported++;
        }
        
        $pdo->commit();
        
        $result = ['success' => true, 'message' => "Импортировано вопросов: $imported"];
        if (!empty($errors)) {
            $result['errors'] = $errors;
        }
        
        return $result;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'error' => 'Ошибка импорта: ' . $e->getMessage()];
    }
}
?>