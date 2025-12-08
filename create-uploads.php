<?php
// create-uploads.php - скрипт для создания папки
$upload_dir = __DIR__ . '/uploads/questions';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    file_put_contents($upload_dir . '/.htaccess', 'Deny from all');
    echo "Папка uploads создана: " . $upload_dir;
} else {
    echo "Папка уже существует: " . $upload_dir;
}
?>