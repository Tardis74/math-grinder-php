<?php
// admin-monitoring.php - Мониторинг списывания
require_once '../config.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мониторинг списывания - Админ-панель</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .team-timeline {
        margin: 20px 0;
    }

    .timeline-item {
        border-left: 3px solid #3498db;
        padding: 10px 15px;
        margin: 10px 0;
        background: #f8f9fa;
        position: relative;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 15px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #3498db;
    }

    .timeline-item.tab-switch {
        border-left-color: #f39c12;
    }

    .timeline-item.tab-switch::before {
        background: #f39c12;
    }

    .timeline-item.copy {
        border-left-color: #e74c3c;
    }

    .timeline-item.copy::before {
        background: #e74c3c;
    }

    .timeline-item.paste {
        border-left-color: #c0392b;
    }

    .timeline-item.paste::before {
        background: #c0392b;
    }

    .timeline-time {
        font-size: 12px;
        color: #7f8c8d;
        margin-bottom: 5px;
    }

    .timeline-event {
        font-weight: bold;
    }

    .team-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin: 15px 0;
    }

    .summary-item {
        text-align: center;
        padding: 10px;
        background: #ecf0f1;
        border-radius: 5px;
    }

    .summary-number {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
    }

    .summary-label {
        font-size: 12px;
        color: #7f8c8d;
    }

    .risk-indicator {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
        margin: 5px;
    }

    .risk-low {
        background: #d4edda;
        color: #155724;
    }

    .risk-medium {
        background: #fff3cd;
        color: #856404;
    }

    .risk-high {
        background: #f8d7da;
        color: #721c24;
    }

    .activity-chart {
        margin: 20px 0;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
    }

    .hour-activity {
        display: flex;
        align-items: end;
        gap: 2px;
        height: 100px;
        margin: 10px 0;
    }

    .hour-bar {
        flex: 1;
        background: #3498db;
        border-radius: 2px 2px 0 0;
        position: relative;
    }

    .hour-bar:hover::after {
        content: attr(data-count);
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: #2c3e50;
        color: white;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 10px;
    }
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
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .cheating-alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #fdcb6e;
            padding: 12px;
            margin: 8px 0;
            border-radius: 4px;
            animation: slideIn 0.3s ease-out;
        }
        
        .new-team-alert {
            background-color: #ffebee;
            border-left-color: #e53935;
        }
        
        .tab-switch-alert {
            background-color: #fff3e0;
            border-left-color: #ff9800;
        }
        
        .copy-alert, .paste-alert {
            background-color: #ffebee;
            border-left-color: #f44336;
        }
        
        .high-violation {
            background-color: #ffebee !important;
        }
        
        .medium-violation {
            background-color: #fff3e0 !important;
        }
        
        .critical-violation {
            background-color: #ffcdd2 !important;
            font-weight: bold;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        
        .stat-card.warning {
            border-left-color: #f39c12;
        }
        
        .stat-card.danger {
            border-left-color: #e74c3c;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .filter-controls {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-controls select, .filter-controls input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .auto-refresh {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .violation-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        .badge-low {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-high {
            background: #f8d7da;
            color: #721c24;
        }
        
        .timestamp {
            font-size: 12px;
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
            </div>
            <ul class="sidebar-menu">
                <li><a href="main.php">📊 Главная</a></li>
                <li><a href="questions.php">❓ Управление вопросами</a></li>
                <li><a href="statistics.php">📈 Детальная статистика</a></li>
                <?php if ($_SESSION['is_superadmin'] ?? false): ?>
                <li><a href="../admin-users.php">👥 Администраторы</a></li>
                <?php endif; ?>
                <li><a href="monitoring.php" class="active">👁️ Мониторинг списывания</a></li>
                <li><a href="../admin-login.php?logout=1" onclick="return confirm('Вы уверены, что хотите выйти?')">🚪 Выйти</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="main-content">
            <div class="card">
                <h1>👁️ Мониторинг списывания</h1>
                <p>Отслеживание попыток нарушения правил проведения мероприятия</p>
                
                <div class="filter-controls">
                    <button class="btn" onclick="loadCheatingData()">🔄 Обновить</button>
                    <button class="btn btn-success" onclick="startAutoRefresh()">▶️ Автообновление</button>
                    <button class="btn" onclick="stopAutoRefresh()">⏹️ Остановить</button>
                    <button class="btn btn-danger" onclick="clearAllAttempts()">🗑️ Очистить все записи</button>
                    
                    <select id="violation-filter" onchange="loadCheatingData()">
                        <option value="all">Все нарушения</option>
                        <option value="tab_switch">Переключения вкладок</option>
                        <option value="copy">Попытки копирования</option>
                        <option value="paste">Попытки вставки</option>
                    </select>
                    
                    <select id="team-filter" onchange="loadCheatingData()">
                        <option value="all">Все команды</option>
                    </select>
                </div>
                
                <div class="auto-refresh">
                    <label>
                        <input type="checkbox" id="auto-refresh-checkbox" onchange="toggleAutoRefresh()">
                        Автообновление каждые 5 секунд
                    </label>
                    <span id="last-update" class="timestamp"></span>
                </div>
            </div>
            
            <div class="card">
                <h2>📊 Статистика нарушений</h2>
                <div id="cheating-stats">
                    <p>Загрузка статистики...</p>
                </div>
            </div>
            
            <div class="card">
                <h2>🚨 Активные уведомления</h2>
                <div id="cheating-alerts">
                    <p>Новых уведомлений нет</p>
                </div>
            </div>
            
            <div class="card">
                <h2>📋 Детальный журнал нарушений</h2>
                <div id="cheating-details">
                    <p>Загрузка данных...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Глобальные переменные
        let autoRefreshInterval = null;
        let lastCheatingData = null;
        let allTeams = [];
        
        // API функции
        async function apiRequest(action, data = null) {
            const options = {
                method: data ? 'POST' : 'GET',
                headers: {'Content-Type': 'application/json'}
            };
            if (data) options.body = JSON.stringify(data);
            
            try {
                const response = await fetch(`../api.php?action=${action}`, options);
                const text = await response.text();
                return JSON.parse(text);
            } catch (error) {
                console.error('API Error:', error);
                return {error: 'Ошибка соединения'};
            }
        }
        
        // Загрузка данных о нарушениях
        async function loadCheatingData() {
            const result = await apiRequest('get-cheating-attempts');
            updateLastUpdateTime();
            
            if (result.error) {
                document.getElementById('cheating-details').innerHTML = '<p>Ошибка загрузки данных</p>';
                return;
            }
            
            // Сохраняем данные для сравнения
            const previousData = lastCheatingData;
            lastCheatingData = result;
            
            // Обновляем фильтр команд
            updateTeamFilter(result);
            
            // Применяем фильтры
            const filteredData = applyFilters(result);
            
            // Обновляем интерфейс
            renderCheatingStats(filteredData);
            renderCheatingDetails(filteredData);
            
            // Показываем уведомления о новых нарушениях
            if (previousData) {
                showNewViolationAlerts(previousData, result);
            }
        }
        
        // Применение фильтров
        function applyFilters(data) {
            const violationFilter = document.getElementById('violation-filter').value;
            const teamFilter = document.getElementById('team-filter').value;
            
            return data.filter(item => {
                const violationMatch = violationFilter === 'all' || 
                    (violationFilter === 'tab_switch' && item.tab_switch_count > 0) ||
                    (violationFilter === 'copy' && item.copy_attempt_count > 0) ||
                    (violationFilter === 'paste' && item.paste_attempt_count > 0);
                
                const teamMatch = teamFilter === 'all' || item.team === teamFilter;
                
                return violationMatch && teamMatch;
            });
        }
        
        // Обновление фильтра команд
        function updateTeamFilter(data) {
            const teamFilter = document.getElementById('team-filter');
            const currentValue = teamFilter.value;
            
            // Собираем уникальные команды
            const teams = [...new Set(data.map(item => item.team))].sort();
            
            // Сохраняем все команды для других функций
            allTeams = teams;
            
            // Обновляем select
            teamFilter.innerHTML = '<option value="all">Все команды</option>';
            teams.forEach(team => {
                const option = document.createElement('option');
                option.value = team;
                option.textContent = team;
                if (team === currentValue) {
                    option.selected = true;
                }
                teamFilter.appendChild(option);
            });
        }
        
        // Отображение статистики
        function renderCheatingStats(data) {
            const container = document.getElementById('cheating-stats');
            
            if (data.length === 0) {
                container.innerHTML = '<p>Нарушений не обнаружено</p>';
                return;
            }
            
            const totalTeams = data.length;
            const totalViolations = data.reduce((sum, item) => 
                sum + item.tab_switch_count + item.copy_attempt_count + item.paste_attempt_count, 0);
            
            const tabSwitches = data.reduce((sum, item) => sum + item.tab_switch_count, 0);
            const copyAttempts = data.reduce((sum, item) => sum + item.copy_attempt_count, 0);
            const pasteAttempts = data.reduce((sum, item) => sum + item.paste_attempt_count, 0);
            
            // Находим команду с наибольшим количеством нарушений
            const worstTeam = data.reduce((worst, current) => {
                const currentTotal = current.tab_switch_count + current.copy_attempt_count + current.paste_attempt_count;
                const worstTotal = worst.tab_switch_count + worst.copy_attempt_count + worst.paste_attempt_count;
                return currentTotal > worstTotal ? current : worst;
            }, data[0]);
            
            const worstTeamTotal = worstTeam.tab_switch_count + worstTeam.copy_attempt_count + worstTeam.paste_attempt_count;
            
            container.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">${totalTeams}</div>
                        <div class="stat-label">Команд с нарушениями</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${totalViolations}</div>
                        <div class="stat-label">Всего нарушений</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-number">${tabSwitches}</div>
                        <div class="stat-label">Переключений вкладок</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-number">${copyAttempts}</div>
                        <div class="stat-label">Попыток копирования</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-number">${pasteAttempts}</div>
                        <div class="stat-label">Попыток вставки</div>
                    </div>
                    <div class="stat-card ${worstTeamTotal > 10 ? 'danger' : 'warning'}">
                        <div class="stat-number">${worstTeamTotal}</div>
                        <div class="stat-label">Нарушений у ${worstTeam.team}</div>
                    </div>
                </div>
            `;
        }
        
        // Отображение детальной таблицы
        function renderCheatingDetails(data) {
            const container = document.getElementById('cheating-details');
            
            if (data.length === 0) {
                container.innerHTML = '<p>Нет данных для отображения</p>';
                return;
            }
            
            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Команда</th>
                            <th>Переключения вкладок</th>
                            <th>Попытки копирования</th>
                            <th>Попытки вставки</th>
                            <th>Всего нарушений</th>
                            <th>Последняя активность</th>
                            <th>Уровень риска</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach(item => {
                const totalViolations = item.tab_switch_count + item.copy_attempt_count + item.paste_attempt_count;
                const riskLevel = getRiskLevel(totalViolations, item.copy_attempt_count + item.paste_attempt_count);
                const riskBadge = getRiskBadge(riskLevel, totalViolations);
                
                html += `
                    <tr class="${riskLevel === 'high' ? 'critical-violation' : riskLevel === 'medium' ? 'medium-violation' : ''}">
                        <td><strong>${item.team}</strong></td>
                        <td>${item.tab_switch_count}</td>
                        <td>${item.copy_attempt_count}</td>
                        <td>${item.paste_attempt_count}</td>
                        <td><strong>${totalViolations}</strong></td>
                        <td class="timestamp">${item.last_tab_switch ? new Date(item.last_tab_switch).toLocaleString() : 'Н/Д'}</td>
                        <td>${riskBadge}</td>
                        <td>
                            <button class="btn btn-warning" onclick="clearTeamAttempts('${item.team}')">Очистить</button>
                            <button class="btn" onclick="viewTeamDetails('${item.team}')">Подробнее</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        // Определение уровня риска
        function getRiskLevel(totalViolations, criticalViolations) {
            if (criticalViolations > 5 || totalViolations > 15) return 'high';
            if (criticalViolations > 2 || totalViolations > 8) return 'medium';
            return 'low';
        }
        
        // Получение бейджа уровня риска
        function getRiskBadge(level, count) {
            const badges = {
                low: `<span class="violation-badge badge-low">Низкий (${count})</span>`,
                medium: `<span class="violation-badge badge-medium">Средний (${count})</span>`,
                high: `<span class="violation-badge badge-high">ВЫСОКИЙ (${count})</span>`
            };
            return badges[level] || badges.low;
        }
        
        // Показ уведомлений о новых нарушениях
        function showNewViolationAlerts(previousData, currentData) {
            const alertsContainer = document.getElementById('cheating-alerts');
            let newAlerts = [];
            
            // Находим новые команды с нарушениями
            const previousTeams = new Set(previousData.map(item => item.team));
            const newTeams = currentData.filter(item => !previousTeams.has(item.team));
            
            newTeams.forEach(team => {
                newAlerts.push({
                    type: 'new_team',
                    team: team.team,
                    message: `🚨 Новая команда с нарушениями: ${team.team}`
                });
            });
            
            // Находим увеличение счетчиков
            currentData.forEach(current => {
                const previous = previousData.find(p => p.team === current.team);
                if (previous) {
                    if (current.tab_switch_count > previous.tab_switch_count) {
                        const diff = current.tab_switch_count - previous.tab_switch_count;
                        newAlerts.push({
                            type: 'tab_switch',
                            team: current.team,
                            message: `⚠️ ${current.team}: +${diff} переключений вкладок`
                        });
                    }
                    if (current.copy_attempt_count > previous.copy_attempt_count) {
                        const diff = current.copy_attempt_count - previous.copy_attempt_count;
                        newAlerts.push({
                            type: 'copy',
                            team: current.team,
                            message: `🚫 ${current.team}: +${diff} попыток копирования`
                        });
                    }
                    if (current.paste_attempt_count > previous.paste_attempt_count) {
                        const diff = current.paste_attempt_count - previous.paste_attempt_count;
                        newAlerts.push({
                            type: 'paste',
                            team: current.team,
                            message: `🚫 ${current.team}: +${diff} попыток вставки`
                        });
                    }
                }
            });
            
            // Показываем уведомления
            if (newAlerts.length > 0) {
                let alertsHtml = '';
                newAlerts.forEach(alert => {
                    const alertClass = `cheating-alert ${
                        alert.type === 'new_team' ? 'new-team-alert' :
                        alert.type === 'tab_switch' ? 'tab-switch-alert' :
                        'copy-alert'
                    }`;
                    
                    alertsHtml += `
                        <div class="${alertClass}">
                            ${alert.message}
                            <span style="float: right; cursor: pointer; font-weight: bold;" 
                                  onclick="this.parentElement.remove()">×</span>
                        </div>
                    `;
                });
                
                // Добавляем новые уведомления в начало
                alertsContainer.innerHTML = alertsHtml + (alertsContainer.innerHTML.includes('Новых уведомлений нет') ? '' : alertsContainer.innerHTML);
                
                // Воспроизводим звук для серьезных нарушений
                if (newAlerts.some(alert => alert.type === 'copy' || alert.type === 'paste')) {
                    playAlertSound();
                }
            }
        }
        
        // Воспроизведение звукового уведомления
        function playAlertSound() {
            // Простой beep звук
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        }
        
        // Управление автообновлением
        function startAutoRefresh() {
            if (!autoRefreshInterval) {
                autoRefreshInterval = setInterval(loadCheatingData, 5000);
                document.getElementById('auto-refresh-checkbox').checked = true;
            }
        }
        
        function stopAutoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                document.getElementById('auto-refresh-checkbox').checked = false;
            }
        }
        
        function toggleAutoRefresh() {
            const checkbox = document.getElementById('auto-refresh-checkbox');
            if (checkbox.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        }
        
        // Обновление времени последнего обновления
        function updateLastUpdateTime() {
            document.getElementById('last-update').textContent = 
                `Последнее обновление: ${new Date().toLocaleTimeString()}`;
        }
        
        // Действия с данными
        async function clearTeamAttempts(team) {
            if (confirm(`Очистить все записи о нарушениях для команды "${team}"?`)) {
                const result = await apiRequest('clear-cheating-attempts', { team: team });
                if (result.success) {
                    alert(`Данные для команды "${team}" очищены`);
                    loadCheatingData();
                } else {
                    alert('Ошибка при очистке данных');
                }
            }
        }
        
        async function clearAllAttempts() {
            if (confirm('Очистить ВСЕ записи о нарушениях? Это действие нельзя отменить.')) {
                const result = await apiRequest('clear-cheating-attempts', {});
                if (result.success) {
                    alert('Все записи о нарушениях очищены');
                    loadCheatingData();
                } else {
                    alert('Ошибка при очистке данных');
                }
            }
        }
        
        function viewTeamDetails(team) {
            showTeamDetailsModal(team);
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            loadCheatingData();
            // Автоматически запускаем автообновление
            startAutoRefresh();
        });
        
        // Очистка при закрытии страницы
        window.addEventListener('beforeunload', stopAutoRefresh);

        // Функции для модального окна детальной информации
        function showTeamDetailsModal(team) {
            document.getElementById('team-details-modal').style.display = 'block';
            loadTeamDetails(team);
        }

        function hideTeamDetailsModal() {
            document.getElementById('team-details-modal').style.display = 'none';
        }

        // Загрузка детальной информации по команде
        async function loadTeamDetails(team) {
            const content = document.getElementById('team-details-content');
            content.innerHTML = '<p>Загрузка информации о команде...</p>';
    
            // Получаем все данные о нарушениях
            const result = await apiRequest('get-cheating-attempts');
    
            if (result.error) {
                content.innerHTML = '<p>Ошибка загрузки данных</p>';
                return;
            }
    
            // Находим данные по выбранной команде
            const teamData = result.find(item => item.team === team);
    
            if (!teamData) {
                content.innerHTML = `<p>Данные по команде "${team}" не найдены</p>`;
                return;
            }
    
            // Получаем детальную историю нарушений
            const detailedHistory = await getTeamDetailedHistory(team);
    
            renderTeamDetails(team, teamData, detailedHistory);
        }

        // Получение детальной истории нарушений команды
        async function getTeamDetailedHistory(team) {
            try {
                const response = await apiRequest('get-detailed-cheating-history', { team: team });
                return response.history || [];
            } catch (error) {
                console.error('Error loading detailed history:', error);
                return [];
            }
        }

        // Отрисовка детальной информации
        function renderTeamDetails(team, teamData, detailedHistory) {
            const content = document.getElementById('team-details-content');
            const totalViolations = teamData.tab_switch_count + teamData.copy_attempt_count + teamData.paste_attempt_count;
    
            // Определяем уровень риска
            const riskLevel = getRiskLevel(totalViolations, teamData.copy_attempt_count + teamData.paste_attempt_count);
            const riskText = {
                low: 'Низкий',
                medium: 'Средний', 
                high: 'Высокий'
            }[riskLevel];
    
            let html = `
                <div style="border-bottom: 2px solid #ecf0f1; padding-bottom: 15px; margin-bottom: 20px;">
                    <h2 style="margin: 0; color: #2c3e50;">${team}</h2>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                        <span class="risk-indicator risk-${riskLevel}">Уровень риска: ${riskText}</span>
                        <span style="color: #7f8c8d;">Всего нарушений: ${totalViolations}</span>
                    </div>
                </div>
        
                <div class="team-summary">
                    <div class="summary-item">
                        <div class="summary-number">${teamData.tab_switch_count}</div>
                        <div class="summary-label">Переключений вкладок</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">${teamData.copy_attempt_count}</div>
                        <div class="summary-label">Попыток копирования</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">${teamData.paste_attempt_count}</div>
                        <div class="summary-label">Попыток вставки</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-number">${totalViolations}</div>
                        <div class="summary-label">Всего нарушений</div>
                    </div>
                </div>
    `       ;
    
            // График активности по часам (если есть детальная история)
            if (detailedHistory.length > 0) {
                html += renderActivityChart(detailedHistory);
            }
    
            // Временная шкала нарушений
            if (detailedHistory.length > 0) {
                html += `
                    <h4>📅 Хронология нарушений</h4>
                    <div class="team-timeline">
                        ${renderTimeline(detailedHistory)}
                    </div>
        `       ;
            } else {
                html += `
                    <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                        <p>Детальная история нарушений недоступна</p>
                        <small>Отображаются только агрегированные данные</small>
                    </div>
        `       ;
            }
    
            // Статистика по типам нарушений
            html += `
                <h4>📈 Статистика по типам нарушений</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                    <div style="padding: 15px; background: #fff3e0; border-radius: 5px;">
                        <strong>Переключения вкладок</strong><br>
                        <span style="font-size: 24px; font-weight: bold; color: #f39c12;">${teamData.tab_switch_count}</span>
                        <div style="background: #f39c12; height: 10px; border-radius: 5px; margin-top: 5px; width: ${(teamData.tab_switch_count / totalViolations * 100) || 0}%"></div>
                    </div>
                    <div style="padding: 15px; background: #ffebee; border-radius: 5px;">
                        <strong>Копирование/Вставка</strong><br>
                        <span style="font-size: 24px; font-weight: bold; color: #e74c3c;">${teamData.copy_attempt_count + teamData.paste_attempt_count}</span>
                        <div style="background: #e74c3c; height: 10px; border-radius: 5px; margin-top: 5px; width: ${((teamData.copy_attempt_count + teamData.paste_attempt_count) / totalViolations * 100) || 0}%"></div>
                    </div>
                </div>
    `       ;
    
            content.innerHTML = html;
        }

        // Отрисовка графика активности
        function renderActivityChart(history) {
            // Группируем по часам
            const hourCounts = Array(24).fill(0);
    
            history.forEach(record => {
                if (record.detected_at) {
                    const hour = new Date(record.detected_at).getHours();
                    hourCounts[hour]++;
                }
            });
    
            const maxCount = Math.max(...hourCounts);
    
            return `
                <h4>⏰ Активность нарушений по времени</h4>
                <div class="activity-chart">
                    <div class="hour-activity">
                        ${hourCounts.map((count, hour) => `
                            <div class="hour-bar" 
                                style="height: ${maxCount > 0 ? (count / maxCount * 80) : 0}px;"
                                data-count="${count}"
                                title="${hour}:00 - ${count} нарушений">
                            </div>
                        `).join('')}
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #7f8c8d;">
                        <span>0:00</span>
                        <span>12:00</span>
                        <span>23:00</span>
                    </div>
                </div>
    `       ;
        }

        // Отрисовка временной шкалы
        function renderTimeline(history) {
            // Сортируем по времени (новые сверху)
            const sortedHistory = history.sort((a, b) => new Date(b.detected_at) - new Date(a.detected_at));
    
            return sortedHistory.slice(0, 20).map(record => {
                const eventType = record.type;
                const eventTime = new Date(record.detected_at).toLocaleString();
                const eventText = {
                    'tab_switch': 'Переключение вкладки',
                    'copy': 'Попытка копирования',
                    'paste': 'Попытка вставки'
                }[eventType] || 'Нарушение';
        
                return `
                    <div class="timeline-item ${eventType}">
                        <div class="timeline-time">${eventTime}</div>
                        <div class="timeline-event">${eventText}</div>
                        ${record.question_id ? `<div style="font-size: 12px; color: #666;">Вопрос ID: ${record.question_id}</div>` : ''}
                    </div>
        `       ;
            }).join('');
        }

        // Очистка истории команды
        async function clearTeamHistory() {
            const team = document.querySelector('#team-details-content h2').textContent;
    
            if (confirm(`Вы уверены, что хотите очистить ВСЮ историю нарушений для команды "${team}"?`)) {
                const result = await apiRequest('clear-cheating-attempts', { team: team });
        
                if (result.success) {
                    alert(`История нарушений для команды "${team}" очищена`);
                    hideTeamDetailsModal();
                    loadCheatingData(); // Обновляем основную таблицу
                } else {
                    alert('Ошибка при очистке истории: ' + (result.message || 'Неизвестная ошибка'));
            }
        }
    }
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

<!-- Модальное окно детальной информации по команде -->
<div id="team-details-modal" class="modal">
    <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <h3>📊 Детальная информация по команде</h3>
        <div id="team-details-content">
            <p>Загрузка информации...</p>
        </div>
        <div style="text-align: right; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
            <button class="btn btn-danger" onclick="clearTeamHistory()">🗑️ Очистить историю</button>
            <button class="btn" onclick="hideTeamDetailsModal()">Закрыть</button>
        </div>
    </div>
</div>
</body>
</html>