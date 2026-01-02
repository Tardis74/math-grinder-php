<?php
// admin-login.php - Страница входа для администраторов
require_once 'config.php';

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    session_start(); // Начинаем новую сессию для сообщения
    $_SESSION['logout_message'] = 'Вы успешно вышли из системы';
    header('Location: admin.php');
    exit;
}

// Если уже авторизован, перенаправляем на главную админ-панель
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход администратора - Математическая мясорубка</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .error-message {
            color: red;
            margin-top: 10px;
        }
        
        .success-message {
            color: green;
            margin-top: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-input {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        .form-input:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .submit-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Вход администратора</h2>
        <form id="admin-login-form">
            <div class="form-group">
                <label for="username" class="form-label">Имя пользователя:</label>
                <input type="text" id="username" placeholder="Введите имя пользователя" required class="form-input">
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Пароль:</label>
                <input type="password" id="password" placeholder="Введите пароль" required class="form-input">
            </div>
            <button type="submit" class="submit-btn">Войти</button>
        </form>
        <div id="login-message"></div>
    </div>

    <script>
        document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
    
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const messageDiv = document.getElementById('login-message');
    
            try {
                const response = await fetch('api.php?action=admin-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
        
                // Сначала получаем текст ответа
                const responseText = await response.text();
                console.log('Raw response:', responseText);
        
                try {
                    // Пытаемся распарсить JSON
                    const data = JSON.parse(responseText);
            
                    if (data.success) {
                        window.location.href = 'admin.php';
                    } else {
                        messageDiv.textContent = data.message || 'Ошибка авторизации';
                        messageDiv.className = 'error-message';
                    }
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    messageDiv.textContent = 'Сервер вернул неверный формат данных. Проверьте консоль для деталей.';
                    messageDiv.className = 'error-message';
                }
            } catch (error) {
                console.error('Network Error:', error);
                messageDiv.textContent = 'Ошибка соединения с сервером';
             messageDiv.className = 'error-message';
            }
        });
    </script>
</body>
</html>