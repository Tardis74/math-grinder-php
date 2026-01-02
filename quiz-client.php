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
        /* ДОПОЛНИТЕЛЬНЫЕ СТИЛИ ДЛЯ КВИЗА - ИСПРАВЛЕНИЯ */
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

        #quiz-container {
            width: 100%;
            min-height: 100vh;
            display: none;
            position: relative;
        }

        #quiz-container.active {
            display: block;
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

        /* ИСПРАВЛЕНИЕ: Основной контент должен растягиваться */
        .main-content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: 100vh;
            width: 100%;
            box-sizing: border-box;
            display: block; /* Важно: block вместо flex */
        }

        /* ИСПРАВЛЕНИЕ: Контейнер вопросов должен быть растягиваемым */
        .questions-interface {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 40px;
            min-height: auto !important; /* Убираем фиксированную высоту */
        }

        #current-question,
        #answer-results,
        #waiting-results,
        #time-up-message {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            display: none;
        }

        #current-question:not([style*="display: none"]),
        #answer-results:not([style*="display: none"]),
        #waiting-results:not([style*="display: none"]),
        #time-up-message:not([style*="display: none"]) {
            display: block !important;
        }

        #current-question.active,
        #answer-results.active,
        #waiting-results.active {
            display: block;
            opacity: 1;
        }

        /* ИСПРАВЛЕНИЕ: Контейнер для изображения с прокруткой если нужно */
        .question-image-container {
            margin: 15px 0;
            text-align: center;
            max-width: 100%;
            max-height: 400px;
            overflow: hidden;
            border-radius: 8px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .question-image-display {
            max-width: 100%;
            max-height: 400px; /* Увеличиваем максимальную высоту */
            border-radius: 12px;
            border: 3px solid #3498db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin: 15px auto;
            display: block;
            object-fit: contain;
            background: #f8f9fa;
        }

        .question-image,
        .question-image-display,
        .question-image-container img {
            max-width: 100%;
            max-height: 380px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 6px;
            display: block;
        }

        /* ИСПРАВЛЕНИЕ: Текст вопроса должен переноситься */
        .question-text {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #2c3e50;
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            margin: 15px 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }

        /* ИСПРАВЛЕНИЕ: Сетка ответов */
        .answers-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin: 20px 0;
            max-width: 100%;
        }

        .answer-option {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            max-width: 100%;
            box-sizing: border-box;
            word-wrap: break-word;
        }

        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
            flex-wrap: wrap; /* Разрешаем перенос */
        }

        .team-score-info {
            font-size: 1.1rem;
            color: #2c3e50;
            word-wrap: break-word;
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
            width: 100%;
            box-sizing: border-box;
        }

        .waiting-for-answers {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #ffeaa7;
            width: 100%;
            box-sizing: border-box;
        }

        .answer-submitted {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 15px 0;
            border: 2px solid #c3e6cb;
            width: 100%;
            box-sizing: border-box;
        }

        #waiting-screen .main-content-container,
        #finished-screen .main-content-container,
        #login-form.active .main-content-container {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 100vh;
        }

        #quiz-container .questions-interface {
            position: relative;
            min-height: 600px; /* Задайте достаточную высоту */
        }

        #current-question,
        #answer-results,
        #waiting-results,
        #time-up-message {
            position: absolute; /* Элементы займут одно и то же место */
            top: 0;
            left: 0;
            width: 100%;
            height: auto;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 1;
            margin: 0; /* Убираем вертикальные отступы */
        }

        #current-question.active,
        #answer-results.active,
        #waiting-results.active,
        #time-up-message.active {
            opacity: 1;
            visibility: visible;
            z-index: 2;
        }

        /* ИСПРАВЛЕНИЕ: Для очень больших изображений */
        @media (max-width: 768px) {
            .main-content-container {
                padding: 20px 10px;
            }
            
            .questions-interface {
                padding: 20px 15px;
            }
            
            .question-display {
                padding: 20px;
            }
            
            .question-image-display {
                max-height: 300px;
            }
            
            .quiz-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
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
                    <div class="question-phase phase-question" style="text-align: center; padding: 12px; margin: 15px 0; border-radius: 8px; font-weight: 600; transition: all 0.3s;">
                        ⏰ Время на вопрос: <span id="question-time-remaining" style="font-weight: bold; font-family: 'Courier New', monospace;">--</span>с
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
                        <div class="timer-display" style="font-size: 2rem; font-weight: bold; margin: 20px 0;">
                            Осталось: <span id="results-waiting-timer" style="font-family: 'Courier New', monospace;">--</span>с
                        </div>
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
        let timerInterval = null;

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

        function startClientTimer(duration, phase) {
            clearInterval(timerInterval);
            
            let timeLeft = duration;
            updateTimer(timeLeft, phase);
            
            timerInterval = setInterval(() => {
                timeLeft--;
                
                if (timeLeft >= 0) {
                    updateTimer(timeLeft, phase);
                }
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    // Проверяем состояние после истечения времени
                    setTimeout(() => checkEventStatus(), 500);
                }
            }, 1000);
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

            await loadEventState();

            const result = await apiRequest('quiz-participant-join', { team });
            
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
                const result = await apiRequest('get-quiz-event-state');
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
                    const timeRemaining = result.time_remaining;
                    const currentPhase = result.session?.phase;
                    
                    console.log('DEBUG: checkEventStatus:', { 
                        timeRemaining, 
                        currentPhase, 
                        hasSubmittedAnswer,
                        selectedAnswers 
                    });
                    
                    await handleQuizState(result);
                    
                    // Если участник уже отправил ответ и мы в фазе вопроса
                    if (hasSubmittedAnswer && currentPhase === 'question' && timeRemaining > 0) {
                        // Обновляем таймер на экране ожидания
                        const timerElement = document.getElementById('results-waiting-timer');
                        if (timerElement) {
                            timerElement.textContent = timeRemaining;
                            
                            if (timeRemaining <= 10) {
                                timerElement.style.color = '#e74c3c';
                            } else {
                                timerElement.style.color = '#3498db';
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error checking event status:', error);
            }
        }

        function saveCurrentSelections() {
            const selections = {
                questionId: lastQuestionId,
                answers: [],
                questionType: null
            };
            
            // Получаем тип вопроса
            const questionTypeInput = document.querySelector('#answers-container input[name="quiz-answer"]');
            if (questionTypeInput) {
                selections.questionType = questionTypeInput.type === 'radio' ? 'single' : 'multiple';
            }
            
            // Сохраняем выбранные ответы
            if (document.getElementById('answers-container')) {
                const inputs = document.querySelectorAll('#answers-container input:checked');
                inputs.forEach(input => {
                    selections.answers.push({
                        id: parseInt(input.value),
                        checked: input.checked,
                        elementId: input.id
                    });
                });
            }
            
            console.log('DEBUG: Saved selections:', selections);
            return selections;
        }

        function restoreSelections(selections) {
            if (!selections || selections.questionId !== lastQuestionId) {
                return;
            }
            
            console.log('DEBUG: Restoring selections:', selections);
            
            // Восстанавливаем выбранные ответы
            selectedAnswers = selections.answers.map(item => item.id);
            
            // Восстанавливаем визуальное состояние
            selections.answers.forEach(item => {
                const input = document.getElementById(item.elementId) || 
                            document.querySelector(`#answers-container input[value="${item.id}"]`);
                
                if (input) {
                    input.checked = item.checked;
                    const option = input.closest('.answer-option');
                    if (option) {
                        if (item.checked) {
                            option.classList.add('selected');
                            option.style.borderColor = '#27ae60';
                            option.style.background = '#d4edda';
                        } else {
                            option.classList.remove('selected');
                            option.style.borderColor = '#e0e0e0';
                            option.style.background = 'white';
                        }
                    }
                }
            });
            
            // Активируем кнопку отправки
            const submitBtn = document.getElementById('submit-quiz-answer-btn');
            if (submitBtn) {
                submitBtn.disabled = selectedAnswers.length === 0;
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
            const currentPhase = session.phase;
            
            console.log('DEBUG: handleCurrentQuestion:', {
                sessionPhase: currentPhase,
                timeRemaining: timeRemaining,
                hasSubmittedAnswer: hasSubmittedAnswer
            });
            
            if (!question) {
                showWaitingScreen();
                return;
            }
            
            currentQuestion = question;
            
            // Проверяем, изменился ли вопрос
            const questionId = question.id || question.quiz_question_id;
            const questionChanged = lastQuestionId !== questionId;
            
            if (questionChanged) {
                console.log('New question detected, resetting state');
                resetParticipantState();
                lastQuestionId = questionId;
            }
            
            // Обрабатываем текущую фазу
            if (currentPhase === 'question') {
                if (!hasSubmittedAnswer) {
                    // Загружаем ответы только если вопрос изменился
                    if (questionChanged) {
                        await displayQuestion(question);
                    }
                    
                    // Обновляем таймер на экране вопроса
                    if (timeRemaining !== null && timeRemaining !== undefined) {
                        updateQuestionTimer(timeRemaining);
                    }
                } else {
                    // Участник уже отправил ответ - показываем ожидание
                    showWaitingForResults(timeRemaining);
                }
                
            } else if (currentPhase === 'answers') {
                // Переходим к результатам
                await showAnswerResultsForAll(question);
            }
        }

        function updateQuestionTimer(timeRemaining) {
            const timerElement = document.getElementById('question-time-remaining');
            const phaseElement = document.querySelector('.question-phase');
            
            if (timerElement) {
                timerElement.textContent = timeRemaining;
                
                // Меняем цвет при малом времени
                if (timeRemaining <= 10) {
                    timerElement.style.color = '#e74c3c';
                    if (phaseElement) {
                        phaseElement.style.background = '#f8d7da';
                        phaseElement.style.color = '#721c24';
                    }
                } else {
                    timerElement.style.color = '#3498db';
                    if (phaseElement) {
                        phaseElement.style.background = '#e8f4fd';
                        phaseElement.style.color = '#2980b9';
                    }
                }
            }
        }

        async function displayQuestion(question) {
            console.log('DEBUG: Display question called', question);
            
            const questionId = question.id || question.quiz_question_id;
            
            // Обновляем текст вопроса и счет
            document.getElementById('selected-question-text').textContent = question.question_text;
            document.getElementById('current-score').textContent = currentParticipant?.score || 0;
            
            // Очищаем контейнер для изображения
            const imageContainer = document.getElementById('selected-question-image');
            if (imageContainer) {
                imageContainer.innerHTML = '';
                
                // Добавляем изображение если есть
                if (question.image_path) {
                    const timestamp = new Date().getTime();
                    const imageUrl = BASE_URL + question.image_path + '?t=' + timestamp;
                    
                    const img = document.createElement('img');
                    img.src = imageUrl;
                    img.className = 'question-image';
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '300px';
                    img.style.borderRadius = '8px';
                    img.style.margin = '15px auto';
                    img.style.display = 'block';
                    img.style.border = '2px solid #e0e0e0';
                    img.alt = 'Изображение вопроса';
                    img.onerror = function() {
                        this.style.display = 'none';
                    };
                    
                    imageContainer.appendChild(img);
                }
            }
            
            // Загружаем ответы только если вопрос изменился
            await loadQuestionAnswers(question);
            
            // Показываем основной интерфейс
            document.getElementById('answer-results').style.display = 'none';
            document.getElementById('current-question').style.display = 'block';
            document.getElementById('answer-submitted-message').style.display = 'none';
            
            console.log('DEBUG: Question displayed, lastQuestionId set to:', lastQuestionId);
        }

        function restoreSelectedAnswers(questionType) {
            console.log('DEBUG: restoreSelectedAnswers called:', { selectedAnswers, questionType });
            
            if (!selectedAnswers.length) return;
            
            const answersContainer = document.getElementById('answers-container');
            if (!answersContainer) return;
            
            // Для radio выбираем только первый
            if (questionType === 'single') {
                const firstAnswerId = selectedAnswers[0];
                const input = document.querySelector(`#answers-container input[value="${firstAnswerId}"]`);
                if (input) {
                    input.checked = true;
                    const parent = input.closest('.answer-option');
                    if (parent) {
                        parent.classList.add('selected');
                    }
                }
            } else {
                // Для checkbox выбираем все
                selectedAnswers.forEach(answerId => {
                    const input = document.querySelector(`#answers-container input[value="${answerId}"]`);
                    if (input) {
                        input.checked = true;
                        const parent = input.closest('.answer-option');
                        if (parent) {
                            parent.classList.add('selected');
                            parent.style.borderColor = '#27ae60';
                            parent.style.background = '#d4edda';
                        }
                    }
                });
            }
        }

        function showWaitingForResults(timeRemaining) {
            document.getElementById('current-question').style.display = 'none';
            document.getElementById('waiting-results').style.display = 'block';
            document.getElementById('answer-submitted-message').style.display = 'block';
            
            const timerElement = document.getElementById('results-waiting-timer');
            if (timerElement) timerElement.textContent = timeRemaining || '--';
        }

        function updateTimer(timeRemaining, phase) {
            console.log('DEBUG: updateTimer called:', { timeRemaining, phase });
            
            if (phase === 'question') {
                const timerElement = document.getElementById('question-time-remaining');
                const phaseElement = document.querySelector('.question-phase');
                
                if (timerElement && timeRemaining !== null && timeRemaining !== undefined) {
                    timerElement.textContent = timeRemaining;
                    
                    // Добавляем предупреждение при малом времени
                    if (timeRemaining <= 10) {
                        timerElement.style.color = '#e74c3c';
                        if (phaseElement) {
                            phaseElement.style.background = '#f8d7da';
                            phaseElement.style.color = '#721c24';
                        }
                    } else {
                        timerElement.style.color = '#3498db';
                        if (phaseElement) {
                            phaseElement.style.background = '#e8f4fd';
                            phaseElement.style.color = '#2980b9';
                        }
                    }
                } else {
                    console.warn('DEBUG: Timer element not found or invalid time:', timerElement, timeRemaining);
                }
                
            } else if (phase === 'answers') {
                const timerElement = document.getElementById('results-waiting-timer');
                if (timerElement && timeRemaining !== null && timeRemaining !== undefined) {
                    timerElement.textContent = timeRemaining;
                    
                    if (timeRemaining <= 5) {
                        timerElement.style.color = '#e74c3c';
                    } else {
                        timerElement.style.color = '#3498db';
                    }
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
            console.log('DEBUG: Resetting participant state');
            
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
            if (submittedMsg) {
                submittedMsg.style.display = 'none';
                submittedMsg.textContent = '';
            }
            
            console.log('DEBUG: Participant state reset complete');
        }

        function showWaitingForResults(timeRemaining = null) {
            console.log('DEBUG: showWaitingForResults called, timeRemaining:', timeRemaining);
            
            // Скрываем текущий вопрос
            const currentQuestion = document.getElementById('current-question');
            if (currentQuestion) {
                currentQuestion.style.display = 'none';
            }
            
            // Показываем контейнер ожидания
            const waitingContainer = document.getElementById('waiting-results');
            if (waitingContainer) {
                waitingContainer.style.display = 'block';
                
                // Форматируем начальное отображение таймера
                let timerDisplay = '--';
                if (timeRemaining !== null && timeRemaining !== undefined && timeRemaining > 0) {
                    timerDisplay = timeRemaining;
                }
                
                waitingContainer.innerHTML = `
                    <div class="waiting-for-answers" style="text-align: center; padding: 40px;">
                        <div style="font-size: 3rem; margin-bottom: 20px;">⏳</div>
                        <h3 style="color: #2c3e50; margin-bottom: 15px;">Ожидание результатов</h3>
                        <p style="color: #7f8c8d; margin-bottom: 20px; font-size: 1.1rem;">
                            Ваш ответ отправлен. Ожидайте, пока все участники ответят...
                        </p>
                        <div class="timer-display" style="font-size: 2rem; font-weight: bold; margin: 20px 0;">
                            Осталось: <span id="results-waiting-timer" style="font-family: 'Courier New', monospace;">${timerDisplay}</span>с
                        </div>
                        <p style="color: #95a5a6; font-size: 0.9rem; margin-top: 20px;">
                            Вы увидите правильные ответы после окончания времени
                        </p>
                    </div>
                `;
                
                // Запускаем обновление таймера, если он есть
                if (timeRemaining !== null && timeRemaining !== undefined && timeRemaining > 0) {
                    startWaitingTimer(timeRemaining);
                }
            }
            
            // Показываем сообщение об отправке
            const submittedMsg = document.getElementById('answer-submitted-message');
            if (submittedMsg) {
                submittedMsg.style.display = 'block';
                submittedMsg.innerHTML = '✅ Ваш ответ отправлен! Ожидайте результатов...';
            }
        }

        function startWaitingTimer(initialTime) {
            let timeLeft = initialTime;
            const timerElement = document.getElementById('results-waiting-timer'); // Исправлено!
            
            if (!timerElement) {
                console.error('DEBUG: Timer element not found!');
                return;
            }
            
            const timerInterval = setInterval(() => {
                if (timeLeft > 0) {
                    timerElement.textContent = timeLeft;
                    
                    // Меняем цвет при малом времени
                    if (timeLeft <= 10) {
                        timerElement.style.color = '#e74c3c';
                    } else {
                        timerElement.style.color = '#3498db';
                    }
                    
                    timeLeft--;
                } else {
                    clearInterval(timerInterval);
                    timerElement.textContent = '0';
                    timerElement.style.color = '#e74c3c';
                    
                    // Автоматически проверяем статус, когда время вышло
                    setTimeout(() => {
                        checkEventStatus();
                    }, 1000);
                }
            }, 1000);
        }

        function updateWaitingTimer(initialTime) {
            let timeLeft = initialTime;
            const waitingContainer = document.getElementById('waiting-results');
            
            if (!waitingContainer) return;
            
            const updateTimerDisplay = () => {
                if (timeLeft > 0) {
                    const timerElement = waitingContainer.querySelector('.waiting-for-answers div');
                    if (timerElement) {
                        timerElement.textContent = `${timeLeft}с`;
                        
                        // Меняем цвет при малом времени
                        if (timeLeft <= 10) {
                            timerElement.style.color = '#e74c3c';
                        } else {
                            timerElement.style.color = '#3498db';
                        }
                    }
                    timeLeft--;
                    setTimeout(updateTimerDisplay, 1000);
                }
            };
            
            updateTimerDisplay();
        }

        async function showAnswerResultsForAll(question) {
            console.log('DEBUG: showAnswerResultsForAll called with:', question);
            
            // Получаем элементы
            const resultsContainer = document.getElementById('answer-results');
            const currentQuestionContainer = document.getElementById('current-question');
            
            if (!resultsContainer || !currentQuestionContainer) {
                console.error('DEBUG: Required containers not found');
                return;
            }
            
            // Скрываем вопрос, показываем результаты
            hideElement('current-question');
            hideElement('waiting-results');
            showElement('answer-results');
            if (!resultsContainer) {
                console.error('DEBUG: Results container not found');
                return;
            }
            
            // Проверяем, не загружены ли уже результаты для этого вопроса
            const questionId = question.quiz_question_id || question.id;
            if (window.lastRenderedQuestionId === questionId && resultsContainer.innerHTML.trim() !== '') {
                console.log('DEBUG: Results already rendered for this question');
                return; // Результаты уже отображены, не перерисовываем
            }
            
            window.lastRenderedQuestionId = questionId;


            
            // Загружаем информацию о правильных ответах
            const questionsResult = await apiRequest('get-quiz-questions');
            if (questionsResult.error) {
                console.error('DEBUG: Error loading questions:', questionsResult.error);
                resultsContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #7f8c8d;">Ошибка загрузки результатов</div>';
                return;
            }
            
            // Находим текущий вопрос
            let currentQ = null;
            let answers = [];
            
            // Ищем вопрос в результате
            if (Array.isArray(questionsResult)) {
                currentQ = questionsResult.find(q => q.id === questionId);
            } else {
                currentQ = questionsResult;
            }
            
            if (!currentQ) {
                console.error('DEBUG: Current question not found');
                resultsContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: #7f8c8d;">Вопрос не найден</div>';
                return;
            }
            
            // ПАРСИМ ответы
            if (currentQ.answers && Array.isArray(currentQ.answers)) {
                answers = currentQ.answers;
            } else if (typeof currentQ.answers === 'string' && currentQ.answers.includes('|')) {
                // Парсим строку вида "21|1|0|0|1;;22|2|0|0|2"
                const answerStrings = currentQ.answers.split(';;');
                answers = answerStrings.map(str => {
                    const parts = str.split('|');
                    return {
                        id: parseInt(parts[0]),
                        answer_text: parts[1],
                        is_correct: parseInt(parts[2]) === 1,
                        points: parseInt(parts[3]) || 0,
                        display_order: parseInt(parts[4]) || 0
                    };
                });
            }
            
            console.log('DEBUG: Parsed answers:', answers);
            
            // Определяем результат участника
            let isCorrect = false;
            let earnedPoints = 0;
            let userAnswerText = '';
            
            if (hasSubmittedAnswer && selectedAnswers.length > 0) {
                // Получаем текст выбранных ответов
                const selectedAnswerTexts = [];
                selectedAnswers.forEach(answerId => {
                    const answer = answers.find(a => a.id === answerId);
                    if (answer) {
                        selectedAnswerTexts.push(answer.answer_text);
                    }
                });
                userAnswerText = selectedAnswerTexts.join(', ');
                
                // Проверяем правильность
                const correctAnswers = answers.filter(a => a.is_correct).map(a => a.id);
                const userAnswers = selectedAnswers;
                
                if (currentQ.question_type === 'single') {
                    isCorrect = correctAnswers.length === 1 && 
                                userAnswers.length === 1 && 
                                correctAnswers[0] === userAnswers[0];
                    if (isCorrect) {
                        const correctAnswer = answers.find(a => a.id === userAnswers[0]);
                        earnedPoints = correctAnswer?.points || 1;
                    }
                } else {
                    // Исправленная логика для множественного выбора
                    const allCorrectSelected = correctAnswers.every(ca => userAnswers.includes(ca));
                    const noIncorrectSelected = userAnswers.every(ua => correctAnswers.includes(ua));
                    isCorrect = allCorrectSelected && noIncorrectSelected;
                    
                    // Считаем баллы для множественного выбора
                    if (userAnswers.length > 0) {
                        userAnswers.forEach(answerId => {
                            const answer = answers.find(a => a.id === answerId);
                            if (answer) {
                                earnedPoints += answer.points || 0;
                            }
                        });
                    }
                }
            }
            
            // Формируем HTML для результатов
            let resultsHTML = `
                <div class="question-phase phase-answers" style="text-align: center; padding: 12px; margin: 15px 0; background: #d4edda; color: #155724; border-radius: 8px; font-weight: 600;">
                    ✅ Результаты ответа
                </div>
                
                <div class="results-content" style="padding: 20px;">
            `;
            if (question.image_path) {
                const timestamp = new Date().getTime();
                const imageUrl = BASE_URL + question.image_path + '?t=' + timestamp;
                
                resultsHTML += `
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="${imageUrl}" 
                            style="max-width: 100%; max-height: 250px; border-radius: 8px; border: 2px solid #e0e0e0;"
                            alt="Изображение вопроса"
                            onerror="this.style.display='none'">
                    </div>
                `;
            }
            
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
            
            // ВАЖНОЕ ИСПРАВЛЕНИЕ: Показываем ВСЕ ответы с пометкой правильных
            if (answers.length > 0) {
                resultsHTML += `
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0; border: 2px solid #e9ecef;">
                        <h4 style="margin-top: 0; color: #2c3e50; text-align: center; margin-bottom: 15px;">Разбор ответов:</h4>
                `;
                
                // Сортируем ответы по порядку отображения (A, B, C...)
                const sortedAnswers = [...answers].sort((a, b) => {
                    return (a.display_order || 0) - (b.display_order || 0);
                });
                
                sortedAnswers.forEach((answer, index) => {
                    const isUserSelected = selectedAnswers.includes(answer.id);
                    const isCorrectAnswer = answer.is_correct;
                    
                    let itemClass = '';
                    let prefix = '';
                    let userChoiceLabel = '';
                    let pointsDisplay = '';
                    let explanation = '';

                    // Форматируем отображение баллов
                    if (answer.points !== 0) {
                        pointsDisplay = `<span style="color: ${answer.points > 0 ? '#27ae60' : '#e74c3c'}; font-weight: bold; margin-left: auto; padding-left: 10px;">
                            ${answer.points > 0 ? '+' : ''}${answer.points} баллов
                        </span>`;
                    } else {
                        pointsDisplay = '<span style="color: #7f8c8d; margin-left: auto; padding-left: 10px;">0 баллов</span>';
                    }
                    
                    // Определяем оформление в зависимости от типа ответа
                    if (isCorrectAnswer && isUserSelected) {
                        // Правильный ответ, выбран участником
                        itemClass = 'background: #d4edda; border-left: 4px solid #28a745;';
                        prefix = '✅ ';
                        userChoiceLabel = '<span style="color: #28a745; font-size: 0.9em; margin-left: 8px; font-weight: 600;">(вы выбрали правильно)</span>';
                        explanation = '<span style="color: #28a745; font-size: 0.85em; display: block; margin-top: 5px;">✓ Правильный ответ</span>';
                    } 
                    else if (isCorrectAnswer) {
                        // Правильный ответ, НЕ выбран участником
                        itemClass = 'background: #e8f4fd; border-left: 4px solid #3498db;';
                        prefix = '✓ ';
                        explanation = '<span style="color: #3498db; font-size: 0.85em; display: block; margin-top: 5px;">Правильный ответ (вы не выбрали)</span>';
                    }
                    else if (isUserSelected) {
                        // Неправильный ответ, выбран участником
                        itemClass = 'background: #f8d7da; border-left: 4px solid #e74c3c;';
                        prefix = '❌ ';
                        userChoiceLabel = '<span style="color: #e74c3c; font-size: 0.9em; margin-left: 8px; font-weight: 600;">(ваш выбор)</span>';
                        explanation = '<span style="color: #e74c3c; font-size: 0.85em; display: block; margin-top: 5px;">✗ Неправильный ответ</span>';
                    }
                    else {
                        // Неправильный ответ, НЕ выбран участником
                        itemClass = 'background: #f8f9fa; border-left: 4px solid #6c757d;';
                        prefix = '○ ';
                        explanation = '<span style="color: #6c757d; font-size: 0.85em; display: block; margin-top: 5px;">Неправильный ответ</span>';
                    }
                    
                    resultsHTML += `
                        <div style="${itemClass} padding: 15px; margin-bottom: 12px; border-radius: 6px; display: flex; flex-direction: column;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="flex: 1;">
                                    <span style="font-weight: 600; color: #2c3e50; margin-right: 8px;">${String.fromCharCode(65 + index)}.</span>
                                    <span style="font-weight: 500;">${answer.answer_text}</span>
                                    ${userChoiceLabel}
                                </div>
                                ${pointsDisplay}
                            </div>
                            ${explanation}
                        </div>
                    `;
                });
                
                resultsHTML += `</div>`;
                
                // Добавляем легенду для понимания значков
                resultsHTML += `
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0; border: 1px solid #dee2e6; font-size: 0.9em;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div style="display: flex; align-items: center;">
                                <span style="margin-right: 8px;">✅</span>
                                <span>Правильный ответ, выбран вами</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="margin-right: 8px;">✓</span>
                                <span>Правильный ответ</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="margin-right: 8px;">❌</span>
                                <span>Неправильный ответ, выбран вами</span>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="margin-right: 8px;">○</span>
                                <span>Неправильный ответ</span>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            const totalBgColor = earnedPoints >= 0 
                ? 'linear-gradient(135deg, #e8f4fd, #d4edda)' 
                : 'linear-gradient(135deg, #f8d7da, #f5c6cb)';
            const totalBorderColor = earnedPoints >= 0 ? '#3498db' : '#e74c3c';
            const totalTextColor = earnedPoints >= 0 ? '#3498db' : '#e74c3c';
            const pointsPrefix = earnedPoints > 0 ? '+' : '';

            resultsHTML += `
                <div style="font-size: 1.4rem; text-align: center; padding: 20px; 
                    background: ${totalBgColor}; 
                    border-radius: 10px; margin: 20px 0; 
                    border: 3px solid ${totalBorderColor}; 
                    font-weight: bold;">
                    Получено баллов: 
                    <strong style="color: ${totalTextColor}; font-size: 1.6rem;">
                        ${pointsPrefix}${earnedPoints}
                    </strong>
                </div>
                </div>
            `;
            
            resultsContainer.innerHTML = resultsHTML;
            
            // Обновляем общий счет в заголовке
            document.getElementById('current-score').textContent = currentParticipant?.score || 0;
            
            console.log('DEBUG: Results displayed successfully');
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
            console.log('DEBUG: loadQuestionAnswers called with question:', question);
            console.log('DEBUG: Image path in question:', question.image_path);
            console.log('DEBUG: Full question object:', JSON.stringify(question, null, 2));
            
            let answersContainer = document.getElementById('answers-container');
            if (!answersContainer) {
                console.error('DEBUG: answersContainer not found!');
                return;
            }
            
            // Очищаем контейнер
            answersContainer.innerHTML = '';
            
            // Определяем ID текущего вопроса
            const currentQuestionId = question.id || question.quiz_question_id;
            if (lastQuestionId !== currentQuestionId) {
                selectedAnswers = [];
                lastQuestionId = currentQuestionId;
            }
            
            // Создаем контейнер для вопроса с изображением
            let questionContainer = document.createElement('div');
            questionContainer.id = 'question-content-container';
            
            const questionTextDiv = document.createElement('div');
            questionTextDiv.className = 'question-text-display';
            questionTextDiv.innerHTML = `
                <div style="font-size: 1.2rem; font-weight: 600; color: #2c3e50; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    ${question.question_text}
                </div>
            `;
            questionContainer.appendChild(questionTextDiv);
            
            // ВАЖНОЕ ИСПРАВЛЕНИЕ: Добавляем изображение если есть
            const imageContainer = document.getElementById('selected-question-image');
            if (imageContainer) {
                imageContainer.innerHTML = ''; // Очищаем
                
                if (question.image_path && question.image_path !== 'null' && question.image_path !== '') {
                    console.log('DEBUG: Attempting to display image:', question.image_path);
                    
                    const timestamp = new Date().getTime();
                    let imageUrl;
                    
                    // УПРОЩАЕМ логику формирования URL - только 2 варианта
                    if (question.image_path.startsWith('http')) {
                        // Если уже полный URL
                        imageUrl = question.image_path;
                    } else {
                        // Просто добавляем базовый путь
                        imageUrl = '/math-grinder-php/' + question.image_path + '?t=' + timestamp;
                    }
                    
                    console.log('DEBUG: Generated image URL:', imageUrl);
                    
                    const img = document.createElement('img');
                    img.src = imageUrl;
                    img.className = 'question-image-display';
                    img.style.maxWidth = '100%';
                    img.style.maxHeight = '300px';
                    img.style.borderRadius = '12px';
                    img.style.margin = '15px auto';
                    img.style.display = 'block';
                    img.style.border = '3px solid #3498db';
                    img.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                    img.alt = 'Изображение вопроса';
                    img.style.objectFit = 'contain';
                    img.style.background = '#f8f9fa';
                    
                    // Обработчики ошибок
                    img.onload = function() {
                        console.log('DEBUG: Image loaded successfully');
                    };
                    
                    img.onerror = function() {
                        console.error('DEBUG: Failed to load image from URL:', imageUrl);
                        this.style.display = 'none';
                    };
                    
                    imageContainer.appendChild(img);
                } else {
                    console.log('DEBUG: No image path or empty path:', question.image_path);
                }
            }
            
            // ПАРСИМ ответы из вопроса
            let answers = [];
            
            // Используем уже загруженные ответы из вопроса
            if (question.answers && Array.isArray(question.answers)) {
                answers = question.answers;
            } else if (typeof question.answers === 'string' && question.answers.includes('|')) {
                // Парсим строку
                const answerStrings = question.answers.split(';;');
                answers = answerStrings.map(str => {
                    const parts = str.split('|');
                    return {
                        id: parseInt(parts[0]),
                        answer_text: parts[1],
                        is_correct: parseInt(parts[2]) === 1,
                        points: parseInt(parts[3]) || 0,
                        display_order: parseInt(parts[4]) || 0
                    };
                });
            }
            
            if (answers.length === 0) {
                console.error('DEBUG: No answers found after parsing');
                answersContainer.innerHTML = '<p style="color: #7f8c8d;">Нет вариантов ответов</p>';
                return;
            }
            
            // Сортируем ответы
            answers.sort((a, b) => (a.display_order || 0) - (b.display_order || 0));
            
            console.log('DEBUG: Rendering', answers.length, 'answers');
            
            // Создаем заголовок
            const header = document.createElement('h4');
            header.textContent = question.question_type === 'multiple' 
                ? 'Выберите один или несколько ответов:' 
                : 'Выберите один ответ:';
            header.style.marginBottom = '15px';
            header.style.color = '#2c3e50';
            header.style.padding = '10px';
            header.style.background = '#e8f4fd';
            header.style.borderRadius = '6px';
            answersContainer.appendChild(header);
            
            // Рендерим ответы
            answers.forEach((answer, index) => {
                const answerElement = document.createElement('div');
                answerElement.className = 'answer-option';
                answerElement.dataset.answerId = answer.id;
                answerElement.dataset.questionType = question.question_type;
                answerElement.style.cssText = `
                    padding: 15px;
                    margin: 10px 0;
                    border: 2px solid #e0e0e0;
                    border-radius: 8px;
                    background: white;
                    cursor: pointer;
                    transition: all 0.2s;
                    display: flex;
                    align-items: center;
                `;
                
                const inputType = question.question_type === 'multiple' ? 'checkbox' : 'radio';
                const inputName = question.question_type === 'multiple' ? 'quiz-answer-multiple' : 'quiz-answer-single';
                
                answerElement.innerHTML = `
                    <input type="${inputType}" 
                        name="${inputName}" 
                        value="${answer.id}"
                        id="answer-${answer.id}"
                        style="margin-right: 15px; transform: scale(1.3);">
                    <label for="answer-${answer.id}" style="cursor: pointer; flex: 1; margin: 0; display: flex; align-items: center;">
                        <span style="font-weight: 600; margin-right: 10px; min-width: 30px; color: #2c3e50;">${String.fromCharCode(65 + index)}.</span>
                        <span style="flex: 1;">${answer.answer_text}</span>
                    </label>
                `;
                
                const input = answerElement.querySelector('input');
                input.addEventListener('change', (e) => {
                    handleAnswerSelection(e, question.question_type);
                });
                
                answerElement.addEventListener('click', (e) => {
                    if (!e.target.matches('input, label')) {
                        if (inputType === 'radio') {
                            input.checked = true;
                        } else {
                            input.checked = !input.checked;
                        }
                        const event = new Event('change');
                        input.dispatchEvent(event);
                    }
                });
                
                answersContainer.appendChild(answerElement);
            });
            
            // Восстанавливаем выбранные ответы
            restoreSelectedAnswers(question.question_type);
            
            const submitBtn = document.getElementById('submit-quiz-answer-btn');
            if (submitBtn) {
                submitBtn.disabled = selectedAnswers.length === 0;
            }
            
            console.log('DEBUG: Answers rendered successfully');
        }

        function handleAnswerSelection(e, questionType) {
            const answerId = parseInt(e.target.value);
            const isChecked = e.target.checked;
            
            console.log('DEBUG: handleAnswerSelection:', { answerId, isChecked, questionType, currentSelected: selectedAnswers });
            
            if (questionType === 'single') {
                // Одиночный выбор
                if (isChecked) {
                    // Снимаем выделение с других radio кнопок
                    document.querySelectorAll('#answers-container input[type="radio"]').forEach(inp => {
                        if (parseInt(inp.value) !== answerId) {
                            inp.checked = false;
                            const parent = inp.closest('.answer-option');
                            if (parent) {
                                parent.classList.remove('selected');
                                parent.style.borderColor = '#e0e0e0';
                                parent.style.background = 'white';
                            }
                        }
                    });
                    
                    // Устанавливаем текущий выбор
                    selectedAnswers = [answerId];
                    const parent = e.target.closest('.answer-option');
                    if (parent) {
                        parent.classList.add('selected');
                        parent.style.borderColor = '#27ae60';
                        parent.style.background = '#d4edda';
                    }
                } else {
                    // Для radio нельзя снять выбор кликом - только выбрать другой
                    selectedAnswers = [];
                }
            } else {
                // Множественный выбор
                if (isChecked) {
                    if (!selectedAnswers.includes(answerId)) {
                        selectedAnswers.push(answerId);
                    }
                    const parent = e.target.closest('.answer-option');
                    if (parent) {
                        parent.classList.add('selected');
                        parent.style.borderColor = '#27ae60';
                        parent.style.background = '#d4edda';
                    }
                } else {
                    selectedAnswers = selectedAnswers.filter(id => id !== answerId);
                    const parent = e.target.closest('.answer-option');
                    if (parent) {
                        parent.classList.remove('selected');
                        parent.style.borderColor = '#e0e0e0';
                        parent.style.background = 'white';
                    }
                }
            }
            
            // Обновляем кнопку отправки
            const submitBtn = document.getElementById('submit-quiz-answer-btn');
            if (submitBtn) {
                submitBtn.disabled = selectedAnswers.length === 0;
                console.log('DEBUG: After selection - selectedAnswers:', selectedAnswers, 'button disabled:', submitBtn.disabled);
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
            console.log('DEBUG: submitQuizAnswer called');
            console.log('DEBUG: selectedAnswers before submit:', selectedAnswers);
            console.log('DEBUG: currentParticipant:', currentParticipant);
            console.log('DEBUG: currentQuestion:', currentQuestion);
            
            if (hasSubmittedAnswer) {
                console.log('DEBUG: Already submitted answer');
                return;
            }
            
            if (!currentQuestion || (!currentQuestion.id && !currentQuestion.quiz_question_id)) {
                alert('Ошибка: вопрос не загружен');
                return;
            }
            
            if (!selectedAnswers.length) {
                alert('Выберите хотя бы один вариант ответа');
                return;
            }
            
            const questionId = currentQuestion.quiz_question_id || currentQuestion.id;
            const questionType = currentQuestion.question_type || 'single';
            
            console.log('DEBUG: Submitting answer:', {
                participant_id: currentParticipant.id,
                quiz_question_id: questionId,
                quiz_answer_ids: selectedAnswers,
                question_type: questionType
            });

            // Блокируем кнопку отправки
            const submitBtn = document.getElementById('submit-quiz-answer-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Отправка...';
            }
            
            try {
                const result = await apiRequest('submit-quiz-answer', {
                    participant_id: currentParticipant.id,
                    quiz_question_id: questionId,
                    quiz_answer_ids: selectedAnswers
                });
                
                console.log('DEBUG: Submit API result:', result);
                
                if (result.success) {
                    hasSubmittedAnswer = true;
                    
                    // Сохраняем выбранные ответы для отображения в результатах
                    console.log('DEBUG: Answer submitted successfully, selectedAnswers:', selectedAnswers);
                    
                    // Обновляем счет участника
                    if (result.points_earned !== undefined) {
                        currentParticipant.score = (currentParticipant.score || 0) + result.points_earned;
                        document.getElementById('current-score').textContent = currentParticipant.score;
                        console.log('DEBUG: Updated score to:', currentParticipant.score);
                    }
                    
                    // Показываем ожидание результатов
                    showWaitingForResults();
                    
                    console.log('DEBUG: Answer submitted successfully');
                } else {
                    alert('Ошибка при отправке ответа: ' + result.error);
                    
                    // Разблокируем кнопку при ошибке
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '📨 Отправить ответ';
                    }
                }
            } catch (error) {
                console.error('DEBUG: Submit error:', error);
                alert('Ошибка соединения при отправке ответа');
                
                // Разблокируем кнопку при ошибке
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '📨 Отправить ответ';
                }
            }
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

        function showElement(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.remove('display-none');
                element.classList.add('active', 'display-block');
            }
        }

        function hideElement(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.remove('active', 'display-block');
                element.classList.add('display-none');
            }
        }

        function showCurrentQuestion() {
            showElement('current-question');
            hideElement('answer-results');
            hideElement('waiting-results');
        }

        function showAnswerResults() {
            hideElement('current-question');
            hideElement('waiting-results');
            showElement('answer-results');
        }

        function showWaitingForResults() {
            hideElement('current-question');
            hideElement('answer-results');
            showElement('waiting-results');
        }

        function resetQuizInterface() {
            showElement('current-question');
            hideElement('answer-results');
            hideElement('waiting-results');
            hideElement('time-up-message');
            hideElement('answer-submitted-message');
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