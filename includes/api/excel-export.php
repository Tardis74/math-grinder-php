<?php
// includes/api/excel-export.php

require_once __DIR__ . '/../functions.php';

// Проверяем наличие библиотеки PhpSpreadsheet
function check_phpspreadsheet() {
    if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        json_response([
            'success' => false, 
            'error' => 'Библиотека PhpSpreadsheet не установлена. Установите: composer require phpoffice/phpspreadsheet'
        ]);
        exit;
    }
}

/**
 * Экспорт вопросов мясорубки в Excel
 */
function export_grinder_questions_excel() {
    global $pdo;
    check_admin_auth();
    check_phpspreadsheet();
    
    try {
        // Получаем вопросы мясорубки
        $stmt = $pdo->query("
            SELECT id, text, answer, points, image_path,
                   has_bonus_points, bonus_first_points, bonus_second_points, bonus_third_points,
                   created_at
            FROM questions 
            WHERE event_type = 'grinder'
            ORDER BY id
        ");
        $questions = $stmt->fetchAll();
        
        // Создаем Excel документ
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Вопросы Мясорубки');
        
        // Заголовки
        $headers = [
            'ID', 'Текст вопроса', 'Ответ', 'Баллы', 'Изображение',
            'Бонусные баллы', '1-й бонус', '2-й бонус', '3-й бонус', 'Дата создания'
        ];
        
        // Записываем заголовки
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getStyleByColumnAndRow($col + 1, 1)->getFont()->setBold(true);
        }
        
        // Записываем данные
        $row = 2;
        foreach ($questions as $question) {
            $sheet->setCellValueByColumnAndRow(1, $row, $question['id']);
            $sheet->setCellValueByColumnAndRow(2, $row, $question['text']);
            $sheet->setCellValueByColumnAndRow(3, $row, $question['answer']);
            $sheet->setCellValueByColumnAndRow(4, $row, $question['points']);
            $sheet->setCellValueByColumnAndRow(5, $row, $question['image_path']);
            $sheet->setCellValueByColumnAndRow(6, $row, $question['has_bonus_points'] ? 'Да' : 'Нет');
            $sheet->setCellValueByColumnAndRow(7, $row, $question['bonus_first_points']);
            $sheet->setCellValueByColumnAndRow(8, $row, $question['bonus_second_points']);
            $sheet->setCellValueByColumnAndRow(9, $row, $question['bonus_third_points']);
            $sheet->setCellValueByColumnAndRow(10, $row, $question['created_at']);
            $row++;
        }
        
        // Настраиваем ширину колонок
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(60);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(20);
        
        // Настраиваем стили для заголовков
        $headerStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'E0E0E0']
            ]
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);
        
        // Добавляем автофильтр
        $sheet->setAutoFilter('A1:J' . ($row - 1));
        
        // Сохраняем файл
        $filename = 'questions_grinder_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Отправляем файл
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        error_log("Excel export error: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка экспорта в Excel: ' . $e->getMessage()]);
    }
}

/**
 * Экспорт вопросов квиза в Excel
 */
function export_quiz_questions_excel() {
    global $pdo;
    check_admin_auth();
    check_phpspreadsheet();
    
    try {
        // Получаем вопросы квиза с ответами
        $stmt = $pdo->query("
            SELECT qq.id, qq.question_text, qq.question_type, 
                   qq.question_time, qq.answer_time, qq.display_order,
                   qq.image_path, qq.created_at,
                   GROUP_CONCAT(CONCAT_WS('|', qa.answer_text, qa.is_correct, qa.points, qa.display_order) 
                       ORDER BY qa.display_order SEPARATOR ';;') as answers_data
            FROM quiz_questions qq
            LEFT JOIN quiz_answers qa ON qq.id = qa.quiz_question_id
            GROUP BY qq.id
            ORDER BY qq.display_order
        ");
        $questions = $stmt->fetchAll();
        
        // Создаем Excel документ
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Вопросы Квиза');
        
        // Заголовки
        $headers = [
            'ID', 'Текст вопроса', 'Тип вопроса', 'Время вопроса (сек)', 
            'Время ответов (сек)', 'Порядок', 'Изображение', 'Дата создания',
            'Ответ 1', 'Правильность 1', 'Баллы 1',
            'Ответ 2', 'Правильность 2', 'Баллы 2',
            'Ответ 3', 'Правильность 3', 'Баллы 3',
            'Ответ 4', 'Правильность 4', 'Баллы 4'
        ];
        
        // Записываем заголовки
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getStyleByColumnAndRow($col + 1, 1)->getFont()->setBold(true);
        }
        
        // Записываем данные
        $row = 2;
        foreach ($questions as $question) {
            $sheet->setCellValueByColumnAndRow(1, $row, $question['id']);
            $sheet->setCellValueByColumnAndRow(2, $row, $question['question_text']);
            $sheet->setCellValueByColumnAndRow(3, $row, $question['question_type'] === 'single' ? 'Одиночный' : 'Множественный');
            $sheet->setCellValueByColumnAndRow(4, $row, $question['question_time']);
            $sheet->setCellValueByColumnAndRow(5, $row, $question['answer_time']);
            $sheet->setCellValueByColumnAndRow(6, $row, $question['display_order']);
            $sheet->setCellValueByColumnAndRow(7, $row, $question['image_path']);
            $sheet->setCellValueByColumnAndRow(8, $row, $question['created_at']);
            
            // Парсим ответы
            $answers = [];
            if ($question['answers_data']) {
                $answers_list = explode(';;', $question['answers_data']);
                foreach ($answers_list as $answer_str) {
                    list($text, $is_correct, $points, $order) = explode('|', $answer_str);
                    $answers[] = [
                        'text' => $text,
                        'is_correct' => $is_correct,
                        'points' => $points,
                        'order' => $order
                    ];
                }
            }
            
            // Записываем ответы (максимум 4 ответа на вопрос)
            for ($i = 0; $i < 4; $i++) {
                $col_start = 9 + ($i * 3); // 9, 12, 15, 18
                
                if (isset($answers[$i])) {
                    $sheet->setCellValueByColumnAndRow($col_start, $row, $answers[$i]['text']);
                    $sheet->setCellValueByColumnAndRow($col_start + 1, $row, $answers[$i]['is_correct'] ? 'Да' : 'Нет');
                    $sheet->setCellValueByColumnAndRow($col_start + 2, $row, $answers[$i]['points']);
                } else {
                    $sheet->setCellValueByColumnAndRow($col_start, $row, '');
                    $sheet->setCellValueByColumnAndRow($col_start + 1, $row, '');
                    $sheet->setCellValueByColumnAndRow($col_start + 2, $row, '');
                }
            }
            
            $row++;
        }
        
        // Настраиваем ширину колонок
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(60);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(30);
        $sheet->getColumnDimension('H')->setWidth(20);
        
        // Ширина для колонок с ответами
        for ($i = 9; $i <= 20; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setWidth(20);
        }
        
        // Настраиваем стили для заголовков
        $headerStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'E0E0E0']
            ]
        ];
        $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);
        
        // Добавляем автофильтр
        $sheet->setAutoFilter('A1:T' . ($row - 1));
        
        // Сохраняем файл
        $filename = 'questions_quiz_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Отправляем файл
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        error_log("Quiz Excel export error: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка экспорта вопросов квиза в Excel: ' . $e->getMessage()]);
    }
}

