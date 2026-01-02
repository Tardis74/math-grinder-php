<?php
// scoreboard-quiz.php - Табло для режима квиза
require_once 'config.php';

// Проверяем режим мероприятия
function get_event_mode() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
        $stmt->execute();
        $state = $stmt->fetch();
        return $state['event_mode'] ?? 'grinder';
    } catch (PDOException $e) {
        return 'grinder';
    }
}

// Получаем состояние квиза
function get_quiz_state() {
    global $pdo;
    try {
        // Получаем общее состояние мероприятия
        $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1");
        $event_state = $stmt->fetch();
        
        // Получаем текущий активный вопрос
        $stmt = $pdo->query("
            SELECT cq.*, qq.question_text, qq.question_type, qq.question_time, qq.answer_time, 
                   qq.image_path, qq.display_order
            FROM current_quiz_question cq
            LEFT JOIN quiz_questions qq ON cq.quiz_question_id = qq.id
            WHERE cq.is_active = 1
            ORDER BY cq.started_at DESC LIMIT 1
        ");
        $current_question = $stmt->fetch();
        
        // Получаем статистику участников
        $stmt = $pdo->query("SELECT COUNT(*) as total_participants FROM participants");
        $participants_count = $stmt->fetch()['total_participants'];
        
        // Получаем рейтинг участников (только если квиз завершен)
        $ranking = [];
        if ($event_state['event_status'] === 'finished') {
            $stmt = $pdo->query("
                SELECT p.id, p.team, p.score as total_score,
                       COUNT(DISTINCT qpa.quiz_question_id) as questions_answered,
                       SUM(CASE WHEN qpa.points_earned > 0 THEN 1 ELSE 0 END) as correct_answers,
                       SUM(qpa.points_earned) as quiz_score
                FROM participants p
                LEFT JOIN quiz_participant_answers qpa ON p.id = qpa.participant_id
                GROUP BY p.id
                ORDER BY quiz_score DESC, p.team
            ");
            $ranking = $stmt->fetchAll();
        }
        
        // Вычисляем оставшееся время
        $time_remaining = null;
        if ($current_question) {
            $now = time();
            if ($current_question['phase'] === 'question' && $current_question['question_end_time']) {
                $end_time = strtotime($current_question['question_end_time']);
                $time_remaining = max(0, $end_time - $now);
            } elseif ($current_question['phase'] === 'answers' && $current_question['answers_end_time']) {
                $end_time = strtotime($current_question['answers_end_time']);
                $time_remaining = max(0, $end_time - $now);
            }
        }
        
        return [
            'event_state' => $event_state,
            'current_question' => $current_question,
            'participants_count' => $participants_count,
            'ranking' => $ranking,
            'time_remaining' => $time_remaining,
            'current_phase' => $current_question['phase'] ?? null
        ];
    } catch (PDOException $e) {
        error_log("Get quiz state error: " . $e->getMessage());
        return [
            'event_state' => ['event_name' => 'Математический квиз', 'event_status' => 'not_started'],
            'current_question' => null,
            'participants_count' => 0,
            'ranking' => [],
            'time_remaining' => null,
            'current_phase' => null
        ];
    }
}

$quiz_state = get_quiz_state();
$event_name = $quiz_state['event_state']['event_name'] ?? 'Математический квиз';
$event_status = $quiz_state['event_state']['event_status'] ?? 'not_started';
$current_question = $quiz_state['current_question'];
$participants_count = $quiz_state['participants_count'];
$ranking = $quiz_state['ranking'];
$current_phase = $quiz_state['current_phase'];
$time_remaining = $quiz_state['time_remaining'];

// Определяем, какой экран показывать
$screen = 'waiting'; // по умолчанию
if ($event_status === 'finished') {
    $screen = 'results';
} elseif ($event_status === 'running' && $current_question) {
    if ($current_phase === 'question') {
        $screen = 'question';
    } elseif ($current_phase === 'answers') {
        $screen = 'answers';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Табло квиза - <?php echo htmlspecialchars($event_name); ?></title>
    <link rel="stylesheet" href="css/light-scoreboard.css">
    <link rel="stylesheet" href="css/light-quiz-scoreboard.css">
    <style>
        /* Дополнительные стили для квиз-табло */
        .quiz-screen {
            display: none;
        }
        .quiz-screen.active {
            display: block;
        }
        
        .question-display {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        
        .question-text {
            font-size: 1.8rem;
            line-height: 1.6;
            color: #2c3e50;
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .question-image-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .question-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 12px;
            border: 3px solid #3498db;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .answers-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 30px 0;
        }
        
        .answer-option {
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            font-size: 1.2rem;
            cursor: default;
            transition: all 0.3s;
        }
        
        .answer-option.correct {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
            font-weight: bold;
        }
        
        .answer-option.selected {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .answer-option.correct.selected {
            background: #c3e6cb;
            border-color: #28a745;
        }
        
        .phase-indicator {
            text-align: center;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.3rem;
        }
        
        .phase-question {
            background: #e8f4fd;
            color: #2980b9;
            border-left: 4px solid #3498db;
        }
        
        .phase-answers {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .results-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .results-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .results-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .results-table tr:hover {
            background: #e3f2fd;
        }
        
        .medal-1 { background: linear-gradient(135deg, #FFD700, #FFA500); }
        .medal-2 { background: linear-gradient(135deg, #C0C0C0, #A9A9A9); }
        .medal-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); }
        
        .medal-cell {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
        }
        
        .timer-display {
            font-size: 2.5rem;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 3px solid #3498db;
        }
        
        .timer-warning {
            animation: pulse 1s infinite;
            border-color: #e74c3c;
            color: #e74c3c;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        @media (max-width: 768px) {
            .answers-grid {
                grid-template-columns: 1fr;
            }
            
            .question-text {
                font-size: 1.4rem;
            }
            
            .timer-display {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1 id="event-title"><?php echo htmlspecialchars($event_name); ?></h1>
            <div class="event-info">
                <div id="timer" class="timer-display">
                    <?php
                    if ($time_remaining !== null) {
                        $minutes = floor($time_remaining / 60);
                        $seconds = $time_remaining % 60;
                        echo sprintf("%02d:%02d", $minutes, $seconds);
                    } else {
                        echo '--:--';
                    }
                    ?>
                </div>
                <div id="event-status" class="event-status status-<?php echo $event_status; ?>">
                    <?php
                    switch($event_status) {
                        case 'running': echo 'ИДЕТ'; break;
                        case 'finished': echo 'ЗАВЕРШЕНО'; break;
                        default: echo 'НЕ НАЧАЛОСЬ';
                    }
                    ?>
                </div>
                <div id="participants-count" class="participants-count">
                    Участников: <strong><?php echo $participants_count; ?></strong>
                </div>
            </div>
        </header>
        
        <main>
            <!-- Экран ожидания начала -->
            <div id="waiting-screen" class="quiz-screen <?php echo $screen === 'waiting' ? 'active' : ''; ?>">
                <div class="question-display" style="text-align: center;">
                    <div style="font-size: 4rem; margin: 30px 0;">⏳</div>
                    <h2>Ожидание начала квиза</h2>
                    <p style="font-size: 1.2rem; color: #7f8c8d; margin: 20px 0;">
                        Квиз "<strong><?php echo htmlspecialchars($event_name); ?></strong>" скоро начнется
                    </p>
                    <div style="background: #e8f4fd; padding: 20px; border-radius: 8px; margin: 30px 0;">
                        <h3>Информация о квизе</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                            <div>
                                <strong>Режим:</strong><br>
                                <span style="color: #3498db; font-weight: 600;">Интерактивный квиз</span>
                            </div>
                            <div>
                                <strong>Участников:</strong><br>
                                <span style="color: #27ae60; font-weight: 600; font-size: 1.3rem;"><?php echo $participants_count; ?></span>
                            </div>
                            <div>
                                <strong>Статус:</strong><br>
                                <span class="status-<?php echo $event_status; ?>" style="padding: 5px 10px; border-radius: 4px;">
                                    <?php echo $event_status === 'not_started' ? 'Ожидание' : 'Готов'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Экран вопроса -->
            <div id="question-screen" class="quiz-screen <?php echo $screen === 'question' ? 'active' : ''; ?>">
                <div class="question-display">
                    <div class="phase-indicator phase-question">
                        ⏰ Вопрос: <span id="question-timer">--:--</span>
                    </div>
                    
                    <?php if ($current_question): ?>
                        <div class="question-text" id="question-text">
                            <?php echo htmlspecialchars($current_question['question_text']); ?>
                        </div>
                        
                        <?php if ($current_question['image_path']): ?>
                        <div class="question-image-container">
                            <?php
                            $image_path = $current_question['image_path'];
                            if (strpos($image_path, '/') === 0) {
                                $image_url = 'http://localhost' . $image_path;
                            } else {
                                $image_url = 'http://localhost/math-grinder-php/' . $image_path;
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                 class="question-image" 
                                 alt="Изображение вопроса"
                                 onerror="this.style.display='none'">
                        </div>
                        <?php endif; ?>
                        
                        <div id="answers-container" class="answers-grid">
                            <!-- Ответы будут загружены через JavaScript -->
                        </div>
                        
                        <div style="text-align: center; margin-top: 30px; color: #7f8c8d; font-size: 1.1rem;">
                            ⚠️ Участники выбирают ответы...
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Экран показа ответов -->
            <div id="answers-screen" class="quiz-screen <?php echo $screen === 'answers' ? 'active' : ''; ?>">
                <div class="question-display">
                    <div class="phase-indicator phase-answers">
                        ✅ Показ правильных ответов: <span id="answers-timer">--:--</span>
                    </div>
                    
                    <?php if ($current_question): ?>
                        <div class="question-text" id="answers-question-text">
                            <?php echo htmlspecialchars($current_question['question_text']); ?>
                        </div>
                        
                        <div id="correct-answers-container" class="answers-grid">
                            <!-- Правильные ответы будут загружены через JavaScript -->
                        </div>
                        
                        <div id="answers-stats" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 30px;">
                            <h3 style="text-align: center; margin-bottom: 15px;">Статистика ответов</h3>
                            <div id="stats-content" style="text-align: center; color: #7f8c8d;">
                                Загрузка статистики...
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Экран результатов -->
            <div id="results-screen" class="quiz-screen <?php echo $screen === 'results' ? 'active' : ''; ?>">
                <div class="question-display">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <div style="font-size: 4rem; margin: 20px 0;">🏆</div>
                        <h2>Финальные результаты квиза</h2>
                        <p style="color: #7f8c8d; font-size: 1.2rem;">
                            "<strong><?php echo htmlspecialchars($event_name); ?></strong>" завершен
                        </p>
                    </div>
                    
                    <?php if (!empty($ranking)): ?>
                        <div class="table-container">
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <th width="60">Место</th>
                                        <th>Команда</th>
                                        <th>Правильных ответов</th>
                                        <th>Всего ответов</th>
                                        <th>Общий балл</th>
                                    </tr>
                                </thead>
                                <tbody id="ranking-body">
                                    <?php foreach ($ranking as $index => $participant): ?>
                                    <tr>
                                        <td>
                                            <?php if ($index < 3): ?>
                                                <div class="medal-cell medal-<?php echo $index + 1; ?>">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($participant['team']); ?></td>
                                        <td><?php echo $participant['correct_answers']; ?></td>
                                        <td><?php echo $participant['questions_answered']; ?></td>
                                        <td style="font-weight: bold; font-size: 1.2rem;">
                                            <?php echo $participant['quiz_score']; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                            <p style="font-size: 1.2rem;">Результаты пока не доступны</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Глобальные переменные
        let currentScreen = '<?php echo $screen; ?>';
        let eventStatus = '<?php echo $event_status; ?>';
        let currentQuestionId = null;
        let timerInterval = null;
        let updateInterval = null;
        let currentAnswers = [];

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
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { error: 'Ошибка соединения с сервером' };
            }
        }

        // Загрузка состояния квиза
        async function loadQuizState() {
            try {
                const result = await apiRequest('get-quiz-session');
                if (result && result.success) {
                    updateQuizState(result);
                }
            } catch (error) {
                console.error('Ошибка загрузки состояния:', error);
            }
        }

        // Обновление состояния квиза
        function updateQuizState(state) {
            const session = state.session;
            const eventState = state.stats;
            const timeRemaining = state.time_remaining;
            const currentPhase = session?.phase;
            
            // Обновляем статус мероприятия
            if (eventState.event_status !== eventStatus) {
                eventStatus = eventState.event_status;
                document.getElementById('event-status').className = `event-status status-${eventStatus}`;
                document.getElementById('event-status').textContent = 
                    eventStatus === 'running' ? 'ИДЕТ' : 
                    eventStatus === 'finished' ? 'ЗАВЕРШЕНО' : 'НЕ НАЧАЛОСЬ';
            }
            
            // Обновляем название мероприятия
            if (eventState.event_name && eventState.event_name !== document.getElementById('event-title').textContent) {
                document.getElementById('event-title').textContent = eventState.event_name;
            }
            
            // Обновляем таймер
            updateTimer(timeRemaining, currentPhase);
            
            // Определяем, какой экран показывать
            let newScreen = 'waiting';
            if (eventStatus === 'finished') {
                newScreen = 'results';
            } else if (eventStatus === 'running' && session?.is_active) {
                if (currentPhase === 'question') {
                    newScreen = 'question';
                    // Загружаем вопрос если он изменился
                    if (session.quiz_question_id !== currentQuestionId) {
                        currentQuestionId = session.quiz_question_id;
                        loadQuestion(session.quiz_question_id);
                    }
                } else if (currentPhase === 'answers') {
                    newScreen = 'answers';
                    // Загружаем правильные ответы
                    loadCorrectAnswers(session.quiz_question_id);
                    loadAnswerStats(session.quiz_question_id);
                }
            }
            
            // Переключаем экран если нужно
            if (newScreen !== currentScreen) {
                switchScreen(newScreen);
            }
        }

        // Обновление таймера
        function updateTimer(timeRemaining, phase) {
            const timerElement = document.getElementById('timer');
            
            if (timeRemaining !== null && timeRemaining > 0) {
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                // Предупреждение при малом времени
                if (timeRemaining <= 10) {
                    timerElement.classList.add('timer-warning');
                } else {
                    timerElement.classList.remove('timer-warning');
                }
                
                // Обновляем фазовый таймер
                if (phase === 'question') {
                    document.getElementById('question-timer').textContent = timerElement.textContent;
                } else if (phase === 'answers') {
                    document.getElementById('answers-timer').textContent = timerElement.textContent;
                }
            } else {
                timerElement.textContent = '--:--';
                timerElement.classList.remove('timer-warning');
            }
        }

        // Переключение экранов
        function switchScreen(screenId) {
            // Скрываем все экраны
            document.querySelectorAll('.quiz-screen').forEach(screen => {
                screen.classList.remove('active');
            });
            
            // Показываем нужный экран
            const screen = document.getElementById(`${screenId}-screen`);
            if (screen) {
                screen.classList.add('active');
                currentScreen = screenId;
            }
        }

        // Загрузка вопроса
        async function loadQuestion(questionId) {
            try {
                const result = await apiRequest('get-quiz-questions');
                if (result && !result.error) {
                    const question = Array.isArray(result) 
                        ? result.find(q => q.id === questionId)
                        : result;
                    
                    if (question) {
                        // Обновляем текст вопроса
                        document.getElementById('question-text').textContent = question.question_text;
                        
                        // Обновляем изображение если есть
                        const imageContainer = document.querySelector('#question-screen .question-image-container');
                        if (question.image_path_relative && imageContainer) {
                            const imageUrl = '/math-grinder-php/' + question.image_path_relative + '?t=' + Date.now();
                            imageContainer.innerHTML = `
                                <img src="${imageUrl}" 
                                     class="question-image" 
                                     alt="Изображение вопроса"
                                     onerror="this.style.display='none'">
                            `;
                        } else if (imageContainer) {
                            imageContainer.innerHTML = '';
                        }
                        
                        // Отображаем варианты ответов (только буквы)
                        const answersContainer = document.getElementById('answers-container');
                        if (answersContainer && question.answers) {
                            let html = '';
                            question.answers.forEach((answer, index) => {
                                html += `
                                    <div class="answer-option">
                                        ${String.fromCharCode(65 + index)}. ${answer.answer_text}
                                    </div>
                                `;
                            });
                            answersContainer.innerHTML = html;
                        }
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки вопроса:', error);
            }
        }

        // Загрузка правильных ответов
        async function loadCorrectAnswers(questionId) {
            try {
                const result = await apiRequest('get-quiz-questions');
                if (result && !result.error) {
                    const question = Array.isArray(result) 
                        ? result.find(q => q.id === questionId)
                        : result;
                    
                    if (question && question.answers) {
                        // Обновляем текст вопроса
                        document.getElementById('answers-question-text').textContent = question.question_text;
                        
                        // Отображаем ответы с выделением правильных
                        const answersContainer = document.getElementById('correct-answers-container');
                        if (answersContainer) {
                            let html = '';
                            question.answers.forEach((answer, index) => {
                                const isCorrect = answer.is_correct;
                                const answerClass = isCorrect ? 'correct' : '';
                                html += `
                                    <div class="answer-option ${answerClass}">
                                        ${String.fromCharCode(65 + index)}. ${answer.answer_text}
                                        ${isCorrect ? ' ✓' : ''}
                                    </div>
                                `;
                            });
                            answersContainer.innerHTML = html;
                        }
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки ответов:', error);
            }
        }

        // Загрузка статистики ответов
        async function loadAnswerStats(questionId) {
            try {
                // Здесь можно добавить API для получения статистики ответов
                // Пока используем заглушку
                const statsElement = document.getElementById('stats-content');
                if (statsElement) {
                    statsElement.innerHTML = `
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 15px;">
                            <div style="background: #e8f4fd; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 2rem; color: #3498db; font-weight: bold;">75%</div>
                                <div style="color: #2c3e50;">Участников ответили</div>
                            </div>
                            <div style="background: #d4edda; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 2rem; color: #28a745; font-weight: bold;">60%</div>
                                <div style="color: #2c3e50;">Правильных ответов</div>
                            </div>
                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px;">
                                <div style="font-size: 2rem; color: #856404; font-weight: bold;">А</div>
                                <div style="color: #2c3e50;">Самый частый ответ</div>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Ошибка загрузки статистики:', error);
            }
        }

        // Загрузка результатов
        async function loadResults() {
            try {
                const result = await apiRequest('get-quiz-participants');
                if (result && result.success && result.participants) {
                    const rankingBody = document.getElementById('ranking-body');
                    if (rankingBody) {
                        let html = '';
                        result.participants.forEach((participant, index) => {
                            html += `
                                <tr>
                                    <td>
                                        ${index < 3 ? 
                                            `<div class="medal-cell medal-${index + 1}">${index + 1}</div>` : 
                                            index + 1
                                        }
                                    </td>
                                    <td>${participant.team}</td>
                                    <td>${participant.correct_answers || 0}</td>
                                    <td>${participant.answers_count || 0}</td>
                                    <td style="font-weight: bold; font-size: 1.2rem;">
                                        ${participant.quiz_score || 0}
                                    </td>
                                </tr>
                            `;
                        });
                        rankingBody.innerHTML = html;
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки результатов:', error);
            }
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', () => {
            // Загружаем начальное состояние
            loadQuizState();
            
            // Настраиваем периодическое обновление
            updateInterval = setInterval(() => {
                loadQuizState();
                
                // Если показываем результаты, обновляем их
                if (currentScreen === 'results') {
                    loadResults();
                }
            }, 2000);
            
            // Загружаем результаты если нужно
            if (currentScreen === 'results') {
                loadResults();
            }
        });

        // Очистка интервалов при закрытии страницы
        window.addEventListener('beforeunload', () => {
            if (timerInterval) clearInterval(timerInterval);
            if (updateInterval) clearInterval(updateInterval);
        });
    </script>
</body>
</html>