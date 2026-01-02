# Веб-приложение для проведения мероприятий

## Для корректной установки на хост сделать следующее:

1. Установить базу данных из файла math_grinder.sql
2. В файле config.php заменить
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'math_grinder');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
   на настройки базы данных sql
   ```php
   define('BASE_URL', 'http://localhost/math-grinder-php');
   ```
   на url домена
3. В файле db.php заменить
   ```php
    private $host = 'localhost';
    private $db_name = 'math_grinder';
    private $username = 'root';
    private $password = '';
    public $conn;
   ```
   на настройки базы данных.

4. Проверить корректность подключения базы данных с помощью db-test.php

5. Установить PhpSpreadsheet (если при загрузке проекта возникнут проблемы с ним)