/**
 * Экспорт результатов мероприятия в Excel
 */
function export_results_excel($event_type = 'grinder') {
    global $pdo;
    check_admin_auth();
    check_phpspreadsheet();
    
    try {
        if ($event_type === 'grinder') {
            // Результаты мясорубки
            $stmt = $pdo->query("
                SELECT p.team, p.score as total_score,
                       COUNT(a.id) as answers_count,
                       SUM(CASE WHEN a.is_correct THEN 1 ELSE 0 END) as correct_answers,
                       SUM(a.points) as total_points,
                       p.created_at as joined_at
                FROM participants p
                LEFT JOIN answers a ON p.id = a.participant_id AND a.event_type = 'grinder'
                WHERE p.event_type = 'grinder'
                GROUP BY p.id
                ORDER BY p.score DESC, p.team
            ");
            $results = $stmt->fetchAll();
            $filename = 'results_grinder_' . date('Y-m-d_His') . '.xlsx';
            $sheet_title = 'Результаты Мясорубки';
            
        } else {
            // Результаты квиза
            $stmt = $pdo->query("
                SELECT p.team, p.score as total_score,
                       COUNT(DISTINCT qpa.quiz_question_id) as questions_answered,
                       SUM(CASE WHEN qpa.points_earned > 0 THEN 1 ELSE 0 END) as correct_answers,
                       SUM(qpa.points_earned) as quiz_score,
                       p.created_at as joined_at
                FROM participants p
                LEFT JOIN quiz_participant_answers qpa ON p.id = qpa.participant_id
                WHERE p.event_type = 'quiz'
                GROUP BY p.id
                ORDER BY quiz_score DESC, p.team
            ");
            $results = $stmt->fetchAll();
            $filename = 'results_quiz_' . date('Y-m-d_His') . '.xlsx';
            $sheet_title = 'Результаты Квиза';
        }
        
        // Создаем Excel документ
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheet_title);
        
        // Заголовки
        $headers = ['Место', 'Команда', 'Общий балл', 'Правильных ответов', 
                   'Всего ответов', ($event_type === 'grinder' ? 'Баллы за ответы' : 'Баллы квиза'), 
                   'Дата присоединения'];
        
        // Записываем заголовки
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
            $sheet->getStyleByColumnAndRow($col + 1, 1)->getFont()->setBold(true);
        }
        
        // Записываем данные
        $row = 2;
        foreach ($results as $index => $result) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $result['team']);
            $sheet->setCellValue('C' . $row, $result['total_score']);
            $sheet->setCellValue('D' . $row, $result['correct_answers'] ?? 0);
            $sheet->setCellValue('E' . $row, $result['answers_count'] ?? $result['questions_answered'] ?? 0);
            $sheet->setCellValue('F' . $row, $result['total_points'] ?? $result['quiz_score'] ?? 0);
            $sheet->setCellValue('G' . $row, $result['joined_at']);
            $row++;
        }
        
        // Настраиваем ширину колонок
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(25);
        
        // Настраиваем стили для заголовков
        $headerStyle = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'E0E0E0']
            ]
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        
        // Форматирование чисел
        $sheet->getStyle('C2:F' . ($row - 1))->getNumberFormat()->setFormatCode('0');
        
        // Формат даты
        $sheet->getStyle('G2:G' . ($row - 1))->getNumberFormat()
            ->setFormatCode('yyyy-mm-dd hh:mm:ss');
        
        // Добавляем автофильтр
        $sheet->setAutoFilter('A1:G' . ($row - 1));
        
        // Сохраняем файл
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Отправляем файл
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        error_log("Results Excel export error: " . $e->getMessage());
        json_response(['success' => false, 'error' => 'Ошибка экспорта результатов в Excel: ' . $e->getMessage()]);
    }
}
?>