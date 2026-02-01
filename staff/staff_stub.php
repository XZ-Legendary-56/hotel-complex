<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Проверяем авторизацию и роль персонала
if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'staff') {
    header('Location: ../pages/login.php');
    exit;
}

// Получаем данные персонала с проверкой
$staff_data = $auth->getStaffData();

// Если данных нет, создаем заглушку
if (!$staff_data) {
    $staff_data = [
        'name' => $_SESSION['username'] ?? 'Сотрудник',
        'position' => 'Сотрудник',
        'email' => $_SESSION['email'] ?? 'Не указан',
        'phone' => 'Не указан',
        'department' => 'Не указан',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Безопасное получение данных с проверкой
$staff_name = htmlspecialchars($staff_data['name'] ?? 'Сотрудник');
$staff_position = htmlspecialchars($staff_data['position'] ?? 'Сотрудник');
$staff_email = htmlspecialchars($staff_data['email'] ?? 'Не указан');
$staff_phone = htmlspecialchars($staff_data['phone'] ?? 'Не указан');
$staff_department = htmlspecialchars($staff_data['department'] ?? 'Не указан');
$staff_created_at = !empty($staff_data['created_at']) ? date('d.m.Y', strtotime($staff_data['created_at'])) : 'Не указана';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель персонала - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stub-container {
            min-height: 80vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stub-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .coming-soon-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ff4757;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Навбар -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../">
                <i class="fas fa-hotel me-2"></i><?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-tie me-1"></i>
                    <?php echo $staff_name; ?>
                </span>
                <a class="nav-link" href="../pages/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Выйти
                </a>
            </div>
        </div>
    </nav>

    <!-- Основной контент -->
    <div class="stub-container d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="stub-card p-5 text-center">
                        <!-- Заголовок -->
                        <div class="mb-4">
                            <div class="feature-icon">
                                <i class="fas fa-user-tie fa-2x text-white"></i>
                            </div>
                            <h1 class="display-4 fw-bold text-primary mb-3">Панель персонала</h1>
                            <p class="lead text-muted">
                                Добро пожаловать, <strong><?php echo $staff_name; ?></strong>!
                            </p>
                            <p class="text-muted">
                                Ваша должность: <span class="badge bg-primary"><?php echo $staff_position; ?></span>
                            </p>
                        </div>

                        <!-- Сообщение о разработке -->
                        <div class="alert alert-info border-0 mb-5">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-tools fa-2x me-3 text-info"></i>
                                <div class="text-start">
                                    <h5 class="alert-heading mb-1">Раздел в разработке</h5>
                                    <p class="mb-0">Панель управления для персонала находится в стадии активной разработки.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Будущие функции -->
                        <div class="row mb-5">
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 border-0 position-relative">
                                    <span class="coming-soon-badge">СКОРО</span>
                                    <div class="card-body">
                                        <i class="fas fa-tasks fa-3x text-primary mb-3"></i>
                                        <h5>Мои задачи</h5>
                                        <p class="text-muted small">Просмотр и выполнение рабочих задач</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 border-0 position-relative">
                                    <span class="coming-soon-badge">СКОРО</span>
                                    <div class="card-body">
                                        <i class="fas fa-broom fa-3x text-success mb-3"></i>
                                        <h5>Уборка номеров</h5>
                                        <p class="text-muted small">Отметка убранных номеров</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100 border-0 position-relative">
                                    <span class="coming-soon-badge">СКОРО</span>
                                    <div class="card-body">
                                        <i class="fas fa-concierge-bell fa-3x text-warning mb-3"></i>
                                        <h5>Заказы услуг</h5>
                                        <p class="text-muted small">Обслуживание гостевых запросов</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Контактная информация -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>
                                    Контактная информация
                                </h6>
                                <div class="row text-start">
                                    <div class="col-md-6 mb-2">
                                        <i class="fas fa-envelope me-2 text-muted"></i>
                                        <?php echo $staff_email; ?>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <i class="fas fa-phone me-2 text-muted"></i>
                                        <?php echo $staff_phone; ?>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <i class="fas fa-building me-2 text-muted"></i>
                                        <?php echo $staff_department; ?>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <i class="fas fa-calendar me-2 text-muted"></i>
                                        В штате с: <?php echo $staff_created_at; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Действия -->
                        <div class="d-grid gap-2 d-md-flex justify-content-center">
                            <a href="../user/profile.php" class="btn btn-outline-primary btn-lg px-4">
                                <i class="fas fa-user me-2"></i>Личный кабинет
                            </a>
                            <a href="../" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-home me-2"></i>На главную
                            </a>
                        </div>

                        <!-- Дополнительная информация -->
                        <div class="mt-4">
                            <p class="text-muted small">
                                <i class="fas fa-clock me-1"></i>
                                По вопросам доступа к функциям панели персонала обращайтесь к администратору.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Футер -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; 2024 <?php echo SITE_NAME; ?>. Все права защищены.</p>
            <p class="text-muted small mt-1">Панель персонала - версия 0.1</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>