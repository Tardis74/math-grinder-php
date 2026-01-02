<?php
// admin-statistics.php - Детальная статистика
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
    <title>Статистика - Админ-панель</title>
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .correct-answer {
            background-color: #d4edda;
        }
        
        .incorrect-answer {
            background-color: #f8d7da;
        }
        
        .no-answer {
            background-color: #f0f0f0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
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
                <li><a href="statistics.php" class="active">📈 Детальная статистика</a></li>
                <?php if ($_SESSION['is_superadmin'] ?? false): ?>
                <li><a href="../admin-users.php">👥 Администраторы</a></li>
                <?php endif; ?>
                <li><a href="monitoring.php">👁️ Мониторинг списывания</a></li>
                <li><a href="../admin-login.php?logout=1" onclick="return confirm('Вы уверены, что хотите выйти?')">🚪 Выйти</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="main-content">
            <div class="card">
                <h1>Детальная статистика</h1>
                <button class="btn" onclick="loadDetailedResults()">🔄 Обновить данные</button>
                <button class="btn" onclick="exportStatistics()">📊 Экспорт статистики</button>
            </div>
            
            <div class="card">
                <h2>Общая статистика</h2>
                <div id="general-stats">
                    <p>Загрузка статистики...</p>
                </div>
            </div>
            
            <div class="card">
                <h2>Детальные результаты</h2>
                <div id="detailed-results">
                    <p>Загрузка результатов...</p>
                </div>
            </div>
            
            <div class="card">
                <h2>Статистика по вопросам</h2>
                <div id="questions-stats">
                    <p>Загрузка статистики вопросов...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
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
        
        // Загрузка детальных результатов
        async function loadDetailedResults() {
            const result = await apiRequest('get-detailed-results');
            
            if (result.error) {
                document.getElementById('detailed-results').innerHTML = '<p>Ошибка загрузки: ' + result.error + '</p>';
                return;
            }
            
            renderGeneralStats(result);
            renderDetailedResults(result);
            renderQuestionsStats(result);
        }
        
        // Общая статистика
        function renderGeneralStats(data) {
            const container = document.getElementById('general-stats');
            
            if (!data.participants || data.participants.length === 0) {
                container.innerHTML = '<p>Нет данных</p>';
                return;
            }
            
            const totalParticipants = data.participants.length;
            const totalQuestions = data.questions.length;
            const totalAnswers = data.answers.length;
            const correctAnswers = data.answers.filter(a => a.is_correct).length;
            const accuracy = totalAnswers > 0 ? Math.round((correctAnswers / totalAnswers) * 100) : 0;
            
            container.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">${totalParticipants}</div>
                        <div class="stat-label">Участников</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${totalQuestions}</div>
                        <div class="stat-label">Вопросов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${totalAnswers}</div>
                        <div class="stat-label">Ответов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${correctAnswers}</div>
                        <div class="stat-label">Правильных</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">${accuracy}%</div>
                        <div class="stat-label">Точность</div>
                    </div>
                </div>
            `;
        }
        
        // Детальные результаты
        function renderDetailedResults(data) {
            const container = document.getElementById('detailed-results');
            
            if (!data.participants || data.participants.length === 0) {
                container.innerHTML = '<p>Нет данных о результатах</p>';
                return;
            }
            
            let html = '<table><thead><tr><th>Место</th><th>Команда</th><th>Баллы</th>';
            
            // Заголовки вопросов
            data.questions.forEach((q, index) => {
                html += `<th>Вопрос ${index + 1}</th>`;
            });
            
            html += '</tr></thead><tbody>';
            
            // Данные участников
            data.participants.forEach((participant, index) => {
                html += `<tr><td>${index + 1}</td><td>${participant.team}</td><td><strong>${participant.score}</strong></td>`;
                
                // Ответы на вопросы
                data.questions.forEach(question => {
                    const answer = data.answers.find(a => 
                        a.participant_id === participant.id && a.question_id === question.id
                    );
                    
                    if (answer) {
                        const className = answer.is_correct ? 'correct-answer' : 'incorrect-answer';
                        const points = answer.points > 0 ? `+${answer.points}` : '0';
                        const order = answer.answer_order ? ` (${answer.answer_order})` : '';
                        html += `<td class="${className}">${points}${order}</td>`;
                    } else {
                        html += '<td class="no-answer">-</td>';
                    }
                });
                
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        // Статистика по вопросам
        function renderQuestionsStats(data) {
            const container = document.getElementById('questions-stats');
            
            if (!data.questions || data.questions.length === 0) {
                container.innerHTML = '<p>Нет вопросов для статистики</p>';
                return;
            }
            
            let html = '<table><thead><tr><th>Вопрос</th><th>Правильный ответ</th><th>Правильных</th><th>Неправильных</th><th>Точность</th><th>Первый ответ</th></tr></thead><tbody>';
            
            data.questions.forEach(question => {
                const questionAnswers = data.answers.filter(a => a.question_id === question.id);
                const correctAnswers = questionAnswers.filter(a => a.is_correct);
                const incorrectAnswers = questionAnswers.filter(a => !a.is_correct);
                const accuracy = questionAnswers.length > 0 ? Math.round((correctAnswers.length / questionAnswers.length) * 100) : 0;
                
                // Находим первого ответившего правильно
                const firstCorrect = correctAnswers.find(a => a.answer_order === 1);
                const firstTeam = firstCorrect ? data.participants.find(p => p.id === firstCorrect.participant_id)?.team : '-';
                
                html += `
                    <tr>
                        <td>${question.text.substring(0, 50)}...</td>
                        <td>${question.answer}</td>
                        <td>${correctAnswers.length}</td>
                        <td>${incorrectAnswers.length}</td>
                        <td>${accuracy}%</td>
                        <td>${firstTeam}</td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        
        async function exportStatistics() {
            const result = await apiRequest('get-detailed-results');
            if (result.error) {
                alert('Ошибка экспорта: ' + result.error);
                return;
            }
            
            // Формируем CSV
            let csv = 'Команда,Общий балл';
            
            // Заголовки вопросов
            result.questions.forEach((q, i) => {
                csv += `,Вопрос ${i + 1}`;
            });
            csv += '\n';
            
            // Данные
            result.participants.forEach(participant => {
                csv += `"${participant.team}",${participant.score}`;
                
                result.questions.forEach(question => {
                    const answer = result.answers.find(a => 
                        a.participant_id === participant.id && a.question_id === question.id
                    );
                    csv += answer ? `,${answer.points}` : ',0';
                });
                
                csv += '\n';
            });
            
            const blob = new Blob([csv], {type: 'text/csv'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `statistics_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            URL.revokeObjectURL(url);
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
        // Инициализация
        document.addEventListener('DOMContentLoaded', loadDetailedResults);
        
    </script>
</body>
</html>