<?php
// quick-test.php - быстрая проверка БД
header('Content-Type: text/plain; charset=utf-8');

// 1. Проверка конфигурационных файлов
echo "1. Проверка конфигурации:\n";

$config_files = ['config.php', 'db.php'];
foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "   ✓ $file найден\n";
        // Пытаемся получить константы из файла
        require_once $file;
    } else {
        echo "   ✗ $file не найден\n";
    }
}

// 2. Проверка подключения
echo "\n2. Проверка подключения к БД:\n";

$required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required_constants as $constant) {
    if (defined($constant)) {
        echo "   ✓ $constant: " . constant($constant) . "\n";
    } else {
        echo "   ✗ $constant не определен\n";
    }
}

// Подключение
if (defined('DB_HOST') && defined('DB_USER')) {
    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($mysqli->connect_error) {
        echo "   ✗ Ошибка подключения: " . $mysqli->connect_error . "\n";
    } else {
        echo "   ✓ Подключение успешно\n";
        echo "   ✓ Версия сервера: " . $mysqli->server_info . "\n";
        
        // Проверка таблиц
        $result = $mysqli->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        echo "\n3. Таблицы в БД (" . count($tables) . "):\n";
        foreach ($tables as $table) {
            echo "   - $table\n";
        }
        
        $mysqli->close();
    }
}

echo "\n=== Тест завершен ===\n";
?>