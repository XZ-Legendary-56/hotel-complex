<?php
// staff/index.php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Проверяем авторизацию и роль
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'staff') {
    header('Location: ../pages/login.php');
    exit;
}

// Перенаправляем на заглушку
header('Location: staff_stub.php');
exit;
?>