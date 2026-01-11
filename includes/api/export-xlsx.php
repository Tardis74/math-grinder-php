<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

function export_questions_xlsx() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        // Создаем новый Excel документ
        $spreadsheet = new Spreadsheet();
        
        // ===================== ЛИСТ 1: Вопросы мясорубки =====================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Grinder Questions');
        
        // Заголовки
        $sheet1->setCellValue('A1', 'ID');
        $sheet1->setCellValue('B1', 'Question Text');
        $sheet1->setCellValue('C1', 'Correct Answer');
        $sheet1->setCellValue('D1', 'Points');
        $sheet1->setCellValue('E1', 'Has Bonus');
        $sheet1->setCellValue('F1', 'Bonus First');
        $sheet1->setCellValue('G1', 'Bonus Second');
        $sheet1->setCellValue('H1', 'Bonus Third');
        $sheet1->setCellValue('I1', 'Image');
        
        // Стили для заголовков
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']]
        ];
        $sheet1->getStyle('A1:I1')->applyFromArray($headerStyle);
        
        // Получаем вопросы мясорубки
        $stmt = $pdo->query("SELECT * FROM questions ORDER BY id");
        $grinderQuestions = $stmt->fetchAll();
        
        $row = 2;
        $imageCount = 0;
        
        foreach ($grinderQuestions as $question) {
            $sheet1->setCellValue('A' . $row, $question['id']);
            $sheet1->setCellValue('B' . $row, $question['text']);
            $sheet1->setCellValue('C' . $row, $question['answer']);
            $sheet1->setCellValue('D' . $row, $question['points']);
            $sheet1->setCellValue('E' . $row, $question['has_bonus_points'] ? 'Yes' : 'No');
            $sheet1->setCellValue('F' . $row, $question['bonus_first_points']);
            $sheet1->setCellValue('G' . $row, $question['bonus_second_points']);
            $sheet1->setCellValue('H' . $row, $question['bonus_third_points']);
            
            // Добавляем изображение если есть
            if ($question['image_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $question['image_path'])) {
                $imagePath = $_SERVER['DOCUMENT_ROOT'] . $question['image_path'];
                
                // Создаем объект для изображения
                $drawing = new Drawing();
                $drawing->setPath($imagePath);
                $drawing->setCoordinates('I' . $row);
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(5);
                $drawing->setWidth(100);
                $drawing->setHeight(100);
                $drawing->setWorksheet($sheet1);
                
                // Устанавливаем высоту строки для изображения
                $sheet1->getRowDimension($row)->setRowHeight(80);
                
                $imageCount++;
            } else {
                $sheet1->setCellValue('I' . $row, 'No image');
            }
            
            // Чередующаяся заливка строк
            if ($row % 2 == 0) {
                $sheet1->getStyle('A' . $row . ':I' . $row)
                    ->getFill()
                    ->setFillType('solid')
                    ->getStartColor()
                    ->setARGB('FFE8F4FF');
            }
            
            $row++;
        }
        
        // Автоматическая ширина колонок
        $sheet1->getColumnDimension('A')->setWidth(10);
        $sheet1->getColumnDimension('B')->setWidth(40);
        $sheet1->getColumnDimension('C')->setWidth(25);
        $sheet1->getColumnDimension('D')->setWidth(10);
        $sheet1->getColumnDimension('E')->setWidth(10);
        $sheet1->getColumnDimension('F')->setWidth(12);
        $sheet1->getColumnDimension('G')->setWidth(12);
        $sheet1->getColumnDimension('H')->setWidth(12);
        $sheet1->getColumnDimension('I')->setWidth(20);
        
        // ===================== ЛИСТ 2: Вопросы квиза =====================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Quiz Questions');
        
        // Заголовки
        $sheet2->setCellValue('A1', 'ID');
        $sheet2->setCellValue('B1', 'Question Text');
        $sheet2->setCellValue('C1', 'Question Type');
        $sheet2->setCellValue('D1', 'Question Time');
        $sheet2->setCellValue('E1', 'Answer Time');
        $sheet2->setCellValue('F1', 'Display Order');
        $sheet2->setCellValue('G1', 'Image');
        
        $sheet2->getStyle('A1:G1')->applyFromArray($headerStyle);
        
        // Получаем вопросы квиза
        $stmt = $pdo->query("SELECT * FROM quiz_questions ORDER BY display_order");
        $quizQuestions = $stmt->fetchAll();
        
        $row = 2;
        foreach ($quizQuestions as $question) {
            $sheet2->setCellValue('A' . $row, $question['id']);
            $sheet2->setCellValue('B' . $row, $question['question_text']);
            $sheet2->setCellValue('C' . $row, $question['question_type']);
            $sheet2->setCellValue('D' . $row, $question['question_time']);
            $sheet2->setCellValue('E' . $row, $question['answer_time']);
            $sheet2->setCellValue('F' . $row, $question['display_order']);
            
            // Добавляем изображение если есть
            if ($question['image_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $question['image_path'])) {
                $imagePath = $_SERVER['DOCUMENT_ROOT'] . $question['image_path'];
                
                $drawing = new Drawing();
                $drawing->setPath($imagePath);
                $drawing->setCoordinates('G' . $row);
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(5);
                $drawing->setWidth(100);
                $drawing->setHeight(100);
                $drawing->setWorksheet($sheet2);
                
                $sheet2->getRowDimension($row)->setRowHeight(80);
                
                $imageCount++;
            } else {
                $sheet2->setCellValue('G' . $row, 'No image');
            }
            
            if ($row % 2 == 0) {
                $sheet2->getStyle('A' . $row . ':G' . $row)
                    ->getFill()
                    ->setFillType('solid')
                    ->getStartColor()
                    ->setARGB('FFE8F4FF');
            }
            
            $row++;
        }
        
        // Ширина колонок
        $sheet2->getColumnDimension('A')->setWidth(10);
        $sheet2->getColumnDimension('B')->setWidth(50);
        $sheet2->getColumnDimension('C')->setWidth(15);
        $sheet2->getColumnDimension('D')->setWidth(15);
        $sheet2->getColumnDimension('E')->setWidth(15);
        $sheet2->getColumnDimension('F')->setWidth(15);
        $sheet2->getColumnDimension('G')->setWidth(20);
        
        // ===================== ЛИСТ 3: Варианты ответов квиза =====================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Quiz Options');
        
        // Заголовки
        $sheet3->setCellValue('A1', 'Question ID');
        $sheet3->setCellValue('B1', 'Answer Text');
        $sheet3->setCellValue('C1', 'Is Correct');
        $sheet3->setCellValue('D1', 'Points');
        $sheet3->setCellValue('E1', 'Display Order');
        
        $sheet3->getStyle('A1:E1')->applyFromArray($headerStyle);
        
        // Получаем варианты ответов
        $stmt = $pdo->query("
            SELECT qa.*, qq.id as question_id 
            FROM quiz_answers qa
            JOIN quiz_questions qq ON qa.quiz_question_id = qq.id
            ORDER BY qq.display_order, qa.display_order
        ");
        $quizOptions = $stmt->fetchAll();
        
        $row = 2;
        foreach ($quizOptions as $option) {
            $sheet3->setCellValue('A' . $row, $option['quiz_question_id']);
            $sheet3->setCellValue('B' . $row, $option['answer_text']);
            $sheet3->setCellValue('C' . $row, $option['is_correct'] ? 'Yes' : 'No');
            $sheet3->setCellValue('D' . $row, $option['points']);
            $sheet3->setCellValue('E' . $row, $option['display_order']);
            
            if ($row % 2 == 0) {
                $sheet3->getStyle('A' . $row . ':E' . $row)
                    ->getFill()
                    ->setFillType('solid')
                    ->getStartColor()
                    ->setARGB('FFE8F4FF');
            }
            
            $row++;
        }
        
        // Ширина колонок
        $sheet3->getColumnDimension('A')->setWidth(15);
        $sheet3->getColumnDimension('B')->setWidth(40);
        $sheet3->getColumnDimension('C')->setWidth(12);
        $sheet3->getColumnDimension('D')->setWidth(10);
        $sheet3->getColumnDimension('E')->setWidth(15);
        
        // ===================== СОХРАНЕНИЕ ФАЙЛА =====================
        $writer = new Xlsx($spreadsheet);
        $filename = 'questions_export_' . date('Y-m-d_His') . '.xlsx';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $writer->save($filepath);
        
        // Отправляем файл пользователю
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        
        // Удаляем временный файл
        unlink($filepath);
        exit;
        
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => 'Ошибка экспорта: ' . $e->getMessage()]);
    }
}
?>