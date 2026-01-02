.<?php
// index.php - Главная страница для участников
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Математическая мясорубка - Участник</title>
    <link rel="stylesheet" href="css/light-participant.css">
    <style>
        #waiting-screen.active,
        #finished-screen.active {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            min-height: 100vh !important;
            background-color: #ecf0f1 !important;
            padding: 20px !important;
        }

        #waiting-screen.active .main-content-container,
        #finished-screen.active .main-content-container {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            width: 100% !important;
            max-width: 600px !important;
        }

        /* Исправляем отображение status-screen */
        #waiting-screen .status-screen,
        #finished-screen .status-screen {
            width: 100% !important;
            margin: 0 !important;
        }
    </style>
</head>
<body>
    <div id="login-form" class="active">
            <div class="login-container">
                <div class="login-header">
                    <div class="login-icon">🎯</div>
                    <h2 id="login-event-name">Математическая мясорубка</h2>
                    <p>Войдите в систему для участия</p>
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
                        🚀 Войти в систему
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>Ожидайте начала мероприятия</p>
                </div>
            </div>
        </div>

    <!-- Экран ожидания -->
    <div id="waiting-screen">
        <div class="main-content-container">
            <div class="status-screen">
                <div class="status-icon waiting-icon">⏳</div>
                <h2 class="status-title">Ожидание начала мероприятия</h2>
                <p class="status-message" id="waiting-message">Команда ожидает начала мероприятия.</p>
                <div class="timer-display" id="waiting-timer">--:--:--</div>
                
                <div class="event-info">
                    <h4>Информация о мероприятии</h4>
                    <div class="info-item">
                        <span class="info-label">Название:</span>
                        <span class="info-value" id="waiting-event-name">Математическая мясорубка</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Длительность:</span>
                        <span class="info-value" id="waiting-duration">-- минут</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Статус:</span>
                        <span class="info-value" id="waiting-status">Не начато</span>
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
                <h2 class="status-title">Мероприятие завершено</h2>
                <p class="status-message">Спасибо за участие! Мероприятие "<span id="finished-event-name">Математическая мясорубка</span>" завершено.</p>
                
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

    <div id="questions-container">
        <div class="main-content-container">
            <div class="questions-interface">
                <!-- Заголовок мероприятия -->
                <div class="event-header">
                    <h2>Мероприятие: <span id="current-event-name">Математическая мясорубка</span></h2>
                    <p>Команда: <strong id="team-name-display"></strong></p>
                </div>
                
                <!-- Выбор вопроса -->
                <div class="question-selector">
                    <h3>Выберите вопрос для ответа:</h3>
                    <div id="questions-grid" class="questions-grid">
                        <!-- Карточки вопросов будут здесь -->
                    </div>
                </div>
                
                <!-- Отображение вопроса и форма ответа -->
                <div id="answer-form" class="question-display">
                    <h3>Текущий вопрос:</h3>
                    
                    <!-- Контейнер для текста вопроса -->
                    <div id="selected-question-text" class="question-text-container"></div>
                    
                    <!-- Контейнер для изображения вопроса -->
                    <div id="selected-question-image" class="question-image-container"></div>
                    
                    <div id="question-status" class="question-status status-not-answered">Статус: Не отвечен</div>
                    
                    <form id="answer-submit-form">
                        <input type="text" id="answer-input" placeholder="Введите ваш ответ" required>
                        <button type="submit" class="answer-submit-btn">📨 Отправить ответ</button>
                    </form>
                </div>

                <div id="result-message"></div>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
        // Глобальные переменные
        let currentParticipant = null;
        let questions = [];
        let questionsStatus = {};
        let selectedQuestionId = null;
        let eventState = null;
        let statusCheckInterval = null;
        
        // Защита от списывания
        let tabSwitchCount = 0;
        let copyAttemptCount = 0;
        let pasteAttemptCount = 0;
        
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
                const response = await fetch(`api.php?action=${action}`, options);
                
                // Получаем текст ответа
                const responseText = await response.text();
                
                // Обрабатываем пустой ответ
                if (!responseText.trim()) {
                    console.log('Empty response received, returning empty array');
                    return [];
                }

                // Пытаемся распарсить JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    if (responseText.includes('<b>Fatal error</b>') || responseText.includes('<br />')) {
                        const errorMatch = responseText.match(/<b>(.*?)<\/b>(.*?)<br \/>/);
                        const errorMessage = errorMatch ? errorMatch[1] + errorMatch[2] : 'Server PHP Error';
                        return { error: `Ошибка сервера: ${errorMessage}` };
                    }
                    return { error: `Неверный ответ от сервера: ${responseText.substring(0, 100)}` };
                }

                // Очищаем данные от числовых индексов
                return cleanApiResponse(result);

            } catch (error) {
                console.error(`API Error for ${action}:`, error);
                return { 
                    error: `Ошибка соединения: ${error.message}`,
                    details: error.toString()
                };
            }
        }
        
        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const teamInput = document.getElementById('team-input');
            if (teamInput) {
                teamInput.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                teamInput.addEventListener('blur', function() {
                    if (!this.value) {
                        this.parentElement.classList.remove('focused');
                    }
                });
            }
            showLoginScreen(); // Показываем экран входа при загрузке
            // Блокировка сочетаний клавиш
            document.addEventListener('keydown', handleKeyDown);
            document.addEventListener('contextmenu', handleContextMenu);
            document.addEventListener('visibilitychange', handleVisibilityChange);
            
            // Обработчики форм
            document.getElementById('team-login-form').addEventListener('submit', handleTeamLogin);
            document.getElementById('answer-submit-form').addEventListener('submit', handleAnswerSubmit);
            
            // Запускаем проверку статуса мероприятия
            startStatusChecking();
        });
        
        // Проверка статуса мероприятия
        async function startStatusChecking() {
            // Загружаем состояние мероприятия сразу при старте
            await checkEventStatus();
            
            // Обновляем название мероприятия на экране входа
            updateLoginScreenEventName();
            
            // Запускаем периодическую проверку
            statusCheckInterval = setInterval(checkEventStatus, 5000); // Увеличим интервал до 5 секунд
        }

        function updateLoginScreenEventName() {
            if (eventState && eventState.event_name) {
                // Обновляем везде, где может быть название мероприятия
                const eventNameElements = document.querySelectorAll('#login-event-name, #current-event-name, #finished-event-name');
                eventNameElements.forEach(element => {
                    element.textContent = eventState.event_name;
                });
            }
        }
        
        async function checkEventStatus() {
            try {
                const result = await apiRequest('get-grinder-event-state');
                if (result && !result.error) {
                    eventState = result;
                    updateStatusDisplays();
                    updateLoginScreenEventName(); // Обновляем название
                    handleAutomaticScreenTransition(result.event_status);
                }
            } catch (error) {
                console.error('Error checking event status:', error);
            }
        }

        function handleAutomaticScreenTransition(newStatus) {
            const currentScreen = getCurrentScreen();
            
            // Если мероприятие завершилось и мы в интерфейсе вопросов - показываем экран завершения
            if (newStatus === 'finished' && currentScreen === 'questions' && currentParticipant) {
                showFinishedScreen();
                return;
            }
            
            // Если мероприятие началось и мы на экране ожидания - переходим к вопросам
            if (newStatus === 'running' && currentScreen === 'waiting' && currentParticipant) {
                showQuestionsInterface();
                return;
            }
            
            // Если мероприятие не началось и мы в интерфейсе вопросов
            if (newStatus === 'not_started' && currentScreen === 'questions' && currentParticipant) {
                showWaitingScreen();
                return;
            }
        }

        function getCurrentScreen() {
            if (document.getElementById('login-form').classList.contains('active')) return 'login';
            if (document.getElementById('waiting-screen').classList.contains('active')) return 'waiting';
            if (document.getElementById('questions-container').classList.contains('active')) return 'questions';
            if (document.getElementById('finished-screen').classList.contains('active')) return 'finished';
            return 'login';
        }


        function showQuestionsInterface() {
            hideAllScreens();
            document.getElementById('questions-container').classList.add('active');
            
            // Обновляем название мероприятия и команду
            if (eventState && eventState.event_name) {
                document.getElementById('current-event-name').textContent = eventState.event_name;
            }
            if (currentParticipant) {
                document.getElementById('team-name-display').innerHTML = 
                    `${currentParticipant.team} <span class="score-badge">${currentParticipant.score} баллов</span>`;
            }
            
            // Загружаем вопросы в виде сетки
            renderQuestionsGrid(); // Заменяем на новую функцию
            loadQuestionsStatus();
            
            console.log('Переход к интерфейсу вопросов');
        }

        function hideAllScreens() {
            const screens = [
                'login-form',
                'waiting-screen', 
                'finished-screen',
                'questions-container'
            ];
            
            screens.forEach(screenId => {
                const element = document.getElementById(screenId);
                if (element) {
                    element.classList.remove('active');
                }
            });
        }
        
        function updateStatusDisplays() {
            if (!eventState) return;
            
            // Обновляем экран ожидания
            document.getElementById('waiting-event-name').textContent = eventState.event_name || 'Математическая мясорубка';
            document.getElementById('waiting-duration').textContent = Math.floor((eventState.timer_duration || 3600) / 60) + ' минут';
            
            const statusText = getStatusText(eventState.event_status);
            document.getElementById('waiting-status').textContent = statusText;
            
            // ИСПРАВЛЕНИЕ: Проверяем, что event_status не пустой
            if (eventState.event_status && eventState.event_status.trim() !== '') {
                document.getElementById('waiting-status').className = `status-${eventState.event_status}`;
            } else {
                document.getElementById('waiting-status').className = 'status-not_started';
            }
            
            // УБИРАЕМ таймер ожидания
            document.getElementById('waiting-timer').style.display = 'none';
        }

        function cleanApiResponse(data) {
            if (!data) return data;
            
            // Если есть ошибка - возвращаем как есть
            if (data.error) return data;
            
            // Если это массив - очищаем каждый элемент
            if (Array.isArray(data)) {
                return data.map(item => cleanApiResponse(item));
            }
            
            // Если это объект - удаляем числовые ключи
            if (typeof data === 'object' && data !== null) {
                const cleaned = {};
                for (const key in data) {
                    // Удаляем числовые ключи (0, 1, 2, ...)
                    if (!/^\d+$/.test(key)) {
                        cleaned[key] = cleanApiResponse(data[key]);
                    }
                }
                return cleaned;
            }
            
            return data;
        }
        
        function getStatusText(status) {
            switch(status) {
                case 'running': return 'Идет';
                case 'finished': return 'Завершено';
                default: return 'Не начато';
            }
        }
        
        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        
        // Обработчики событий
        async function handleTeamLogin(e) {
            e.preventDefault();

            // Проверяем статус мероприятия
            if (!eventState) {
                await checkEventStatus();
            }
            
            const teamInput = document.getElementById('team-input');
            const team = teamInput.value.trim();

            if (!team) {
                showResultMessage('Введите название команды', false);
                return;
            }

            // Если мероприятие не началось - регистрируем и показываем ожидание
            if (eventState.event_status === 'not_started') {
                const result = await apiRequest('grinder-participant-join', { team });
                
                if (result.error) {
                    showResultMessage(result.error, false);
                    return;
                }
                
                currentParticipant = result.participant;
                questions = result.questions;
                showWaitingScreen();
                return;
            }
            
            // Если мероприятие завершено - показываем экран завершения
            if (eventState.event_status === 'finished') {
                const result = await apiRequest('grinder-participant-join', { team });
                
                if (result.error) {
                    showResultMessage(result.error, false);
                    return;
                }
                
                currentParticipant = result.participant;
                questions = result.questions;
                showFinishedScreen();
                return;
            }

            // Если мероприятие идет - обычный вход
            const result = await apiRequest('grinder-participant-join', { team });

            if (result.error) {
                showResultMessage(result.error, false);
                return;
            }

            // Успешный вход
            currentParticipant = result.participant;
            questions = result.questions;
            showQuestionsInterface();
        }

        function updateWaitingScreenInfo() {
            if (!eventState) return;
            
            document.getElementById('waiting-event-name').textContent = eventState.event_name || 'Математическая мясорубка';
            document.getElementById('waiting-duration').textContent = Math.floor((eventState.timer_duration || 3600) / 60) + ' минут';
            
            const statusText = getStatusText(eventState.event_status);
            document.getElementById('waiting-status').textContent = statusText;
            
            if (eventState.event_status === 'not_started') {
                document.getElementById('waiting-timer').textContent = formatTime(eventState.timer_remaining || 0);
            }
        }

        function showLoginScreen() {
            hideAllScreens();
            document.getElementById('login-form').classList.add('active');
        }
        
        function showWaitingScreen() {
                hideAllScreens();
                document.getElementById('waiting-screen').classList.add('active');
                
                // Заполняем информацию о команде
                const teamInput = document.getElementById('team-input');
                if (teamInput && teamInput.value.trim()) {
                    document.getElementById('waiting-message').textContent = 
                        `Команда "${teamInput.value.trim()}" ожидает начала мероприятия.`;
                }
                
                // Обновляем информацию о мероприятии
                if (eventState) {
                    updateWaitingScreenInfo();
                }
            }
        
        function showFinishedScreen() {
            hideAllScreens();
            document.getElementById('finished-screen').classList.add('active');
            
            // Заполняем информацию о результатах
            if (currentParticipant) {
                document.getElementById('finished-team-name').textContent = currentParticipant.team;
                document.getElementById('finished-score').textContent = currentParticipant.score;
                
                // Подсчитываем правильные ответы
                const correctAnswers = Object.values(questionsStatus).filter(status => status.is_correct).length;
                document.getElementById('finished-correct-answers').textContent = correctAnswers;
                
                // Обновляем название мероприятия
                if (eventState && eventState.event_name) {
                    document.getElementById('finished-event-name').textContent = eventState.event_name;
                }
            }
            
            // Останавливаем проверку статуса
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
            }
            
            console.log('Показан экран завершения с результатами');
        }
        
        function handleQuestionDropdownChange() {
            const selectedValue = document.getElementById('questions-dropdown').value;
            document.getElementById('select-question-btn').disabled = !selectedValue;
        }
        
        function handleSelectQuestion() {
            const selectedValue = document.getElementById('questions-dropdown').value;
            if (!selectedValue) return;
            
            const question = questions.find(q => q.id == selectedValue);
            if (!question) return;
            
            selectedQuestionId = question.id;
            
            // 1. Отображаем текст вопроса
            const questionTextElement = document.getElementById('selected-question-text');
            questionTextElement.innerHTML = `<div class="question-text">${question.text}</div>`;
            
            // 2. Отображаем изображение (если есть)
            const questionImageElement = document.getElementById('selected-question-image');
            
            // Очищаем предыдущее изображение
            questionImageElement.innerHTML = '';
            
            if (question.image_path && question.image_path !== 'null' && question.image_path !== '') {
                // Формируем правильный URL для изображения
                let imageUrl = question.image_path;
                
                // Если путь не абсолютный, добавляем BASE_URL
                if (!imageUrl.startsWith('http')) {
                    if (!imageUrl.startsWith('/')) {
                        imageUrl = '/' + imageUrl;
                    }
                    imageUrl = BASE_URL + imageUrl;
                }
                
                // Добавляем timestamp для избежания кеширования
                imageUrl += '?t=' + new Date().getTime();
                
                console.log('Loading question image:', imageUrl);
                
                // Создаем элемент изображения
                const img = document.createElement('img');
                img.src = imageUrl;
                img.alt = 'Изображение вопроса';
                img.className = 'question-image';
                
                // Обработчик ошибки загрузки
                img.onerror = function() {
                    console.error('Failed to load image:', this.src);
                    this.style.display = 'none';
                    questionImageElement.innerHTML = '<p class="image-error">⚠️ Изображение не загружено</p>';
                };
                
                // Обработчик успешной загрузки
                img.onload = function() {
                    console.log('Image loaded successfully:', this.src);
                };
                
                questionImageElement.appendChild(img);
                questionImageElement.style.display = 'block';
            } else {
                questionImageElement.style.display = 'none';
            }
            
            // 3. Обновляем статус вопроса
            updateQuestionStatus(selectedQuestionId);
            
            // 4. Показываем блок с вопросом и фокусируемся на поле ввода
            document.getElementById('answer-form').classList.add('active');
            document.getElementById('answer-input').focus();
        }

        
        async function handleAnswerSubmit(e) {
            e.preventDefault();
            const answerInput = document.getElementById('answer-input');
            const answer = answerInput.value.trim();
            
            if (!selectedQuestionId) {
                showResultMessage('Выберите вопрос для ответа', false);
                return;
            }
            
            if (!answer) {
                showResultMessage('Введите ответ', false);
                return;
            }
            
            // Показываем индикатор загрузки
            const submitBtn = document.querySelector('.answer-submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Отправка...';
            submitBtn.disabled = true;
            
            try {
                const result = await apiRequest('grinder-answer-submit', {
                    participant_id: currentParticipant.id,
                    question_id: selectedQuestionId,
                    answer: answer
                });
                
                if (result.error) {
                    showResultMessage(result.error, false);
                } else if (result.success) {
                    showResultMessage(result.message, result.is_correct);
                    
                    // Обновляем статус вопроса
                    questionsStatus[selectedQuestionId] = {
                        answered: true,
                        is_correct: result.is_correct,
                        points: result.points || 0
                    };
                    
                    // Обновляем счет участника только если ответ правильный
                    if (result.is_correct && result.points) {
                        currentParticipant.score += result.points;
                    }
                    
                    // Перерисовываем список вопросов и обновляем статус
                    renderQuestionsGrid();
                    updateQuestionStatus(selectedQuestionId);
                    answerInput.value = '';
                    
                    // Также обновляем отображение счета в заголовке
                    document.getElementById('team-name-display').innerHTML = 
                        `${currentParticipant.team} <span class="score-badge">${currentParticipant.score} баллов</span>`;
                }
            } catch (error) {
                console.error('Error submitting answer:', error);
                showResultMessage('Ошибка при отправке ответа: ' + error.message, false);
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        }
        
        // Вспомогательные функции
        function renderQuestionsGrid() {
            const grid = document.getElementById('questions-grid');
            if (!grid) return;
            
            grid.innerHTML = '';

            if (questions.length === 0) {
                grid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; padding: 20px;">Вопросы пока не добавлены</p>';
                return;
            }

            const sortedQuestions = [...questions].sort((a, b) => a.id - b.id);
            
            sortedQuestions.forEach((question, index) => {
                const card = document.createElement('div');
                card.className = 'question-card';
                card.dataset.questionId = question.id;
                
                // Определяем статус вопроса
                const status = questionsStatus[question.id];
                let statusClass = '';
                let statusIcon = '';
                let statusText = 'Не отвечен';
                
                if (status && status.answered) {
                    if (status.is_correct) {
                        statusClass = 'answered correct';
                        statusIcon = '';
                        statusText = `+${status.points}`;
                    } else {
                        statusClass = 'answered incorrect';
                        statusIcon = '';
                        statusText = 'Неверно';
                    }
                }
                
                // ИСПРАВЛЕНИЕ: Проверяем и добавляем классы правильно
                if (statusClass && statusClass.trim() !== '') {
                    // Разделяем классы по пробелам и добавляем каждый
                    const classes = statusClass.split(' ');
                    classes.forEach(className => {
                        if (className.trim() !== '') {
                            card.classList.add(className);
                        }
                    });
                }
                
                // Добавляем класс selected если это текущий выбранный вопрос
                if (selectedQuestionId == question.id) {
                    card.classList.add('selected');
                }
                
                card.innerHTML = `
                    <div class="question-status-indicator">${statusIcon}</div>
                    <div class="question-number">${index + 1}</div>
                    <div class="question-status">${statusText}</div>
                    ${question.image_path && question.image_path !== 'null' && question.image_path !== '' ? 
                        '<div class="question-image-indicator">🖼️</div>' : ''}
                `;
                
                card.addEventListener('click', () => {
                    selectQuestionFromGrid(question.id);
                });
                
                grid.appendChild(card);
            });
        }

        function selectQuestionFromGrid(questionId) {
            const question = questions.find(q => q.id == questionId);
            if (!question) return;
            
            selectedQuestionId = questionId;
            
            // Убираем выделение со всех карточек
            document.querySelectorAll('.question-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Добавляем выделение выбранной карточке
            const selectedCard = document.querySelector(`.question-card[data-question-id="${questionId}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            // Отображаем текст вопроса
            const questionTextElement = document.getElementById('selected-question-text');
            questionTextElement.innerHTML = `<div class="question-text">${question.text}</div>`;
            
            // Отображаем изображение (если есть)
            const questionImageElement = document.getElementById('selected-question-image');
            questionImageElement.innerHTML = '';
            
            if (question.image_path && question.image_path !== 'null' && question.image_path !== '') {
                let imageUrl = question.image_path;
                if (!imageUrl.startsWith('http')) {
                    if (!imageUrl.startsWith('/')) {
                        imageUrl = '/' + imageUrl;
                    }
                    imageUrl = BASE_URL + imageUrl;
                }
                
                imageUrl += '?t=' + new Date().getTime();
                
                const img = document.createElement('img');
                img.src = imageUrl;
                img.alt = 'Изображение вопроса';
                img.className = 'question-image';
                
                img.onerror = function() {
                    console.error('Failed to load image:', this.src);
                    this.style.display = 'none';
                    questionImageElement.innerHTML = '<p class="image-error">⚠️ Изображение не загружено</p>';
                };
                
                questionImageElement.appendChild(img);
                questionImageElement.style.display = 'block';
            } else {
                questionImageElement.style.display = 'none';
            }
            
            // Обновляем статус вопроса
            updateQuestionStatus(selectedQuestionId);
            
            // Показываем блок с вопросом и фокусируемся на поле ввода
            document.getElementById('answer-form').classList.add('active');
            document.getElementById('answer-input').focus();
        }
        
        async function loadQuestionsStatus() {
            if (!currentParticipant) return;

            const result = await apiRequest('get-grinder-questions-status', {
                participant_id: currentParticipant.id
            });

            if (result && result.error) {
                console.error('Error loading questions status:', result.error);
                questionsStatus = {};
                renderQuestionsGrid(); // Заменяем на новую функцию
                return;
            }

            if (!result || !Array.isArray(result)) {
                console.log('No questions status data received, initializing empty');
                questionsStatus = {};
                renderQuestionsGrid(); // Заменяем на новую функцию
                return;
            }

            questionsStatus = {};
            result.forEach(q => {
                questionsStatus[q.question_id] = {
                    answered: true,
                    is_correct: q.is_correct || false,
                    points: q.points || 0
                };
            });

            renderQuestionsGrid(); // Заменяем на новую функцию
            console.log('Questions status loaded:', questionsStatus);
        }
        
        function updateQuestionStatus(questionId) {
            const statusElement = document.getElementById('question-status');
            const status = questionsStatus[questionId];
            
            if (!status) {
                statusElement.textContent = 'Статус: Не отвечен';
                statusElement.className = 'question-status status-not-answered';
                return;
            }
            
            if (status.is_correct) {
                statusElement.textContent = `Статус: Отвечен верно (+${status.points} баллов)`;
                statusElement.className = 'question-status status-answered';
            } else {
                statusElement.textContent = 'Статус: Отвечен неверно';
                statusElement.className = 'question-status status-not-answered';
            }
        }
        
        function showResultMessage(message, isSuccess) {
            const resultElement = document.getElementById('result-message');
            resultElement.textContent = message;
            resultElement.className = isSuccess ? 'correct' : 'incorrect';
            
            setTimeout(() => {
                resultElement.textContent = '';
                resultElement.className = '';
            }, 3000);
        }
        
        // Защита от списывания
        function handleKeyDown(e) {
            // Блокировка Ctrl+C
            if (e.ctrlKey && e.key === 'c') {
                e.preventDefault();
                copyAttemptCount++;
                apiRequest('report-cheating', {
                    type: 'copy',
                    participant_id: currentParticipant?.id,
                    count: copyAttemptCount
                });
                alert('Копирование текста запрещено!');
                return false;
            }
            
            // Блокировка Ctrl+V
            if (e.ctrlKey && e.key === 'v') {
                e.preventDefault();
                pasteAttemptCount++;
                apiRequest('report-cheating', {
                    type: 'paste',
                    participant_id: currentParticipant?.id,
                    count: pasteAttemptCount
                });
                alert('Вставка текста запрещена!');
                return false;
            }
        }
        
        function handleContextMenu(e) {
            e.preventDefault();
            return false;
        }
        
        function handleVisibilityChange() {
            if (document.hidden && currentParticipant) {
                tabSwitchCount++;
                apiRequest('report-cheating', {
                    type: 'tab_switch',
                    participant_id: currentParticipant.id,
                    count: tabSwitchCount
                });
                
                alert(`Внимание! Переключение вкладок фиксируется. Попытка ${tabSwitchCount}`);
            }
        }
    </script>
</body>
</html>