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

// Получаем все номера
$query = "SELECT r.*, rt.name as room_type_name 
          FROM rooms r 
          JOIN room_types rt ON r.room_type_id = rt.id 
          ORDER BY r.room_number";
$rooms = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Получаем типы номеров для формы
$room_types = $db->query("SELECT * FROM room_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Обработка добавления/редактирования номера
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        $room_number = $_POST['room_number'];
        $room_type_id = $_POST['room_type_id'];
        $floor = $_POST['floor'];
        $status = $_POST['status'];
        
        $insert_query = "INSERT INTO rooms (room_number, room_type_id, floor, status) 
                         VALUES (:room_number, :room_type_id, :floor, :status)";
        $stmt = $db->prepare($insert_query);
        $stmt->bindParam(':room_number', $room_number);
        $stmt->bindParam(':room_type_id', $room_type_id);
        $stmt->bindParam(':floor', $floor);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            header('Location: rooms.php?success=1');
            exit;
        }
    }
    
    if (isset($_POST['update_status'])) {
        $room_id = $_POST['room_id'];
        $status = $_POST['status'];
        
        $update_query = "UPDATE rooms SET status = :status WHERE id = :id";
        $stmt = $db->prepare($update_query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $room_id);
        
        if ($stmt->execute()) {
            header('Location: rooms.php?success=1');
            exit;
        }
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
                        <a class="nav-link text-white" href="bookings.php">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Бронирования
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="rooms.php">
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
                <h1 class="h2">Управление номерами</h1>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Операция выполнена успешно!</div>
            <?php endif; ?>

            <!-- Форма добавления номера -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Добавить номер</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Номер комнаты</label>
                                    <input type="text" class="form-control" name="room_number" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Тип номера</label>
                                    <select class="form-select" name="room_type_id" required>
                                        <?php foreach ($room_types as $type): ?>
                                            <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Этаж</label>
                                    <input type="number" class="form-control" name="floor" min="1" max="10" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">Статус</label>
                                    <select class="form-select" name="status">
                                        <option value="available">Доступен</option>
                                        <option value="occupied">Занят</option>
                                        <option value="maintenance">Ремонт</option>
                                        <option value="cleaning">Уборка</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="add_room" class="btn btn-primary w-100">Добавить</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Список номеров -->
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Номер</th>
                                    <th>Тип</th>
                                    <th>Этаж</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?php echo $room['id']; ?></td>
                                    <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($room['room_type_name']); ?></td>
                                    <td><?php echo $room['floor']; ?> этаж</td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($room['status']) {
                                                case 'available': echo 'success'; break;
                                                case 'occupied': echo 'danger'; break;
                                                case 'maintenance': echo 'warning'; break;
                                                case 'cleaning': echo 'info'; break;
                                                default: echo 'secondary';
                                            }
                                        ?>">
                                            <?php 
                                            switch($room['status']) {
                                                case 'available': echo 'Доступен'; break;
                                                case 'occupied': echo 'Занят'; break;
                                                case 'maintenance': echo 'Ремонт'; break;
                                                case 'cleaning': echo 'Уборка'; break;
                                                default: echo $room['status'];
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="available" <?php echo $room['status'] == 'available' ? 'selected' : ''; ?>>Доступен</option>
                                                <option value="occupied" <?php echo $room['status'] == 'occupied' ? 'selected' : ''; ?>>Занят</option>
                                                <option value="maintenance" <?php echo $room['status'] == 'maintenance' ? 'selected' : ''; ?>>Ремонт</option>
                                                <option value="cleaning" <?php echo $room['status'] == 'cleaning' ? 'selected' : ''; ?>>Уборка</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>