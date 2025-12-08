<?php
// admin-quiz/questions.php - Управление квиз-вопросами
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
        header('Location: ../admin-grinder/main.php');
        exit;
    }
} catch (PDOException $e) {
    // Оставляем в текущей ветке при ошибке
}

// Получаем статистику вопросов
$questions_stats = [
    'total_questions' => 0,
    'single_choice' => 0,
    'multiple_choice' => 0,
    'total_answers' => 0
];

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_questions,
            SUM(CASE WHEN question_type = 'single' THEN 1 ELSE 0 END) as single_choice,
            SUM(CASE WHEN question_type = 'multiple' THEN 1 ELSE 0 END) as multiple_choice
        FROM quiz_questions
    ");
    $stats = $stmt->fetch();
    $questions_stats['total_questions'] = $stats['total_questions'];
    $questions_stats['single_choice'] = $stats['single_choice'];
    $questions_stats['multiple_choice'] = $stats['multiple_choice'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_answers FROM quiz_answers");
    $questions_stats['total_answers'] = $stmt->fetch()['total_answers'];
    
} catch (PDOException $e) {
    // Ошибка - используем значения по умолчанию
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление квиз-вопросами - Админ-панель</title>
    <link rel="stylesheet" href="../css/styles.css">
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
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        
        textarea.form-input {
            resize: vertical;
            min-height: 100px;
        }
        
        select.form-input {
            background-color: white;
            cursor: pointer;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .quiz-question-item {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.3s;
        }
        
        .quiz-question-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .question-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-primary {
            background: #3498db;
            color: white;
        }
        
        .badge-success {
            background: #27ae60;
            color: white;
        }
        
        .badge-warning {
            background: #f39c12;
            color: white;
        }
        
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        
        .answers-container {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .answer-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #6c757d;
        }
        
        .answer-item.correct {
            background: #d4edda;
            border-left-color: #28a745;
        }
        
        .answer-text {
            flex: 1;
            margin-right: 15px;
        }
        
        .answer-points {
            width: 60px;
            text-align: center;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .question-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .time-settings {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        
        .answer-controls {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .answer-form-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .answer-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        .answer-checkbox {
            width: 20px;
            height: 20px;
        }
        
        .answer-points-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            text-align: center;
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
        
        .stat-card.success {
            border-left-color: #27ae60;
        }
        
        .stat-card.info {
            border-left-color: #17a2b8;
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
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Админ-панель</h2>
                <small style="color: #bdc3c7;">Режим: Квиз</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="main.php">📊 Главная</a></li>
                <li><a href="questions.php" class="active">🎯 Управление квиз-вопросами</a></li>
                <?php if ($_SESSION['is_superadmin'] ?? false): ?>
                <li><a href="../admin-users.php">👥 Администраторы</a></li>
                <?php endif; ?>
                <li><a href="../admin-login.php?logout=1" onclick="return confirm('Вы уверены, что хотите выйти?')">🚪 Выйти</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="card">
                <h1>🎯 Управление квиз-вопросами</h1>
                <p>Создавайте и управляйте вопросами для квиза с вариантами ответов, таймерами и настройками баллов.</p>
                
                <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                    <button class="btn btn-success" onclick="showAddQuestionModal()">➕ Добавить вопрос</button>
                    <button class="btn" onclick="reorderQuestions()">📋 Изменить порядок</button>
                    <button class="btn" onclick="importQuestions()">📥 Импорт вопросов</button>
                    <button class="btn btn-danger" onclick="clearAllQuestions()">🗑️ Очистить все</button>
                </div>
            </div>
            
            <!-- Статистика -->
            <div class="card">
                <h2>📊 Статистика вопросов</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $questions_stats['total_questions']; ?></div>
                        <div class="stat-label">Всего вопросов</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-number"><?php echo $questions_stats['single_choice']; ?></div>
                        <div class="stat-label">Одиночный выбор</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-number"><?php echo $questions_stats['multiple_choice']; ?></div>
                        <div class="stat-label">Множественный выбор</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-number"><?php echo $questions_stats['total_answers']; ?></div>
                        <div class="stat-label">Всего ответов</div>
                    </div>
                </div>
            </div>
            
            <!-- Список вопросов -->
            <div class="card">
                <h2>Список вопросов квиза</h2>
                <div id="questions-list">
                    <p>Загрузка вопросов...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления/редактирования вопроса -->
    <div id="question-modal" class="modal">
        <div class="modal-content">
            <h3 id="modal-title">Добавить вопрос квиза</h3>
            <form id="question-form">
                <input type="hidden" id="edit-question-id">
                
                <div class="form-group">
                    <label for="question-text">Текст вопроса:</label>
                    <textarea id="question-text" class="form-input" rows="4" required placeholder="Введите текст вопроса..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="question-type">Тип вопроса:</label>
                    <select id="question-type" class="form-input">
                        <option value="single">Один правильный ответ</option>
                        <option value="multiple">Несколько правильных ответов</option>
                    </select>
                </div>
                
                <div class="time-settings">
                    <div class="form-group">
                        <label for="question-time">Время на вопрос (секунды):</label>
                        <input type="number" id="question-time" class="form-input" value="30" min="5" max="300" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="answer-time">Время показа ответов (секунды):</label>
                        <input type="number" id="answer-time" class="form-input" value="10" min="5" max="60" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Варианты ответов:</label>
                    <div class="answer-controls">
                        <div id="answers-container">
                            <!-- Ответы будут добавляться динамически -->
                        </div>
                        <button type="button" class="btn" onclick="addAnswerField()" style="margin-top: 10px;">➕ Добавить ответ</button>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                    <button type="button" class="btn" onclick="hideQuestionModal()">Отмена</button>
                    <button type="submit" class="btn btn-success">Сохранить вопрос</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Глобальные переменные
        let answerCount = 0;
        let currentAnswers = [];

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

        // Загрузка списка вопросов
        async function loadQuestions() {
            const result = await apiRequest('get-quiz-questions');
            const container = document.getElementById('questions-list');
            
            if (result.error) {
                container.innerHTML = '<p>Ошибка загрузки вопросов: ' + result.error + '</p>';
                return;
            }
            
            if (result.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🎯</div>
                        <h3>Вопросы не добавлены</h3>
                        <p>Создайте первый вопрос для вашего квиза</p>
                        <button class="btn btn-success" onclick="showAddQuestionModal()">➕ Добавить первый вопрос</button>
                    </div>
                `;
                return;
            }
            
            let html = '';
            result.forEach((question, index) => {
                const questionTypeText = question.question_type === 'single' ? 'Один ответ' : 'Несколько ответов';
                const questionTypeClass = question.question_type === 'single' ? 'badge-success' : 'badge-warning';
                const correctAnswers = question.answers ? question.answers.filter(a => a.is_correct).length : 0;
                
                html += `
                    <div class="quiz-question-item" data-question-id="${question.id}">
                        <div class="question-header">
                            <div style="flex: 1;">
                                <h3 style="margin: 0 0 10px 0;">Вопрос #${question.display_order || index + 1}</h3>
                                <div class="question-meta">
                                    <span class="badge ${questionTypeClass}">${questionTypeText}</span>
                                    <span class="badge badge-primary">${question.question_time}с на вопрос</span>
                                    <span class="badge badge-info">${question.answer_time}с на ответы</span>
                                    <span class="badge" style="background: #6c757d; color: white;">${correctAnswers} правильных</span>
                                </div>
                            </div>
                            <div style="font-size: 18px; color: #6c757d;">#${question.display_order || index + 1}</div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong>Текст вопроса:</strong>
                            <p style="margin: 5px 0; background: white; padding: 10px; border-radius: 4px;">${question.question_text}</p>
                        </div>
                        
                        ${question.answers && question.answers.length > 0 ? `
                            <div class="answers-container">
                                <strong>Варианты ответов:</strong>
                                ${question.answers.map(answer => `
                                    <div class="answer-item ${answer.is_correct ? 'correct' : ''}">
                                        <div class="answer-text">${answer.answer_text}</div>
                                        ${answer.is_correct ? '<span style="color: green; margin: 0 10px;">✓ Правильный</span>' : ''}
                                        ${answer.points > 0 ? `<span class="answer-points" style="color: blue;">+${answer.points}</span>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        ` : '<p>Нет вариантов ответов</p>'}
                        
                        <div class="question-actions">
                            <button class="btn btn-warning" onclick="editQuestion(${question.id})">✏️ Редактировать</button>
                            <button class="btn btn-danger" onclick="deleteQuestion(${question.id})">🗑️ Удалить</button>
                            <button class="btn" onclick="moveQuestionUp(${question.id})" ${index === 0 ? 'disabled' : ''}>⬆️ Вверх</button>
                            <button class="btn" onclick="moveQuestionDown(${question.id})" ${index === result.length - 1 ? 'disabled' : ''}>⬇️ Вниз</button>
                            <button class="btn" onclick="duplicateQuestion(${question.id})">📋 Дублировать</button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // Управление ответами в форме
        function addAnswerField(answer = { text: '', is_correct: false, points: 0 }) {
            const container = document.getElementById('answers-container');
            const answerId = `answer-${++answerCount}`;
            
            const answerHtml = `
                <div class="answer-form-item" id="${answerId}">
                    <input type="text" class="answer-input" placeholder="Текст ответа" value="${answer.text}" required>
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" class="answer-checkbox" ${answer.is_correct ? 'checked' : ''}>
                        Правильный
                    </label>
                    <input type="number" class="answer-points-input" placeholder="Баллы" value="${answer.points}" min="0" max="100">
                    <button type="button" class="btn btn-danger" onclick="removeAnswerField('${answerId}')" style="padding: 5px 8px;">×</button>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', answerHtml);
        }

        function removeAnswerField(id) {
            const element = document.getElementById(id);
            if (element && document.querySelectorAll('.answer-form-item').length > 1) {
                element.remove();
            } else {
                alert('Должен остаться хотя бы один ответ');
            }
        }

        // Модальные окна
        function showAddQuestionModal() {
            document.getElementById('modal-title').textContent = 'Добавить вопрос квиза';
            document.getElementById('edit-question-id').value = '';
            document.getElementById('question-text').value = '';
            document.getElementById('question-type').value = 'single';
            document.getElementById('question-time').value = '30';
            document.getElementById('answer-time').value = '10';
            
            // Очищаем ответы
            document.getElementById('answers-container').innerHTML = '';
            answerCount = 0;
            
            // Добавляем 4 пустых ответа по умолчанию
            for (let i = 0; i < 4; i++) {
                addAnswerField();
            }
            
            document.getElementById('question-modal').style.display = 'block';
        }

        function hideQuestionModal() {
            document.getElementById('question-modal').style.display = 'none';
        }

        // Редактирование вопроса
        async function editQuestion(id) {
            const result = await apiRequest('get-quiz-questions');
            if (result.error) {
                alert('Ошибка загрузки вопроса');
                return;
            }
            
            const question = result.find(q => q.id === id);
            if (!question) {
                alert('Вопрос не найден');
                return;
            }
            
            document.getElementById('modal-title').textContent = 'Редактировать вопрос квиза';
            document.getElementById('edit-question-id').value = question.id;
            document.getElementById('question-text').value = question.question_text;
            document.getElementById('question-type').value = question.question_type;
            document.getElementById('question-time').value = question.question_time;
            document.getElementById('answer-time').value = question.answer_time;
            
            // Очищаем и заполняем ответы
            document.getElementById('answers-container').innerHTML = '';
            answerCount = 0;
            
            if (question.answers && question.answers.length > 0) {
                question.answers.forEach(answer => {
                    addAnswerField({
                        text: answer.answer_text,
                        is_correct: answer.is_correct,
                        points: answer.points
                    });
                });
            } else {
                // Добавляем пустые ответы если нет существующих
                for (let i = 0; i < 4; i++) {
                    addAnswerField();
                }
            }
            
            document.getElementById('question-modal').style.display = 'block';
        }

        // Обработка формы
        document.getElementById('question-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const id = document.getElementById('edit-question-id').value;
            const questionText = document.getElementById('question-text').value;
            const questionType = document.getElementById('question-type').value;
            const questionTime = parseInt(document.getElementById('question-time').value);
            const answerTime = parseInt(document.getElementById('answer-time').value);
            
            // Собираем ответы
            const answers = [];
            const answerElements = document.querySelectorAll('#answers-container .answer-form-item');
            
            answerElements.forEach(element => {
                const text = element.querySelector('.answer-input').value.trim();
                const isCorrect = element.querySelector('.answer-checkbox').checked;
                const points = parseInt(element.querySelector('.answer-points-input').value) || 0;
                
                if (text) {
                    answers.push({
                        text: text,
                        is_correct: isCorrect,
                        points: points
                    });
                }
            });
            
            // Валидация
            if (!questionText.trim()) {
                alert('Введите текст вопроса');
                return;
            }
            
            if (answers.length === 0) {
                alert('Добавьте хотя бы один ответ');
                return;
            }
            
            // Проверяем, есть ли правильные ответы
            const hasCorrectAnswers = answers.some(answer => answer.is_correct);
            if (!hasCorrectAnswers) {
                alert('Должен быть хотя бы один правильный ответ');
                return;
            }
            
            // Для одиночного выбора проверяем, что только один ответ правильный
            if (questionType === 'single') {
                const correctAnswersCount = answers.filter(answer => answer.is_correct).length;
                if (correctAnswersCount > 1) {
                    alert('Для типа "Один правильный ответ" может быть только один правильный вариант');
                    return;
                }
            }
            
            const action = id ? 'update-quiz-question' : 'add-quiz-question';
            const data = {
                id: id || null,
                question_text: questionText,
                question_type: questionType,
                question_time: questionTime,
                answer_time: answerTime,
                answers: answers
            };
            
            const result = await apiRequest(action, data);
            
            if (result.success) {
                hideQuestionModal();
                loadQuestions();
                alert('Вопрос сохранен!');
            } else {
                alert('Ошибка: ' + result.error);
            }
        });

        // Управление вопросами
        async function deleteQuestion(id) {
            if (confirm('Вы уверены, что хотите удалить этот вопрос?')) {
                const result = await apiRequest('delete-quiz-question', { id: id });
                if (result.success) {
                    alert('Вопрос удален!');
                    loadQuestions();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            }
        }

        async function moveQuestionUp(id) {
            const result = await apiRequest('move-quiz-question-up', { id: id });
            if (result.success) {
                loadQuestions();
            } else {
                alert('Ошибка: ' + result.error);
            }
        }

        async function moveQuestionDown(id) {
            const result = await apiRequest('move-quiz-question-down', { id: id });
            if (result.success) {
                loadQuestions();
            } else {
                alert('Ошибка: ' + result.error);
            }
        }

        async function duplicateQuestion(id) {
            if (confirm('Создать копию этого вопроса?')) {
                const result = await apiRequest('duplicate-quiz-question', { id: id });
                if (result.success) {
                    alert('Вопрос скопирован!');
                    loadQuestions();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            }
        }

        async function reorderQuestions() {
            alert('Функция изменения порядка вопросов (реализуем позже)');
        }

        async function importQuestions() {
            alert('Функция импорта вопросов (реализуем позже)');
        }

        async function clearAllQuestions() {
            if (confirm('ВНИМАНИЕ! Удалить ВСЕ вопросы квиза?\n\nЭто действие нельзя отменить.')) {
                const result = await apiRequest('clear-all-quiz-questions', {});
                if (result.success) {
                    alert('Все вопросы удалены!');
                    loadQuestions();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            }
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', loadQuestions);
    </script>
</body>
</html>