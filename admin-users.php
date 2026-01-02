<?php
// admin-users.php - Управление администраторами (общий для всех режимов)
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Проверяем права суперадминистратора
if (!is_superadmin()) {
    // Перенаправляем в соответствующую главную страницу в зависимости от режима
    try {
        $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
        $stmt->execute();
        $state = $stmt->fetch();
        $event_mode = $state ? $state['event_mode'] : 'grinder';
        
        if ($event_mode === 'quiz') {
            header('Location: admin-quiz/main.php');
        } else {
            header('Location: admin-grinder/main.php');
        }
        exit;
    } catch (PDOException $e) {
        header('Location: admin-grinder/main.php');
        exit;
    }
}

// Получаем информацию о текущем пользователе
$current_admin_id = $_SESSION['admin_id'];
$is_superadmin = $_SESSION['is_superadmin'] ?? false;

// Определяем базовый путь для возврата
try {
    $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
    $stmt->execute();
    $state = $stmt->fetch();
    $event_mode = $state ? $state['event_mode'] : 'grinder';
    $return_url = $event_mode === 'quiz' ? 'admin-quiz/main.php' : 'admin-grinder/main.php';
} catch (PDOException $e) {
    $return_url = 'admin-grinder/main.php';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Администраторы - Админ-панель</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
        }

        .superadmin-badge {
            display: inline-block;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #8B4513;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }

        .admin-role {
            font-weight: bold;
            color: #2c3e50;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        select.form-input {
            background-color: white;
            cursor: pointer;
        }

        select.form-input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .role-superadmin {
            color: #d35400;
        }

        .role-admin {
            color: #3498db;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            background: #ecf0f1;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 0;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: #34495e;
            color: white;
            border-left: 4px solid #3498db;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .admin-list {
            display: grid;
            gap: 15px;
        }
        
        .admin-item {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-info h4 {
            margin: 0 0 5px 0;
        }
        
        .admin-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        .current-user {
            border-left: 4px solid #3498db;
            background: #e8f4fd;
        }

        .mode-indicator {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }

        .mode-indicator p {
            margin: 5px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Боковая панель -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Админ-панель</h2>
                <small style="color: #bdc3c7;">Управление администраторами</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="<?php echo $return_url; ?>">📊 Главная</a></li>
                <li><a href="admin-users.php" class="active">👥 Администраторы</a></li>
                <li><a href="admin-login.php?logout=1" onclick="return confirm('Вы уверены, что хотите выйти?')">🚪 Выйти</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="main-content">
            <!-- Индикатор режима -->

            <div class="card">
                <h1>Управление администраторами</h1>
                <p>Текущий пользователь: <strong><?php echo $_SESSION['admin_username']; ?></strong> 
                   <?php if ($is_superadmin): ?>
                   <span class="superadmin-badge">СУПЕРАДМИН</span>
                   <?php endif; ?>
                </p>
            </div>
            
            <div class="card">
                <h2>Смена пароля</h2>
                <form id="change-password-form">
                    <div class="form-group">
                        <label for="current-password">Текущий пароль:</label>
                        <input type="password" id="current-password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="new-password">Новый пароль:</label>
                        <input type="password" id="new-password" class="form-input" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Подтвердите новый пароль:</label>
                        <input type="password" id="confirm-password" class="form-input" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-success">Сменить пароль</button>
                </form>
                <div id="password-change-status"></div>
            </div>
            
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Список администраторов</h2>
                    <?php if ($_SESSION['is_superadmin'] ?? false): ?>
                    <button class="btn btn-success" onclick="showAddAdminModal()">➕ Добавить администратора</button>
                    <?php endif; ?>
                </div>
                
                <div id="admins-list">
                    <p>Загрузка списка администраторов...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления администратора -->
    <div id="add-admin-modal" class="modal">
        <div class="modal-content">
            <h3>Добавить администратора</h3>
            <form id="add-admin-form">
                <div class="form-group">
                    <label for="new-admin-username">Имя пользователя:</label>
                    <input type="text" id="new-admin-username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="new-admin-password">Пароль:</label>
                    <input type="password" id="new-admin-password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="new-admin-password-confirm">Подтвердите пароль:</label>
                    <input type="password" id="new-admin-password-confirm" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="new-admin-role">Роль:</label>
                    <select id="new-admin-role" class="form-input">
                        <option value="0">Администратор</option>
                        <option value="1">Суперадминистратор</option>
                    </select>
                    <div style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">
                        Суперадминистраторы имеют полный доступ ко всем функциям системы
                    </div>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="hideAddAdminModal()">Отмена</button>
                    <button type="submit" class="btn btn-success">Добавить</button>
                </div>
            </form>
            <div id="add-admin-status"></div>
        </div>
    </div>

    <!-- Модальное окно редактирования администратора -->
    <div id="edit-admin-modal" class="modal">
        <div class="modal-content">
            <h3>Редактировать администратора</h3>
            <form id="edit-admin-form">
                <input type="hidden" id="edit-admin-id">
                <div class="form-group">
                    <label for="edit-admin-username">Имя пользователя:</label>
                    <input type="text" id="edit-admin-username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit-admin-password">Новый пароль (оставьте пустым, если не меняется):</label>
                    <input type="password" id="edit-admin-password" class="form-input" minlength="6">
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="hideEditAdminModal()">Отмена</button>
                    <button type="submit" class="btn btn-success">Сохранить</button>
                </div>
            </form>
            <div id="edit-admin-status"></div>
        </div>
    </div>

    <script>
        // Определяем базовый URL для API
        const API_BASE = 'api.php';
        const RETURN_URL = '<?php echo $return_url; ?>';

        // API функции
        async function apiRequest(action, data = null) {
            const options = {
                method: data ? 'POST' : 'GET',
                headers: {'Content-Type': 'application/json'}
            };
            if (data) options.body = JSON.stringify(data);
            
            try {
                const response = await fetch(`${API_BASE}?action=${action}`, options);
                const text = await response.text();
                return JSON.parse(text);
            } catch (error) {
                console.error('API Error:', error);
                return {error: 'Ошибка соединения'};
            }
        }

        async function promoteToSuperadmin(id, username) {
            if (confirm(`Назначить администратора "${username}" суперадминистратором?\n\nСуперадминистратор получит полный доступ ко всем функциям системы.`)) {
                const result = await apiRequest('promote-to-superadmin', { id: id });
                
                if (result.success) {
                    alert(`Администратор "${username}" теперь суперадминистратор!`);
                    loadAdmins();
                } else {
                    alert('Ошибка: ' + (result.message || 'Не удалось назначить суперадминистратором'));
                }
            }
        }
        
        // Загрузка списка администраторов
        async function loadAdmins() {
            const result = await apiRequest('get-admins');
            console.log('Администраторы с сервера:', result);
            
            const container = document.getElementById('admins-list');
            
            if (result.error) {
                container.innerHTML = '<p>Ошибка загрузки списка администраторов</p>';
                return;
            }
            
            if (result.length === 0) {
                container.innerHTML = '<p>Нет администраторов</p>';
                return;
            }
            
            let html = '<div class="admin-list">';
            result.forEach(admin => {
                const isCurrent = admin.id === <?php echo $_SESSION['admin_id']; ?>;
                // Правильно определяем статус суперадминистратора
                const isSuperadmin = admin.is_superadmin === 1 || admin.is_superadmin === true || admin.is_superadmin === '1';
                
                console.log(`Админ: ${admin.username}, is_superadmin: ${admin.is_superadmin}, тип: ${typeof admin.is_superadmin}`);
                
                html += `
                    <div class="admin-item ${isCurrent ? 'current-user' : ''}">
                        <div class="admin-info">
                            <h4>${admin.username} ${isSuperadmin ? '👑' : ''}</h4>
                            <p>ID: ${admin.id} • <span class="admin-role ${isSuperadmin ? 'role-superadmin' : 'role-admin'}">${isSuperadmin ? 'Суперадминистратор' : 'Администратор'}</span> • Создан: ${new Date(admin.created_at).toLocaleDateString()}</p>
                            ${isCurrent ? '<p><em>👤 Текущий пользователь</em></p>' : ''}
                        </div>
                        <div class="admin-actions">
                            ${(isCurrent || !isSuperadmin) ? `<button class="btn btn-warning" onclick="editAdmin(${admin.id}, '${admin.username}', ${isSuperadmin})">✏️ Редактировать</button>` : ''}
                            ${!isSuperadmin ? `<button class="btn btn-success" onclick="promoteToSuperadmin(${admin.id}, '${admin.username}')">👑 Назначить суперадмином</button>` : ''}
                            ${!isSuperadmin && !isCurrent ? `<button class="btn btn-danger" onclick="deleteAdmin(${admin.id}, '${admin.username}')">🗑️ Удалить</button>` : ''}
                            ${isSuperadmin && !isCurrent ? `<span style="color: #666; font-style: italic;">Суперадминистратор</span>` : ''}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
        
        // Смена пароля текущего пользователя
        document.getElementById('change-password-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const status = document.getElementById('password-change-status');
            
            if (newPassword !== confirmPassword) {
                status.innerHTML = '<p style="color: red;">Пароли не совпадают</p>';
                return;
            }
            
            if (newPassword.length < 6) {
                status.innerHTML = '<p style="color: red;">Пароль должен быть не менее 6 символов</p>';
                return;
            }
            
            const result = await apiRequest('change-password', {
                oldPassword: currentPassword,
                newPassword: newPassword
            });
            
            if (result.success) {
                status.innerHTML = '<p style="color: green;">Пароль успешно изменен</p>';
                document.getElementById('change-password-form').reset();
            } else {
                status.innerHTML = `<p style="color: red;">${result.message || 'Ошибка при смене пароля'}</p>`;
            }
        });
        
        // Модальные окна для администраторов
        function showAddAdminModal() {
            document.getElementById('new-admin-username').value = '';
            document.getElementById('new-admin-password').value = '';
            document.getElementById('new-admin-password-confirm').value = '';
            document.getElementById('new-admin-role').value = '0'; // По умолчанию администратор
            document.getElementById('add-admin-status').innerHTML = '';
            document.getElementById('add-admin-modal').style.display = 'block';
        }
        
        function hideAddAdminModal() {
            document.getElementById('add-admin-modal').style.display = 'none';
        }
        
        function showEditAdminModal() {
            document.getElementById('edit-admin-password').value = '';
            document.getElementById('edit-admin-status').innerHTML = '';
            document.getElementById('edit-admin-modal').style.display = 'block';
        }
        
        function hideEditAdminModal() {
            document.getElementById('edit-admin-modal').style.display = 'none';
        }
        
        function editAdmin(id, username, isSuperadmin) {
            document.getElementById('edit-admin-id').value = id;
            document.getElementById('edit-admin-username').value = username;
            
            // Скрываем поле пароля для суперадминистраторов (кроме себя)
            const currentAdminId = <?php echo $_SESSION['admin_id']; ?>;
            const isCurrentUser = id === currentAdminId;
            
            if (isSuperadmin && !isCurrentUser) {
                alert('Невозможно редактировать другого суперадминистратора');
                return;
            }
            
            showEditAdminModal();
        }
        
        // Добавление администратора
        document.getElementById('add-admin-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('new-admin-username').value;
            const password = document.getElementById('new-admin-password').value;
            const confirmPassword = document.getElementById('new-admin-password-confirm').value;
            const isSuperadmin = document.getElementById('new-admin-role').value === '1';
            const status = document.getElementById('add-admin-status');
            
            if (password !== confirmPassword) {
                status.innerHTML = '<p style="color: red;">Пароли не совпадают</p>';
                return;
            }
            
            if (password.length < 6) {
                status.innerHTML = '<p style="color: red;">Пароль должен быть не менее 6 символов</p>';
                return;
            }
            
            const result = await apiRequest('add-admin', {
                username: username,
                password: password,
                is_superadmin: isSuperadmin
            });
            
            if (result.success) {
                status.innerHTML = '<p style="color: green;">Администратор успешно добавлен</p>';
                setTimeout(() => {
                    hideAddAdminModal();
                    loadAdmins();
                }, 1500);
            } else {
                status.innerHTML = `<p style="color: red;">${result.message || 'Ошибка при добавлении администратора'}</p>`;
            }
        });
        
        // Редактирование администратора
        document.getElementById('edit-admin-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const id = document.getElementById('edit-admin-id').value;
            const username = document.getElementById('edit-admin-username').value;
            const password = document.getElementById('edit-admin-password').value;
            const status = document.getElementById('edit-admin-status');
            
            const data = { id: id, username: username };
            if (password) {
                if (password.length < 6) {
                    status.innerHTML = '<p style="color: red;">Пароль должен быть не менее 6 символов</p>';
                    return;
                }
                data.password = password;
            }
            
            const result = await apiRequest('update-admin', data);
            
            if (result.success) {
                status.innerHTML = '<p style="color: green;">Администратор успешно обновлен</p>';
                setTimeout(() => {
                    hideEditAdminModal();
                    loadAdmins();
                }, 1500);
            } else {
                status.innerHTML = `<p style="color: red;">${result.message || 'Ошибка при обновлении администратора'}</p>`;
            }
        });
        
        // Удаление администратора
        async function deleteAdmin(id, username) {
            // Проверяем, не является ли администратор суперадминистратором
            const result = await apiRequest('get-admins');
            const admin = result.find(a => a.id === id);
            
            if (admin && admin.is_superadmin) {
                alert('Невозможно удалить суперадминистратора');
                return;
            }
            
            if (confirm(`Вы уверены, что хотите удалить администратора "${username}"?`)) {
                const result = await apiRequest('delete-admin', { id: id });
                
                if (result.success) {
                    alert('Администратор удален');
                    loadAdmins();
                } else {
                    alert(result.message || 'Ошибка при удалении администратора');
                }
            }
        }

        async function logout() {
            if (confirm('Вы уверены, что хотите выйти?')) {
                const result = await apiRequest('admin-logout');
                if (result.success) {
                    window.location.href = 'admin-login.php';
                } else {
                    alert('Ошибка при выходе: ' + (result.message || 'Неизвестная ошибка'));
                }
            }
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', loadAdmins);
    </script>
</body>
</html>