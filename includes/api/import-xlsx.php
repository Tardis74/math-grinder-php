<?php
// includes/api/import-xlsx.php - версия с отладкой

// Включаем отображение всех ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Увеличиваем лимиты для больших файлов
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function import_questions_xlsx($input) {
    global $pdo;
    
    // Упрощенная проверка админа для отладки
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        json_response(['success' => false, 'error' => 'Требуется авторизация администратора']);
        return;
    }
    
    try {
        error_log("Начало импорта XLSX");
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Файл не загружен. Код ошибки: ' . $_FILES['file']['error']);
        }
        
        $file = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        error_log("Файл: $fileName, тип: $fileType, размер: " . filesize($file));
        
        if ($fileType !== 'xlsx') {
            throw new Exception('Поддерживаются только файлы XLSX. Ваш файл: .' . $fileType);
        }
        
        if (!file_exists($file) || filesize($file) === 0) {
            throw new Exception('Файл пустой или не существует');
        }
        
        // Загружаем Excel файл
        error_log("Загрузка файла в PhpSpreadsheet...");
        $spreadsheet = IOFactory::load($file);
        error_log("Файл загружен успешно");
        
        // Проверяем доступные листы
        $sheetNames = $spreadsheet->getSheetNames();
        error_log("Доступные листы: " . implode(', ', $sheetNames));
        
        $pdo->beginTransaction();
        $imported = [
            'grinder' => 0,
            'quiz' => 0,
            'options' => 0
        ];
        $errors = [];
        
        // ===================== ИМПОРТ ВОПРОСОВ МЯСОРУБКИ =====================
        if (in_array('Grinder Questions', $sheetNames)) {
            error_log("Обработка листа 'Grinder Questions'");
            $sheet = $spreadsheet->getSheetByName('Grinder Questions');
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            
            error_log("Строк в листе: $highestRow, колонок до: $highestColumn");
            
            // Получаем все изображения из листа
            $images = [];
            $drawingCollection = $sheet->getDrawingCollection();
            if ($drawingCollection) {
                foreach ($drawingCollection as $drawing) {
                    $coordinates = $drawing->getCoordinates();
                    $images[$coordinates] = $drawing;
                    error_log("Найдено изображение в ячейке: $coordinates");
                }
            }
            
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $text = trim($sheet->getCell('B' . $row)->getValue());
                    $answer = trim($sheet->getCell('C' . $row)->getValue());
                    
                    // Пропускаем пустые строки
                    if (empty($text) && empty($answer)) {
                        continue;
                    }
                    
                    if (empty($text) || empty($answer)) {
                        $errors[] = "Строка $row (Grinder): отсутствует вопрос или ответ";
                        continue;
                    }
                    
                    $points = (int)$sheet->getCell('D' . $row)->getValue() ?: 1;
                    $hasBonusCell = $sheet->getCell('E' . $row)->getValue();
                    $hasBonus = (strtolower($hasBonusCell) === 'yes' || $hasBonusCell === 1 || $hasBonusCell === '1') ? 1 : 0;
                    $bonusFirst = (int)$sheet->getCell('F' . $row)->getValue() ?: 0;
                    $bonusSecond = (int)$sheet->getCell('G' . $row)->getValue() ?: 0;
                    $bonusThird = (int)$sheet->getCell('H' . $row)->getValue() ?: 0;
                    
                    error_log("Обработка строки $row: '$text' -> '$answer', points: $points");
                    
                    // Обработка изображения
                    $imagePath = null;
                    $imageCell = 'I' . $row;
                    
                    if (isset($images[$imageCell])) {
                        error_log("Найдено изображение для строки $row");
                        $imagePath = save_drawing_to_file($images[$imageCell], 'grinder');
                        error_log("Изображение сохранено: $imagePath");
                    }
                    
                    // Сохраняем в БД
                    $stmt = $pdo->prepare("
                        INSERT INTO questions (text, answer, points, image_path, 
                            has_bonus_points, bonus_first_points, bonus_second_points, bonus_third_points)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $text, $answer, $points, $imagePath,
                        $hasBonus, $bonusFirst, $bonusSecond, $bonusThird
                    ]);
                    
                    $imported['grinder']++;
                    error_log("Вопрос мясорубки сохранен: ID " . $pdo->lastInsertId());
                    
                } catch (Exception $e) {
                    $errorMsg = "Строка $row (Grinder): " . $e->getMessage();
                    $errors[] = $errorMsg;
                    error_log($errorMsg);
                }
            }
            
            error_log("Импортировано вопросов мясорубки: " . $imported['grinder']);
        } else {
            error_log("Лист 'Grinder Questions' не найден");
        }
        
        // ===================== ИМПОРТ ВОПРОСОВ КВИЗА =====================
        if (in_array('Quiz Questions', $sheetNames)) {
            error_log("Обработка листа 'Quiz Questions'");
            $sheet = $spreadsheet->getSheetByName('Quiz Questions');
            $highestRow = $sheet->getHighestDataRow();
            
            error_log("Строк в листе квиза: $highestRow");
            
            // Получаем все изображения из листа
            $images = [];
            $drawingCollection = $sheet->getDrawingCollection();
            if ($drawingCollection) {
                foreach ($drawingCollection as $drawing) {
                    $coordinates = $drawing->getCoordinates();
                    $images[$coordinates] = $drawing;
                }
            }
            
            $quizMap = []; // Для связи ID из файла с реальными ID
            
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $questionText = trim($sheet->getCell('B' . $row)->getValue());
                    
                    // Пропускаем пустые строки
                    if (empty($questionText)) {
                        continue;
                    }
                    
                    $questionType = trim($sheet->getCell('C' . $row)->getValue()) ?: 'single';
                    $questionTime = (int)$sheet->getCell('D' . $row)->getValue() ?: 30;
                    $answerTime = (int)$sheet->getCell('E' . $row)->getValue() ?: 10;
                    $displayOrder = (int)$sheet->getCell('F' . $row)->getValue() ?: 0;
                    
                    error_log("Обработка вопроса квиза $row: '$questionText'");
                    
                    // Обработка изображения
                    $imagePath = null;
                    $imageCell = 'G' . $row;
                    
                    if (isset($images[$imageCell])) {
                        $imagePath = save_drawing_to_file($images[$imageCell], 'quiz');
                    }
                    
                    // Сохраняем в БД
                    $stmt = $pdo->prepare("
                        INSERT INTO quiz_questions (question_text, question_type, question_time, 
                            answer_time, display_order, image_path)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $questionText, $questionType, $questionTime, 
                        $answerTime, $displayOrder, $imagePath
                    ]);
                    
                    $newId = $pdo->lastInsertId();
                    $oldId = (int)$sheet->getCell('A' . $row)->getValue();
                    
                    if ($oldId) {
                        $quizMap[$oldId] = $newId;
                    }
                    
                    $imported['quiz']++;
                    error_log("Вопрос квиза сохранен: ID $newId");
                    
                } catch (Exception $e) {
                    $errorMsg = "Строка $row (Quiz): " . $e->getMessage();
                    $errors[] = $errorMsg;
                    error_log($errorMsg);
                }
            }
            
            error_log("Импортировано вопросов квиза: " . $imported['quiz']);
            error_log("Карта ID: " . json_encode($quizMap));
        } else {
            error_log("Лист 'Quiz Questions' не найден");
        }
        
        // ===================== ИМПОРТ ВАРИАНТОВ ОТВЕТОВ КВИЗА =====================
        if (in_array('Quiz Options', $sheetNames) && !empty($quizMap)) {
            error_log("Обработка листа 'Quiz Options'");
            $sheet = $spreadsheet->getSheetByName('Quiz Options');
            $highestRow = $sheet->getHighestDataRow();
            
            error_log("Строк с вариантами ответов: $highestRow");
            
            for ($row = 2; $row <= $highestRow; $row++) {
                try {
                    $oldQuestionId = (int)$sheet->getCell('A' . $row)->getValue();
                    $answerText = trim($sheet->getCell('B' . $row)->getValue());
                    
                    if (empty($answerText)) {
                        continue;
                    }
                    
                    // Проверяем, есть ли такой ID вопроса в карте
                    if (!isset($quizMap[$oldQuestionId])) {
                        $errors[] = "Строка $row (Options): вопрос с ID $oldQuestionId не найден при импорте";
                        continue;
                    }
                    
                    $isCorrectCell = $sheet->getCell('C' . $row)->getValue();
                    $isCorrect = (strtolower($isCorrectCell) === 'yes' || $isCorrectCell === 1 || $isCorrectCell === '1') ? 1 : 0;
                    $points = (int)$sheet->getCell('D' . $row)->getValue() ?: 0;
                    $displayOrder = (int)$sheet->getCell('E' . $row)->getValue() ?: 0;
                    
                    $realQuestionId = $quizMap[$oldQuestionId];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO quiz_answers (quiz_question_id, answer_text, is_correct, points, display_order)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $realQuestionId, $answerText, $isCorrect, $points, $displayOrder
                    ]);
                    
                    $imported['options']++;
                    
                } catch (Exception $e) {
                    $errors[] = "Строка $row (Options): " . $e->getMessage();
                }
            }
            
            error_log("Импортировано вариантов ответов: " . $imported['options']);
        } else {
            if (empty($quizMap)) {
                error_log("Пропускаем лист 'Quiz Options': нет импортированных вопросов квиза");
            }
        }
        
        $pdo->commit();
        error_log("Транзакция завершена успешно");
        
        $message = sprintf(
            "Импорт завершен: %d вопросов мясорубки, %d вопросов квиза, %d вариантов ответов",
            $imported['grinder'], $imported['quiz'], $imported['options']
        );
        
        $result = [
            'success' => true,
            'message' => $message,
            'imported' => $imported
        ];
        
        if (!empty($errors)) {
            $result['errors'] = $errors;
        }
        
        error_log("Результат импорта: " . json_encode($result));
        json_response($result);
        
    } catch (Exception $e) {
        error_log("КРИТИЧЕСКАЯ ОШИБКА ИМПОРТА: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
        
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("Транзакция откачена");
        }
        
        json_response([
            'success' => false, 
            'error' => 'Критическая ошибка импорта: ' . $e->getMessage(),
            'trace' => (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin']) ? $e->getTraceAsString() : null
        ]);
    }
}

