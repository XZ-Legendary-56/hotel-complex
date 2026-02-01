<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Проверяем авторизацию
if (!$auth->isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

// Получаем данные персонала по email
$staff_data = $auth->getStaffData();

if (!$staff_data) {
    echo "<h1>Доступ запрещен</h1>";
    echo "<p>Ваш аккаунт не найден в базе персонала.</p>";
    echo "<p>Email вашего аккаунта: " . ($_SESSION['email'] ?? 'не указан') . "</p>";
    echo "<a href='../' class='btn btn-primary'>Вернуться на главную</a>";
    exit;
}

// Получаем назначенные номера
$assigned_rooms_query = $db->prepare("
    SELECT r.room_number, rt.name as room_type, r.status, r.floor
    FROM staff_rooms sr
    JOIN rooms r ON sr.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE sr.staff_id = :staff_id
    ORDER BY r.room_number
");
$assigned_rooms_query->bindParam(':staff_id', $staff_data['id']);
$assigned_rooms_query->execute();
$assigned_rooms = $assigned_rooms_query->fetchAll(PDO::FETCH_ASSOC);

// Получаем назначенные услуги
$assigned_services_query = $db->prepare("
    SELECT s.name, s.category, s.price, s.description
    FROM service_staff ss
    JOIN services s ON ss.service_id = s.id
    WHERE ss.staff_id = :staff_id AND s.status = 'active'
    ORDER BY s.category, s.name
");
$assigned_services_query->bindParam(':staff_id', $staff_data['id']);
$assigned_services_query->execute();
$assigned_services = $assigned_services_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль персонала - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .room-status-available { border-left: 4px solid #28a745; }
        .room-status-occupied { border-left: 4px solid #dc3545; }
        .room-status-cleaning { border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <!-- Навбар для персонала -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="staff_profile.php">
                <i class="fas fa-user-tie me-2"></i>Персонал
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($staff_data['name']); ?>
                </span>
                <a class="nav-link" href="../pages/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Выйти
                </a>
            </div>
        </div>
        <!-- В навбаре персонала добавьте -->
<div class="navbar-nav">
    <a class="nav-link" href="staff_profile.php">
        <i class="fas fa-user me-1"></i>Профиль
    </a>
    <a class="nav-link" href="staff_tasks.php">
        <i class="fas fa-tasks me-1"></i>Мои задачи
    </a>
    <a class="nav-link" href="../pages/logout.php">
        <i class="fas fa-sign-out-alt me-1"></i>Выйти
    </a>
</div>
    </nav>

    <!-- Профиль -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="bg-white rounded-circle p-3 d-inline-block">
                        <i class="fas fa-user-tie fa-3x text-primary"></i>
                    </div>
                </div>
                <div class="col-md-6">
                    <h1 class="h3 mb-1"><?php echo htmlspecialchars($staff_data['name']); ?></h1>
                    <p class="mb-1">
                        <i class="fas fa-briefcase me-2"></i><?php echo htmlspecialchars($staff_data['position']); ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>В штате с: <?php echo date('d.m.Y', strtotime($staff_data['created_at'])); ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="mb-0"><?php echo count($assigned_rooms); ?></h4>
                            <small>Номера</small>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0"><?php echo count($assigned_services); ?></h4>
                            <small>Услуги</small>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0"><?php echo $staff_data['department'] ?? 'Не указан'; ?></h4>
                            <small>Отдел</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Левая колонка - Номера и услуги -->
            <div class="col-lg-8">
                <!-- Назначенные номера -->
                <div class="card stat-card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bed me-2"></i>Мои номера
                            <span class="badge bg-light text-dark"><?php echo count($assigned_rooms); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($assigned_rooms) > 0): ?>
                            <div class="row">
                                <?php foreach ($assigned_rooms as $room): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="p-3 border rounded room-status-<?php echo $room['status']; ?>">
                                            <h6 class="mb-1">Номер <?php echo htmlspecialchars($room['room_number']); ?></h6>
                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($room['room_type']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-<?php 
                                                    switch($room['status']) {
                                                        case 'available': echo 'success'; break;
                                                        case 'occupied': echo 'danger'; break;
                                                        case 'cleaning': echo 'warning'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php 
                                                    switch($room['status']) {
                                                        case 'available': echo 'Свободен'; break;
                                                        case 'occupied': echo 'Занят'; break;
                                                        case 'cleaning': echo 'Уборка'; break;
                                                        default: echo $room['status'];
                                                    }
                                                    ?>
                                                </span>
                                                <small><?php echo $room['floor']; ?> этаж</small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Номера не назначены</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Мои услуги -->
                <div class="card stat-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-concierge-bell me-2"></i>Мои услуги
                            <span class="badge bg-dark"><?php echo count($assigned_services); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($assigned_services) > 0): ?>
                            <div class="row">
                                <?php foreach ($assigned_services as $service): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="p-3 border rounded">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($service['name']); ?></h6>
                                            <p class="mb-1 text-muted"><?php echo htmlspecialchars($service['category']); ?></p>
                                            <?php if (!empty($service['description'])): ?>
                                                <p class="small text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
                                            <?php endif; ?>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-success fw-bold"><?php echo number_format($service['price'], 2); ?> руб.</span>
                                                <span class="badge bg-info">Активна</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-concierge-bell fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Услуги не назначены</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Правая колонка - Информация -->
            <div class="col-lg-4">
                <!-- Личная информация -->
                <div class="card stat-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>Мой профиль
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong><i class="fas fa-briefcase me-2"></i>Должность:</strong><br>
                            <?php echo htmlspecialchars($staff_data['position']); ?>
                        </div>
                        <?php if (!empty($staff_data['department'])): ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-building me-2"></i>Отдел:</strong><br>
                            <?php echo htmlspecialchars($staff_data['department']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($staff_data['phone'])): ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-phone me-2"></i>Телефон:</strong><br>
                            <?php echo htmlspecialchars($staff_data['phone']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($staff_data['email'])): ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-envelope me-2"></i>Email:</strong><br>
                            <?php echo htmlspecialchars($staff_data['email']); ?>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-calendar me-2"></i>Дата регистрации:</strong><br>
                            <?php echo date('d.m.Y', strtotime($staff_data['created_at'])); ?>
                        </div>
                        <div>
                            <strong><i class="fas fa-circle me-2"></i>Статус:</strong><br>
                            <span class="badge bg-<?php echo $staff_data['status'] == 'active' ? 'success' : 'danger'; ?>">
                                <?php echo $staff_data['status'] == 'active' ? 'Активен' : 'Неактивен'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="card stat-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Быстрые действия
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary">
                                <i class="fas fa-list me-2"></i>Мои задачи
                            </button>
                            <button class="btn btn-outline-success">
                                <i class="fas fa-broom me-2"></i>Отчет об уборке
                            </button>
                            <button class="btn btn-outline-info">
                                <i class="fas fa-calendar me-2"></i>График работы
                            </button>
                            <button class="btn btn-outline-warning">
                                <i class="fas fa-clock me-2"></i>Отметить время
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>