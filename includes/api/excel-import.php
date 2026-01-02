<?php
// includes/api/excel-import.php

require_once __DIR__ . '/../functions.php';

// Проверяем наличие библиотеки PhpSpreadsheet
function check_phpspreadsheet_import() {
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        return ['success' => false, 'error' => 'Библиотека PhpSpreadsheet не установлена'];
    }
    return null;
}

/**
 * Импорт вопросов из Excel файла
 */
function import_questions_from_excel($file_path, $event_type = 'grinder') {
    global $pdo;
    
    $error = check_phpspreadsheet_import();
    if ($error) return $error;
    
    try {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($file_path);
        $sheet = $spreadsheet->getActiveSheet();
        
        $imported = 0;
        $errors = [];
        
        $pdo->beginTransaction();
        
        // Определяем количество строк
        $highestRow = $sheet->getHighestRow();
        
        // Читаем заголовки (первая строка)
        $headers = [];
        for ($col = 1; $col <= 20; $col++) { // Максимум 20 колонок
            $cellValue = $sheet->getCellByColumnAndRow($col, 1)->getValue();
            if (!empty($cellValue)) {
                $headers[$col] = trim($cellValue);
            }
        }
        
        // Определяем тип импорта по заголовкам
        if (in_array('Тип вопроса', $headers)) {
            // Импорт вопросов квиза
            return import_quiz_questions_from_excel_sheet($sheet, $headers, $highestRow);
        } else {
            // Импорт вопросов мясорубки
            return import_grinder_questions_from_excel_sheet($sheet, $headers, $highestRow, $event_type);
        }
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Excel import error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Ошибка импорта из Excel: ' . $e->getMessage()];
    }
}

/**
 * Импорт вопросов мясорубки из Excel
 */
