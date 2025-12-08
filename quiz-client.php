<?php
// quiz-client.php - Интерфейс участника для режима квиза
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Квиз - Математическая мясорубка</title>
    <link rel="stylesheet" href="css/light-participant.css">
    <style>
        /* Дополнительные стили специфичные для квиза */
        .quiz-mode-indicator {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
            display: inline-block;
        }
        
        .answers-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin: 20px 0;
        }
        
        .answer-option {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            display: flex;
            align-items: center;
        }
        
        .answer-option:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
        }
        
        .answer-option.selected {
            border-color: #27ae60;
            background: #d4edda;
        }
        
        .answer-option input {
            margin-right: 12px;
            transform: scale(1.2);
        }
        
        .answer-option label {
            cursor: pointer;
            flex: 1;
            margin: 0;
        }
        
        .quiz-timer {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
        
        .quiz-timer.warning {
            color: #e74c3c;
            animation: pulse 1s infinite;
        }
        
        .question-phase {
            text-align: center;
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .phase-question {
            background: #e8f4fd;
            color: #2980b9;
            border-left: 4px solid #3498db;
        }
        
        .phase-answers {
            background: #d4edda;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }
        
        .score-change {
            font-size: 1.3rem;
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #e8f4fd, #d4edda);
            border-radius: 10px;
            margin: 20px 0;
            border: 3px solid #3498db;
            font-weight: bold;
        }
        
        .correct-answer-item {
            padding: 15px;
            background: #d4edda;
            border: 2px solid #27ae60;
            border-radius: 8px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .correct-answer-item span:first-child {
            color: #155724;
            font-weight: 600;
        }
        
        .correct-answer-item span:last-child {
            color: #7f8c8d;
            font-weight: bold;
        }
        
        #quiz-container {
            position: relative;
            z-index: 10;
        }
        
        #quiz-container.active {
            display: block;
        }

        #waiting-screen, #finished-screen, #login-form {
            position: relative;
            z-index: 20;
        }

        .question-display {
            position: relative;
            z-index: 5;
        }

        #answer-results {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #e9ecef;
            position: relative;
            z-index: 15;
        }

        #waiting-results {
            background: #fff3cd;
            border: 2px solid #ffeaa7;
            border-radius: 10px;
            padding: 25px;
            margin: 20px 0;
            text-align: center;
        }

        #waiting-screen.active, 
        #finished-screen.active,
        #quiz-container.active {
            position: relative;
            z-index: 100;
        }

        #login-form.active {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background-color: #ecf0f1;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }

        .login-container {
            margin: 0 auto; /* Центрирование */
        }

        #login-form:not(.active),
        #waiting-screen:not(.active),
        #finished-screen:not(.active),
        #quiz-container:not(.active) {
            display: none !important;
        }
        
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .team-score-info {
            font-size: 1.1rem;
            color: #2c3e50;
        }
        
        .team-score-info strong {
            color: #3498db;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }
        
        .time-up-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #f5c6cb;
        }
        
        .waiting-for-answers {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #ffeaa7;
        }
        
        .answer-submitted {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 15px 0;
            border: 2px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <!-- Экран входа -->
    <div id="login-form" class="active">
        <div class="login-container">
            <div class="login-header">
                <div class="login-icon">🧠</div>
                <h2 id="login-event-name">Математический квиз</h2>
                <p>Войдите в систему для участия в квизе</p>
                <div class="quiz-mode-indicator">РЕЖИМ: КВИЗ</div>
            </div>
            
            <form id="team-login-form" class="login-form">
                <div class="login-input-group">
                    <input type="text" 
                        id="team-input" 
                        class="login-input" 
                        placeholder="Введите название команды" 
                        required>
                </div>
                
                <button type="submit" class="login-btn">
                    🎯 Присоединиться к квизу
                </button>
            </form>
            
            <div class="login-footer">
                <p>Ожидайте начала квиза</p>
            </div>
        </div>
    </div>

    <!-- Экран ожидания -->
    <div id="waiting-screen">
        <div class="main-content-container">
            <div class="status-screen">
                <div class="status-icon waiting-icon">⏳</div>
                <h2 class="status-title">Ожидание начала квиза</h2>
                <p class="status-message" id="waiting-message">Команда ожидает начала квиза.</p>
                
                <div class="event-info">
                    <h4>Информация о квизе</h4>
                    <div class="info-item">
                        <span class="info-label">Название:</span>
                        <span class="info-value" id="waiting-event-name">Математический квиз</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Режим:</span>
                        <span class="info-value">Интерактивный квиз</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Статус:</span>
                        <span class="info-value" id="waiting-status">Не начат</span>
                    </div>
                </div>
                
                <button class="refresh-btn" onclick="checkEventStatus()">
                    🔄 Обновить статус
                </button>
            </div>
        </div>
    </div>

    <!-- Экран завершения -->
    <div id="finished-screen">
        <div class="main-content-container">
            <div class="status-screen">
                <div class="status-icon finished-icon">🏁</div>
                <h2 class="status-title">Квиз завершен</h2>
                <p class="status-message">Спасибо за участие в квизе! "<span id="finished-event-name">Математический квиз</span>" завершен.</p>
                
                <div class="event-info">
                    <h4>Ваши результаты:</h4>
                    <div class="info-item">
                        <span class="info-label">Команда:</span>
                        <span class="info-value" id="finished-team-name">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Набранные баллы:</span>
                        <span class="info-value" id="finished-score">0</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Правильных ответов:</span>
                        <span class="info-value" id="finished-correct-answers">0</span>
                    </div>
                </div>
                
                <button class="refresh-btn" onclick="location.reload()">
                    🔄 Вернуться к входу
                </button>
            </div>
        </div>
    </div>

    <!-- Интерфейс квиза -->
    <div id="quiz-container">
        <div class="main-content-container">
            <div class="questions-interface">
                <!-- Заголовок мероприятия -->
                <div class="event-header">
                    <h2>Квиз: <span id="current-event-name">Математический квиз</span></h2>
                    <div class="quiz-header">
                        <div class="team-score-info">
                            Команда: <strong id="team-name-display"></strong> | 
                            Баллы: <strong id="current-score">0</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Текущий вопрос -->
                <div id="current-question" class="question-display">
                    <div class="question-phase phase-question">
                        ⏰ Время на вопрос: <span id="question-time-remaining">00</span>с
                    </div>
                    
                    <div id="selected-question-text" class="question-text"></div>
                    
                    <!-- Контейнер для изображения вопроса -->
                    <div id="selected-question-image" class="question-image-container"></div>
                    
                    <!-- Сообщение об отправленном ответе -->
                    <div id="answer-submitted-message" class="answer-submitted" style="display: none;">
                        ✅ Ваш ответ отправлен! Ожидайте результатов...
                    </div>
                    
                    <!-- Варианты ответов -->
                    <div id="answers-container" class="answers-grid">
                        <!-- Ответы будут добавляться динамически -->
                    </div>

                    <div class="question-actions">
                        <button id="submit-quiz-answer-btn" class="answer-submit-btn" disabled>
                            📨 Отправить ответ
                        </button>
                    </div>
                </div>

                <!-- Экран ожидания результатов -->
                <div id="waiting-results" class="question-display" style="display: none;">
                    <div class="waiting-for-answers">
                        <h3>⏳ Ожидание результатов</h3>
                        <p>Все участники отвечают на вопрос...</p>
                        <div class="timer-display" id="results-waiting-timer">--</div>
                    </div>
                </div>

                <!-- Экран результатов ответа -->
                <div id="answer-results" class="question-display" style="display: none;">
                    <div class="question-phase phase-answers">
                        ✅ Результаты ответа
                    </div>
                    
                    <div id="results-message" class="question-text" style="text-align: center; font-size: 1.2rem; padding: 20px;"></div>
                    
                    <div id="correct-answers-list" style="margin: 20px 0;">
                        <!-- Правильные ответы будут показаны здесь -->
                    </div>
                    
                    <div class="score-change">
                        Получено баллов: <strong id="earned-points">0</strong>
                    </div>
                </div>

                <!-- Сообщение об истечении времени -->
                <div id="time-up-message" class="time-up-message" style="display: none;">
                    <h3>⏰ Время вышло!</h3>
                    <p>Вы не успели ответить на вопрос.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log('Quiz client initialized');
        // БАЗОВЫЕ ПЕРЕМЕННЫЕ И ФУНКЦИИ
        const BASE_URL = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
        
        // Глобальные переменные для квиза
        let currentParticipant = null;
        let currentQuestion = null;
        let selectedAnswers = [];
        let questionTimer = null;
        let resultsTimer = null;
        let timeRemaining = 0;
        let answerTimeRemaining = 0;
        let eventState = null;
        let statusCheckInterval = null;
        let hasSubmittedAnswer = false;
        let lastQuestionId = null; // Добавляем для отслеживания смены вопроса
        let isAnswerPhase = false; // Флаг фазы ответов
        let lastUpdateTime = 0;
        let updateCooldown = 1000; // Минимальный интервал между обновлениями (1 секунда)
        let isUpdating = false;

        // API функции
        async function apiRequest(action, data = null) {
            const options = {
                method: data ? 'POST' : 'GET',
                headers: {'Content-Type': 'application/json'}
            };
            if (data) options.body = JSON.stringify(data);
            
            try {
                const response = await fetch(`api.php?action=${action}`, options);
                const text = await response.text();
                
                if (!text.trim()) {
                    return { error: 'Пустой ответ от сервера' };
                }
                
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError, 'Response text:', text);
                    return { error: 'Неверный ответ от сервера' };
                }
            } catch (error) {
                console.error('API Error:', error);
                return { error: 'Ошибка соединения: ' + error.message };
            }
        }

        // Управление экранами
        function showScreen(screenId) {
            document.querySelectorAll('#login-form, #waiting-screen, #finished-screen, #quiz-container').forEach(screen => {
                screen.classList.remove('active');
                screen.style.display = 'none';
            });
            
            const screen = document.getElementById(screenId);
            if (screen) {
                screen.classList.add('active');
                screen.style.display = 'block';
            }
        }

        function showQuizContainer() {
            showScreen('quiz-container');
            resetQuizInterface();
        }

        function resetQuizState() {
            hasSubmittedAnswer = false;
            isAnswerPhase = false;
            selectedAnswers = [];
            lastQuestionId = null;
            currentQuestion = null;
            
            // Очищаем таймеры
            if (questionTimer) clearInterval(questionTimer);
            if (resultsTimer) clearInterval(resultsTimer);
        }

        function resetQuizInterface() {
            document.getElementById('answer-results').style.display = 'none';
            document.getElementById('current-question').style.display = 'block';
            document.getElementById('waiting-results').style.display = 'none';
            document.getElementById('time-up-message').style.display = 'none';
            document.getElementById('answer-submitted-message').style.display = 'none';
            
            const submitBtn = document.getElementById('submit-quiz-answer-btn');
            if (submitBtn) submitBtn.disabled = true;
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            // Обработчики форм
            document.getElementById('team-login-form').addEventListener('submit', handleTeamLogin);
            document.getElementById('submit-quiz-answer-btn').addEventListener('click', submitQuizAnswer);
            
            showLoginScreen();
            
            // Запускаем проверку статуса
            setInterval(checkEventStatus, 1000);
        });

        // Обработчик входа
        async function handleTeamLogin(e) {
            e.preventDefault();

            const teamInput = document.getElementById('team-input');
            const team = teamInput?.value.trim();

            if (!team) {
                alert('Введите название команды');
                return;
            }

            const result = await apiRequest('participant-join', { team });
            
            if (result.error) {
                alert('Ошибка: ' + result.error);
                return;
            }

            currentParticipant = result.participant;
            
            // Обновляем отображение команды
            const teamElements = document.querySelectorAll('#waiting-team-name, #team-name-display, #finished-team-name');
            teamElements.forEach(el => {
                if (el) el.textContent = team;
            });
            
            showWaitingScreen();
        }

        // Функции управления экранами
        function showLoginScreen() {
            showScreen('login-form');
        }
        
        function showWaitingScreen() {
            showScreen('waiting-screen');
            resetQuizState();
        }

        async function loadEventState() {
            try {
                const result = await apiRequest('get-event-state-full');
                if (result && !result.error) {
                    eventState = result;
                    updateWaitingScreenInfo();
                    console.log('Event state loaded:', eventState);
                }
            } catch (error) {
                console.error('Error loading event state:', error);
            }
        }

        function hideAllScreens() {
            console.log('Hiding all screens');
            
            const screens = [
                'login-form',
                'waiting-screen', 
                'finished-screen',
                'quiz-container'
            ];
            
            screens.forEach(screenId => {
                const element = document.getElementById(screenId);
                if (element) {
                    element.classList.remove('active');
                    element.style.display = 'none';
                    console.log(`Hidden: ${screenId}`);
                } else {
                    console.error(`Screen element not found: ${screenId}`);
                }
            });
            
            // Сбрасываем все внутренние отображения в quiz-container
            const quizElements = [
                'answer-results',
                'current-question',
                'waiting-results',
                'time-up-message',
                'answer-submitted-message'
            ];
            
            quizElements.forEach(elementId => {
                const element = document.getElementById(elementId);
                if (element) {
                    element.style.display = 'none';
                }
            });
        }

        function updateWaitingScreenInfo() {
            if (!eventState) {
                console.log('No event state to update waiting screen');
                return;
            }
            
            console.log('Updating waiting screen with event state:', eventState);
            
            const waitingEventName = document.getElementById('waiting-event-name');
            const waitingDuration = document.getElementById('waiting-duration');
            const waitingStatus = document.getElementById('waiting-status');
            const waitingTimer = document.getElementById('waiting-timer');
            
            if (waitingEventName) {
                waitingEventName.textContent = eventState.event_name || 'Математический квиз';
            }
            
            if (waitingDuration) {
                waitingDuration.textContent = Math.floor((eventState.timer_duration || 3600) / 60) + ' минут';
            }
            
            if (waitingStatus) {
                const statusText = getStatusText(eventState.event_status);
                waitingStatus.textContent = statusText;
                waitingStatus.className = `status-${eventState.event_status}`;
            }
            
            // Обновляем таймер ожидания
            if (waitingTimer) {
                if (eventState.event_status === 'not_started' && eventState.timer_remaining) {
                    waitingTimer.textContent = formatTime(eventState.timer_remaining);
                } else if (eventState.event_status === 'running') {
                    waitingTimer.textContent = 'Идет квиз...';
                } else {
                    waitingTimer.textContent = '00:00:00';
                }
            }
        }
        
        function showFinishedScreen() {
            showScreen('finished-screen');
            if (currentParticipant) {
                document.getElementById('finished-team-name').textContent = currentParticipant.team;
                document.getElementById('finished-score').textContent = currentParticipant.score || 0;
            }
        }

        // Проверка статуса мероприятия
        async function startStatusChecking() {
            console.log('Starting status checking');
            
            // Сначала загружаем состояние мероприятия
            await loadEventState();
            
            // Затем запускаем периодическую проверку
            statusCheckInterval = setInterval(checkEventStatus, 1000);
            
            console.log('Status checking started');
        }

        async function checkEventStatus() {
            try {
                const result = await apiRequest('get-quiz-session');
                if (result && result.success) {
                    await handleQuizState(result);
                }
            } catch (error) {
                console.error('Error checking event status:', error);
            }
        }

        function updateTimerFromServer(serverTimeRemaining, phase) {
            console.log(`Server timer update: ${serverTimeRemaining}s, phase: ${phase}`);
            
            if (phase === 'question' && !hasSubmittedAnswer) {
                const timerElement = document.getElementById('question-time-remaining');
                if (timerElement) {
                    timerElement.textContent = serverTimeRemaining;
                    
                    // Обновляем цвет при малом времени
                    const phaseElement = document.getElementById('current-question')?.querySelector('.question-phase');
                    if (phaseElement) {
                        if (serverTimeRemaining <= 10) {
                            phaseElement.classList.add('warning');
                            timerElement.style.color = '#e74c3c';
                        } else {
                            phaseElement.classList.remove('warning');
                            timerElement.style.color = 'inherit';
                        }
                    }
                }
            } else if (phase === 'answers') {
                const timerElement = document.getElementById('results-waiting-timer');
                if (timerElement) {
                    timerElement.textContent = serverTimeRemaining;
                }
            }
            
            // ДОБАВЛЯЕМ: обновление таймера ожидания
            if (phase === 'question' && serverTimeRemaining !== null) {
                const waitingTimer = document.getElementById('waiting-timer');
                if (waitingTimer) {
                    waitingTimer.textContent = formatTime(serverTimeRemaining);
                }
            }
        }

        function updateStatusDisplays() {
            if (!eventState) return;
            
            // Обновляем информацию на экранах ожидания и завершения
            const eventName = eventState.event_name || 'Математический квиз';
            const waitingEventName = document.getElementById('waiting-event-name');
            const currentEventName = document.getElementById('current-event-name');
            const finishedEventName = document.getElementById('finished-event-name');
            const loginEventName = document.getElementById('login-event-name');
            
            if (waitingEventName) waitingEventName.textContent = eventName;
            if (currentEventName) currentEventName.textContent = eventName;
            if (finishedEventName) finishedEventName.textContent = eventName;
            if (loginEventName) loginEventName.textContent = eventName;
            
            const statusText = getStatusText(eventState.event_status);
            const waitingStatus = document.getElementById('waiting-status');
            if (waitingStatus) waitingStatus.textContent = statusText;
        }

        function getStatusText(status) {
            switch(status) {
                case 'running': return 'Идет';
                case 'finished': return 'Завершено';
                default: return 'Не начат';
            }
        }

        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // ЛОГИКА КВИЗА
        async function handleQuizState(state) {
            if (!currentParticipant) return;

            const eventStatus = state.stats.event_status;
            
            if (eventStatus === 'finished') {
                showFinishedScreen();
                return;
            }
            
            if (eventStatus === 'running' && state.session.is_active) {
                showQuizContainer();
                await handleCurrentQuestion(state);
            } else {
                showWaitingScreen();
            }
        }

        function correctServerTime(serverTime) {
            // Если время сервера сильно отличается, можно добавить коррекцию
            const localTime = Math.floor(Date.now() / 1000);
            const diff = serverTime - localTime;
            
            // Если разница больше 10 минут, корректируем
            if (Math.abs(diff) > 600) {
                console.warn(`Time difference detected: ${diff} seconds`);
                return localTime;
            }
            
            return serverTime;
        }

        // Новая функция для обработки текущего вопроса
        async function handleCurrentQuestion(state) {
            const session = state.session;
            const question = state.current_question;
            const timeRemaining = state.time_remaining;
            
            if (!question) {
                showWaitingScreen();
                return;
            }
            
            currentQuestion = question;
            
            // Сбрасываем состояние если вопрос изменился
            if (lastQuestionId !== question.id) {
                console.log('New question detected, resetting state');
                resetParticipantState();
                lastQuestionId = question.id;
            }
            
            if (session.phase === 'question') {
                if (!hasSubmittedAnswer) {
                    await displayQuestion(question);
                    if (timeRemaining !== null && timeRemaining !== undefined) {
                        updateTimer(timeRemaining, 'question');
                    }
                } else {
                    showWaitingForResults(timeRemaining);
                }
            } else if (session.phase === 'answers') {
                await showAnswerResultsForAll(question);
                if (timeRemaining !== null && timeRemaining !== undefined) {
                    updateTimer(timeRemaining, 'answers');
                }
            }
        }

        async function displayQuestion(question) {
            // Обновляем только если вопрос изменился
            if (lastQuestionId !== question.id) {
                document.getElementById('selected-question-text').textContent = question.question_text;
                document.getElementById('current-score').textContent = currentParticipant?.score || 0;
                
                await loadQuestionAnswers(question);
                lastQuestionId = question.id;
            }
            
            // Показываем основной интерфейс
            document.getElementById('answer-results').style.display = 'none';
            document.getElementById('current-question').style.display = 'block';
            document.getElementById('answer-submitted-message').style.display = 'none';
        }

        function showWaitingForResults(timeRemaining) {
            document.getElementById('current-question').style.display = 'none';
            document.getElementById('waiting-results').style.display = 'block';
            document.getElementById('answer-submitted-message').style.display = 'block';
            
            const timerElement = document.getElementById('results-waiting-timer');
            if (timerElement) timerElement.textContent = timeRemaining || '--';
        }

        function updateTimer(timeRemaining, phase) {
            if (phase === 'question') {
                const timerElement = document.getElementById('question-time-remaining');
                if (timerElement) {
                    timerElement.textContent = timeRemaining;
                    
                    if (timeRemaining <= 10) {
                        timerElement.style.color = '#e74c3c';
                    } else {
                        timerElement.style.color = 'inherit';
                    }
                }
            } else if (phase === 'answers') {
                const timerElement = document.getElementById('results-waiting-timer');
                if (timerElement) {
                    timerElement.textContent = timeRemaining;
                }
            }
        }

        function updateTimerDisplay(timeRemaining, phase) {
            if (phase === 'question') {
                const timerElement = document.getElementById('question-time-remaining');
                if (timerElement) timerElement.textContent = timeRemaining;
                
                if (timeRemaining <= 10) {
                    timerElement.style.color = '#e74c3c';
                } else {
                    timerElement.style.color = 'inherit';
                }
            } else if (phase === 'answers') {
                const timerElement = document.getElementById('results-waiting-timer');
                if (timerElement) timerElement.textContent = timeRemaining;
            }
        }

        function startServerBasedTimer(duration, phase) {
            clearInterval(questionTimer);
            
            let timeLeft = duration;
            updateTimerDisplay(timeLeft, phase);
            
            questionTimer = setInterval(() => {
                timeLeft--;
                updateTimerDisplay(timeLeft, phase);
                
                if (timeLeft <= 0) {
                    clearInterval(questionTimer);
                    // Автоматически проверяем состояние для перехода к следующей фазе
                    checkEventStatus();
                }
            }, 1000);
        }

        function updateTimerDisplay(timeLeft, phase) {
            if (phase === 'question') {
                const timerElement = document.getElementById('question-time-remaining');
                const phaseElement = document.getElementById('current-question')?.querySelector('.question-phase');
                
                if (timerElement) timerElement.textContent = timeLeft;
                
                if (phaseElement) {
                    if (timeLeft <= 10) {
                        phaseElement.classList.add('warning');
                        if (timerElement) timerElement.style.color = '#e74c3c';
                    } else {
                        phaseElement.classList.remove('warning');
                        if (timerElement) timerElement.style.color = 'inherit';
                    }
                }
            } else if (phase === 'answers') {
                const timerElement = document.getElementById('results-waiting-timer');
                if (timerElement) timerElement.textContent = timeLeft;
            }
        }

        function resetParticipantState() {
            console.log('Resetting participant state');
            
            hasSubmittedAnswer = false;
            selectedAnswers = [];
            
            // Сбрасываем UI элементы
            const submitBtn = document.getElementById('submit-quiz-answer-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = '📨 Отправить ответ';
            }
            
            // Сбрасываем выбор ответов
            const answersContainer = document.getElementById('answers-container');
            if (answersContainer) {
                const allInputs = answersContainer.querySelectorAll('input[type="radio"], input[type="checkbox"]');
                allInputs.forEach(input => {
                    input.checked = false;
                    const parent = input.closest('.answer-option');
                    if (parent) {
                        parent.classList.remove('selected');
                        parent.style.borderColor = '#e0e0e0';
                        parent.style.background = 'white';
                    }
                });
            }
            
            // Скрываем сообщение об отправке
            const submittedMsg = document.getElementById('answer-submitted-message');
            if (submittedMsg) submittedMsg.style.display = 'none';
            
            console.log('Participant state reset complete');
        }

        function showWaitingForResults(timeRemaining = null) {
            // Скрываем текущий вопрос
            document.getElementById('current-question').style.display = 'none';
            document.getElementById('current-question').style.opacity = '0';
            
            // Показываем контейнер ожидания
            const waitingContainer = document.getElementById('waiting-results');
            waitingContainer.style.display = 'block';
            waitingContainer.style.opacity = '1';
            
            // Создаем содержимое ожидания
            let waitingHTML = `
                <div class="waiting-for-answers" style="text-align: center; padding: 40px;">
                    <h3>⏳ Ожидание результатов</h3>
                    <p>Все участники отвечают на вопрос...</p>
            `;
            
            if (timeRemaining !== null) {
                waitingHTML += `
                    <div class="timer-display" style="font-size: 2rem; font-weight: bold; color: #3498db; margin: 30px 0;">
                        ${timeRemaining}с
                    </div>
                `;
            }
            
            waitingHTML += `</div>`;
            waitingContainer.innerHTML = waitingHTML;
            
            // Показываем сообщение об отправке
            document.getElementById('answer-submitted-message').style.display = 'block';
        }

        async function showAnswerResultsForAll(question) {
            console.log('Showing results for question:', question);
            
            // Получаем элементы
            const resultsContainer = document.getElementById('answer-results');
            const currentQuestionContainer = document.getElementById('current-question');
            
            // Скрываем вопрос и показываем результаты ВМЕСТО него
            currentQuestionContainer.style.display = 'none';
            currentQuestionContainer.style.opacity = '0';
            
            // Устанавливаем результаты на то же место
            resultsContainer.style.display = 'block';
            resultsContainer.style.opacity = '1';
            
            // Загружаем информацию о вопросе
            const questions = await apiRequest('get-quiz-questions');
            if (questions.error) {
                console.error('Error loading questions:', questions.error);
                resultsContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #7f8c8d;">Ошибка загрузки результатов</div>';
                return;
            }
            
            const questionId = question.quiz_question_id || question.id;
            const currentQ = questions.find(q => q.id === questionId);
            if (!currentQ) {
                console.error('Current question not found');
                resultsContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #7f8c8d;">Вопрос не найден</div>';
                return;
            }
            
            // Определяем результат
            let isCorrect = false;
            let earnedPoints = 0;
            let userAnswerText = '';
            
            if (hasSubmittedAnswer && selectedAnswers.length > 0) {
                // Получаем текст выбранных ответов
                const selectedAnswerTexts = [];
                selectedAnswers.forEach(answerId => {
                    const answer = currentQ.answers.find(a => a.id === answerId);
                    if (answer) {
                        selectedAnswerTexts.push(answer.answer_text);
                    }
                });
                userAnswerText = selectedAnswerTexts.join(', ');
                
                // Проверяем правильность
                const correctAnswers = currentQ.answers.filter(a => a.is_correct).map(a => a.id);
                const userAnswers = selectedAnswers;
                
                if (currentQ.question_type === 'single') {
                    isCorrect = correctAnswers.length === 1 && userAnswers.length === 1 && correctAnswers[0] === userAnswers[0];
                    if (isCorrect) {
                        earnedPoints = currentQ.answers.find(a => a.id === userAnswers[0])?.points || 1;
                    }
                } else {
                    const allCorrectSelected = correctAnswers.every(ca => userAnswers.includes(ca));
                    const noIncorrectSelected = userAnswers.every(ua => correctAnswers.includes(ua));
                    isCorrect = allCorrectSelected && noIncorrectSelected;
                    
                    if (isCorrect) {
                        earnedPoints = currentQ.answers
                            .filter(a => a.is_correct)
                            .reduce((sum, answer) => sum + (answer.points || 1), 0);
                    }
                }
                
                // Обновляем счет
                if (isCorrect && currentParticipant) {
                    currentParticipant.score = (currentParticipant.score || 0) + earnedPoints;
                    await apiRequest('update-participant-score', {
                        participant_id: currentParticipant.id,
                        score: currentParticipant.score
                    });
                }
            }
            
            // Формируем HTML для результатов
            let resultsHTML = `
                <div class="question-phase phase-answers" style="text-align: center; padding: 12px; margin: 15px 0; background: #d4edda; color: #155724; border-radius: 8px; font-weight: 600;">
                    ✅ Результаты ответа
                </div>
                
                <div class="results-content" style="padding: 20px;">
            `;
            
            if (hasSubmittedAnswer && selectedAnswers.length > 0) {
                resultsHTML += `
                    <div style="margin-bottom: 25px; text-align: center;">
                        <div style="font-size: 1.4rem; margin-bottom: 15px; font-weight: bold;">
                            ${isCorrect ? 
                                '🎉 <span style="color: #27ae60;">Правильно!</span>' : 
                                '❌ <span style="color: #e74c3c;">Неправильно!</span>'
                            }
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; border: 2px solid #e9ecef;">
                            <strong>Ваш ответ:</strong> <span style="color: #2c3e50;">${userAnswerText}</span>
                        </div>
                    </div>
                `;
            } else {
                resultsHTML += `
                    <div style="margin-bottom: 25px; text-align: center;">
                        <div style="font-size: 1.4rem; color: #e74c3c; margin-bottom: 15px; font-weight: bold;">
                            ⏰ <span>Время вышло!</span>
                        </div>
                        <p style="color: #7f8c8d;">Вы не успели ответить на вопрос</p>
                    </div>
                `;
            }
            
            // Показываем правильные ответы
            const correctAnswers = currentQ.answers.filter(a => a.is_correct);
            if (correctAnswers.length > 0) {
                resultsHTML += `
                    <div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 25px 0; border: 2px solid #c3e6cb;">
                        <h4 style="margin-top: 0; color: #155724; text-align: center; margin-bottom: 15px;">Правильные ответы:</h4>
                `;
                
                correctAnswers.forEach((answer, index) => {
                    const answerIndex = currentQ.answers.findIndex(a => a.id === answer.id);
                    resultsHTML += `
                        <div style="display: flex; justify-content: space-between; align-items: center; 
                            padding: 12px 0; border-bottom: 1px solid #c3e6cb; margin-bottom: 8px;">
                            <span style="font-weight: 500;">
                                ${String.fromCharCode(65 + answerIndex)}. ${answer.answer_text}
                            </span>
                            <span style="color: #28a745; font-weight: bold; background: white; padding: 4px 10px; border-radius: 4px;">
                                +${answer.points || 1}
                            </span>
                        </div>
                    `;
                });
                
                resultsHTML += `</div>`;
            }
            
            resultsHTML += `
                <div style="font-size: 1.4rem; text-align: center; padding: 20px; 
                    background: linear-gradient(135deg, #e8f4fd, #d4edda); 
                    border-radius: 10px; margin: 20px 0; border: 3px solid #3498db; font-weight: bold;">
                    Получено баллов: <strong style="color: #3498db; font-size: 1.6rem;">${earnedPoints}</strong>
                </div>
                </div>
            `;
            
            resultsContainer.innerHTML = resultsHTML;
            
            // Обновляем общий счет в заголовке
            document.getElementById('current-score').textContent = currentParticipant?.score || 0;
            
            console.log('Results displayed successfully');
        }

        function startAutoWaitTimer() {
            let waitTime = 10;
            const timerElement = document.getElementById('auto-wait-timer');
            
            const waitInterval = setInterval(() => {
                waitTime--;
                
                if (timerElement) {
                    timerElement.textContent = waitTime;
                }
                
                if (waitTime <= 0) {
                    clearInterval(waitInterval);
                    checkEventStatus();
                }
            }, 1000);
        }

        async function loadCurrentQuestion() {
            const result = await apiRequest('get-current-question');
            
            if (result.success && result.current_question) {
                currentQuestion = result.current_question;
                showQuizContainer();
                displayQuizQuestion(currentQuestion);
                
                if (currentQuestion.phase === 'question') {
                    startQuestionTimer(currentQuestion.question_time);
                } else if (currentQuestion.phase === 'answers') {
                    // Если фаза ответов, сразу показываем результаты
                    showAnswerResultsForAll();
                }
            } else {
                showWaitingScreen();
            }
        }

        async function displayQuizQuestion(question) {
            console.log('Displaying question:', question);
            
            // Обновляем текст вопроса
            const questionText = document.getElementById('selected-question-text');
            if (questionText) questionText.textContent = question.question_text;
            
            // Обновляем счет
            const currentScore = document.getElementById('current-score');
            if (currentScore) currentScore.textContent = currentParticipant?.score || 0;
            
            // Показываем основной интерфейс
            document.getElementById('answer-results').style.display = 'none';
            document.getElementById('current-question').style.display = 'block';
            document.getElementById('time-up-message').style.display = 'none';
            document.getElementById('waiting-results').style.display = 'none';
            document.getElementById('answer-submitted-message').style.display = 'none';
            
            // Загружаем ответы
            await loadQuestionAnswers(question);
        }

        async function loadQuestionAnswers(question) {
            const answersContainer = document.getElementById('answers-container');
            if (!answersContainer) return;
            
            answersContainer.innerHTML = '';
            selectedAnswers = [];
            
            // Создаем заголовок для контейнера
            const header = document.createElement('h3');
            header.textContent = 'Выберите ответ:';
            header.style.marginBottom = '15px';
            header.style.color = '#2c3e50';
            answersContainer.appendChild(header);
            
            if (!question) {
                const errorMsg = document.createElement('p');
                errorMsg.textContent = 'Вопрос не загружен';
                errorMsg.style.textAlign = 'center';
                errorMsg.style.color = '#7f8c8d';
                answersContainer.appendChild(errorMsg);
                return;
            }
            
            // Загружаем полный список вопросов с ответами
            const questionsResult = await apiRequest('get-quiz-questions');
            if (questionsResult.error) {
                const errorMsg = document.createElement('p');
                errorMsg.textContent = 'Ошибка загрузки вопросов';
                errorMsg.style.textAlign = 'center';
                errorMsg.style.color = '#7f8c8d';
                answersContainer.appendChild(errorMsg);
                return;
            }
            
            const questionId = question.quiz_question_id || question.id;
            const fullQuestion = questionsResult.find(q => q.id === questionId);
            if (!fullQuestion) {
                const errorMsg = document.createElement('p');
                errorMsg.textContent = 'Вопрос не найден';
                errorMsg.style.textAlign = 'center';
                errorMsg.style.color = '#7f8c8d';
                answersContainer.appendChild(errorMsg);
                return;
            }
            
            if (fullQuestion.answers && fullQuestion.answers.length > 0) {
                fullQuestion.answers.forEach((answer, index) => {
                    const answerElement = document.createElement('div');
                    answerElement.className = 'answer-option';
                    answerElement.style.padding = '15px';
                    answerElement.style.border = '2px solid #e0e0e0';
                    answerElement.style.borderRadius = '8px';
                    answerElement.style.cursor = 'pointer';
                    answerElement.style.transition = 'all 0.3s';
                    answerElement.style.background = 'white';
                    answerElement.style.display = 'flex';
                    answerElement.style.alignItems = 'center';
                    answerElement.style.marginBottom = '10px';
                    
                    answerElement.innerHTML = `
                        <input type="${fullQuestion.question_type === 'single' ? 'radio' : 'checkbox'}" 
                            name="quiz-answer" 
                            value="${answer.id}"
                            id="answer-${answer.id}"
                            style="margin-right: 12px; transform: scale(1.2);">
                        <label for="answer-${answer.id}" style="cursor: pointer; flex: 1; margin: 0;">
                            <span style="font-weight: 600; margin-right: 8px;">${String.fromCharCode(65 + index)}.</span>
                            ${answer.answer_text}
                        </label>
                    `;
                    
                    const input = answerElement.querySelector('input');
                    input.addEventListener('change', (e) => {
                        handleAnswerSelection(e, fullQuestion.question_type);
                    });
                    
                    answerElement.addEventListener('click', (e) => {
                        if (e.target.tagName !== 'INPUT') {
                            input.checked = !input.checked;
                            const event = new Event('change');
                            input.dispatchEvent(event);
                        }
                    });
                    
                    answersContainer.appendChild(answerElement);
                });
            } else {
                const noAnswersMsg = document.createElement('p');
                noAnswersMsg.textContent = 'Нет вариантов ответов';
                noAnswersMsg.style.textAlign = 'center';
                noAnswersMsg.style.color = '#7f8c8d';
                answersContainer.appendChild(noAnswersMsg);
            }
            
            const submitBtn = document.getElementById('submit-quiz-answer-btn');
            if (submitBtn) submitBtn.disabled = true;
        }

        function handleAnswerSelection(e, questionType) {
            const answerId = parseInt(e.target.value);
            
            if (questionType === 'single') {
                // Снимаем выделение с других вариантов
                document.querySelectorAll('input[name="quiz-answer"]').forEach(inp => {
                    inp.parentElement.classList.remove('selected');
                });
                selectedAnswers = [answerId];
                e.target.parentElement.classList.add('selected');
            } else {
                if (e.target.checked) {
                    selectedAnswers.push(answerId);
                    e.target.parentElement.classList.add('selected');
                } else {
                    selectedAnswers = selectedAnswers.filter(id => id !== answerId);
                    e.target.parentElement.classList.remove('selected');
                }
            }
            
            // ИСПРАВЛЕНИЕ: кнопка должна оставаться активной при выборе ответа
            const submitBtn = document.getElementById('submit-quiz-answer-btn');
            if (submitBtn) {
                submitBtn.disabled = selectedAnswers.length === 0;
                console.log('Selected answers:', selectedAnswers, 'Button disabled:', submitBtn.disabled);
            }
        }

        function startQuestionTimer(duration) {
            clearInterval(questionTimer);
            timeRemaining = duration;
            updateQuizTimerDisplay();
            
            questionTimer = setInterval(() => {
                timeRemaining--;
                updateQuizTimerDisplay();
                
                if (timeRemaining <= 0) {
                    clearInterval(questionTimer);
                    // Время вышло - либо отправляем ответ, либо показываем сообщение
                    if (hasSubmittedAnswer) {
                        // Если ответ уже отправлен, ждем результатов
                        showWaitingForResults();
                    } else if (selectedAnswers.length > 0) {
                        // Если выбран ответ, но не отправлен - автоматически отправляем
                        submitQuizAnswer();
                    } else {
                        // Если ответ не выбран - показываем сообщение об истечении времени
                        showTimeUpMessage();
                    }
                }
            }, 1000);
        }

        function updateQuizTimerDisplay() {
            const timerElement = document.getElementById('question-time-remaining');
            const phaseElement = document.getElementById('current-question')?.querySelector('.question-phase');
            
            if (timerElement) timerElement.textContent = timeRemaining;
            
            if (phaseElement) {
                if (timeRemaining <= 10) {
                    phaseElement.classList.add('warning');
                    if (timerElement) timerElement.style.color = '#e74c3c';
                } else {
                    phaseElement.classList.remove('warning');
                    if (timerElement) timerElement.style.color = 'inherit';
                }
            }
        }

        async function handleQuizAnswerSubmit() {
            await submitQuizAnswer();
        }

        async function submitQuizAnswer() {
            if (hasSubmittedAnswer) return;
            
            if (!currentQuestion || (!currentQuestion.id && !currentQuestion.quiz_question_id)) {
                alert('Ошибка: вопрос не загружен');
                return;
            }
            
            if (!selectedAnswers.length) {
                alert('Выберите вариант ответа');
                return;
            }

            const questionId = currentQuestion.quiz_question_id || currentQuestion.id;
            
            console.log('Submitting answer:', {
                participant_id: currentParticipant.id,
                quiz_question_id: questionId,
                quiz_answer_ids: selectedAnswers
            });

            const result = await apiRequest('submit-quiz-answer', {
                participant_id: currentParticipant.id,
                quiz_question_id: questionId,
                quiz_answer_ids: selectedAnswers
            });
            
            if (result.success) {
                hasSubmittedAnswer = true;
                document.getElementById('answer-submitted-message').style.display = 'block';
                document.getElementById('submit-quiz-answer-btn').disabled = true;
                console.log('Answer submitted successfully');
                
                // СРАЗУ показываем ожидание результатов
                showWaitingForResults();
            } else {
                alert('Ошибка при отправке ответа: ' + result.error);
            }
        }

        function showWaitingForResults() {
            document.getElementById('current-question').style.display = 'none';
            document.getElementById('waiting-results').style.display = 'block';
            document.getElementById('answer-submitted-message').style.display = 'block';
        }

        function startResultsTimer() {
            clearInterval(resultsTimer);
            answerTimeRemaining = currentQuestion.answer_time || 10;
            updateResultsTimerDisplay();
            
            resultsTimer = setInterval(() => {
                answerTimeRemaining--;
                updateResultsTimerDisplay();
                
                if (answerTimeRemaining <= 0) {
                    clearInterval(resultsTimer);
                    // Загружаем и показываем результаты
                    showAnswerResultsForAll();
                }
            }, 1000);
        }

        function updateResultsTimerDisplay() {
            const timerElement = document.getElementById('results-waiting-timer');
            if (timerElement) timerElement.textContent = answerTimeRemaining;
        }

        async function showAnswerResultsForAll() {
            // Загружаем информацию о правильных ответах
            const questions = await apiRequest('get-quiz-questions');
            if (questions.error) {
                console.error('Error loading questions:', questions.error);
                return;
            }
            
            const currentQ = questions.find(q => q.id === currentQuestion.id);
            if (!currentQ) {
                console.error('Current question not found');
                return;
            }
            
            // Показываем экран результатов
            document.getElementById('waiting-results').style.display = 'none';
            document.getElementById('answer-results').style.display = 'block';
            
            // Определяем, был ли ответ правильным
            let isCorrect = false;
            let earnedPoints = 0;
            
            if (hasSubmittedAnswer) {
                // Проверяем правильность ответа
                const correctAnswers = currentQ.answers.filter(a => a.is_correct).map(a => a.id);
                const userAnswers = selectedAnswers;
                
                if (currentQuestion.question_type === 'single') {
                    isCorrect = correctAnswers.length === 1 && userAnswers.length === 1 && correctAnswers[0] === userAnswers[0];
                } else {
                    // Для множественного выбора - все правильные должны быть выбраны и никаких неправильных
                    const allCorrectSelected = correctAnswers.every(ca => userAnswers.includes(ca));
                    const noIncorrectSelected = userAnswers.every(ua => correctAnswers.includes(ua));
                    isCorrect = allCorrectSelected && noIncorrectSelected;
                }
                
                // Начисляем баллы
                if (isCorrect) {
                    earnedPoints = currentQ.answers.find(a => a.id === userAnswers[0])?.points || 1;
                    if (currentParticipant) {
                        currentParticipant.score = (currentParticipant.score || 0) + earnedPoints;
                    }
                }
            }
            
            // Показываем сообщение о результате
            const resultsMessage = document.getElementById('results-message');
            if (resultsMessage) {
                if (hasSubmittedAnswer) {
                    if (isCorrect) {
                        resultsMessage.innerHTML = `🎉 <strong>Правильно!</strong> Вы ответили верно!`;
                        resultsMessage.style.color = '#27ae60';
                    } else {
                        resultsMessage.innerHTML = `❌ <strong>Неправильно!</strong> Попробуйте в следующий раз!`;
                        resultsMessage.style.color = '#e74c3c';
                    }
                } else {
                    resultsMessage.innerHTML = `⏰ <strong>Время вышло!</strong> Вы не успели ответить.`;
                    resultsMessage.style.color = '#e74c3c';
                }
            }
            
            const earnedPointsEl = document.getElementById('earned-points');
            if (earnedPointsEl) earnedPointsEl.textContent = earnedPoints;
            
            // Обновляем счет
            const currentScore = document.getElementById('current-score');
            if (currentScore) currentScore.textContent = currentParticipant?.score || 0;
            
            // Показываем правильные ответы
            const correctAnswersContainer = document.getElementById('correct-answers-list');
            if (correctAnswersContainer) {
                correctAnswersContainer.innerHTML = '<h4 style="margin-bottom: 15px; color: #2c3e50;">Правильные ответы:</h4>';
                
                currentQ.answers.forEach((answer, index) => {
                    if (answer.is_correct) {
                        const correctAnswer = document.createElement('div');
                        correctAnswer.className = 'correct-answer-item';
                        correctAnswer.innerHTML = `
                            <span>${String.fromCharCode(65 + index)}. ${answer.answer_text}</span>
                            <span>+${answer.points} баллов</span>
                        `;
                        correctAnswersContainer.appendChild(correctAnswer);
                    }
                });
            }
        }

        function showTimeUpMessage() {
            document.getElementById('current-question').style.display = 'none';
            document.getElementById('answer-results').style.display = 'none';
            document.getElementById('time-up-message').style.display = 'block';
            
            // Автоматически возвращаем на экран ожидания через 3 секунды
            setTimeout(() => {
                showWaitingScreen();
            }, 3000);
        }
    </script>
</body>
</html>