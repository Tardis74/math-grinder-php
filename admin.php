<?php
// admin.php - Умная переадресация для администратора
require_once 'config.php';

// Если администратор уже авторизован, перенаправляем на нужную ветку
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Получаем режим мероприятия
    try {
        $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
        $stmt->execute();
        $state = $stmt->fetch();
        $event_mode = $state ? $state['event_mode'] : 'grinder';
        
        // Сохраняем статус суперадминистратора в сессии если еще не сохранен
        if (!isset($_SESSION['is_superadmin'])) {
            $stmt = $pdo->prepare("SELECT is_superadmin FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            $_SESSION['is_superadmin'] = $admin && $admin['is_superadmin'];
        }
        
        // Перенаправляем в соответствующую ветку
        if ($event_mode === 'quiz') {
            header('Location: admin-quiz/main.php');
        } else {
            header('Location: admin-grinder/main.php');
        }
        exit;
        
    } catch (PDOException $e) {
        // При ошибке используем ветку мясорубки по умолчанию
        header('Location: admin-grinder/main.php');
        exit;
    }
} else {
    // Если не авторизован, перенаправляем на страницу входа
    header('Location: admin-login.php');
    exit;
}
?>