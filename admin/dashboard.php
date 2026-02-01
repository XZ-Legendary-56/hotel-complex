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

// Статистика
$bookings_count = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$users_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$rooms_count = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$revenue = $db->query("SELECT SUM(total_price) FROM bookings WHERE status = 'completed'")->fetchColumn();
$revenue = $revenue ? $revenue : 0;

// Последние бронирования
$recent_bookings = $db->query("SELECT * FROM booking_details ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Статистика по статусам бронирований
$booking_stats = $db->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
            <div class="position-sticky">
                <div class="sidebar-header p-3 text-center text-white">
                    <h5>Админ Панель</h5>
                    <hr class="my-2">
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Дашборд
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="bookings.php">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Бронирования
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="rooms.php">
                            <i class="fas fa-bed me-2"></i>
                            Номера
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="users.php">
                            <i class="fas fa-users me-2"></i>
                            Пользователи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="services.php">
                            <i class="fas fa-concierge-bell me-2"></i>
                            Услуги
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>
                            Отчеты
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="../pages/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Выйти
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Дашборд</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="badge bg-success">
                        <i class="fas fa-circle me-1"></i>Online
                    </span>
                </div>
            </div>

            <!-- Статистика -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Бронирования</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $bookings_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Пользователи</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $users_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Номера</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rooms_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-bed fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Доход</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($revenue, 2); ?> руб.</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-ruble-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Последние бронирования -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Последние бронирования</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Гость</th>
                                            <th>Номер</th>
                                            <th>Даты</th>
                                            <th>Сумма</th>
                                            <th>Статус</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                            <td>
                                                <?php echo date('d.m.Y', strtotime($booking['check_in_date'])); ?> - 
                                                <?php echo date('d.m.Y', strtotime($booking['check_out_date'])); ?>
                                            </td>
                                            <td><?php echo number_format($booking['total_price'], 2); ?> руб.</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch($booking['status']) {
                                                        case 'confirmed': echo 'success'; break;
                                                        case 'pending': echo 'warning'; break;
                                                        case 'cancelled': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo $booking['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Статистика -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Статистика бронирований</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($booking_stats as $stat): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        <?php 
                                        switch($stat['status']) {
                                            case 'confirmed': echo 'Подтвержденные'; break;
                                            case 'pending': echo 'Ожидание'; break;
                                            case 'cancelled': echo 'Отмененные'; break;
                                            case 'completed': echo 'Завершенные'; break;
                                            default: echo $stat['status'];
                                        }
                                        ?>
                                    </span>
                                    <span class="font-weight-bold"><?php echo $stat['count']; ?></span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-<?php 
                                        switch($stat['status']) {
                                            case 'confirmed': echo 'success'; break;
                                            case 'pending': echo 'warning'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            case 'completed': echo 'info'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>" style="width: <?php echo ($stat['count'] / $bookings_count * 100); ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Быстрые действия -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Быстрые действия</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="bookings.php" class="btn btn-primary">
                                    <i class="fas fa-calendar me-1"></i>Управление бронированиями
                                </a>
                                <a href="rooms.php" class="btn btn-success">
                                    <i class="fas fa-bed me-1"></i>Управление номерами
                                </a>
                                <a href="users.php" class="btn btn-info">
                                    <i class="fas fa-users me-1"></i>Управление пользователями
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.sidebar {
    min-height: 100vh;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

.sidebar .nav-link {
    border-radius: 0;
    padding: 15px 20px;
    transition: all 0.3s;
}

.sidebar .nav-link:hover {
    background-color: #2c3e50;
}

.sidebar .nav-link.active {
    background-color: #007bff;
}

.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
}

.progress {
    border-radius: 5px;
}
</style>

<?php include '../includes/footer.php'; ?>