<?php
require_once '../config.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Получаем настройки мясорубки
try {
    $stmt = $pdo->query("SELECT * FROM grinder_events WHERE id = 1");
    $grinder_settings = $stmt->fetch();
    
    if (!$grinder_settings) {
        // Создаем запись по умолчанию
        $stmt = $pdo->prepare("
            INSERT INTO grinder_events (id, event_name, timer_duration) 
            VALUES (1, 'Математическая мясорубка', 3600)
        ");
        $stmt->execute();
        $stmt = $pdo->query("SELECT * FROM grinder_events WHERE id = 1");
        $grinder_settings = $stmt->fetch();
    }
    
    // Рассчитываем оставшееся время
    if ($grinder_settings['event_status'] === 'running' && $grinder_settings['event_end_time']) {
        $end_time = strtotime($grinder_settings['event_end_time']);
        $current_time = time();
        $remaining = max(0, $end_time - $current_time);
        $grinder_settings['timer_remaining'] = $remaining;
    } else {
        $grinder_settings['timer_remaining'] = $grinder_settings['timer_duration'];
    }
    
} catch (PDOException $e) {
    $grinder_settings = [
        'event_name' => 'Математическая мясорубка',
        'event_status' => 'not_started',
        'timer_duration' => 3600,
        'timer_remaining' => 3600,
        'is_accepting_answers' => 1,
        'is_ranking_frozen' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - Панель администратора (Мясорубка)</title>
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

        .settings-form {
            margin: 20px 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .form-help {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .control-button {
            width: 100%;
            padding: 14px;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .control-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .control-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .notification {
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
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Боковая панель -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Админ-панель</h2>
                <p style="font-size: 0.9rem; color: #bdc3c7;">Режим: Мясорубка</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="main.php" class="active">📊 Главная</a></li>
                <li><a href="questions.php">❓ Управление вопросами</a></li>
                <li><a href="statistics.php">📈 Детальная статистика</a></li>
                <li><a href="monitoring.php">👁️ Мониторинг списывания</a></li>
                <?php if ($_SESSION['is_superadmin'] ?? false): ?>
                <li><a href="../admin-users.php">👥 Администраторы</a></li>
                <?php endif; ?>
                <li><a href="../admin-login.php?logout=1" onclick="return confirm('Вы уверены, что хотите выйти?')">🚪 Выйти</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="main-content">
            <div class="dashboard-header">
                <h1>Панель управления - Математическая мясорубка</h1>
                <p>Добро пожаловать, <?php echo $_SESSION['admin_username']; ?>!</p>
            </div>
            
            <!-- Карточки статуса -->
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Управление мероприятием</h3>    
                        <!-- Настройки мероприятия -->
                        <div style="margin-bottom: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #3498db;">
                            <h4 style="margin-top: 0; color: #2c3e50;">⚙️ Настройки мероприятия</h4>
                            
                            <div class="form-group">
                                <label class="form-label">Название мероприятия:</label>
                                <div style="margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                    Текущее: <span id="current-event-name"><?php echo htmlspecialchars($grinder_settings['event_name']); ?></span>
                                </div>
                                <input type="text" id="event-name" class="form-input"
                                    value="<?php echo htmlspecialchars($grinder_settings['event_name']); ?>"
                                    placeholder="Например: Математическая мясорубка 2024">
                                <div class="form-help">
                                    Это название будет отображаться у участников
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Длительность (минуты):</label>
                                <div style="margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                    Текущая: <span id="current-timer-duration"><?php echo floor(($grinder_settings['timer_duration'] ?? 3600) / 60); ?></span> мин
                                </div>
                                <input type="number" id="timer-duration" class="form-input" 
                                    value="<?php echo floor(($grinder_settings['timer_duration'] ?? 3600) / 60); ?>" 
                                    min="1" max="480" placeholder="60">
                                <div class="form-help">
                                    Установите длительность мероприятия от 1 до 480 минут (8 часов)
                                </div>
                            </div>
                            
                            <button class="control-button" onclick="updateEventSettings()" 
                                    style="background: #3498db;">
                                💾 Сохранить настройки
                            </button>
                        </div>

                        
                        
                        <!-- Управление мероприятием -->
                        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #e74c3c;">
                            <h4 style="margin-top: 0; color: #2c3e50;">🎮 Управление мероприятием</h4>
                            
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <!-- Основная кнопка управления -->
                                <button id="event-control-btn" class="control-button" 
                                        style="background: #27ae60;">
                                    🚀 Начать мероприятие
                                </button>
                                
                                <!-- Управление приемом ответов -->
                                <button id="answers-control-btn" class="control-button" 
                                        style="background: #f39c12;">
                                    ⏸️ Остановить прием ответов
                                </button>
                                
                                <!-- Управление рейтингом -->
                                <button id="ranking-freeze-btn" class="control-button" 
                                    style="background: #3498db;">
                                    ❄️ Заморозить рейтинг
                                </button>
                                
                                <!-- Статус заморозки -->
                                <div style="text-align: center; padding: 10px; background: #d1ecf1; border-radius: 4px; border: 1px solid #bee5eb;">
                                    <span id="freeze-status-text" style="color: #0c5460; font-weight: 500;">❄️ Рейтинг заморожен</span>
                                </div>
                            </div>
                        </div>
                </div>
                
                <div class="card">
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

                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0;">
                                    <span style="font-weight: 500;">Прием ответов:</span>
                                    <span id="answers-status" style="font-weight: 500;">❌ Остановлен</span>
                                </div>
                            </div>
                        </div>
                        <div class="mode-switcher">
                            <h4>🎮 Переключение режима мероприятия</h4>
                            <p>Текущий режим: <strong>Математическая мясорубка</strong></p>
                            <button class="control-button" onclick="switchToQuizMode()" style="background: #9b59b6;">
                                🎯 Переключиться в режим Квиз
                            </button>
                            <div class="form-help" style="margin-top: 10px;">
                                При переключении вы будете перенаправлены в панель управления квизом.<br>
                                Настройки мясорубки сохранятся и не будут сброшены.
                            </div>
                        </div>
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
                <a href="monitoring.php" class="btn">Подробный мониторинг</a>
            </div>
        </div>
    </div>

    <script>
        let adminTimerInterval = null;
        let eventStatus = 'not_started';
        let isRankingFrozen = false;
        let isAcceptingAnswers = false;

        // API функции для МЯСОРУБКИ
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
                const responseText = await response.text();
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    if (responseText.includes('<b>Fatal error</b>') || responseText.includes('<br />')) {
                        const errorMatch = responseText.match(/<b>(.*?)<\/b>(.*?)<br \/>/);
                        const errorMessage = errorMatch ? errorMatch[1] + errorMatch[2] : 'Server PHP Error';
                        return { error: 'Ошибка сервера: ' + errorMessage };
                    }
                    return { error: 'Неверный ответ от сервера: ' + responseText.substring(0, 100) };
                }
                
                return result;
                
            } catch (error) {
                console.error('API Error for ' + action + ':', error);
                return { 
                    error: 'Ошибка соединения: ' + error.message,
                    details: error.toString()
                };
            }
        }

        // Переключение на режим квиза
        async function switchToQuizMode() {
            if (confirm('Переключиться в режим Квиз?\n\nВы будете перенаправлены в панель управления квизом.\nНастройки мясорубки сохранятся.')) {
                try {
                    const result = await apiRequest('update-event-mode', {
                        event_mode: 'quiz'
                    });
                    
                    if (result.success) {
                        window.location.href = '../admin-quiz/main.php';
                    } else {
                        showNotification('Ошибка: ' + result.error, 'error');
                    }
                } catch (error) {
                    showNotification('Ошибка при переключении режима', 'error');
                }
            }
        }

        // Обновление настроек мероприятия (МЯСОРУБКИ)
        async function updateEventSettings() {
            const eventName = document.getElementById('event-name').value;
            const timerMinutes = parseInt(document.getElementById('timer-duration').value);
            
            if (!eventName.trim()) {
                alert('Введите название мероприятия');
                return;
            }
            
            if (isNaN(timerMinutes) || timerMinutes < 1 || timerMinutes > 480) {
                alert('Длительность должна быть от 1 до 480 минут');
                return;
            }
            
            const saveBtn = document.querySelector('button[onclick="updateEventSettings()"]');
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Сохранение...';
            saveBtn.disabled = true;
            
            try {
                // Используем API для мясорубки
                const result = await apiRequest('update-grinder-event-settings', {
                    event_name: eventName,
                    timer_duration: timerMinutes * 60 // конвертируем в секунды
                });
                
                if (result.success) {
                    showNotification('Настройки мясорубки сохранены!', 'success');
                    // Обновляем отображение
                    document.getElementById('current-event-name').textContent = eventName;
                    document.getElementById('current-timer-duration').textContent = timerMinutes;
                    // Загружаем обновленное состояние
                    await loadEventState();
                } else {
                    showNotification('Ошибка: ' + result.error, 'error');
                }
            } catch (error) {
                showNotification('Ошибка при сохранении настроек', 'error');
            } finally {
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            }
        }

        // Загрузка состояния мероприятия (МЯСОРУБКИ)
        async function loadEventState() {
            try {
                // Используем API для мясорубки
                const result = await apiRequest('get-grinder-event-state');
                
                if (result && !result.error) {
                    updateEventUI(result);
                    initializeAdminTimer(result);
                } else {
                    // Fallback состояние
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
                console.error('Error loading event state:', error);
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

        // Управление мероприятием (МЯСОРУБКА)
        async function handleEventControl() {
            try {
                const eventControlBtn = document.getElementById('event-control-btn');
                if (!eventControlBtn) return;

                // Получаем текущее состояние
                const state = await apiRequest('get-grinder-event-state');
                if (!state || state.error) {
                    showNotification('Не удалось получить состояние мероприятия', 'error');
                    return;
                }

                let confirmMessage, action;

                switch(state.event_status) {
                    case 'not_started':
                        confirmMessage = 'Начать мясорубку? Участники увидят вопросы и смогут отправлять ответы.';
                        action = 'start-grinder-event';
                        break;
                    case 'running':
                        confirmMessage = 'Завершить мясорубку? Прием ответов будет прекращен.';
                        action = 'finish-grinder-event';
                        break;
                    case 'finished':
                        confirmMessage = 'Сбросить мясорубку? Все вернется в начальное состояние.';
                        action = 'reset-grinder-event';
                        break;
                    default:
                        showNotification('Неизвестный статус мероприятия', 'error');
                        return;
                }

                if (confirm(confirmMessage)) {
                    const originalText = eventControlBtn.textContent;
                    eventControlBtn.textContent = 'Выполнение...';
                    eventControlBtn.disabled = true;

                    const result = await apiRequest(action, {});
                    
                    if (result.success) {
                        let successMessage;
                        switch(action) {
                            case 'start-grinder-event': 
                                successMessage = 'Мясорубка начата!'; 
                                break;
                            case 'finish-grinder-event': 
                                successMessage = 'Мясорубка завершена!'; 
                                break;
                            case 'reset-grinder-event': 
                                successMessage = 'Мясорубка сброшена!'; 
                                break;
                        }
                        showNotification(successMessage, 'success');
                        await loadEventState();
                    } else {
                        showNotification('Ошибка: ' + result.error, 'error');
                        eventControlBtn.textContent = originalText;
                        eventControlBtn.disabled = false;
                    }
                }

            } catch (error) {
                showNotification('Ошибка при управлении мероприятием', 'error');
                const eventControlBtn = document.getElementById('event-control-btn');
                if (eventControlBtn) {
                    eventControlBtn.disabled = false;
                    await loadEventState();
                }
            }
        }

        // Управление приемом ответов (МЯСОРУБКА)
        async function toggleAnswersControl() {
            try {
                const state = await apiRequest('get-grinder-event-state');
                if (!state || state.error) {
                    showNotification('Не удалось получить состояние мероприятия', 'error');
                    return;
                }

                const action = state.is_accepting_answers ? 'stop-grinder-answers' : 'resume-grinder-answers';
                const message = state.is_accepting_answers ? 
                    'Остановить прием ответов? Участники не смогут отправлять новые ответы.' : 
                    'Возобновить прием ответов? Участники снова смогут отправлять ответы.';

                if (confirm(message)) {
                    const result = await apiRequest(action, {});
                    if (result.success) {
                        const newState = state.is_accepting_answers ? 'остановлен' : 'возобновлен';
                        showNotification(`Прием ответов ${newState}!`, 'success');
                        await loadEventState();
                    } else {
                        showNotification('Ошибка: ' + result.error, 'error');
                    }
                }
            } catch (error) {
                showNotification('Ошибка при изменении приема ответов', 'error');
            }
        }

        // Управление заморозкой рейтинга (МЯСОРУБКА)
        async function toggleRankingFreeze() {
            try {
                const state = await apiRequest('get-grinder-event-state');
                if (!state || state.error) {
                    showNotification('Не удалось получить состояние мероприятия', 'error');
                    return;
                }

                const action = state.is_ranking_frozen ? 'unfreeze-grinder-ranking' : 'freeze-grinder-ranking';
                const message = state.is_ranking_frozen ? 
                    'Разморозить рейтинг? Изменения снова будут отображаться на табло.' : 
                    'Заморозить рейтинг? Текущие результаты останутся на табло, но новые изменения не будут видны.';

                if (confirm(message)) {
                    const result = await apiRequest(action, {});
                    if (result.success) {
                        const newState = state.is_ranking_frozen ? 'разморожен' : 'заморожен';
                        showNotification(`Рейтинг ${newState}!`, 'success');
                        await loadEventState();
                    } else {
                        showNotification('Ошибка: ' + result.error, 'error');
                    }
                }
            } catch (error) {
                showNotification('Ошибка при изменении статуса рейтинга', 'error');
            }
        }

        // Обновление интерфейса
        function updateEventUI(state) {
            eventStatus = state.event_status || 'not_started';
            isRankingFrozen = state.is_ranking_frozen || false;
            isAcceptingAnswers = state.is_accepting_answers || false;
            
            // Обновляем название и длительность в полях ввода
            document.getElementById('event-name').value = state.event_name || 'Математическая мясорубка';
            document.getElementById('timer-duration').value = Math.floor((state.timer_duration || 3600) / 60);
            
            // Обновляем отображение
            document.getElementById('current-event-name').textContent = state.event_name || 'Математическая мясорубка';
            document.getElementById('current-timer-duration').textContent = Math.floor((state.timer_duration || 3600) / 60);
            
            // Обновляем таймер
            updateAdminTimerDisplay(state.timer_remaining || state.timer_duration || 3600);
            updateTimerProgress(state.timer_remaining || 0, state.timer_duration || 3600);
            
            // Обновляем статус мероприятия
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
            
            // Обновляем статус приема ответов
            const answersStatus = document.getElementById('answers-status');
            if (answersStatus) {
                answersStatus.textContent = state.is_accepting_answers ? '✅ Активен' : '❌ Остановлен';
            }
            
            // Обновляем кнопки
            updateButtonsState(state);
        }

        // Обновление состояния кнопок
        function updateButtonsState(state) {
            const eventControlBtn = document.getElementById('event-control-btn');
            const answersControlBtn = document.getElementById('answers-control-btn');
            const rankingFreezeBtn = document.getElementById('ranking-freeze-btn');
            const freezeStatusText = document.getElementById('freeze-status-text');
            
            // Кнопка управления мероприятием
            if (eventControlBtn) {
                switch(state.event_status) {
                    case 'running':
                        eventControlBtn.textContent = '🛑 Завершить мясорубку';
                        eventControlBtn.style.backgroundColor = '#e74c3c';
                        eventControlBtn.onclick = handleEventControl;
                        break;
                    case 'finished':
                        eventControlBtn.textContent = '🔄 Сбросить мясорубку';
                        eventControlBtn.style.backgroundColor = '#f39c12';
                        eventControlBtn.onclick = handleEventControl;
                        break;
                    default:
                        eventControlBtn.textContent = '🚀 Начать мясорубку';
                        eventControlBtn.style.backgroundColor = '#27ae60';
                        eventControlBtn.onclick = handleEventControl;
                }
                eventControlBtn.disabled = false;
            }
            
            // Кнопка управления приемом ответов
            if (answersControlBtn) {
                if (state.event_status !== 'running') {
                    answersControlBtn.textContent = 'Прием ответов недоступен';
                    answersControlBtn.style.backgroundColor = '#bdc3c7';
                    answersControlBtn.disabled = true;
                } else {
                    answersControlBtn.disabled = false;
                    if (state.is_accepting_answers) {
                        answersControlBtn.textContent = '⏸️ Остановить прием ответов';
                        answersControlBtn.style.backgroundColor = '#f39c12';
                    } else {
                        answersControlBtn.textContent = '▶️ Возобновить прием ответов';
                        answersControlBtn.style.backgroundColor = '#2ecc71';
                    }
                    answersControlBtn.onclick = toggleAnswersControl;
                }
            }
            
            // Кнопка управления заморозкой рейтинга
            if (rankingFreezeBtn) {
                rankingFreezeBtn.disabled = false;
                if (state.is_ranking_frozen) {
                    rankingFreezeBtn.textContent = '🔥 Разморозить рейтинг';
                    rankingFreezeBtn.style.backgroundColor = '#e67e22';
                    if (freezeStatusText) {
                        freezeStatusText.textContent = '❄️ Рейтинг заморожен';
                        freezeStatusText.parentElement.style.background = '#d1ecf1';
                        freezeStatusText.parentElement.style.borderColor = '#bee5eb';
                    }
                } else {
                    rankingFreezeBtn.textContent = '❄️ Заморозить рейтинг';
                    rankingFreezeBtn.style.backgroundColor = '#3498db';
                    if (freezeStatusText) {
                        freezeStatusText.textContent = '🔥 Рейтинг активен';
                        freezeStatusText.parentElement.style.background = '#d4edda';
                        freezeStatusText.parentElement.style.borderColor = '#c3e6cb';
                    }
                }
                rankingFreezeBtn.onclick = toggleRankingFreeze;
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
                } else if (remainingSeconds <= 1800) { // 30 минут
                    timerElement.style.color = '#f39c12';
                } else {
                    timerElement.style.color = '#2c3e50';
                }
            } else {
                timerElement.textContent = '00:00:00';
                timerElement.style.color = '#2c3e50';
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
                if (remainingSeconds <= 300) {
                    progressBar.style.backgroundColor = '#e74c3c';
                } else if (remainingSeconds <= 1800) {
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

        // Инициализация таймера
        function initializeAdminTimer(state) {
            if (adminTimerInterval) {
                clearInterval(adminTimerInterval);
            }
            
            if (state.event_status === 'running') {
                let remaining = state.timer_remaining || state.timer_duration;
                
                adminTimerInterval = setInterval(() => {
                    if (remaining > 0) {
                        remaining--;
                        updateAdminTimerDisplay(remaining);
                        updateTimerProgress(remaining, state.timer_duration);
                    } else {
                        clearInterval(adminTimerInterval);
                        // Автоматически обновляем состояние
                        loadEventState();
                    }
                }, 1000);
            }
        }

        // Показать уведомление
        function showNotification(message, type = 'info') {
            const colors = {
                info: '#3498db',
                success: '#27ae60', 
                warning: '#f39c12',
                error: '#e74c3c'
            };
            
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.style.backgroundColor = colors[type] || colors.info;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        // Остальные функции (сохранение, экспорт и т.д.)
        async function saveResults() {
            if (confirm('Сохранить результаты мероприятия?')) {
                const result = await apiRequest('save-results', {});
                if (result.success) {
                    const blob = new Blob([result.file_data], {type: 'application/json'});
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = result.file_name;
                    a.click();
                    URL.revokeObjectURL(url);
                    showNotification('Результаты сохранены!', 'success');
                } else {
                    showNotification('Ошибка сохранения: ' + result.error, 'error');
                }
            }
        }
        
        async function clearAllData() {
            if (confirm('ВНИМАНИЕ! Удалить ВСЕ данные? Это действие нельзя отменить.')) {
                const result = await apiRequest('clear-results', {});
                if (result.success) {
                    showNotification('Все данные очищены', 'success');
                } else {
                    showNotification('Ошибка очистки: ' + result.error, 'error');
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
            } else {
                showNotification('Ошибка экспорта: ' + result.error, 'error');
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
            } else {
                showNotification('Ошибка экспорта: ' + result.error, 'error');
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
            } else {
                showNotification('Ошибка экспорта: ' + result.error, 'error');
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

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            loadEventState();
            loadCheatingOverview();
            
            // Привязываем кнопки управления ответами и рейтингом
            document.getElementById('answers-control-btn').addEventListener('click', toggleAnswersControl);
            document.getElementById('ranking-freeze-btn').addEventListener('click', toggleRankingFreeze);
            
            // Периодическое обновление состояния
            setInterval(async () => {
                try {
                    await loadEventState();
                } catch (error) {
                    console.error('Ошибка периодического обновления:', error);
                }
            }, 30000);
            
            // Обработчики Enter для полей ввода
            document.getElementById('event-name').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    updateEventSettings();
                }
            });
            
            document.getElementById('timer-duration').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    updateEventSettings();
                }
            });
        });
    </script>
</body>
</html>