<?php
// admin-main.php - Главная страница админ-панели
require_once '../config.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Проверяем, что режим правильный
try {
    $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
    $stmt->execute();
    $state = $stmt->fetch();
    
    if ($state && $state['event_mode'] === 'quiz') {
        // Если режим квиз, перенаправляем в другую ветку
        header('Location: ../admin-quiz/main.php');
        exit;
    }
} catch (PDOException $e) {
    // Оставляем в текущей ветке при ошибке
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - Панель администратора</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .mode-switcher {
            background: #e8f4fd;
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        
        .mode-switcher h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }

        #event-mode {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            background: white;
            font-size: 14px;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
            font-family: inherit;
        }

        #event-mode:hover {
            border-color: #3498db;
        }

        #event-mode:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        #event-mode option {
            padding: 10px 15px;
            background: white;
            color: #2c3e50;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
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
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-success:hover {
            background: #219a52;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-frozen {
            background: #cce7ff;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Боковая панель -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Админ-панель</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="main.php" class="active">📊 Главная</a></li>
                <li><a href="questions.php">❓ Управление вопросами</a></li>
                <li><a href="statistics.php">📈 Детальная статистика</a></li>
                <?php if ($_SESSION['is_superadmin'] ?? false): ?>
                <li><a href="../admin-users.php">👥 Администраторы</a></li>
                <?php endif; ?>
                <li><a href="monitoring.php">👁️ Мониторинг списывания</a></li>
                <li><a href="../admin-login.php?logout=1" onclick="return confirm('Вы уверены, что хотите выйти?')">🚪 Выйти</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="main-content">
            <div class="dashboard-header">
                <h1>Главная панель управления</h1>
                <p>Добро пожаловать, <?php echo $_SESSION['admin_username']; ?>!</p>
            </div>
            
            <!-- Карточки статуса -->
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Управление мероприятием</h3>    
                        <!-- Настройки мероприятия -->
                        <div style="margin-bottom: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #3498db;">
                            <h4 style="margin-top: 0; color: #2c3e50;">⚙️ Настройки мероприятия</h4>
                            
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Название мероприятия:</label>
                                <input type="text" id="event-name" 
                                    placeholder="Например: Математическая мясорубка 2024"
                                    style="width: 100%; padding: 12px; border: 1px solid #bdc3c7; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                <div style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">
                                    Это название будет отображаться у участников
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Длительность (минуты):</label>
                                <input type="number" id="timer-duration" min="1" max="480" 
                                    placeholder="60"
                                    style="width: 100%; padding: 12px; border: 1px solid #bdc3c7; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                <div style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">
                                    Установите длительность мероприятия от 1 до 480 минут
                                </div>
                            </div>
                            
                            <button class="btn" onclick="updateEventSettings()" 
                                    style="width: 100%; padding: 14px; background: #3498db; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">
                                💾 Сохранить настройки
                            </button>
                        </div>

                        <div class="mode-switcher">
                            <h4>🎮 Переключение режима мероприятия</h4>
                            <p>Текущий режим: <strong>Математическая мясорубка</strong></p>
                            <button class="btn btn-success" onclick="switchToQuizMode()">
                                🎯 Переключиться в режим Квиз
                            </button>
                            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                                При переключении вы будете перенаправлены в панель управления квизом
                            </p>
                        </div>
                        
                        <!-- Статус мероприятия -->
                        <div style="margin-bottom: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #2ecc71;">
                            <h4 style="margin-top: 0; color: #2c3e50;">📊 Текущий статус</h4>
                            
                            <div style="display: grid; gap: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                                    <span style="font-weight: 500;">Статус:</span>
                                    <span id="event-status-badge" class="status-badge status-inactive">Не начато</span>
                                </div>
                                
                                <div style="padding: 8px 0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="font-weight: 500;">Таймер:</span>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span id="timer-display" style="font-family: 'Courier New', monospace; font-weight: bold; font-size: 18px; color: #2c3e50;">--:--</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Прогресс-бар таймера -->
                                    <div style="background: #ecf0f1; border-radius: 10px; height: 8px; overflow: hidden; margin-top: 5px;">
                                        <div id="timer-progress" style="height: 100%; background: #3498db; width: 0%; transition: width 1s ease, background-color 0.3s ease;"></div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: #7f8c8d; margin-top: 4px;">
                                        <span>Начало</span>
                                        <span id="timer-progress-text">0%</span>
                                        <span>Конец</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Управление мероприятием -->
                        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #e74c3c;">
                            <h4 style="margin-top: 0; color: #2c3e50;">🎮 Управление мероприятием</h4>
                            
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <!-- Одна универсальная кнопка -->
                                <button id="event-control-btn" class="btn" onclick="handleEventControl()" 
                                        style="padding: 14px; background: #27ae60; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">
                                    🚀 Начать мероприятие
                                </button>
                                <button id="ranking-freeze-btn" class="btn" onclick="toggleRankingFreeze()" 
                                    style="width: 100%; padding: 14px; background: #3498db; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">
                                    ❄️ Заморозить рейтинг
                                </button>
                                
                                <!-- Статус заморозки -->
                                <div style="text-align: center; padding: 10px; background: #fff3cd; border-radius: 4px; border: 1px solid #ffeaa7;">
                                    <span id="freeze-status-text" style="color: #856404; font-weight: 500;">Рейтинг активен</span>
                                </div>
                            </div>
                        </div>
                </div>
                
                <div class="card">
                    <h3>Быстрые действия</h3>
                    <div class="quick-actions">
                        <a href="questions.php" class="btn">📝 Управление вопросами</a>
                        <a href="statistics.php" class="btn">📈 Детальная статистика</a>
                        <a href="monitoring.php" class="btn">👁️ Мониторинг списывания</a>
                        <button class="btn-success" onclick="saveResults()">💾 Сохранить результаты</button>
                        <button class="btn-danger" onclick="clearAllData()">🗑️ Очистить все данные</button>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Экспорт данных</h3>
                    <div class="quick-actions">
                        <button class="btn" onclick="exportExcel()">📊 Excel отчет</button>
                        <button class="btn" onclick="exportCSV()">📄 CSV отчет</button>
                        <button class="btn" onclick="exportJSON()">🔧 JSON данные</button>
                    </div>
                </div>
            </div>
            
            <!-- Мониторинг списывания (упрощенный) -->
            <div class="card">
                <h3>Активность списывания</h3>
                <div id="cheating-overview">
                    <p>Загрузка данных...</p>
                </div>
                <a href="admin-monitoring.php" class="btn">Подробный мониторинг</a>
            </div>
        </div>
    </div>

    <script>
        let adminTimerInterval = null;
        let eventStatus = 'not_started'; // Добавляем инициализацию
        let isUserEditingName = false;
        let isUserEditingDuration = false;

        // API функции
        async function apiRequest(action, data = null) {
            const options = {
                method: data ? 'POST' : 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            };
            
            if (data) {
                options.body = JSON.stringify(data);
            }            
            try {
                const response = await fetch('../api.php?action=' + action, options);
                
                // Получаем текст ответа для отладки
                const responseText = await response.text();
                
                // Проверяем, является ли ответ валидным JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('🔧 JSON Parse Error:', parseError);
                    // Если это HTML ошибка, пытаемся извлечь полезную информацию
                    if (responseText.includes('<b>Fatal error</b>') || responseText.includes('<br />')) {
                        const errorMatch = responseText.match(/<b>(.*?)<\/b>(.*?)<br \/>/);
                        const errorMessage = errorMatch ? errorMatch[1] + errorMatch[2] : 'Server PHP Error';
                        return { error: 'Ошибка сервера: ' + errorMessage };
                    }
                    return { error: 'Неверный ответ от сервера: ' + responseText.substring(0, 100) };
                }
                
                return result;
                
            } catch (error) {
                console.error('🔧 API Error for ' + action + ':', error);
                return { 
                    error: 'Ошибка соединения: ' + error.message,
                    details: error.toString()
                };
            }
        }

        async function switchToQuizMode() {
            if (confirm('Переключиться в режим Квиз?\n\nВы будете перенаправлены в панель управления квизом.')) {
                try {
                    const result = await apiRequest('update-event-mode', {
                        event_mode: 'quiz'
                    });
                    
                    if (result.success) {
                        window.location.href = '../admin-quiz/main.php';
                    } else {
                        alert('Ошибка: ' + result.error);
                    }
                } catch (error) {
                    alert('Ошибка при переключении режима');
                }
            }
        }

        async function updateEventMode() {
            const eventMode = document.getElementById('event-mode').value;
            
            try {
                const result = await apiRequest('update-event-mode', {
                    event_mode: eventMode
                });
                
                if (result.success) {
                    showNotification('Режим мероприятия обновлен!', 'success');
                    
                    // Обновляем текст кнопки в быстрых действиях
                    updateQuickActionsButton(eventMode);
                } else {
                    showNotification('Ошибка: ' + result.error, 'error');
                }
            } catch (error) {
                showNotification('Ошибка при обновлении режима', 'error');
            }
        }

        function updateQuickActionsButton(eventMode) {
            const questionsButton = document.querySelector('.quick-actions a[href="admin-questions.php"]');
            if (questionsButton) {
                if (eventMode === 'quiz') {
                    questionsButton.textContent = '🎯 Управление квизом';
                    questionsButton.href = 'admin-quiz.php';
                } else {
                    questionsButton.textContent = '📝 Управление вопросами';
                    questionsButton.href = 'admin-questions.php';
                }
            }
        }

        async function handleEventControl() {
            try {
                const eventControlBtn = document.getElementById('event-control-btn');
                if (!eventControlBtn) {
                    return;
                }

                // Получаем текущее состояние
                const state = await apiRequest('get-event-state-full');
                if (!state || state.error) {
                    showNotification('Не удалось получить состояние мероприятия', 'error');
                    return;
                }

                let confirmMessage, action;

                switch(state.event_status) {
                    case 'not_started':
                        confirmMessage = 'Начать мероприятие? Участники увидят вопросы и смогут отправлять ответы.';
                        action = 'start-event';
                        break;
                    case 'running':
                        confirmMessage = 'Остановить мероприятие? Прием ответов будет прекращен.';
                        action = 'finish-event';
                        break;
                    case 'finished':
                        confirmMessage = 'Перезапустить мероприятие? Все вернется в начальное состояние.';
                        action = 'reset-event';
                        break;
                    default:
                        showNotification('Неизвестный статус мероприятия', 'error');
                        return;
                }

                if (confirm(confirmMessage)) {
                    // Сохраняем оригинальный текст
                    const originalText = eventControlBtn.textContent;
                    eventControlBtn.textContent = 'Выполнение...';
                    eventControlBtn.disabled = true;

                    const result = await apiRequest(action, {});
                    
                    if (result.success) {
                        let successMessage;
                        switch(action) {
                            case 'start-event': successMessage = 'Мероприятие начато!'; break;
                            case 'finish-event': successMessage = 'Мероприятие остановлено!'; break;
                            case 'reset-event': successMessage = 'Мероприятие перезапущено!'; break;
                        }
                        showNotification(successMessage, 'success');
                        await loadEventState();
                    } else {
                        showNotification('Ошибка: ' + result.error, 'error');
                        // Восстанавливаем кнопку при ошибке
                        eventControlBtn.textContent = originalText;
                        eventControlBtn.disabled = false;
                    }
                }

            } catch (error) {
                showNotification('Ошибка при управлении мероприятием', 'error');
                // Восстанавливаем кнопку при ошибке
                const eventControlBtn = document.getElementById('event-control-btn');
                if (eventControlBtn) {
                    eventControlBtn.disabled = false;
                    await loadEventState(); // Обновляем состояние для восстановления правильного текста
                }
            }
        }
        
        // Функции управления
        async function toggleRankingFreeze() {
            try {
                const state = await apiRequest('get-event-state-full');
                if (!state || state.error) {
                    showNotification('Не удалось получить состояние мероприятия', 'error');
                    return;
                }

                const action = state.is_ranking_frozen ? 'unfreeze-ranking' : 'freeze-ranking';
                const message = state.is_ranking_frozen ? 
                    'Разморозить рейтинг? Изменения снова будут отображаться на табло.' : 
                    'Заморозить рейтинг? Текущие результаты останутся на табло, но новые изменения не будут видны.';

                if (confirm(message)) {
                    const result = await apiRequest(action, {});
                    if (result.success) {
                        const newState = state.is_ranking_frozen ? 'разморожен' : 'заморожен';
                        showNotification(`Рейтинг ${newState}!`, 'success');
                        await loadEventState(); // Обновляем UI
                    } else {
                        showNotification('Ошибка: ' + (result.error || result.message), 'error');
                    }
                }
            } catch (error) {
                showNotification('Ошибка при изменении статуса рейтинга', 'error');
            }
        }
        
        async function toggleAnswers() {
            // Получаем текущее состояние с сервера
            const state = await apiRequest('get-event-state-full');
            if (state && !state.error) {
                const action = state.is_accepting_answers ? 'stop-answers' : 'resume-answers';
                const message = state.is_accepting_answers ? 'Остановить прием ответов?' : 'Возобновить прием ответов?';
                
                if (confirm(message)) {
                    const result = await apiRequest(action, {});
                    if (result.success) {
                        showNotification(result.message || 'Статус приема ответов изменен', 'success');
                        await loadEventState(); // Обновляем UI
                    } else {
                        showNotification('Ошибка: ' + (result.error || result.message), 'error');
                    }
                }
            } else {
                showNotification('Не удалось получить состояние мероприятия', 'error');
            }
        }
        
        async function saveResults() {
            if (confirm('Сохранить результаты мероприятия?')) {
                const result = await apiRequest('save-results', {});
                if (result.success) {
                    // Скачивание файла
                    const blob = new Blob([result.file_data], {type: 'application/json'});
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = result.file_name;
                    a.click();
                    URL.revokeObjectURL(url);
                    alert('Результаты сохранены!');
                }
            }
        }
        
        async function clearAllData() {
            if (confirm('ВНИМАНИЕ! Удалить ВСЕ данные? Это действие нельзя отменить.')) {
                const result = await apiRequest('clear-results', {});
                if (result.success) {
                    alert('Все данные очищены');
                }
            }
        }
        
        async function exportExcel() {
            const result = await apiRequest('export-excel', {});
            if (result.success) {
                const blob = new Blob([result.file_data], {type: 'text/csv'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = result.file_name;
                a.click();
                URL.revokeObjectURL(url);
            }
        }
        
        async function exportCSV() {
            const result = await apiRequest('export-csv', {});
            if (result.success) {
                const blob = new Blob([result.file_data], {type: 'text/csv'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = result.file_name;
                a.click();
                URL.revokeObjectURL(url);
            }
        }
        
        async function exportJSON() {
            const result = await apiRequest('save-results', {});
            if (result.success) {
                const blob = new Blob([result.file_data], {type: 'application/json'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = result.file_name;
                a.click();
                URL.revokeObjectURL(url);
            }
        }
        
        // Загрузка данных мониторинга
        async function loadCheatingOverview() {
            const result = await apiRequest('get-cheating-attempts');
            const container = document.getElementById('cheating-overview');
            
            if (result.error || result.length === 0) {
                container.innerHTML = '<p>Нарушений не обнаружено</p>';
                return;
            }
            
            let html = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">';
            result.slice(0, 4).forEach(attempt => {
                const total = attempt.tab_switch_count + attempt.copy_attempt_count + attempt.paste_attempt_count;
                html += `
                    <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                        <strong>${attempt.team}</strong><br>
                        <small>Нарушений: ${total}</small>
                    </div>
                `;
            });
            html += '</div>';
            if (result.length > 4) {
                html += `<p><small>... и еще ${result.length - 4} команд</small></p>`;
            }
            container.innerHTML = html;
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
        document.addEventListener('DOMContentLoaded', loadCheatingOverview);

        // Таймер мероприятия
        // Глобальные переменные для таймера
        

        // Загрузка состояния мероприятия
        async function loadEventState() {
            try {
                const result = await apiRequest('get-event-state-full');
                
                if (result && !result.error) {
                    updateEventUI(result);
                    initializeAdminTimer();
                } else {
                    // Показываем fallback состояние
                    updateEventUI({
                        event_name: 'Математическая мясорубка',
                        event_status: 'not_started',
                        timer_duration: 3600,
                        timer_remaining: 3600,
                        is_ranking_frozen: false,
                        is_accepting_answers: false
                    });
                }
            } catch (error) {
                // Показываем fallback состояние
                updateEventUI({
                    event_name: 'Математическая мясорубка',
                    event_status: 'not_started', 
                    timer_duration: 3600,
                    timer_remaining: 3600,
                    is_ranking_frozen: false,
                    is_accepting_answers: false
                });
            }
        }

        // Получение синхронизированного состояния мероприятия для админ-панели
       // Получение состояния мероприятия
        async function getAdminSynchronizedState() {
            try {
                const result = await apiRequest('get-event-state-full');
                if (result.error) {
                    return null;
                }
                return result;
            } catch (error) {
                return null;
            }
        }

        // Инициализация синхронизированного таймера в админ-панели
        function initializeAdminTimer() {
            if (adminTimerInterval) {
                clearInterval(adminTimerInterval);
                adminTimerInterval = null;
            }
            
            // Запускаем интервал для периодического обновления состояния
            adminTimerInterval = setInterval(async () => {
                await loadEventState();
            }, 1000); // Обновляем каждую секунду
        }

        // Синхронизация админ-панели с сервером
        async function syncAdminWithServer() {
            try {
                
                const timerResult = await syncTimerOnly();
                
                if (timerResult) {
                    
                    if (timerResult.timer_remaining !== adminTimerRemaining) {
                        adminTimerRemaining = timerResult.timer_remaining;
                        updateAdminTimerDisplay();
                        updateTimerProgress();
                    }
                    
                    // Обновляем статус если изменился
                    if (timerResult.event_status && timerResult.event_status !== eventStatus) {
                        eventStatus = timerResult.event_status;
                        updateEventStatus();
                    }
                } else {
                    const fullState = await getAdminSynchronizedState();
                    if (fullState) {
                        updateEventUI(fullState);
                    }
                }
            } catch (error) {
            }
        }

        // Обновление прогресс-бара таймера
        function updateTimerProgress() {
            const progressBar = document.getElementById('timer-progress');
            const totalDuration = parseInt(document.getElementById('timer-duration').value) * 60 || 3600;
            
            if (totalDuration > 0 && adminTimerRemaining > 0) {
                const progress = ((totalDuration - adminTimerRemaining) / totalDuration) * 100;
                progressBar.style.width = `${progress}%`;
                
                // Изменяем цвет прогресс-бара в зависимости от оставшегося времени
                if (adminTimerRemaining <= 300) {
                    progressBar.style.backgroundColor = '#e74c3c';
                } else if (adminTimerRemaining <= 600) {
                    progressBar.style.backgroundColor = '#f39c12';
                } else {
                    progressBar.style.backgroundColor = '#3498db';
                }
            } else {
                progressBar.style.width = '0%';
            }
            updateTimerProgressText();
        }

        // Обработка истечения времени в админ-панели
        async function handleAdminTimerExpired() {
            // Автоматически завершаем мероприятие при истечении времени
            const result = await apiRequest('finish-event', {});
            if (result.success) {
                await loadEventState();
                showNotification('⏰ Время мероприятия истекло! Мероприятие автоматически завершено.', 'warning');
            }
        }

        // Показ уведомлений в админ-панели
        function showNotification(message, type = 'info') {
            // Создаем элемент уведомления
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                z-index: 10000;
                max-width: 300px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                animation: slideIn 0.3s ease-out;
            `;
            
            // Устанавливаем цвет в зависимости от типа
            const colors = {
                info: '#3498db',
                success: '#27ae60', 
                warning: '#f39c12',
                error: '#e74c3c'
            };
            notification.style.backgroundColor = colors[type] || colors.info;
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Автоматически удаляем через 5 секунд
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        // Обновление отображения таймера в админ-панели
        // Обновление интерфейса мероприятия
        function updateEventUI(state) {
    
            // Обновляем глобальную переменную статуса
            eventStatus = state.event_status || 'not_started';
            
            // Обновляем таймер
            updateAdminTimerDisplay(state.timer_remaining);
            updateTimerProgress(state.timer_remaining, state.timer_duration);
            
            // Обновляем статусы - ТОЛЬКО СТАТУС МЕРОПРИЯТИЯ
            const statusBadge = document.getElementById('event-status-badge');
            
            if (statusBadge) {
                switch(state.event_status) {
                    case 'running':
                        statusBadge.textContent = 'Идет';
                        statusBadge.className = 'status-badge status-active';
                        break;
                    case 'finished':
                        statusBadge.textContent = 'Завершено';
                        statusBadge.className = 'status-badge status-inactive';
                        break;
                    default:
                        statusBadge.textContent = 'Не начато';
                        statusBadge.className = 'status-badge status-inactive';
                }
            }
            
            // ОБНОВЛЯЕМ СТАТУС ЗАМОРОЗКИ РЕЙТИНГА
            updateRankingFreezeUI(state.is_ranking_frozen);
            
            // Обновляем кнопку управления мероприятием
            const eventControlBtn = document.getElementById('event-control-btn');
            if (eventControlBtn && typeof eventControlBtn.disabled !== 'undefined') {
                switch(state.event_status) {
                    case 'running':
                        eventControlBtn.textContent = '🛑 Остановить мероприятие';
                        eventControlBtn.style.backgroundColor = '#e74c3c';
                        eventControlBtn.disabled = false;
                        break;
                    case 'finished':
                        eventControlBtn.textContent = '🔄 Перезапустить мероприятие';
                        eventControlBtn.style.backgroundColor = '#f39c12';
                        eventControlBtn.disabled = false;
                        break;
                    default:
                        eventControlBtn.textContent = '🚀 Начать мероприятие';
                        eventControlBtn.style.backgroundColor = '#27ae60';
                        eventControlBtn.disabled = false;
                }
            }
        }

        function updateRankingFreezeUI(isFrozen) {
            const rankingFreezeBtn = document.getElementById('ranking-freeze-btn');
            const freezeStatusText = document.getElementById('freeze-status-text');
            
            if (isFrozen) {
                // Рейтинг заморожен
                rankingFreezeBtn.innerHTML = '🔥 Разморозить рейтинг';
                rankingFreezeBtn.style.backgroundColor = '#e67e22';
                freezeStatusText.textContent = '❄️ Рейтинг заморожен';
                freezeStatusText.style.color = '#0c5460';
                freezeStatusText.parentElement.style.background = '#d1ecf1';
                freezeStatusText.parentElement.style.border = '1px solid #bee5eb';
            } else {
                // Рейтинг активен
                rankingFreezeBtn.innerHTML = '❄️ Заморозить рейтинг';
                rankingFreezeBtn.style.backgroundColor = '#3498db';
                freezeStatusText.textContent = '🔥 Рейтинг активен';
                freezeStatusText.style.color = '#155724';
                freezeStatusText.parentElement.style.background = '#d4edda';
                freezeStatusText.parentElement.style.border = '1px solid #c3e6cb';
            }
        }

        // Добавляем CSS анимации для уведомлений
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        function initializeEventHandlers() {
            const eventNameInput = document.getElementById('event-name');
            const timerDurationInput = document.getElementById('timer-duration');
            
            if (eventNameInput) {
                eventNameInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        updateEventSettings();
                    }
                });
            }
            
            if (timerDurationInput) {
                timerDurationInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        updateEventSettings();
                    }
                });
            }
        }

        // Обновление отображения таймера
        function updateAdminTimerDisplay(remainingSeconds) {
            const timerElement = document.getElementById('timer-display');
            if (remainingSeconds > 0) {
                const hours = Math.floor(remainingSeconds / 3600);
                const minutes = Math.floor((remainingSeconds % 3600) / 60);
                const seconds = remainingSeconds % 60;
                
                timerElement.textContent = 
                    hours.toString().padStart(2, '0') + ':' + 
                    minutes.toString().padStart(2, '0') + ':' + 
                    seconds.toString().padStart(2, '0');
                
                // Визуальные индикаторы
                if (remainingSeconds <= 300) { // 5 минут
                    timerElement.style.color = '#e74c3c';
                    timerElement.style.fontWeight = 'bold';
                } else if (remainingSeconds <= 1800) { // 30 минут
                    timerElement.style.color = '#f39c12';
                    timerElement.style.fontWeight = 'bold';
                } else {
                    timerElement.style.color = '#2c3e50';
                    timerElement.style.fontWeight = 'normal';
                }
            } else {
                timerElement.textContent = '00:00:00';
                timerElement.style.color = '#2c3e50';
            }
        }

        // Обновление отображения таймера
        function updateTimerDisplay(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            document.getElementById('timer-display').textContent = minutes.toString().padStart(2, '0') + ':' + secs.toString().padStart(2, '0');
        }

        async function updateEventSettings() {
            const eventName = document.getElementById('event-name').value;
            const timerDuration = parseInt(document.getElementById('timer-duration').value) * 60; // переводим в секунды
            
            if (!eventName.trim()) {
                alert('Введите название мероприятия');
                return;
            }
            
            if (isNaN(timerDuration) || timerDuration <= 0) {
                alert('Введите корректную длительность');
                return;
            }
            
            // Показываем индикатор загрузки
            const saveBtn = document.querySelector('button[onclick="updateEventSettings()"]');
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Сохранение...';
            saveBtn.disabled = true;
            
            try {
                const result = await apiRequest('update-event-settings', {
                    event_name: eventName,
                    timer_duration: timerDuration
                });
                
                if (result.success) {
                    showNotification('Настройки сохранены!', 'success');
                    // После успешного сохранения сбрасываем флаги редактирования
                    isUserEditingName = false;
                    isUserEditingDuration = false;
                    // Принудительно обновляем состояние
                    await loadEventState();
                } else {
                    showNotification('Ошибка: ' + result.error, 'error');
                }
            } catch (error) {
                showNotification('Ошибка при сохранении настроек', 'error');
            } finally {
                // Восстанавливаем кнопку
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            }
        }



        // Обновление текста прогресс-бара
        function updateTimerProgressText() {
            const progressText = document.getElementById('timer-progress-text');
            const totalDuration = parseInt(document.getElementById('timer-duration').value) * 60 || 3600;
            
            if (totalDuration > 0 && adminTimerRemaining > 0) {
                const progress = ((totalDuration - adminTimerRemaining) / totalDuration) * 100;
                progressText.textContent = `${Math.round(progress)}%`;
            } else {
                progressText.textContent = '0%';
            }
        }

        // Обновление прогресс-бара таймера
        function updateTimerProgress(remainingSeconds, totalDuration) {
            const progressBar = document.getElementById('timer-progress');
            const progressText = document.getElementById('timer-progress-text');
            
            if (totalDuration > 0 && remainingSeconds > 0) {
                const progress = ((totalDuration - remainingSeconds) / totalDuration) * 100;
                progressBar.style.width = `${progress}%`;
                progressText.textContent = `${Math.round(progress)}%`;
                
                // Изменяем цвет прогресс-бара
                if (remainingSeconds <= 300) { // 5 минут
                    progressBar.style.backgroundColor = '#e74c3c';
                } else if (remainingSeconds <= 1800) { // 30 минут
                    progressBar.style.backgroundColor = '#f39c12';
                } else {
                    progressBar.style.backgroundColor = '#3498db';
                }
            } else {
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                progressBar.style.backgroundColor = '#3498db';
            }
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventHandlers(); // Добавьте эту строку
            loadEventState();
            loadCheatingOverview();
            
            // Периодическая синхронизация с обработкой ошибок
            setInterval(async () => {
                try {
                    await syncAdminWithServer();
                } catch (error) {
                }
            }, 30000); // Синхронизация каждые 30 секунд
        });
    
        async function handleQuestionsNavigation() {
            try {
                // Показываем индикатор загрузки
                const originalText = event.target.textContent;
                event.target.textContent = '⏳ Загрузка...';
                
                // Получаем текущий режим мероприятия
                const result = await apiRequest('get-event-mode');
                
                if (result.success) {
                    // Перенаправляем в зависимости от режима
                    if (result.event_mode === 'quiz') {
                        window.location.href = 'admin-quiz.php';
                    } else {
                        window.location.href = 'admin-questions.php';
                    }
                } else {
                    // По умолчанию используем обычное управление вопросами
                    window.location.href = 'admin-questions.php';
                }
            } catch (error) {
                console.error('Navigation error:', error);
                // При ошибке используем обычное управление вопросами
                window.location.href = 'admin-questions.php';
            }
        }
        
    </script>
</body>
</html>