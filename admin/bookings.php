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

// Получаем все бронирования
$query = "SELECT b.*, u.username, u.first_name, u.last_name, 
                 r.room_number, rt.name as room_type_name, rt.price_per_night
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN rooms r ON b.room_id = r.id
          JOIN room_types rt ON r.room_type_id = rt.id
          ORDER BY b.created_at DESC";

$bookings = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $update_query = "UPDATE bookings SET status = :status WHERE id = :id";
    $stmt = $db->prepare($update_query);
    $stmt->bindParam(':status', $new_status);
    $stmt->bindParam(':id', $booking_id);
    
    if ($stmt->execute()) {
        // Если статус изменен на "completed" или "cancelled", освобождаем комнату
        if ($new_status === 'completed' || $new_status === 'cancelled') {
            $room_query = "SELECT room_id FROM bookings WHERE id = :id";
            $room_stmt = $db->prepare($room_query);
            $room_stmt->bindParam(':id', $booking_id);
            $room_stmt->execute();
            $room_id = $room_stmt->fetchColumn();
            
            if ($room_id) {
                $update_room = "UPDATE rooms SET status = 'available' WHERE id = :room_id";
                $update_stmt = $db->prepare($update_room);
                $update_stmt->bindParam(':room_id', $room_id);
                $update_stmt->execute();
            }
        }
        
        header('Location: bookings.php?success=1');
        exit;
    }
}

// Обработка удаления бронирования
if (isset($_GET['delete'])) {
    $booking_id = $_GET['delete'];
    
    // Сначала получаем room_id для освобождения комнаты
    $room_query = "SELECT room_id FROM bookings WHERE id = :id";
    $room_stmt = $db->prepare($room_query);
    $room_stmt->bindParam(':id', $booking_id);
    $room_stmt->execute();
    $room_id = $room_stmt->fetchColumn();
    
    // Удаляем бронирование
    $delete_query = "DELETE FROM bookings WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(':id', $booking_id);
    
    if ($delete_stmt->execute()) {
        // Освобождаем комнату
        if ($room_id) {
            $update_room = "UPDATE rooms SET status = 'available' WHERE id = :room_id";
            $update_stmt = $db->prepare($update_room);
            $update_stmt->bindParam(':room_id', $room_id);
            $update_stmt->execute();
        }
        
        header('Location: bookings.php?success=2');
        exit;
    }
}
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
                        <a class="nav-link active text-white" href="bookings.php">
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
                <h1 class="h2">Управление бронированиями</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Назад в дашборд
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    if ($_GET['success'] == 1) {
                        echo 'Статус бронирования успешно обновлен!';
                    } elseif ($_GET['success'] == 2) {
                        echo 'Бронирование успешно удалено!';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Все бронирования</h6>
                    <span class="badge bg-primary">Всего: <?php echo count($bookings); ?></span>
                </div>
                <div class="card-body">
                    <?php if (count($bookings) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Гость</th>
                                        <th>Номер</th>
                                        <th>Даты</th>
                                        <th>Ночей</th>
                                        <th>Гости</th>
                                        <th>Стоимость</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): 
                                        $nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo $booking['id']; ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['username']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['room_number']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($booking['room_type_name']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo date('d.m.Y', strtotime($booking['check_in_date'])); ?>
                                            <br>
                                            <small class="text-muted">по</small>
                                            <br>
                                            <?php echo date('d.m.Y', strtotime($booking['check_out_date'])); ?>
                                        </td>
                                        <td><?php echo $nights; ?></td>
                                        <td><?php echo $booking['guests_count']; ?></td>
                                        <td>
                                            <span class="text-success fw-bold">
                                                <?php echo number_format($booking['total_price'], 2); ?> руб.
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="mb-2">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Ожидание</option>
                                                    <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Подтверждено</option>
                                                    <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Отменено</option>
                                                    <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Завершено</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
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
                                        <td>
                                            <div class="btn-group-vertical">
                                                <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-sm btn-info mb-1" title="Детали">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?delete=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Вы уверены, что хотите удалить это бронирование?')"
                                                   title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h4>Бронирования не найдены</h4>
                            <p class="text-muted">Нет активных бронирований.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Статистика -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white text-center">
                        <div class="card-body">
                            <h6>Ожидающие</h6>
                            <h3>
                                <?php 
                                $pending = array_filter($bookings, function($b) { return $b['status'] == 'pending'; });
                                echo count($pending);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white text-center">
                        <div class="card-body">
                            <h6>Подтвержденные</h6>
                            <h3>
                                <?php 
                                $confirmed = array_filter($bookings, function($b) { return $b['status'] == 'confirmed'; });
                                echo count($confirmed);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white text-center">
                        <div class="card-body">
                            <h6>Завершенные</h6>
                            <h3>
                                <?php 
                                $completed = array_filter($bookings, function($b) { return $b['status'] == 'completed'; });
                                echo count($completed);
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.table th {
    background-color: #2c3e50;
    color: white;
}
.btn-group-vertical .btn {
    margin-bottom: 2px;
}
</style>

<?php include '../includes/footer.php'; ?>