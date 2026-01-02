<?php
// quiz-scoreboard.php - Табло рейтинга
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Табло рейтинга - Математическая мясорубка</title>
    <link rel="stylesheet" href="css/light-scoreboard.css">
    <script>
        let lastPhase = null;
        let lastParticipantsCount = 0;
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Табло запущено');
            loadData();
        });

        async function loadData() {
            try {
                console.log('Загружаем данные...');
                
                // 1. Получаем состояние квиза
                const stateResponse = await fetch('api.php?action=get-quiz-scoreboard-state');
                const state = await stateResponse.json();
                
                // 2. Получаем сессию квиза (оттуда берем фазу)
                const sessionResponse = await fetch('api.php?action=get-quiz-session');
                const session = await sessionResponse.json();
                
                // 3. Обновляем заголовок
                updateHeader(state, session);
                
                // 4. Проверяем, нужно ли обновлять рейтинг
                if (shouldUpdateRanking(state, session)) {
                    console.log('Обновляем рейтинг...');
                    // Получаем участников
                    const participantsResponse = await fetch('api.php?action=get-quiz-participants');
                    const participants = await participantsResponse.json();
                    
                    if (participants.success && participants.participants) {
                        updateRankingTable(participants.participants);
                    }
                }
                
            } catch (error) {
                console.error('Ошибка загрузки:', error);
            }
            
            // Повторяем каждые 1 секунду
            setTimeout(loadData, 1000);
        }
        
        function shouldUpdateRanking(state, session) {
            const eventStatus = state.event_state?.event_status || 'not_started';
            const currentPhase = session.success ? session.session?.phase : 'waiting';
            const participantsCount = state.total_participants || 0;
            
            // Сохраняем текущие значения для сравнения
            const phaseChanged = currentPhase !== lastPhase;
            const participantsChanged = participantsCount !== lastParticipantsCount;
            
            lastPhase = currentPhase;
            lastParticipantsCount = participantsCount;
            
            // Обновляем рейтинг если:
            // 1. Фаза НЕ 'question' (т.е. waiting, answers или finished)
            // 2. Или изменилось количество участников
            // 3. Или статус мероприятия 'finished'
            
            if (currentPhase === 'question') {
                // В фазе вопроса НЕ обновляем, если только не изменилось количество участников
                return participantsChanged;
            }
            
            // Во всех остальных случаях обновляем
            return true;
        }
        
        function updateHeader(state, session) {
            // Обновляем заголовок
            const eventName = state.event_state?.event_name || 'Математический квиз';
            document.getElementById('event-title').textContent = eventName;
            
            // Определяем фазу из сессии
            let currentPhase = 'waiting';
            let currentPhaseText = 'ожидание';
            let phaseColor = '#f39c12';
            
            if (session.success && session.session) {
                currentPhase = session.session.phase;
                
                if (currentPhase === 'question') {
                    currentPhaseText = 'вопрос';
                    phaseColor = '#3498db';
                } else if (currentPhase === 'answers') {
                    currentPhaseText = 'ответы';
                    phaseColor = '#27ae60';
                }
            }
            
            // Обновляем статус
            const eventStatus = state.event_state?.event_status || 'not_started';
            const statusElement = document.getElementById('event-status');
            
            if (eventStatus === 'running') {
                statusElement.textContent = 'Идёт';
                statusElement.className = 'event-status status-running';
            } else if (eventStatus === 'finished') {
                statusElement.textContent = 'Завершён';
                statusElement.className = 'event-status status-finished';
                currentPhaseText = 'завершено';
                phaseColor = '#e74c3c';
            } else {
                statusElement.textContent = 'Ожидание';
                statusElement.className = 'event-status status-not-started';
            }
            
            // Обновляем фазу
            const phaseElement = document.getElementById('current-phase');
            phaseElement.textContent = currentPhaseText;
            phaseElement.style.color = phaseColor;
            phaseElement.style.fontWeight = 'bold';
            
            // Обновляем количество участников
            document.getElementById('participants-count').textContent = 
                state.total_participants || 0;
        }
        
        function updateRankingTable(participants) {
            const tbody = document.getElementById('ranking-body');
            
            // Сортируем по баллам
            participants.sort((a, b) => {
                const scoreA = a.total_score || a.quiz_score || 0;
                const scoreB = b.total_score || b.quiz_score || 0;
                return scoreB - scoreA;
            });
            
            let html = '';
            participants.forEach((participant, index) => {
                const rank = index + 1;
                const team = participant.team || `Участник ${participant.id}`;
                const score = participant.total_score || participant.quiz_score || 0;
                
                let rowClass = '';
                if (index === 0) rowClass = 'highlight-row';
                
                html += `<tr${rowClass ? ' class="' + rowClass + '"' : ''}>
                    <td>${rank}</td>
                    <td>${team}</td>
                    <td>${score}</td>
                </tr>`;
            });
            
            // Плавное обновление только если данные изменились
            if (tbody.innerHTML !== html) {
                tbody.style.opacity = '0.8';
                setTimeout(() => {
                    tbody.innerHTML = html;
                    tbody.style.opacity = '1';
                }, 100);
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1 id="event-title">Математический квиз</h1>
            <div class="event-info">
                <span>Статус: <span id="event-status" class="event-status status-not-started">Ожидание</span></span>
                <span>Фаза: <span id="current-phase">ожидание</span></span>
                <span>Участников: <span id="participants-count">0</span></span>
            </div>
        </header>
        
        <div class="table-container">
            <table class="detailed-table">
                <thead>
                    <tr>
                        <th>Место</th>
                        <th>Команда</th>
                        <th>Баллы</th>
                    </tr>
                </thead>
                <tbody id="ranking-body">
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px; color: #7f8c8d;">
                            Загрузка рейтинга...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>