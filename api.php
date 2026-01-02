<?php
// Временно включаем полную отладку
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Простой тест - если этот код работает, значит проблема в функциях
if ($_GET['action'] == 'test') {
    header('Content-Type: application/json');
    echo json_encode(['test' => 'ok']);
    exit;
}
// Загружаем общие функции
require_once 'config.php';
require_once 'includes/functions.php';

function clean_output() {
    // Убираем любые пробелы и BOM в начале вывода
    if (ob_get_level()) {
        ob_clean();
    }
}

// Функция для JSON ответа
function json_response($data, $status_code = 200) {
    // Очищаем вывод перед отправкой JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Подключение к БД
try {
    $pdo = new PDO("mysql:host=localhost;dbname=math_grinder;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    json_response(['error' => 'Ошибка БД: ' . $e->getMessage()], 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $_GET['action'] ?? '';

// Обработка действий
switch ($action) {
    case 'get-quiz-stats':
        if ($method == 'GET') {
            try {
                // Базовая статистика для квиза
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM quiz_questions");
                $questions_count = $stmt->fetch()['count'];
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM participants");
                $participants_count = $stmt->fetch()['count'];
                
                // Здесь позже добавим логику для текущего вопроса
                $current_question = null;
                
                json_response([
                    'success' => true,
                    'questions_count' => $questions_count,
                    'participants_count' => $participants_count,
                    'current_question' => $current_question
                ]);
                
            } catch (PDOException $e) {
                json_response(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;
    case 'get-quiz-activity':
        if ($method == 'GET') {
            try {
                // Получаем общее количество участников
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM participants WHERE event_type = 'quiz'");
                $total_participants = $stmt->fetch()['count'];
                
                // Получаем количество ответов на текущий вопрос
                $current_answers = 0;
                
                // Получаем текущий активный вопрос
                $stmt = $pdo->query("SELECT quiz_question_id FROM current_quiz_question WHERE is_active = 1 LIMIT 1");
                $currentQuestion = $stmt->fetch();
                
                if ($currentQuestion) {
                    $questionId = $currentQuestion['quiz_question_id'];
                    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM quiz_participant_answers WHERE quiz_question_id = ?");
                    $stmt->execute([$questionId]);
                    $current_answers = $stmt->fetch()['cnt'];
                }
                
                json_response([
                    'success' => true,
                    'total_participants' => $total_participants,
                    'current_answers' => $current_answers,
                    'active_participants' => 0 // Пока оставляем 0, можно реализовать позже
                ]);
                
            } catch (PDOException $e) {
                json_response([
                    'success' => false,
                    'error' => 'Ошибка получения активности: ' . $e->getMessage()
                ]);
            }
        }
        break;
    case 'reset-quiz-data':
        if ($method == 'POST') {
            check_admin_auth();
            
            try {

                // 1. Удаляем ответы на квиз
                $stmt = $pdo->prepare("DELETE FROM quiz_participant_answers");
                $stmt->execute();

                // 2. Удаляем всех участников квиза
                $stmt = $pdo->prepare("DELETE FROM participants WHERE event_type = 'quiz'");
                $stmt->execute();
                
                // 3. Сбрасываем текущий вопрос квиза
                $stmt = $pdo->prepare("DELETE FROM current_quiz_question");
                $stmt->execute();
                
                // 4. Сбрасываем сессию квиза
                $stmt = $pdo->prepare("DELETE FROM quiz_session");
                $stmt->execute();
                
                // 5. Сбрасываем состояние квиза в таблице quiz_events
                $stmt = $pdo->prepare("
                    UPDATE quiz_events SET 
                    event_status = 'not_started',
                    is_accepting_answers = 1,
                    is_ranking_frozen = 0,
                    event_start_time = NULL,
                    event_end_time = NULL,
                    updated_at = NOW()
                    WHERE id = 1
                ");
                $stmt->execute();
                
                // 6. Сбрасываем общее состояние в event_state
                $stmt = $pdo->prepare("
                    UPDATE event_state SET 
                    event_status = 'not_started',
                    is_accepting_answers = 1,
                    is_ranking_frozen = 0,
                    event_start_time = NULL,
                    event_end_time = NULL,
                    updated_at = NOW()
                    WHERE id = 1
                ");
                $stmt->execute();
                
                json_response([
                    'success' => true, 
                    'message' => 'Данные квиза полностью сброшены'
                ]);
                
            } catch (Exception $e) {
                json_response(['success' => false, 'error' => 'Ошибка сброса квиза: ' . $e->getMessage()]);
            }
        }
        break;
    case 'delete-questions-bulk':
        if ($method == 'POST') {
            require_once 'includes/api/bulk-questions.php';
            delete_questions_bulk();
        }
        break;
    case 'start-quiz':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-session.php';
            start_quiz();
        }
        break;
    case 'update-participant-score':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-participants.php';
            update_participant_score($input);
        }
        break;
    case 'add-quiz-question-with-image':
    case 'update-quiz-question-with-image':
        require_once 'includes/api/quiz-questions-with-images.php';
        if ($_GET['action'] === 'add-quiz-question-with-image') {
            add_quiz_question_with_image();
        } else {
            update_quiz_question_with_image();
        }
        break;
    case 'export-grinder-questions-excel':
        if ($method == 'GET') {
            check_admin_auth();
            require_once 'includes/api/excel-export.php';
            export_grinder_questions_excel();
        }
        break;

    case 'export-quiz-questions-excel':
        if ($method == 'GET') {
            check_admin_auth();
            require_once 'includes/api/excel-export.php';
            export_quiz_questions_excel();
        }
        break;

    case 'export-results-excel':
        if ($method == 'GET') {
            check_admin_auth();
            require_once 'includes/api/excel-export.php';
            $event_type = $_GET['event_type'] ?? 'grinder';
            export_results_excel($event_type);
        }
        break;

    // Excel импорт
    case 'import-questions-excel':
        if ($method == 'POST') {
            check_admin_auth();
            
            // Проверяем загрузку файла
            if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != UPLOAD_ERR_OK) {
                json_response(['success' => false, 'error' => 'Ошибка загрузки файла']);
            }
            
            $event_type = $_POST['event_type'] ?? 'grinder';
            $temp_file = $_FILES['excel_file']['tmp_name'];
            
            require_once 'includes/api/excel-import.php';
            $result = import_questions_from_excel($temp_file, $event_type);
            
            json_response($result);
        }
        break;

    case 'get-quiz-questions':
        if ($method == 'GET') {
            require_once 'includes/api/quiz-questions.php';
            get_quiz_questions();
        }
        break;
    case 'add-quiz-question':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-questions.php';
            add_quiz_question($input);
        }
        break;
    case 'update-quiz-question':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-questions.php';
            update_quiz_question($input);
        }
        break;
    case 'delete-quiz-question':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-questions.php';
            delete_quiz_question($input);
        }
        break;
    case 'move-quiz-question-up':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-questions.php';
            move_quiz_question_up($input);
        }
        break;
    case 'move-quiz-question-down':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-questions.php';
            move_quiz_question_down($input);
        }
        break;
    case 'duplicate-quiz-question':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-questions.php';
            duplicate_quiz_question($input);
        }
        break;
    case 'clear-all-quiz-questions':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-questions.php';
            clear_all_quiz_questions($input);
        }
        break;
    case 'get-quiz-state':
        if ($method == 'GET') {
            require_once 'includes/api/quiz-state.php';
            get_quiz_state();
        }
        break;
    case 'start-quiz-question':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-state.php';
            start_quiz_question($input);
        }
        break;
    case 'get-quiz-session':
        if ($method == 'GET') {
            require_once 'includes/api/quiz-session.php';
            get_quiz_session();
        }
        break;

    case 'get-current-question':
        if ($method == 'GET') {
            require_once 'includes/api/quiz-state.php';
            get_current_question();
        }
        break;

    case 'submit-quiz-answer':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-participants.php';
            submit_quiz_answer($input);
        }
        break;
    case 'next-quiz-question':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-session.php';
            next_question();
        }
        break;
    case 'get-quiz-participants':
        if ($method == 'GET') {
            require_once 'includes/api/quiz-participants.php';
            get_quiz_participants();
        }
        break;
    case 'show-quiz-answers':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-session.php';
            show_answers();
        }
        break;
    case 'reset-quiz':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-session.php';
            reset_quiz();
        }
        break;
    case 'pause-quiz':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-state.php';
            pause_quiz($input);
        }
        break;
    case 'end-quiz':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-state.php';
            end_quiz($input);
        }
        break;
    case 'get-scoreboard':
        if ($method == 'GET') {
            require_once 'includes/api/scoreboard.php';
            get_scoreboard();
        }
        break;
    
    case 'report-cheating':
        if ($method == 'POST') {
            require_once 'includes/api/monitoring.php';
            report_cheating($input);
        }
        break;
    
    case 'get-admins':
        if ($method == 'GET') {
            check_admin_auth();
            // Добавляем проверку суперадминистратора
            if (!is_superadmin()) {
                json_response(['error' => 'Недостаточно прав'], 403);
            }
            try {
                // Убедимся, что выбираем поле is_superadmin
                $stmt = $pdo->query("SELECT id, username, is_superadmin, created_at FROM admins ORDER BY id");
                $admins = $stmt->fetchAll();
                json_response($admins);
            } catch (PDOException $e) {
                json_response(['error' => 'Ошибка получения списка администраторов: ' . $e->getMessage()]);
            }
        }
        break;

    case 'add-admin':
        if ($method == 'POST') {
            check_admin_auth();
            // Добавляем проверку суперадминистратора
            if (!is_superadmin()) {
                json_response(['success' => false, 'message' => 'Недостаточно прав']);
            }
            
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            $is_superadmin = $input['is_superadmin'] ?? false;
            
            if (empty($username) || empty($password)) {
                json_response(['success' => false, 'message' => 'Заполните все поля']);
            }
            
            try {
                // Проверяем существование пользователя
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    json_response(['success' => false, 'message' => 'Пользователь с таким именем уже существует']);
                }
                
                // Добавляем нового администратора
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (username, password, is_superadmin) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $is_superadmin ? 1 : 0]);
                
                json_response(['success' => true, 'message' => 'Администратор успешно добавлен']);
            } catch (PDOException $e) {
                json_response(['success' => false, 'message' => 'Ошибка добавления администратора: ' . $e->getMessage()]);
            }
        }
        break;

    case 'update-admin':
        if ($method == 'POST') {
            check_admin_auth();
            if (!is_superadmin()) {
                json_response(['success' => false, 'message' => 'Недостаточно прав']);
            }
            
            $id = $input['id'] ?? 0;
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            
            if (empty($id) || empty($username)) {
                json_response(['success' => false, 'message' => 'Неверные данные']);
            }
            
            try {
                // Проверяем существование пользователя (кроме текущего)
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetch()) {
                    json_response(['success' => false, 'message' => 'Пользователь с таким именем уже существует']);
                }
                
                // Обновляем данные
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
                    $stmt->execute([$username, $hashedPassword, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE admins SET username = ? WHERE id = ?");
                    $stmt->execute([$username, $id]);
                }
                
                json_response(['success' => true, 'message' => 'Администратор успешно обновлен']);
            } catch (PDOException $e) {
                json_response(['success' => false, 'message' => 'Ошибка обновления администратора: ' . $e->getMessage()]);
            }
        }
        break;

    case 'promote-to-superadmin':
        if ($method == 'POST') {
            check_admin_auth();
            // Добавляем проверку суперадминистратора
            if (!is_superadmin()) {
                json_response(['success' => false, 'message' => 'Недостаточно прав']);
            }
            
            $id = $input['id'] ?? 0;
            
            if (empty($id)) {
                json_response(['success' => false, 'message' => 'ID администратора не указан']);
            }
            
            try {
                // Назначаем администратора суперадминистратором
                $stmt = $pdo->prepare("UPDATE admins SET is_superadmin = 1 WHERE id = ?");
                $stmt->execute([$id]);
                
                json_response(['success' => true, 'message' => 'Администратор назначен суперадминистратором']);
            } catch (PDOException $e) {
                json_response(['success' => false, 'message' => 'Ошибка назначения суперадминистратора: ' . $e->getMessage()]);
            }
        }
        break;

    case 'delete-admin':
        if ($method == 'POST') {
            check_admin_auth();
            if (!is_superadmin()) {
                json_response(['success' => false, 'message' => 'Недостаточно прав']);
            }
            
            $id = $input['id'] ?? 0;
            
            if (empty($id)) {
                json_response(['success' => false, 'message' => 'ID администратора не указан']);
            }
            
            // Не позволяем удалить самого себя
            if ($id == $_SESSION['admin_id']) {
                json_response(['success' => false, 'message' => 'Нельзя удалить самого себя']);
            }
            
            try {
                // Проверяем, не является ли администратор суперадминистратором
                $stmt = $pdo->prepare("SELECT is_superadmin FROM admins WHERE id = ?");
                $stmt->execute([$id]);
                $targetAdmin = $stmt->fetch();
                
                if ($targetAdmin && $targetAdmin['is_superadmin']) {
                    json_response(['success' => false, 'message' => 'Невозможно удалить суперадминистратора']);
                }
                
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                $stmt->execute([$id]);
                
                json_response(['success' => true, 'message' => 'Администратор удален']);
            } catch (PDOException $e) {
                json_response(['success' => false, 'message' => 'Ошибка удаления администратора: ' . $e->getMessage()]);
            }
        }
        break;

    case 'save-results':
        if ($method == 'POST') {
            require_once 'includes/api/results.php';
            save_results();
        }
        break;
    
    case 'participant-join':
        if ($method == 'POST') {
            require_once 'includes/api/participants.php';
            participant_join($input);
        }
        break;
    
    case 'get-questions-status':
        if ($method == 'GET') {
            require_once 'includes/api/participants.php';
            get_questions_status($_GET);
        }
        break;
    
    case 'answer-submit':
        if ($method == 'POST') {
            require_once 'includes/api/participants.php';
            answer_submit($input);
        }
        break;
    case 'debug-event-state':
        if ($method == 'GET') {
            try {
                $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1");
                $state = $stmt->fetch();
                
                // Добавляем дополнительную информацию для отладки
                $stmt = $pdo->query("SELECT NOW() as mysql_now, UNIX_TIMESTAMP() as unix_now");
                $time_info = $stmt->fetch();
                
                json_response([
                    'event_state' => $state,
                    'time_info' => $time_info,
                    'php_time' => time(),
                    'debug' => [
                        'timer_duration' => $state ? $state['timer_duration'] : null,
                        'timer_remaining' => $state ? $state['timer_remaining'] : null,
                        'should_decrease' => $state && $state['event_status'] === 'running'
                    ]
                ]);
                
            } catch (PDOException $e) {
                json_response(['error' => 'Debug error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'debug-db-state':
        if ($method == 'GET') {
            try {
                $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1");
                $state = $stmt->fetch();
                
                // Проверяем, действительно ли запись обновлена
                $stmt = $pdo->query("SELECT NOW() as db_time, UNIX_TIMESTAMP() as db_timestamp");
                $db_info = $stmt->fetch();
                
                json_response([
                    'database_state' => $state,
                    'database_info' => $db_info,
                    'php_time' => date('Y-m-d H:i:s'),
                    'php_timestamp' => time()
                ]);
                
            } catch (PDOException $e) {
                json_response(['error' => 'Debug DB error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'start-event':
        if ($method == 'POST') {
            try {
                error_log("Processing start-event request");
                
                // Получаем текущие настройки
                $stmt = $pdo->query("SELECT timer_duration FROM event_state WHERE id = 1");
                $current = $stmt->fetch();
                $timer_duration = $current ? $current['timer_duration'] : 3600;
                
                error_log("Timer duration: " . $timer_duration);
                
                // Устанавливаем время окончания правильно
                $event_start_time = date('Y-m-d H:i:s');
                $event_end_time = date('Y-m-d H:i:s', time() + $timer_duration);
                
                error_log("Start time: " . $event_start_time);
                error_log("End time: " . $event_end_time);
                
                // Явно начинаем транзакцию
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    UPDATE event_state SET 
                    event_status = 'running',
                    event_start_time = ?,
                    event_end_time = ?,
                    updated_at = NOW()
                    WHERE id = 1
                ");
                $stmt->execute([$event_start_time, $event_end_time]);
                
                // Явно коммитим изменения
                $pdo->commit();
                
                // Немедленно получаем обновленное состояние
                $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1");
                $new_state = $stmt->fetch();
                
                error_log("Event started successfully. New status: " . $new_state['event_status']);
                
                json_response([
                    'success' => true, 
                    'message' => 'Мероприятие начато!',
                    'state' => $new_state
                ]);
                
            } catch (PDOException $e) {
                // Откатываем в случае ошибки
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Start-event PDO error: " . $e->getMessage());
                json_response([
                    'success' => false, 
                    'error' => 'Ошибка начала мероприятия: ' . $e->getMessage()
                ]);
            }
        }
        break;

    case 'finish-event':
        if ($method == 'POST') {
            try {
                $stmt = $pdo->prepare("
                    UPDATE event_state SET 
                    event_status = 'finished',
                    event_end_time = NULL,
                    updated_at = NOW()
                    WHERE id = 1
                ");
                $stmt->execute();
                json_response(['success' => true, 'message' => 'Мероприятие завершено!']);
            } catch (PDOException $e) {
                json_response(['success' => false, 'error' => 'Ошибка завершения мероприятия: ' . $e->getMessage()]);
            }
        }
        break;

    case 'update-event-settings':
        if ($method == 'POST') {
            check_admin_auth();
            
            // Получаем текущий режим
            $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
            $stmt->execute();
            $mode = $stmt->fetchColumn();
            
            if ($mode === 'quiz') {
                $result = update_quiz_event_settings($input);
            } else {
                $result = update_grinder_event_settings($input);
            }
            
            if ($result) {
                json_response(['success' => true, 'message' => 'Настройки мероприятия обновлены!']);
            } else {
                json_response(['success' => false, 'error' => 'Ошибка обновления настроек']);
            }
        }
        break;

    case 'update-quiz-event-settings':
        if ($method == 'POST') {
            check_admin_auth();
            $result = update_quiz_event_settings($input);
            if ($result) {
                json_response(['success' => true, 'message' => 'Настройки квиза обновлены!']);
            } else {
                json_response(['success' => false, 'error' => 'Ошибка обновления настроек квиза']);
            }
        }
        break;

    // МЯСОРУБКА: Получение состояния
    case 'get-grinder-event-state':
        if ($method == 'GET') {
            // Подключаем файл с функцией
            $grinderEventFile = __DIR__ . '/includes/api/grinder-event.php';
            if (!file_exists($grinderEventFile)) {
                // Если файл не существует, возвращаем состояние по умолчанию
                json_response([
                    'event_name' => 'Математическая мясорубка',
                    'event_status' => 'not_started', 
                    'timer_duration' => 3600,
                    'timer_remaining' => 3600,
                    'is_accepting_answers' => true,
                    'is_ranking_frozen' => false
                ]);
            }
            
            require_once $grinderEventFile;
            
            // Проверяем, определена ли функция
            if (!function_exists('get_grinder_event_state_full')) {
                json_response([
                    'error' => 'Function get_grinder_event_state_full not found',
                    'event_name' => 'Математическая мясорубка',
                    'event_status' => 'not_started',
                    'timer_duration' => 3600,
                    'timer_remaining' => 3600,
                    'is_accepting_answers' => true,
                    'is_ranking_frozen' => false
                ]);
            }
            
            get_grinder_event_state_full();
        }
        break;

    // МЯСОРУБКА: Обновление настроек
    case 'update-grinder-event-settings':
        if ($method == 'POST') {
            check_admin_auth();
            require_once 'includes/api/grinder-event.php';
            update_grinder_event_settings($input);
        }
        break;

    // МЯСОРУБКА: Начало мероприятия
    case 'start-grinder-event':
        if ($method == 'POST') {
            require_once 'includes/api/grinder-admin.php';
            start_grinder_event();
        }
        break;

    // МЯСОРУБКА: Завершение мероприятия
    case 'finish-grinder-event':
        if ($method == 'POST') {
            require_once 'includes/api/grinder-admin.php';
            finish_grinder_event();
        }
        break;

    // МЯСОРУБКА: Сброс мероприятия
    case 'reset-grinder-event':
        if ($method == 'POST') {
            require_once 'includes/api/grinder-admin.php';
            reset_grinder_event();
        }
        break;

    // МЯСОРУБКА: Остановка приема ответов
    case 'stop-grinder-answers':
        if ($method == 'POST') {
            require_once 'includes/api/grinder-admin.php';
            stop_grinder_answers();
        }
        break;

    // МЯСОРУБКА: Возобновление приема ответов
    case 'resume-grinder-answers':
        if ($method == 'POST') {
            require_once 'includes/api/grinder-admin.php';
            resume_grinder_answers();
        }
        break;

    // МЯСОРУБКА: Заморозка рейтинга
    case 'freeze-grinder-ranking':
        if ($method == 'POST') {
            require_once 'includes/api/grinder-admin.php';
            freeze_grinder_ranking();
        }
        break;

    // МЯСОРУБКА: Разморозка рейтинга
    case 'unfreeze-grinder-ranking':
        if ($method == 'POST') {
            require_once 'includes/api/grinder-admin.php';
            unfreeze_grinder_ranking();
        }
        break;
    case 'grinder-participant-join':
        if ($method == 'POST') {
            require_once 'includes/api/participants.php';
            $input['event_type'] = 'grinder';
            participant_join($input);
        }
        break;

    case 'grinder-answer-submit':
        if ($method == 'POST') {
            require_once 'includes/api/participants.php';
            $input['event_type'] = 'grinder';
            answer_submit($input);
        }
        break;

    case 'get-grinder-questions-status':
        if ($method == 'GET') {
            require_once 'includes/api/participants.php';
            $input = $_GET;
            $input['event_type'] = 'grinder';
            get_questions_status($input);
        }
        break;
    case 'quiz-participant-join':
        if ($method == 'POST') {
            require_once 'includes/api/participants.php';
            $input['event_type'] = 'quiz';
            participant_join($input);
        }
        break;
    case 'quiz-answer-submit':
        if ($method == 'POST') {
            // Здесь нужен отдельный файл для ответов квиза
            require_once 'includes/api/quiz-participants.php';
            submit_quiz_answer($input);
        }
        break;
    case 'get-quiz-questions-status':
        if ($method == 'GET') {
            require_once 'includes/api/quiz-participants.php';
            // Нужно создать эту функцию или адаптировать существующую
            get_quiz_questions_status($_GET);
        }
        break;
    case 'update-quiz-event-name':
        if ($method == 'POST') {
            require_once 'includes/api/quiz-event.php';
            update_quiz_event_name($input);
        }
        break;
    case 'get-raw-db-state':
        if ($method == 'GET') {
            try {
                $stmt = $pdo->prepare("SELECT * FROM event_state WHERE id = 1");
                $stmt->execute();
                $state = $stmt->fetch();
                
                if (!$state) {
                    json_response(['error' => 'No record found']);
                } else {
                    json_response($state);
                }
                
            } catch (PDOException $e) {
                json_response(['error' => 'DB error: ' . $e->getMessage()]);
            }
        }
        break;

    case 'get-quiz-event-state':
        if ($method == 'GET') {
            json_response(get_quiz_event_state());
        }
        break;

    case 'reset-event':
        if ($method == 'POST') {
            try {
                $stmt = $pdo->prepare("
                    UPDATE event_state SET 
                    event_status = 'not_started',
                    is_accepting_answers = 0,
                    event_start_time = NULL,
                    event_end_time = NULL,
                    updated_at = NOW()
                    WHERE id = 1
                ");
                $stmt->execute();
                json_response(['success' => true, 'message' => 'Мероприятие сброшено!']);
            } catch (PDOException $e) {
                json_response(['success' => false, 'error' => 'Ошибка сброса мероприятия: ' . $e->getMessage()]);
            }
        }
        break;

    case 'admin-login':
        if ($method == 'POST') {
            require_once 'includes/api/admin.php';
            admin_login($input);
        }
        break;
    case 'update-question':
        if ($method == 'POST') {
            require_once 'includes/api/questions-with-images.php';
            update_question();
        }
        break;
    
    case 'get-event-mode':
        if ($method == 'GET') {
            try {
                $stmt = $pdo->prepare("SELECT event_mode FROM event_state WHERE id = 1");
                $stmt->execute();
                $state = $stmt->fetch();
                
                json_response([
                    'success' => true,
                    'event_mode' => $state ? $state['event_mode'] : 'grinder'
                ]);
            } catch (PDOException $e) {
                json_response(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;
        
    case 'update-event-mode':
        if ($method == 'POST') {
            check_admin_auth();
            $event_mode = $input['event_mode'] ?? 'grinder';
            
            try {
                $stmt = $pdo->prepare("UPDATE event_state SET event_mode = ? WHERE id = 1");
                $stmt->execute([$event_mode]);
                json_response(['success' => true, 'message' => 'Режим мероприятия обновлен']);
            } catch (PDOException $e) {
                json_response(['success' => false, 'error' => 'Ошибка обновления режима: ' . $e->getMessage()]);
            }
        }
        break;

    case 'export-questions-enhanced':
        if ($method == 'GET') {
            check_admin_auth();
            require_once 'includes/api/export-enhanced.php';
            export_questions_enhanced();
        }
        break;

    case 'import-questions-simple':
        if ($method == 'POST') {
            check_admin_auth();
            $file_content = $input['file_content'] ?? '';
            require_once 'includes/api/import-enhanced.php';
            $result = import_questions_simple($file_content);
            json_response($result);
        }
        break;

    case 'cleanup-images':
        if ($method == 'POST') {
            check_admin_auth();
            require_once 'includes/api/questions-extended.php';
            $cleaned_count = cleanup_unused_images();
            json_response(['success' => true, 'message' => "Очищено $cleaned_count неиспользуемых изображений"]);
        }
        break;
    case 'admin-logout':
        if ($method == 'POST') {
            require_once 'includes/api/admin.php';
            admin_logout();
        }
        break;
    case 'get-detailed-cheating-history':
        if ($method == 'GET') {
            check_admin_auth();
            $team = $_GET['team'] ?? '';
        
            if (empty($team)) {
                json_response(['error' => 'Команда не указана']);
            }
        
            try {
                $stmt = $pdo->prepare("
                    SELECT ca.*, p.team, q.text as question_text
                    FROM cheating_attempts ca
                    LEFT JOIN participants p ON ca.participant_id = p.id
                    LEFT JOIN questions q ON ca.question_id = q.id
                    WHERE p.team = ?
                    ORDER BY ca.detected_at DESC
                    LIMIT 100
                ");
                $stmt->execute([$team]);
                $history = $stmt->fetchAll();
            
                json_response([
                    'success' => true,
                    'team' => $team,
                    'history' => $history
                ]);
            
            } catch (PDOException $e) {
                error_log("Get detailed cheating history error: " . $e->getMessage());
                json_response(['error' => 'Ошибка получения истории: ' . $e->getMessage()]);
            }
        }
        break;

    case 'delete-question':
        if ($method == 'POST') {
            require_once 'includes/api/questions.php';
            delete_question($input);
        }
        break;

    case 'import-questions':
        if ($method == 'POST') {
            require_once 'includes/api/questions.php';
            import_questions_from_file($input);
        }
        break;    
    
    case 'clear-cheating-attempts':
        if ($method == 'POST') {
            check_admin_auth();
            $team = $input['team'] ?? null;
        
            try {
                if ($team) {
                    // Очищаем для конкретной команды
                    $stmt = $pdo->prepare("DELETE FROM cheating_attempts WHERE participant_id IN (SELECT id FROM participants WHERE team = ?)");
                    $stmt->execute([$team]);
                    $message = "Данные для команды '$team' очищены";
                } else {
                    // Очищаем все
                    $stmt = $pdo->prepare("DELETE FROM cheating_attempts");
                    $stmt->execute();
                    $message = "Все записи о нарушениях очищены";
                }
            
                json_response(['success' => true, 'message' => $message]);
            } catch (PDOException $e) {
                json_response(['success' => false, 'message' => 'Ошибка очистки: ' . $e->getMessage()]);
            }
        }
        break;

    case 'clear-results':
        if ($method == 'POST') {
            error_log("Clear-results action called");
            require_once 'includes/api/results.php';
            error_log("Results.php loaded");
            clear_results();
            error_log("Clear_results function called");
        }
        break;
    case 'export-excel':
        if ($method == 'POST') {
            require_once 'includes/api/export.php';
            export_to_excel();
        }
        break;
    
    case 'export-csv':
        if ($method == 'POST') {
            require_once 'includes/api/export.php';
            export_to_csv();
        }
        break;
    case 'get-detailed-results':
        if ($method == 'GET') {
            try {
                // Получаем участников
                $stmt = $pdo->query("SELECT * FROM participants ORDER BY score DESC");
                $participants = $stmt->fetchAll();
            
                // Получаем вопросы
                $stmt = $pdo->query("SELECT * FROM questions ORDER BY id");
                $questions = $stmt->fetchAll();
            
                // Получаем ответы
                $stmt = $pdo->query("SELECT * FROM answers");
                $answers = $stmt->fetchAll();
            
                json_response([
                    'participants' => $participants,
                    'questions' => $questions,
                    'answers' => $answers
                ]);
            
            } catch (Exception $e) {
                json_response(['error' => 'Ошибка получения результатов: ' . $e->getMessage()]);
            }
        } else {
            json_response(['error' => 'Метод не поддерживается'], 405);
        }
        break;
    case 'start-timer':
        if ($method == 'POST') {
            $start_time = time() * 1000; // В миллисекундах для JavaScript
            $stmt = $pdo->prepare("UPDATE event_state SET event_start_time = ? WHERE id = 1");
            $result = $stmt->execute([$start_time]);
            json_response(['success' => true, 'message' => 'Таймер запущен', 'start_time' => $start_time]);
        } else {
            json_response(['error' => 'Метод не поддерживается'], 405);
        }
        break;
    
    case 'stop-timer':
        if ($method == 'POST') {
            $stmt = $pdo->prepare("UPDATE event_state SET event_start_time = NULL WHERE id = 1");
            $result = $stmt->execute();
            json_response(['success' => true, 'message' => 'Таймер остановлен']);
        } else {
            json_response(['error' => 'Метод не поддерживается'], 405);
        }
        break;
   case 'stop-answers':
        if ($method == 'POST') {
            $stmt = $pdo->prepare("UPDATE event_state SET is_accepting_answers = 0 WHERE id = 1");
            $result = $stmt->execute();
            json_response(['success' => true, 'message' => 'Прием ответов остановлен']);
        } else {
            json_response(['error' => 'Метод не поддерживается'], 405);
        }
        break;

    case 'clear-all-questions':
        if ($method == 'POST') {
            check_admin_auth();
            try {
                $stmt = $pdo->prepare("DELETE FROM questions");
                $stmt->execute();
                json_response(['success' => true, 'message' => 'Все вопросы удалены']);
            } catch (Exception $e) {
                json_response(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    case 'resume-answers':
        if ($method == 'POST') {
            $stmt = $pdo->prepare("UPDATE event_state SET is_accepting_answers = 1 WHERE id = 1");
            $result = $stmt->execute();
            json_response(['success' => true, 'message' => 'Прием ответов возобновлен']);
        } else {
            json_response(['error' => 'Метод не поддерживается'], 405);
        }
        break;

    case 'get-questions':
        if ($method == 'GET') {
            try {
                $stmt = $pdo->query("
                    SELECT id, text, answer, points, image_path, 
                        has_bonus_points, bonus_first_points, bonus_second_points, bonus_third_points,
                        created_at, updated_at
                    FROM questions 
                    ORDER BY id
                ");
                $questions = $stmt->fetchAll();
                
                // Убедимся, что image_path правильно форматирован
                foreach ($questions as &$question) {
                    if ($question['image_path'] && !empty($question['image_path'])) {
                        // Убедимся, что путь начинается с /
                        if (strpos($question['image_path'], '/') !== 0) {
                            $question['image_path'] = '/' . $question['image_path'];
                        }
                    }
                }
                
                json_response($questions);
            } catch (Exception $e) {
                json_response(['error' => 'Ошибка получения вопросов: ' . $e->getMessage()]);
            }
        }
        break;
    case 'add-question':
        if ($method == 'POST') {
            require_once 'includes/api/questions-with-images.php';
            add_question();
        }
        break;

        
    case 'get-cheating-attempts':
        if ($method == 'GET') {
            require_once 'includes/api/monitoring.php';
            get_cheating_attempts();
        }
        break;
        
    case 'freeze-ranking':
        if ($method == 'POST') {
            $stmt = $pdo->prepare("UPDATE event_state SET is_ranking_frozen = 1 WHERE id = 1");
            $result = $stmt->execute();
            json_response(['success' => true, 'message' => 'Рейтинг заморожен']);
        }
        break;
        
    case 'unfreeze-ranking':
        if ($method == 'POST') {
            $stmt = $pdo->prepare("UPDATE event_state SET is_ranking_frozen = 0 WHERE id = 1");
            $result = $stmt->execute();
            json_response(['success' => true, 'message' => 'Рейтинг разморожен']);
        }
        break;
        
    case 'get-event-state':
        if ($method == 'GET') {
            $stmt = $pdo->query("SELECT * FROM event_state WHERE id = 1");
            $state = $stmt->fetch();
            json_response($state);
        }
        break;
        
    default:
        json_response(['error' => 'Неизвестное действие: ' . $action], 404);
}
?>