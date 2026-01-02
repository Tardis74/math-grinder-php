<?php
require_once __DIR__ . '/../functions.php';

function get_cheating_attempts() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        error_log("Fetching cheating attempts...");
        
        // Упрощенный запрос - получаем все нарушения и имена команд
        $stmt = $pdo->query("
            SELECT 
                COALESCE(p.team, 'Неизвестная команда') as team,
                c.type,
                c.count,
                c.last_attempt
            FROM cheating_attempts c
            LEFT JOIN participants p ON c.participant_id = p.id
            ORDER BY c.last_attempt DESC
        ");
        
        $attempts = $stmt->fetchAll();
        error_log("Found " . count($attempts) . " cheating records");
        
        // Группируем по командам в PHP
        $grouped = [];
        foreach ($attempts as $attempt) {
            $team = $attempt['team'];
            
            if (!isset($grouped[$team])) {
                $grouped[$team] = [
                    'team' => $team,
                    'tab_switch_count' => 0,
                    'copy_attempt_count' => 0,
                    'paste_attempt_count' => 0,
                    'last_tab_switch' => null
                ];
            }
            
            switch ($attempt['type']) {
                case 'tab_switch':
                    $grouped[$team]['tab_switch_count'] += $attempt['count'];
                    // Обновляем последнее переключение вкладок
                    if (!$grouped[$team]['last_tab_switch'] || $attempt['last_attempt'] > $grouped[$team]['last_tab_switch']) {
                        $grouped[$team]['last_tab_switch'] = $attempt['last_attempt'];
                    }
                    break;
                case 'copy':
                    $grouped[$team]['copy_attempt_count'] += $attempt['count'];
                    break;
                case 'paste':
                    $grouped[$team]['paste_attempt_count'] += $attempt['count'];
                    break;
            }
        }
        
        $result = array_values($grouped);
        error_log("Grouped into " . count($result) . " teams");
        
        json_response($result);
        
    } catch (PDOException $e) {
        error_log("Get cheating attempts error: " . $e->getMessage());
        json_response(['error' => 'Ошибка получения данных: ' . $e->getMessage()], 500);
    }
}

function report_cheating($data) {
    global $pdo;
    
    $participant_id = $data['participant_id'] ?? null;
    $type = $data['type'] ?? '';
    
    error_log("=== REPORT CHEATING CALLED ===");
    error_log("Participant ID: " . $participant_id);
    error_log("Type: " . $type);
    
    if (empty($type)) {
        error_log("Error: Type is empty");
        json_response(['error' => 'Тип нарушения не указан']);
    }
    
    try {
        if ($participant_id) {
            // Проверим существование участника
            $stmt = $pdo->prepare("SELECT id, team FROM participants WHERE id = ?");
            $stmt->execute([$participant_id]);
            $participant = $stmt->fetch();
            
            error_log("Participant found: " . json_encode($participant));
            
            if (!$participant) {
                error_log("Participant not found: $participant_id");
                json_response(['error' => 'Участник не найден']);
            }
            
            // Вставляем запись о нарушении
            $stmt = $pdo->prepare("
                INSERT INTO cheating_attempts (participant_id, type, count, last_attempt) 
                VALUES (?, ?, 1, NOW())
            ");
            $result = $stmt->execute([$participant_id, $type]);
            
            error_log("Insert result: " . ($result ? 'success' : 'failed'));
            
            if ($result) {
                $inserted_id = $pdo->lastInsertId();
                error_log("Cheating attempt inserted successfully. ID: $inserted_id");
                json_response(['success' => true, 'message' => 'Нарушение зафиксировано', 'id' => $inserted_id]);
            } else {
                error_log("Insert failed - no rows affected");
                json_response(['error' => 'Ошибка вставки в БД']);
            }
        } else {
            error_log("No participant_id provided, but still logging attempt type: $type");
            json_response(['success' => true, 'message' => 'Нарушение зафиксировано (без участника)']);
        }
        
    } catch (PDOException $e) {
        error_log("Report cheating PDO error: " . $e->getMessage());
        error_log("Error code: " . $e->getCode());
        json_response(['error' => 'Ошибка БД: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Report cheating general error: " . $e->getMessage());
        json_response(['error' => 'Общая ошибка: ' . $e->getMessage()]);
    }
}
?>