function save_drawing_to_file($drawing, $type) {
    try {
        $uploadDir = __DIR__ . '/../../uploads/questions';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Не удалось создать папку для загрузки: ' . $uploadDir);
            }
        }
        
        // Получаем расширение файла
        $extension = 'png'; // по умолчанию
        
        // Пробуем разные способы получить тип изображения
        if (method_exists($drawing, 'getMimeType')) {
            $mimeType = $drawing->getMimeType();
            $extensions = [
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg'
            ];
            
            if (isset($extensions[$mimeType])) {
                $extension = $extensions[$mimeType];
            }
        }
        
        // Генерируем уникальное имя файла
        $filename = $type . '_import_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;
        
        error_log("Сохранение изображения в: $filepath");
        
        // Сохраняем изображение
        if (method_exists($drawing, 'getPath') && file_exists($drawing->getPath())) {
            // Для обычного Drawing копируем файл
            copy($drawing->getPath(), $filepath);
        } elseif (method_exists($drawing, 'getImageString')) {
            // Для MemoryDrawing
            $imageData = $drawing->getImageString();
            file_put_contents($filepath, $imageData);
        } elseif (method_exists($drawing, 'getImageResource')) {
            // Альтернативный способ для MemoryDrawing
            $imageResource = $drawing->getImageResource();
            if ($extension === 'png') {
                imagepng($imageResource, $filepath);
            } elseif ($extension === 'jpg' || $extension === 'jpeg') {
                imagejpeg($imageResource, $filepath, 90);
            } elseif ($extension === 'gif') {
                imagegif($imageResource, $filepath);
            } else {
                imagepng($imageResource, $filepath);
            }
        } else {
            throw new Exception('Не удалось получить данные изображения');
        }
        
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new Exception('Изображение не было сохранено');
        }
        
        $relativePath = '/uploads/questions/' . $filename;
        error_log("Изображение сохранено успешно: $relativePath");
        
        return $relativePath;
        
    } catch (Exception $e) {
        error_log("Ошибка сохранения изображения: " . $e->getMessage());
        return null;
    }
}
?>