function import_grinder_questions_from_excel_sheet($sheet, $headers, $highestRow, $event_type) {
    global $pdo;
    
    $imported = 0;
    $errors = [];
    
    // Маппинг заголовков на индексы колонок
    $col_map = [];
    foreach ($headers as $col => $header) {
        $col_map[$header] = $col;
    }
    
    // Начинаем со второй строки (первая - заголовки)
    for ($row = 2; $row <= $highestRow; $row++) {
        // Проверяем, пустая ли строка
        $firstCell = $sheet->getCellByColumnAndRow(1, $row)->getValue();
        if (empty($firstCell)) {
            continue;
        }
        
        try {
            // Извлекаем данные
            $text = isset($col_map['Текст вопроса']) ? 
                   $sheet->getCellByColumnAndRow($col_map['Текст вопроса'], $row)->getValue() : 
                   $sheet->getCellByColumnAndRow(1, $row)->getValue();
            
            $answer = isset($col_map['Ответ']) ? 
                     $sheet->getCellByColumnAndRow($col_map['Ответ'], $row)->getValue() : 
                     $sheet->getCellByColumnAndRow(2, $row)->getValue();
            
            $points = isset($col_map['Баллы']) ? 
                     $sheet->getCellByColumnAndRow($col_map['Баллы'], $row)->getValue() : 1;
            
            $image_path = isset($col_map['Изображение']) ? 
                         $sheet->getCellByColumnAndRow($col_map['Изображение'], $row)->getValue() : null;
            
            $has_bonus = isset($col_map['Бонусные баллы']) ? 
                        (strtolower($sheet->getCellByColumnAndRow($col_map['Бонусные баллы'], $row)->getValue()) === 'да') : false;
            
            $bonus_first = isset($col_map['1-й бонус']) ? 
                          $sheet->getCellByColumnAndRow($col_map['1-й бонус'], $row)->getValue() : 0;
            
            $bonus_second = isset($col_map['2-й бонус']) ? 
                           $sheet->getCellByColumnAndRow($col_map['2-й бонус'], $row)->getValue() : 0;
            
            $bonus_third = isset($col_map['3-й бонус']) ? 
                          $sheet->getCellByColumnAndRow($col_map['3-й бонус'], $row)->getValue() : 0;
            
            // Проверяем обязательные поля
            if (empty($text) || empty($answer)) {
                $errors[] = "Строка {$row}: Вопрос и ответ не могут быть пустыми";
                continue;
            }
            
            // Вставляем вопрос
            $stmt = $pdo->prepare("
                INSERT INTO questions (text, answer, points, image_path, 
                                      has_bonus_points, bonus_first_points, bonus_second_points, bonus_third_points,
                                      event_type, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $success = $stmt->execute([
                $text, $answer, intval($points), $image_path,
                $has_bonus ? 1 : 0, intval($bonus_first), intval($bonus_second), intval($bonus_third),
                $event_type
            ]);
            
            if ($success) {
                $imported++;
            } else {
                $errors[] = "Строка {$row}: Ошибка базы данных";
            }
            
        } catch (Exception $e) {
            $errors[] = "Строка {$row}: " . $e->getMessage();
        }
    }
    
    $pdo->commit();
    
    $result = ['success' => true, 'message' => "Импортировано вопросов мясорубки: $imported"];
    if (!empty($errors)) {
        $result['errors'] = $errors;
        $result['error_count'] = count($errors);
    }
    
    return $result;
}

/**
 * Импорт вопросов квиза из Excel
 */
function import_quiz_questions_from_excel_sheet($sheet, $headers, $highestRow) {
    global $pdo;
    
    $imported = 0;
    $errors = [];
    
    // Маппинг заголовков
    $col_map = [];
    foreach ($headers as $col => $header) {
        $col_map[$header] = $col;
    }
    
    // Определяем колонки для ответов (Ответ 1, Ответ 2, и т.д.)
    $answer_cols = [];
    foreach ($col_map as $header => $col) {
        if (preg_match('/^Ответ (\d+)$/', $header, $matches)) {
            $answer_num = $matches[1];
            $answer_cols[$answer_num] = [
                'text_col' => $col,
                'correct_col' => isset($col_map["Правильность {$answer_num}"]) ? $col_map["Правильность {$answer_num}"] : null,
                'points_col' => isset($col_map["Баллы {$answer_num}"]) ? $col_map["Баллы {$answer_num}"] : null
            ];
        }
    }
    
    $pdo->beginTransaction();
    
    // Начинаем со второй строки
    for ($row = 2; $row <= $highestRow; $row++) {
        $firstCell = $sheet->getCellByColumnAndRow(1, $row)->getValue();
        if (empty($firstCell)) continue;
        
        try {
            // Извлекаем данные вопроса
            $question_text = isset($col_map['Текст вопроса']) ? 
                            $sheet->getCellByColumnAndRow($col_map['Текст вопроса'], $row)->getValue() : 
                            $sheet->getCellByColumnAndRow(1, $row)->getValue();
            
            $question_type_raw = isset($col_map['Тип вопроса']) ? 
                                $sheet->getCellByColumnAndRow($col_map['Тип вопроса'], $row)->getValue() : 'single';
            $question_type = (strtolower($question_type_raw) === 'множественный' || $question_type_raw === 'multiple') ? 
                            'multiple' : 'single';
            
            $question_time = isset($col_map['Время вопроса (сек)']) ? 
                            $sheet->getCellByColumnAndRow($col_map['Время вопроса (сек)'], $row)->getValue() : 30;
            
            $answer_time = isset($col_map['Время ответов (сек)']) ? 
                          $sheet->getCellByColumnAndRow($col_map['Время ответов (сек)'], $row)->getValue() : 10;
            
            $display_order = isset($col_map['Порядок']) ? 
                            $sheet->getCellByColumnAndRow($col_map['Порядок'], $row)->getValue() : 0;
            
            $image_path = isset($col_map['Изображение']) ? 
                         $sheet->getCellByColumnAndRow($col_map['Изображение'], $row)->getValue() : null;
            
            // Проверяем обязательные поля
            if (empty($question_text)) {
                $errors[] = "Строка {$row}: Текст вопроса не может быть пустым";
                continue;
            }
            
            // Получаем максимальный порядок отображения
            $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM quiz_questions");
            $max_order = $stmt->fetch()['max_order'] ?? 0;
            $display_order = $display_order > 0 ? $display_order : $max_order + 1;
            
            // Вставляем вопрос
            $stmt = $pdo->prepare("
                INSERT INTO quiz_questions (question_text, question_type, question_time, 
                                           answer_time, display_order, image_path, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            if (!$stmt->execute([$question_text, $question_type, intval($question_time), 
                                intval($answer_time), $display_order, $image_path])) {
                $errors[] = "Строка {$row}: Ошибка при добавлении вопроса";
                continue;
            }
            
            $question_id = $pdo->lastInsertId();
            
            // Обрабатываем ответы
            $answer_order = 1;
            foreach ($answer_cols as $answer_num => $cols) {
                $text_col = $cols['text_col'];
                $correct_col = $cols['correct_col'];
                $points_col = $cols['points_col'];
                
                $answer_text = $sheet->getCellByColumnAndRow($text_col, $row)->getValue();
                if (empty($answer_text)) continue;
                
                $is_correct = $correct_col ? 
                             (strtolower($sheet->getCellByColumnAndRow($correct_col, $row)->getValue()) === 'да') : false;
                
                $points = $points_col ? 
                         $sheet->getCellByColumnAndRow($points_col, $row)->getValue() : 
                         ($is_correct ? 1 : 0);
                
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_answers (quiz_question_id, answer_text, is_correct, points, display_order)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                if (!$stmt->execute([$question_id, $answer_text, $is_correct ? 1 : 0, 
                                    intval($points), $answer_order])) {
                    $errors[] = "Строка {$row}: Ошибка при добавлении ответа {$answer_num}";
                    continue 2; // Переходим к следующему вопросу
                }
                
                $answer_order++;
            }
            
            $imported++;
            
        } catch (Exception $e) {
            $errors[] = "Строка {$row}: " . $e->getMessage();
        }
    }
    
    $pdo->commit();
    
    $result = ['success' => true, 'message' => "Импортировано вопросов квиза: $imported"];
    if (!empty($errors)) {
        $result['errors'] = $errors;
        $result['error_count'] = count($errors);
    }
    
    return $result;
}
?>