<?php
// admin-quiz/main.php - Главная страница админ-панели квиза
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Проверяем, что режим правильный
try {
    $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
    $stmt->execute();
    $state = $stmt->fetch();
    
    if ($state && $state['event_mode'] !== 'quiz') {
        // Если режим не квиз, перенаправляем в другую ветку
        header('Location: ../admin-grinder/main.php');
        exit;
    }
} catch (PDOException $e) {
    // Оставляем в текущей ветке при ошибке
}

// Получаем базовую информацию о квизе
$quiz_stats = [
    'questions_count' => 0,
    'participants_count' => 0,
    'current_question' => null,
    'quiz_status' => 'not_started'
];

try {
    // Считаем вопросы
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM quiz_questions");
    $quiz_stats['questions_count'] = $stmt->fetch()['count'];
    
    // Считаем участников
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM participants");
    $quiz_stats['participants_count'] = $stmt->fetch()['count'];
    
    // Получаем статус квиза из event_state
    $stmt = $pdo->query("SELECT event_status FROM event_state WHERE id = 1");
    $event_state = $stmt->fetch();
    $quiz_stats['quiz_status'] = $event_state ? $event_state['event_status'] : 'not_started';
    
} catch (PDOException $e) {
    // Ошибка - используем значения по умолчанию
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - Панель администратора (Квиз)</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .quiz-timer {
            font-size: 1rem;
            font-weight: normal;
            color: #2c3e50;
            text-align: left;
            margin: 0;
            padding: 0;
            background: none;
            border: none;
            box-shadow: none;
        }

        /* Убираем анимации таймера которые могут вызывать глюки */
        .timer-warning {
            /* Убираем анимацию pulse */
            animation: none;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-secondary:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
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
            padding: 12px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .mode-switcher {
            background: #fff3cd;
            border: 2px solid #f39c12;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        
        .mode-switcher h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        
        .quiz-controls {
            background: #e8f4fd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .quiz-controls h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .control-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
        
        .stat-card.success {
            border-left-color: #27ae60;
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
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-not_started {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-running {
            background: #d4edda;
            color: #155724;
        }
        
        .status-finished {
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
                <small style="color: #bdc3c7;">Режим: Квиз</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="main.php" class="active">📊 Главная</a></li>
                <li><a href="questions.php">🎯 Управление квиз-вопросами</a></li>
                <?php if ($_SESSION['is_superadmin'] ?? false): ?>
                <li><a href="../admin-users.php">👥 Администраторы</a></li>
                <?php endif; ?>
                <li><a href="../admin-login.php?logout=1" onclick="return confirm('Вы уверены, что хотите выйти?')">🚪 Выйти</a></li>
            </ul>
        </div>
        
        <!-- Основной контент -->
        <div class="main-content">
            <div class="dashboard-header">
                <h1>Главная панель управления Квизом</h1>
                <p>Добро пожаловать, <?php echo $_SESSION['admin_username']; ?>! <strong>Режим: Квиз</strong></p>
            </div>

            <!-- Переключатель режимов -->
            <div class="mode-switcher">
                <h4>🎮 Переключение режима мероприятия</h4>
                <p>Текущий режим: <strong>Квиз</strong></p>
                <button class="btn btn-warning" onclick="switchToGrinderMode()">
                    🔄 Переключиться в режим Мясорубка
                </button>
                <p style="font-size: 12px; color: #666; margin-top: 10px;">
                    При переключении вы будете перенаправлены в панель управления мясорубкой
                </p>
            </div>
            
            <!-- Статистика квиза -->
            <div class="card">
                <h2>📊 Статистика квиза</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $quiz_stats['questions_count']; ?></div>
                        <div class="stat-label">Вопросов в квизе</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-number"><?php echo $quiz_stats['participants_count']; ?></div>
                        <div class="stat-label">Участников</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-number" id="current-question-display">-</div>
                        <div class="stat-label">Текущий вопрос</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <span id="quiz-status-badge" class="status-badge status-<?php echo $quiz_stats['quiz_status']; ?>">
                                <?php 
                                switch($quiz_stats['quiz_status']) {
                                    case 'running': echo 'ИДЕТ'; break;
                                    case 'finished': echo 'ЗАВЕРШЕН'; break;
                                    default: echo 'НЕ НАЧАТ';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="stat-label">Статус квиза</div>
                    </div>
                </div>
            </div>
            
            <!-- Управление квизом -->
            <div class="quiz-controls">
                <h3>🎯 Управление текущим квизом</h3>
                <div class="control-buttons">
                    <button class="btn btn-success" onclick="startQuiz()" id="start-quiz-btn">▶️ Начать квиз</button>
                    <button class="btn" onclick="nextQuestion()" id="next-question-btn" disabled>⏭️ Следующий вопрос</button>
                    <button class="btn" onclick="showAnswers()" id="show-answers-btn" disabled>👁️ Показать ответы</button>
                    <button class="btn btn-danger" onclick="endQuiz()" id="end-quiz-btn" disabled>⏹️ Завершить квиз</button>
                    <!-- НОВАЯ КНОПКА СБРОСА -->
                    <button class="btn btn-secondary" onclick="resetQuiz()" id="reset-quiz-btn" style="background: #6c757d;">🔄 Сбросить квиз</button>
                </div>
                
                <div id="quiz-status" style="margin-top: 15px; padding: 10px; background: white; border-radius: 5px;">
                    <strong>Статус:</strong> <span id="current-status">Ожидание начала квиза</span>
                    <br>
                    <strong>Таймер:</strong> <span id="quiz-timer">--:--</span>
                </div>
            </div>
            
            <!-- Быстрые действия -->
            <div class="card">
                <h3>Быстрые действия</h3>
                <div class="quick-actions">
                    <a href="questions.php" class="btn">🎯 Управление квиз-вопросами</a>
                    <button class="btn-success" onclick="exportResults()">💾 Экспорт результатов</button>
                    <button class="btn-warning" onclick="manageWaitingRoom()">👥 Управление залом ожидания</button>
                    <button class="btn" onclick="viewScoreboard()">🏆 Показать таблицу результатов</button>
                </div>
            </div>
            
            <!-- Мониторинг активности -->
            <div class="card">
                <h3>👁️ Активность участников</h3>
                <div id="activity-monitor">
                    <p>Загрузка данных активности...</p>
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
                console.log(`API Request: ${action}`, data);
                const response = await fetch(`../api.php?action=${action}`, options);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                console.log(`API Response for ${action}:`, text.substring(0, 200));
                
                if (!text.trim()) {
                    return {error: 'Пустой ответ от сервера'};
                }
                
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError, 'Response:', text);
                    return {error: 'Неверный JSON от сервера'};
                }
            } catch (error) {
                console.error('API Fetch Error:', error);
                return {error: 'Ошибка соединения: ' + error.message};
            }
        }

        // Переключение в режим мясорубки
        async function switchToGrinderMode() {
            if (confirm('Переключиться в режим Математическая мясорубка?\n\nВы будете перенаправлены в панель управления мясорубкой.')) {
                try {
                    const result = await apiRequest('update-event-mode', {
                        event_mode: 'grinder'
                    });
                    
                    if (result.success) {
                        alert('Режим изменен! Перенаправление...');
                        window.location.href = '../admin-grinder/main.php';
                    } else {
                        alert('Ошибка: ' + result.error);
                    }
                } catch (error) {
                    alert('Ошибка при переключении режима');
                }
            }
        }

        // Функции управления квизом
        async function startQuiz() {
            if (confirm('Начать квиз? Вопросы будут автоматически сменяться.')) {
                const result = await apiRequest('start-quiz', {});
                if (result.success) {
                    alert('Квиз начался! Вопросы будут автоматически сменяться.');
                    loadQuizStats();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            }
        }

        async function nextQuestion() {
            const result = await apiRequest('next-quiz-question', {});
            if (result.success) {
                alert('Переход к следующему вопросу');
                loadQuizStats();
            } else {
                alert('Ошибка: ' + result.error);
            }
        }

        async function showAnswers() {
            const result = await apiRequest('show-quiz-answers', {});
            if (result.success) {
                alert('Показ ответов активирован');
                loadQuizStats();
            } else {
                alert('Ошибка: ' + result.error);
            }
        }

        async function pauseQuiz() {
            const result = await apiRequest('pause-quiz', {});
            if (result.success) {
                updateQuizStatus('Пауза');
                updateControlButtons(false);
                alert('Квиз на паузе');
                loadQuizStats();
            } else {
                alert('Ошибка: ' + result.error);
            }
        }

        async function endQuiz() {
            if (confirm('Завершить квиз? Все участники увидят финальные результаты.')) {
                const result = await apiRequest('end-quiz', {});
                if (result.success) {
                    updateQuizStatus('Завершен');
                    updateControlButtons(false);
                    alert('Квиз завершен!');
                    loadQuizStats();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            }
        }

        // Вспомогательные функции
        function updateQuizStatus(status) {
            document.getElementById('current-status').textContent = status;
        }

        function updateControlButtons(isRunning) {
            const startBtn = document.getElementById('start-quiz-btn');
            const nextBtn = document.getElementById('next-question-btn');
            const showBtn = document.getElementById('show-answers-btn');
            const pauseBtn = document.getElementById('pause-quiz-btn');
            const endBtn = document.getElementById('end-quiz-btn');
            const resetBtn = document.getElementById('reset-quiz-btn');

            if (isRunning) {
                startBtn.disabled = true;
                nextBtn.disabled = false;
                showBtn.disabled = false;
                pauseBtn.disabled = false;
                endBtn.disabled = false;
                resetBtn.disabled = true; // Нельзя сбрасывать во время квиза
            } else {
                startBtn.disabled = false;
                nextBtn.disabled = true;
                showBtn.disabled = true;
                pauseBtn.disabled = true;
                endBtn.disabled = true;
                resetBtn.disabled = false; // Можно сбрасывать когда квиз не активен
            }
        }

        async function resetQuiz() {
            if (confirm('Сбросить квиз? Все данные будут очищены.')) {
                const result = await apiRequest('reset-quiz', {});
                if (result.success) {
                    alert('Квиз сброшен!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            }
        }

        async function exportResults() {
            alert('Экспорт результатов (функция в разработке)');
            // Позже реализуем
        }

        async function manageWaitingRoom() {
            alert('Управление залом ожидания (функция в разработке)');
            // Позже реализуем
        }

        async function viewScoreboard() {
            window.open('../scoreboard.php', '_blank');
        }

        // Загрузка статистики квиза
        async function loadQuizStats() {
            try {
                const result = await apiRequest('get-quiz-session');
                if (result && result.success) {
                    const session = result.session;
                    const eventStatus = result.stats.event_status;
                    
                    // Обновляем статус
                    const statusBadge = document.getElementById('quiz-status-badge');
                    statusBadge.className = `status-badge status-${eventStatus}`;
                    statusBadge.textContent = eventStatus === 'running' ? 'ИДЕТ' : 
                                            eventStatus === 'finished' ? 'ЗАВЕРШЕН' : 'НЕ НАЧАТ';
                    
                    // Обновляем информацию о текущем вопросе
                    if (result.current_question) {
                        document.getElementById('current-question-display').textContent = 
                            `#${result.current_question.display_order}`;
                        
                        let statusText = `Фаза: ${session.phase === 'question' ? 'Вопрос' : 'Ответы'}`;
                        if (result.time_remaining !== null) {
                            statusText += ` | Осталось: ${result.time_remaining}с`;
                        }
                        document.getElementById('current-status').textContent = statusText;
                        
                        // Обновляем статистику
                        document.getElementById('quiz-timer').textContent = 
                            `Участников: ${result.stats.participants_count} | Вопросов: ${result.stats.questions_count}`;
                    } else {
                        document.getElementById('current-question-display').textContent = '-';
                        document.getElementById('current-status').textContent = 
                            `Статус: ${eventStatus === 'running' ? 'Ожидание вопроса' : 'Квиз не активен'}`;
                        document.getElementById('quiz-timer').textContent = '--:--';
                    }
                    
                    // Обновляем состояние кнопок
                    updateControlButtons(eventStatus === 'running' && session.is_active);
                }
            } catch (error) {
                console.error('Error loading quiz stats:', error);
            }
        }

        function updateQuizTimer(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            document.getElementById('quiz-timer').textContent = 
                `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Загрузка активности
        async function loadActivity() {
            const result = await apiRequest('get-quiz-activity');
            const container = document.getElementById('activity-monitor');
            
            if (result.success) {
                container.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Активных участников:</strong> ${result.active_participants || 0}
                        </div>
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Ответов на текущий вопрос:</strong> ${result.current_answers || 0}
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = '<p>Нет данных об активности</p>';
            }
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            loadQuizStats();
            loadActivity();
            
            // ДОБАВЛЯЕМ АВТООБНОВЛЕНИЕ КАЖДУЮ СЕКУНДУ
            setInterval(() => {
                loadQuizStats();
                loadActivity();
            }, 1000); // 1 секунда вместо 5
        });
    </script>
</body>
</html>