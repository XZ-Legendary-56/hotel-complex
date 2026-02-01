<?php
session_start();

// Настройки базы данных
define('DB_HOST', '127.127.126.26');
define('DB_NAME', 'hotel_complex');
define('DB_USER', 'root');
define('DB_PASS', '');

// Настройки сайта
define('SITE_NAME', 'Гостиничный комплекс "Престиж"');
define('SITE_URL', 'http://localhost/hotel_complex');

// Добавьте в config.php
// Настройки почты
ini_set('SMTP', 'diksan2004@mail.ru');
ini_set('smtp_port', 25);
ini_set('sendmail_from', 'noreply@hotel-system.com');

// Обработка ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>