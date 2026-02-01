<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Проверяем авторизацию и права администратора
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Перенаправляем на dashboard
header('Location: dashboard.php');
exit;
?>