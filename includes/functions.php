<?php
// includes/functions.php - Общие функции

function check_admin_auth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Используем глобальную функцию json_response из api.php
        if (function_exists('json_response')) {
            json_response(['error' => 'Требуется авторизация администратора'], 401);
        } else {
            // Fallback если функция не определена
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Требуется авторизация администратора'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
?>