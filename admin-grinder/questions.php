<?php
//questions.php - Управление вопросами
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
    <title>Управление вопросами - Админ-панель</title>
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
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .question-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .question-actions {
            margin-top: 10px;
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
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            box-sizing: border-box;
        }

        /* ФОРМЫ */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #bdc3c7;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* ЧЕКБОКС - ИСПРАВЛЕННЫЙ */
        .checkbox-container {
            margin: 20px 0;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #ddd;
            transition: all 0.2s;
        }

        .checkbox-label:hover {
            background: #e8f4fd;
            border-color: #3498db;
        }

        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
        }

        .checkbox-label span {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            white-space: nowrap;
        }

        /* БОНУСНЫЕ БАЛЛЫ */
        .bonus-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            display: none;
        }

        .bonus-section h4 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 16px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 8px;
        }

        .bonus-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }

        .bonus-item {
            text-align: center;
            background: white;
            padding: 15px;
            border-radius: 6px;
        }

        .bonus-item label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #7f8c8d;
            font-size: 13px;
        }

        .bonus-input {
            text-align: center;
            font-weight: bold;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            width: 80px;
        }

        /* ИЗОБРАЖЕНИЯ */
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #bdc3c7;
            border-radius: 6px;
            background: #f8f9fa;
            cursor: pointer;
        }

        .image-preview-container {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            display: none;
            text-align: center;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
            margin: 0 auto 15px auto; /* Увеличил отступ снизу */
            display: block; /* Важно: делаем блочным */
        }

        .remove-image-btn {
            display: block; /* Меняем на block чтобы была на новой строке */
            margin: 15px auto 0 auto; /* Центрируем и добавляем отступ сверху */
            padding: 8px 15px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            width: auto; /* Ширина по содержимому */
        }

        .remove-image-btn:hover {
            background: #c0392b;
        }

        /* ОТОБРАЖЕНИЕ ВОПРОСОВ */
        .question-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .points-badge {
            background: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .bonus-badge {
            background: #27ae60;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .image-badge {
            background: #9b59b6;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .question-image {
            max-width: 300px;
            max-height: 200px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
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
                <li><a href="questions.php" class="active">❓ Управление вопросами</a></li>
                <li><a href="statistics.php">📈 Детальная статистика</a></li>
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
                <h1>Управление вопросами</h1>
                
                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button class="btn btn-success" onclick="showAddQuestionModal()">➕ Добавить вопрос</button>
                    <button class="btn" onclick="showImportModal()">📁 Импорт из файла</button>
                    <button class="btn" onclick="exportQuestions()">📤 Экспорт вопросов</button>
                    <button class="btn btn-danger" onclick="clearAllQuestions()">🗑️ Очистить все</button>
                </div>
            </div>
            
            <div class="card">
                <h2>Список вопросов</h2>
                <div id="questions-list">
                    <p>Загрузка вопросов...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно добавления/редактирования вопроса -->
    <div id="question-modal" class="modal">
        <div class="modal-content">
            <h3 id="modal-title">Добавить вопрос</h3>
            <form id="question-form">
                <input type="hidden" id="edit-question-id">
                
                <div class="form-group">
                    <label for="modal-question-text">Текст вопроса:</label>
                    <textarea id="modal-question-text" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="modal-question-answer">Правильный ответ:</label>
                    <input type="text" id="modal-question-answer" required>
                </div>
                
                <div class="form-group">
                    <label for="modal-question-points">Баллы за правильный ответ:</label>
                    <input type="number" id="modal-question-points" value="1" min="1" max="100" required>
                </div>

                <div class="form-group">
                    <label for="modal-question-image">Изображение к вопросу (опционально):</label>
                    <input type="file" id="modal-question-image" accept="image/*" onchange="previewImage(this)">
                    <div id="modal-image-preview" class="image-preview-container">
                        <img id="modal-preview-img" src="" class="preview-image">
                        <button type="button" onclick="removeImage()" class="remove-image-btn">× Удалить изображение</button>
                    </div>
                </div>
                
                <div class="checkbox-container">
                    <label class="checkbox-label">
                        <input type="checkbox" id="modal-has-bonus-points" onchange="toggleBonusPoints()">
                        <span>Бонусные баллы за скорость</span>
                    </label>
                </div>
                
                <div id="modal-bonus-points-section" class="bonus-section">
                    <h4>🎯 Бонусные баллы за скорость ответа</h4>
                    <div class="bonus-grid">
                        <div class="bonus-item">
                            <label for="modal-bonus-first">🥇 1-е место:</label>
                            <input type="number" id="modal-bonus-first" value="0" min="0" max="50" class="bonus-input">
                        </div>
                        <div class="bonus-item">
                            <label for="modal-bonus-second">🥈 2-е место:</label>
                            <input type="number" id="modal-bonus-second" value="0" min="0" max="50" class="bonus-input">
                        </div>
                        <div class="bonus-item">
                            <label for="modal-bonus-third">🥉 3-е место:</label>
                            <input type="number" id="modal-bonus-third" value="0" min="0" max="50" class="bonus-input">
                        </div>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                    <button type="button" class="btn" onclick="hideQuestionModal()">Отмена</button>
                    <button type="submit" class="btn btn-success">Сохранить вопрос</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно импорта -->
    <div id="import-modal" class="modal">
        <div class="modal-content">
            <h3>Импорт вопросов из файла</h3>
            <p><strong>Формат:</strong> Каждая строка: вопрос|ответ</p>
            <p><strong>Пример:</strong><br>Сколько будет 2+2?|4<br>Столица России?|Москва</p>
            
            <div class="form-group">
                <label for="file-content">Содержимое файла:</label>
                <textarea id="file-content" rows="10" placeholder="Вставьте содержимое файла сюда..."></textarea>
            </div>
            
            <div id="import-status"></div>
            
            <div style="text-align: right;">
                <button type="button" class="btn" onclick="hideImportModal()">Отмена</button>
                <button type="button" class="btn btn-success" onclick="importQuestions()">Импортировать</button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = 'http://localhost/math-grinder-php';
        let currentImageFile = null;
        let currentImagePath = '';

        // API функции
        async function apiRequest(action, data = null) {
            const options = {
                method: data ? 'POST' : 'GET',
                headers: {}
            };
            
            if (data && !(data instanceof FormData)) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            } else if (data instanceof FormData) {
                options.body = data;
            }
            
            try {
                const response = await fetch(`../api.php?action=${action}`, options);
                const text = await response.text();
                return JSON.parse(text);
            } catch (error) {
                console.error('API Error:', error);
                return {error: 'Ошибка соединения'};
            }
        }
        
        // Загрузка вопросов
        async function loadQuestions() {
            const result = await apiRequest('get-questions');
            const container = document.getElementById('questions-list');
            
            if (result.error) {
                container.innerHTML = '<p>Ошибка загрузки вопросов</p>';
                return;
            }
            
            if (result.length === 0) {
                container.innerHTML = '<p>Вопросы не добавлены</p>';
                return;
            }
            
            const sortedQuestions = [...result].sort((a, b) => a.id - b.id);
            
            let html = '';
            sortedQuestions.forEach((question, index) => {
                const hasBonus = Boolean(question.has_bonus_points);
                const hasImage = question.image_path && question.image_path !== 'null' && question.image_path !== '';
                
                html += `
                    <div class="question-item">
                        <div class="question-meta">
                            <h4 style="margin: 0;">Вопрос #${index + 1}</h4>
                            <span class="points-badge">${question.points} баллов</span>
                            ${hasImage ? '<span class="image-badge">🖼️ С изображением</span>' : ''}
                            ${hasBonus ? `
                                <span class="bonus-badge">
                                    ⚡ Бонусы: +${question.bonus_first_points}/+${question.bonus_second_points}/+${question.bonus_third_points}
                                </span>
                            ` : ''}
                        </div>
                        
                        ${hasImage ? `
                            <div style="margin: 10px 0;">
                                <img src="${BASE_URL}${question.image_path}?t=${new Date().getTime()}" 
                                    class="question-image" 
                                    alt="Изображение вопроса"
                                    onerror="this.style.display='none'">
                            </div>
                        ` : ''}
                        
                        <p><strong>Текст:</strong> ${question.text}</p>
                        <p><strong>Ответ:</strong> ${question.answer}</p>
                        
                        <div class="question-actions">
                            <button class="btn btn-warning" onclick="editQuestion(
                                ${question.id}, 
                                '${question.text.replace(/'/g, "\\'")}', 
                                '${question.answer.replace(/'/g, "\\'")}',
                                ${question.points},
                                ${hasBonus},
                                ${question.bonus_first_points},
                                ${question.bonus_second_points},
                                ${question.bonus_third_points},
                                '${question.image_path || ''}'
                            )">✏️ Редактировать</button>
                            <button class="btn btn-danger" onclick="deleteQuestion(${question.id})">🗑️ Удалить</button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
        
        // Модальные окна
        function showAddQuestionModal() {
            document.getElementById('modal-title').textContent = 'Добавить вопрос';
            document.getElementById('edit-question-id').value = '';
            document.getElementById('modal-question-text').value = '';
            document.getElementById('modal-question-answer').value = '';
            document.getElementById('modal-question-points').value = '1';
            
            // Сбрасываем чекбокс
            document.getElementById('modal-has-bonus-points').checked = false;
            document.getElementById('modal-bonus-first').value = '0';
            document.getElementById('modal-bonus-second').value = '0';
            document.getElementById('modal-bonus-third').value = '0';
            document.getElementById('modal-bonus-points-section').style.display = 'none';
            
            removeImage();
            document.getElementById('question-modal').style.display = 'block';
        }
        
        function hideQuestionModal() {
            document.getElementById('question-modal').style.display = 'none';
        }
        
        function showImportModal() {
            document.getElementById('file-content').value = '';
            document.getElementById('import-status').innerHTML = '';
            document.getElementById('import-modal').style.display = 'block';
        }
        
        function hideImportModal() {
            document.getElementById('import-modal').style.display = 'none';
        }
        
        function editQuestion(id, text, answer, points = 1, hasBonus = false, bonusFirst = 0, bonusSecond = 0, bonusThird = 0, imagePath = '') {
            document.getElementById('modal-title').textContent = 'Редактировать вопрос';
            document.getElementById('edit-question-id').value = id;
            document.getElementById('modal-question-text').value = text;
            document.getElementById('modal-question-answer').value = answer;
            document.getElementById('modal-question-points').value = points;
            
            // ИСПРАВЛЕНИЕ: Правильное восстановление чекбокса
            // Преобразуем разные форматы в boolean
            const hasBonusBool = hasBonus == 1 || hasBonus === true || hasBonus === 'true' || hasBonus === '1';
            console.log('Setting checkbox state:', hasBonusBool, 'from value:', hasBonus, 'type:', typeof hasBonus);
            
            document.getElementById('modal-has-bonus-points').checked = hasBonusBool;
            
            // Всегда устанавливаем значения бонусных баллов (даже если чекбокс выключен)
            document.getElementById('modal-bonus-first').value = bonusFirst || '0';
            document.getElementById('modal-bonus-second').value = bonusSecond || '0';
            document.getElementById('modal-bonus-third').value = bonusThird || '0';
            
            // Показываем/скрываем секцию бонусов
            document.getElementById('modal-bonus-points-section').style.display = hasBonusBool ? 'block' : 'none';
            
            // Изображение
            currentImagePath = imagePath || '';
            console.log('Editing question image path:', imagePath);
            
            if (imagePath && imagePath !== 'null' && imagePath !== '' && imagePath !== null) {
                const timestamp = new Date().getTime();
                const imageUrl = BASE_URL + imagePath + '?t=' + timestamp;
                
                document.getElementById('modal-preview-img').src = imageUrl;
                document.getElementById('modal-image-preview').style.display = 'block';
                currentImagePath = imagePath;
                
                console.log('Image preview set to:', imageUrl);
            } else {
                removeImage();
                console.log('No image to preview');
            }
            
            document.getElementById('question-modal').style.display = 'block';
        }
        
        // Обработка форм
        document.getElementById('question-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const id = document.getElementById('edit-question-id').value;
            const text = document.getElementById('modal-question-text').value;
            const answer = document.getElementById('modal-question-answer').value;
            const points = parseInt(document.getElementById('modal-question-points').value);
            const hasBonus = document.getElementById('modal-has-bonus-points').checked ? 'true' : 'false';
            const bonusFirst = parseInt(document.getElementById('modal-bonus-first').value) || 0;
            const bonusSecond = parseInt(document.getElementById('modal-bonus-second').value) || 0;
            const bonusThird = parseInt(document.getElementById('modal-bonus-third').value) || 0;
            
            
            // Используем FormData для поддержки файлов
            const formData = new FormData();
            if (id) formData.append('id', id);
            formData.append('text', text);
            formData.append('answer', answer);
            formData.append('points', points);
            formData.append('has_bonus_points', hasBonus ? '1' : '0');
            formData.append('bonus_first_points', bonusFirst);
            formData.append('bonus_second_points', bonusSecond);
            formData.append('bonus_third_points', bonusThird);
            
            // Добавляем флаг удаления изображения
            if (currentImagePath === 'DELETE_IMAGE') {
                formData.append('delete_image', 'true');
            }

            // Добавляем изображение если есть
            const imageInput = document.getElementById('modal-question-image');
            if (imageInput.files[0]) {
                formData.append('image', imageInput.files[0]);
                console.log('Image added to form data');
            }
            
            const action = id ? 'update-question' : 'add-question';
            
            try {
                console.log('Sending request to:', action);
                
                const response = await fetch(`../api.php?action=${action}`, {
                    method: 'POST',
                    body: formData
                    // НЕ добавляем Content-Type - браузер сам установит с boundary
                });
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    alert('Ошибка сервера: неверный формат ответа');
                    return;
                }
                
                if (result.success) {
                    hideQuestionModal();
                    loadQuestions();
                    alert('Вопрос сохранен!');
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                console.error('Network error:', error);
                alert('Ошибка сети: ' + error.message);
            }
        });

        
        async function deleteQuestion(id) {
            if (confirm('Вы уверены, что хотите удалить этот вопрос?')) {
                const result = await apiRequest('delete-question', {id: id});
                if (result.success) {
                    alert('Вопрос удален!');
                    loadQuestions();
                } else {
                    alert('Ошибка при удалении вопроса');
                }
            }
        }

        // Работа с изображениями
        function previewImage(input) {
            const preview = document.getElementById('modal-image-preview');
            const img = document.getElementById('modal-preview-img');
            
            if (input.files && input.files[0]) {
                currentImageFile = input.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
                currentImageFile = null;
            }
        }

        function removeImage() {
            document.getElementById('modal-question-image').value = '';
            document.getElementById('modal-image-preview').style.display = 'none';
            currentImageFile = null;
            
            // Устанавливаем специальный флаг для удаления изображения на сервере
            currentImagePath = 'DELETE_IMAGE'; // Специальное значение для сервера
        }

        function toggleBonusPoints() {
            const bonusSection = document.getElementById('modal-bonus-points-section');
            const hasBonus = document.getElementById('modal-has-bonus-points').checked;
            bonusSection.style.display = hasBonus ? 'block' : 'none';
        }
        
        async function importQuestions() {
            const content = document.getElementById('file-content').value.trim();
            const status = document.getElementById('import-status');
            
            if (!content) {
                status.innerHTML = '<p style="color: red;">Введите содержимое файла</p>';
                return;
            }
            
            status.innerHTML = '<p>Импорт вопросов...</p>';
            
            const result = await apiRequest('import-questions', {file_content: content});
            if (result.success) {
                let message = `<p style="color: green;">${result.message}</p>`;
                if (result.errors && result.errors.length > 0) {
                    message += `<div style="max-height: 100px; overflow-y: auto;">
                        <strong>Ошибки:</strong>
                        <ul style="color: red;">${result.errors.map(e => `<li>${e}</li>`).join('')}</ul>
                    </div>`;
                }
                status.innerHTML = message;
                setTimeout(() => {
                    hideImportModal();
                    loadQuestions();
                }, 2000);
            } else {
                status.innerHTML = `<p style="color: red;">Ошибка: ${result.error}</p>`;
            }
        }
        
        async function exportQuestions() {
            const result = await apiRequest('get-questions');
            if (result.error) {
                alert('Ошибка экспорта: ' + result.error);
                return;
            }
            
            let content = '';
            result.forEach(q => {
                content += `${q.text}|${q.answer}\n`;
            });
            
            const blob = new Blob([content], {type: 'text/plain'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `questions_${new Date().toISOString().split('T')[0]}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        }
        
        async function clearAllQuestions() {
            if (confirm('ВНИМАНИЕ! Удалить ВСЕ вопросы?')) {
                const result = await apiRequest('clear-all-questions', {});
                if (result.success) {
                    loadQuestions();
                    alert(result.message);
                } else {
                    alert('Ошибка: ' + result.error);
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
        document.addEventListener('DOMContentLoaded', loadQuestions);
    </script>
</body>
</html>