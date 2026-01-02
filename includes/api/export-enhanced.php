<?php
// includes/api/export-enhanced.php
function export_questions_enhanced() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT text, answer, points, image_path, 
                   has_bonus_points, bonus_first_points, bonus_second_points, bonus_third_points
            FROM questions 
            ORDER BY id
        ");
        $questions = $stmt->fetchAll();
        
        $has_images = false;
        $export_data = [];
        
        foreach ($questions as $question) {
            $export_data[] = [
                'text' => $question['text'],
                'answer' => $question['answer'],
                'points' => $question['points'],
                'has_bonus' => (bool)$question['has_bonus_points'],
                'bonus_first' => $question['bonus_first_points'],
                'bonus_second' => $question['bonus_second_points'], 
                'bonus_third' => $question['bonus_third_points'],
                'image' => $question['image_path']
            ];
            
            if ($question['image_path']) {
                $has_images = true;
            }
        }
        
        if ($has_images) {
            export_questions_with_images($export_data);
        } else {
            export_questions_simple($export_data);
        }
        
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => 'Ошибка экспорта: ' . $e->getMessage()]);
    }
}

function export_questions_simple($questions) {
    $content = "# Экспорт вопросов\n";
    $content .= "# Формат: Вопрос | Ответ\n";
    $content .= "# Создан: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($questions as $q) {
        $content .= $q['text'] . " | " . $q['answer'] . "\n";
    }
    
    $filename = "questions_export_" . date('Y-m-d_His') . ".txt";
    
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $content;
    exit;
}

function export_questions_with_images($questions) {
    // Создаем временную папку
    $temp_dir = sys_get_temp_dir() . '/questions_export_' . uniqid();
    mkdir($temp_dir, 0755, true);
    
    // Создаем файл с вопросами
    $questions_content = "# Экспорт вопросов с изображениями\n";
    $questions_content .= "# Формат: Вопрос | Ответ | Путь к изображению\n";
    $questions_content .= "# Создан: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($questions as $index => $q) {
        $image_filename = '';
        if ($q['image']) {
            $image_filename = 'image_' . ($index + 1) . '_' . basename($q['image']);
            $questions_content .= $q['text'] . " | " . $q['answer'] . " | " . $image_filename . "\n";
            
            // Копируем изображение
            $source_path = __DIR__ . '/../..' . $q['image'];
            if (file_exists($source_path)) {
                copy($source_path, $temp_dir . '/' . $image_filename);
            }
        } else {
            $questions_content .= $q['text'] . " | " . $q['answer'] . " | \n";
        }
    }
    
    file_put_contents($temp_dir . '/questions.txt', $questions_content);
    
    // Создаем ZIP архив
    $zip_filename = "questions_with_images_" . date('Y-m-d_His') . ".zip";
    $zip_path = sys_get_temp_dir() . '/' . $zip_filename;
    
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
        // Добавляем файл с вопросами
        $zip->addFile($temp_dir . '/questions.txt', 'questions.txt');
        
        // Добавляем изображения
        foreach (glob($temp_dir . '/image_*') as $image_file) {
            $zip->addFile($image_file, basename($image_file));
        }
        
        $zip->close();
    }
    
    // Отправляем архив
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);
    
    // Очищаем временные файлы
    array_map('unlink', glob($temp_dir . '/*'));
    rmdir($temp_dir);
    unlink($zip_path);
    
    exit;
}
?>