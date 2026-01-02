<?php
// includes/api/admin.php

function admin_login($input) {
    global $pdo;
    
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['is_superadmin'] = (bool)$admin['is_superadmin'];
            
            // Обновляем время последнего входа
            $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$admin['id']]);
            
            json_response(['success' => true, 'message' => 'Авторизация успешна']);
        } else {
            json_response(['success' => false, 'message' => 'Неверное имя пользователя или пароль']);
        }
    } catch (PDOException $e) {
        json_response(['success' => false, 'message' => 'Ошибка авторизации: ' . $e->getMessage()]);
    }
}

function admin_logout() {
    // Очищаем все данные сессии
    $_SESSION = array();
    
    // Если нужно уничтожить cookie сессии
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Уничтожаем сессию
    session_destroy();
    
    json_response(['success' => true, 'message' => 'Выход выполнен успешно']);
}

function admin_status() {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        json_response(['logged_in' => true, 'username' => $_SESSION['admin_username']]);
    } else {
        json_response(['logged_in' => false]);
    }
}

function change_password($data) {
    global $pdo;
    
    check_admin_auth();
    
    $oldPassword = $data['oldPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword)) {
        json_response(['success' => false, 'message' => 'Заполните все поля']);
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (!$admin || !password_verify($oldPassword, $admin['password'])) {
            json_response(['success' => false, 'message' => 'Текущий пароль неверен']);
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
        
        json_response(['success' => true, 'message' => 'Пароль успешно изменен']);
    } catch (PDOException $e) {
        error_log("Change password error: " . $e->getMessage());
        json_response(['success' => false, 'message' => 'Ошибка сервера']);
    }
}

// Функции для управления администраторами
function get_admins() {
    global $pdo;
    
    check_admin_auth();
    
    try {
        $stmt = $pdo->query("SELECT id, username, created_at FROM admins ORDER BY id");
        $admins = $stmt->fetchAll();
        json_response($admins);
    } catch (PDOException $e) {
        json_response(['error' => 'Ошибка получения списка администраторов: ' . $e->getMessage()]);
    }
}

function add_admin($data) {
    global $pdo;
    
    check_admin_auth();
    
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        json_response(['success' => false, 'message' => 'Заполните все поля']);
    }
    
    try {
        // Проверяем существование пользователя
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            json_response(['success' => false, 'message' => 'Пользователь с таким именем уже существует']);
        }
        
        // Добавляем нового администратора
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashedPassword]);
        
        json_response(['success' => true, 'message' => 'Администратор успешно добавлен']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'message' => 'Ошибка добавления администратора: ' . $e->getMessage()]);
    }
}

function update_admin($data) {
    global $pdo;
    
    check_admin_auth();
    
    $id = $data['id'] ?? 0;
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($id) || empty($username)) {
        json_response(['success' => false, 'message' => 'Неверные данные']);
    }
    
    try {
        // Проверяем существование пользователя (кроме текущего)
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $stmt->execute([$username, $id]);
        if ($stmt->fetch()) {
            json_response(['success' => false, 'message' => 'Пользователь с таким именем уже существует']);
        }
        
        // Обновляем данные
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
            $stmt->execute([$username, $hashedPassword, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
            $stmt->execute([$username, $id]);
        }
        
        json_response(['success' => true, 'message' => 'Администратор успешно обновлен']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'message' => 'Ошибка обновления администратора: ' . $e->getMessage()]);
    }
}

function delete_admin($data) {
    global $pdo;
    
    check_admin_auth();
    
    $id = $data['id'] ?? 0;
    
    if (empty($id)) {
        json_response(['success' => false, 'message' => 'ID администратора не указан']);
    }
    
    // Не позволяем удалить самого себя
    if ($id == $_SESSION['admin_id']) {
        json_response(['success' => false, 'message' => 'Нельзя удалить самого себя']);
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        
        json_response(['success' => true, 'message' => 'Администратор удален']);
    } catch (PDOException $e) {
        json_response(['success' => false, 'message' => 'Ошибка удаления администратора: ' . $e->getMessage()]);
    }
}
?>