<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: bookings.php');
    exit;
}

$booking_id = (int)$_GET['id'];

// Получаем детальную информацию о бронировании
$query = "SELECT b.*, u.*, r.room_number, rt.name as room_type_name, rt.price_per_night
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN rooms r ON b.room_id = r.id
          JOIN room_types rt ON r.room_type_id = rt.id
          WHERE b.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $booking_id);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: bookings.php');
    exit;
}

// Рассчитываем количество ночей
$nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
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
                        <a class="nav-link text-white" href="dashboard.php">
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
                <h1 class="h2">Детали бронирования #<?php echo $booking['id']; ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="bookings.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Назад к списку
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Информация о бронировании</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>ID бронирования</th>
                                    <td>#<?php echo $booking['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Номер комнаты</th>
                                    <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>Тип номера</th>
                                    <td><?php echo htmlspecialchars($booking['room_type_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Дата заезда</th>
                                    <td><?php echo date('d.m.Y', strtotime($booking['check_in_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Дата выезда</th>
                                    <td><?php echo date('d.m.Y', strtotime($booking['check_out_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Количество ночей</th>
                                    <td><?php echo $nights; ?></td>
                                </tr>
                                <tr>
                                    <th>Количество гостей</th>
                                    <td><?php echo $booking['guests_count']; ?></td>
                                </tr>
                                <tr>
                                    <th>Цена за ночь</th>
                                    <td><?php echo number_format($booking['price_per_night'], 2); ?> руб.</td>
                                </tr>
                                <tr>
                                    <th>Общая стоимость</th>
                                    <td class="fw-bold text-success"><?php echo number_format($booking['total_price'], 2); ?> руб.</td>
                                </tr>
                                <tr>
                                    <th>Статус</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($booking['status']) {
                                                case 'confirmed': echo 'success'; break;
                                                case 'pending': echo 'warning'; break;
                                                case 'cancelled': echo 'danger'; break;
                                                case 'completed': echo 'info'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php 
                                            switch($booking['status']) {
                                                case 'confirmed': echo 'Подтверждено'; break;
                                                case 'pending': echo 'Ожидание'; break;
                                                case 'cancelled': echo 'Отменено'; break;
                                                case 'completed': echo 'Завершено'; break;
                                                default: echo $booking['status'];
                                            }
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Дата создания</th>
                                    <td><?php echo date('d.m.Y H:i', strtotime($booking['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Информация о госте</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>ID пользователя</th>
                                    <td><?php echo $booking['user_id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Имя пользователя</th>
                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?php echo htmlspecialchars($booking['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Имя</th>
                                    <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Телефон</th>
                                    <td><?php echo htmlspecialchars($booking['phone'] ?? 'Не указан'); ?></td>
                                </tr>
                                <tr>
                                    <th>Роль</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($booking['role']) {
                                                case 'admin': echo 'danger'; break;
                                                case 'staff': echo 'warning'; break;
                                                default: echo 'success';
                                            }
                                        ?>">
                                            <?php 
                                            switch($booking['role']) {
                                                case 'admin': echo 'Админ'; break;
                                                case 'staff': echo 'Персонал'; break;
                                                default: echo 'Гость';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($booking['special_requests'])): ?>
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Особые пожелания</h6>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>