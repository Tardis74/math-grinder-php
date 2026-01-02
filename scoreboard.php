<?php
// scoreboard.php - Таблица результатов
require_once 'config.php';

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

$mode = get_event_mode();

if ($mode === 'quiz') {
    // Для квиза используем scoreboard-quiz.php
    if (basename($_SERVER['PHP_SELF']) !== 'scoreboard-quiz.php') {
        header('Location: scoreboard-quiz.php');
        exit;
    }
} else {
    // Получаем состояние мясорубки
    $event_state = get_grinder_event_state();
    $is_frozen = $event_state['is_ranking_frozen'] ?? false;
    $event_name = $event_state['event_name'] ?? 'Математическая мясорубка';
    $event_status = $event_state['event_status'] ?? 'not_started';
    $timer_remaining = $event_state['timer_remaining'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Таблица результатов - Математическая мясорубка</title>
    <link rel="stylesheet" href="css/light-scoreboard.css">
</head>
<body>
    <div class="container">
        <header>
            <h1 id="event-title"><?php echo htmlspecialchars($event_name); ?></h1>
            <div class="event-info">
                <div id="timer" class="<?php echo $timer_remaining <= 300 ? 'timer-warning' : ''; ?>">
                    <?php
                    if ($event_status === 'running' && $timer_remaining > 0) {
                        $hours = floor($timer_remaining / 3600);
                        $minutes = floor(($timer_remaining % 3600) / 60);
                        $seconds = $timer_remaining % 60;
                        echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
                    } else {
                        echo '00:00:00';
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
                <div id="freeze-indicator" class="<?php echo $is_frozen ? '' : 'hidden'; ?>">РЕЙТИНГ ЗАМОРОЖЕН</div>
            </div>
        </header>
        
        <main>
            <div class="table-container">
                <table class="detailed-table" id="detailed-scoreboard">
                    <thead>
                        <tr>
                            <th>Место</th>
                            <th>Команда</th>
                            <!-- Заголовки вопросов будут добавлены через JavaScript -->
                            <th class="total-score">Общий балл</th>
                        </tr>
                    </thead>
                    <tbody id="scoreboard-body">
                        <tr>
                            <td colspan="100" style="text-align: center; padding: 20px;">
                                <div class="no-data">
                                    <?php
                                    if ($event_status === 'not_started') {
                                        echo 'Мероприятие еще не началось';
                                    } elseif ($event_status === 'finished') {
                                        echo 'Мероприятие завершено';
                                    } else {
                                        echo 'Данные пока не доступны';
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // API функции
        async function apiRequest(action, data = null) {
            let url = `api.php?action=${action}`;
            const options = {
                method: 'GET',
                headers: {'Content-Type': 'application/json'}
            };
            
            if (data) {
                // Для GET запросов добавляем параметры в URL
                const params = new URLSearchParams(data);
                url += '&' + params.toString();
            }
            
            try {
                const response = await fetch(url, options);
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { error: 'Ошибка соединения с сервером' };
            }
        }

        // Глобальные переменные
        let lastScores = {};
        let isRankingFrozen = <?php echo $is_frozen ? 'true' : 'false'; ?>;
        let eventStatus = '<?php echo $event_status; ?>';
        let timerRemaining = <?php echo $timer_remaining; ?>;
        let questions = [];
        let scoreboardTimerInterval = null;

        // Загрузка состояния мероприятия
        async function loadEventState() {
            try {
                const result = await apiRequest('get-grinder-event-state');
                if (result && !result.error) {
                    updateEventStatus(result);
                    updateTimerDisplay(result.timer_remaining);
                    
                    // Обновляем глобальные переменные
                    if (result.event_status !== eventStatus) {
                        eventStatus = result.event_status;
                    }
                    if (result.timer_remaining !== timerRemaining) {
                        timerRemaining = result.timer_remaining;
                    }
                    if (result.is_ranking_frozen !== isRankingFrozen) {
                        isRankingFrozen = result.is_ranking_frozen;
                        updateFreezeIndicator();
                    }
                    if (result.event_name && result.event_name !== document.getElementById('event-title').textContent) {
                        document.getElementById('event-title').textContent = result.event_name;
                        document.title = `Таблица результатов - ${result.event_name}`;
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки состояния:', error);
            }
        }

        function updateEventStatus(state) {
            const statusElement = document.getElementById('event-status');
            const statusClass = `status-${state.event_status}`;
            
            statusElement.classList.remove('status-not-started', 'status-running', 'status-finished');
            statusElement.classList.add(statusClass);
            
            switch(state.event_status) {
                case 'running':
                    statusElement.textContent = 'ИДЕТ';
                    break;
                case 'finished':
                    statusElement.textContent = 'ЗАВЕРШЕНО';
                    break;
                default:
                    statusElement.textContent = 'НЕ НАЧАЛОСЬ';
            }
        }

        function updateFreezeIndicator() {
            const freezeIndicator = document.getElementById('freeze-indicator');
            if (isRankingFrozen) {
                freezeIndicator.classList.remove('hidden');
            } else {
                freezeIndicator.classList.add('hidden');
            }
        }

        function updateTimerDisplay(remainingSeconds) {
            const timerElement = document.getElementById('timer');
            if (remainingSeconds > 0) {
                const hours = Math.floor(remainingSeconds / 3600);
                const minutes = Math.floor((remainingSeconds % 3600) / 60);
                const seconds = remainingSeconds % 60;
                
                timerElement.textContent = 
                    hours.toString().padStart(2, '0') + ':' + 
                    minutes.toString().padStart(2, '0') + ':' + 
                    seconds.toString().padStart(2, '0');
                
                if (remainingSeconds <= 300) {
                    timerElement.classList.add('timer-warning');
                } else {
                    timerElement.classList.remove('timer-warning');
                }
            } else {
                timerElement.textContent = '00:00:00';
                timerElement.classList.remove('timer-warning');
            }
        }

        // Обновление таблицы результатов
        async function updateScoreboard() {
            if (isRankingFrozen) {
                return;
            }
            
            const result = await apiRequest('get-scoreboard', {
                event_type: 'grinder'
            });
            if (result.error) {
                console.error('Ошибка получения результатов:', result.error);
                return;
            }
            
            const scoreboardBody = document.getElementById('scoreboard-body');
            
            if (!result.participants || result.participants.length === 0) {
                let message = 'Данные пока не доступны';
                if (eventStatus === 'not_started') message = 'Мероприятие еще не началось';
                if (eventStatus === 'finished') message = 'Мероприятие завершено';
                
                scoreboardBody.innerHTML = `<tr><td colspan="100" style="text-align: center; padding: 20px;"><div class="no-data">${message}</div></td></tr>`;
                return;
            }
            
            // Сохраняем вопросы для использования при обновлении
            questions = result.questions || [];
            
            // Обновляем заголовки таблицы
            updateTableHeaders();
            
            // Обновляем тело таблицы
            let html = '';
            
            result.participants.forEach((participant, index) => {
                const previousScore = lastScores[participant.team] || 0;
                const scoreChanged = previousScore !== participant.score;
                const highlightClass = scoreChanged ? 'highlight-row' : '';
                
                html += `
                    <tr class="${highlightClass}" id="participant-${participant.id}">
                        <td>${index + 1}</td>
                        <td>${participant.team}</td>
                `;
                
                // Добавляем ячейки для каждого вопроса
                questions.forEach(question => {
                    const answer = participant.answers.find(a => a.question_id === question.id);
                    if (answer) {
                        if (answer.is_correct) {
                            const orderBadge = answer.answer_order === 1 ? ' 🥇' : answer.answer_order === 2 ? ' 🥈' : '';
                            html += `<td class="correct-answer" title="Порядок ответа: ${answer.answer_order}">+${answer.points}${orderBadge}</td>`;
                        } else {
                            html += `<td class="incorrect-answer">0</td>`;
                        }
                    } else {
                        html += `<td class="no-answer">-</td>`;
                    }
                });
                
                html += `<td class="total-score">${participant.score}</td></tr>`;
                
                // Сохраняем текущий счет для сравнения в следующий раз
                lastScores[participant.team] = participant.score;
            });
            
            scoreboardBody.innerHTML = html;
            
            // Добавляем анимации для измененных строк
            setTimeout(() => {
                result.participants.forEach((participant, index) => {
                    const previousScore = lastScores[participant.team] || 0;
                    const scoreChanged = previousScore !== participant.score;
                    
                    if (scoreChanged) {
                        const element = document.getElementById(`participant-${participant.id}`);
                        if (element) {
                            element.classList.add('highlight-row');
                            setTimeout(() => element.classList.remove('highlight-row'), 2000);
                        }
                    }
                });
            }, 10);
        }

        // Функция для обновления заголовков таблицы
        function updateTableHeaders() {
            const tableHead = document.querySelector('#detailed-scoreboard thead tr');
            
            // Удаляем старые заголовки вопросов (если есть)
            const oldQuestionHeaders = document.querySelectorAll('.question-header');
            oldQuestionHeaders.forEach(header => header.remove());
            
            // Добавляем заголовки для вопросов
            const totalScoreHeader = document.querySelector('.total-score');
            
            questions.forEach((question, index) => {
                const th = document.createElement('th');
                th.className = 'question-header';
                th.textContent = `В${index + 1}`;
                th.title = question.text;
                
                tableHead.insertBefore(th, totalScoreHeader);
            });
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', () => {
            loadEventState();
            updateScoreboard();
            
            // Обновляем состояние каждую секунду
            scoreboardTimerInterval = setInterval(() => {
                loadEventState();
                updateScoreboard();
            }, 1000);
        });
    </script>
</body>
</html>