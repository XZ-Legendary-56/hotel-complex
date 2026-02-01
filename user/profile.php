<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/classes/User.php';
require_once '../includes/classes/Booking.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = new User($db);
$booking = new Booking($db);

// Получаем данные пользователя
$user_data = $user->readOne($user_id);
$user_bookings = $booking->readByUser($user_id);

$success = '';
$error = '';

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $update_data = [
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone'])
    ];
    
    if ($user->update($user_id, $update_data)) {
        $success = 'Профиль успешно обновлен!';
    } else {
        $error = 'Ошибка при обновлении профиля';
    }
}

// Обработка смены пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = 'Новые пароли не совпадают';
    } elseif (strlen($new_password) < 6) {
        $error = 'Пароль должен содержать не менее 6 символов';
    } else {
        if ($user->changePassword($user_id, $current_password, $new_password)) {
            $success = 'Пароль успешно изменен!';
        } else {
            $error = 'Текущий пароль неверен';
        }
    }
}
?>

<?php include '../includes/header.php'; ?>



<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h5>Меню</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="profile.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-user me-2"></i>Профиль
                </a>
                <a href="bookings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-calendar-alt me-2"></i>Мои бронирования
                </a>
                <a href="../pages/logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Выйти
                </a>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($user_data['username']); ?></p>
                <span class="badge bg-<?php 
                    switch($user_data['role']) {
                        case 'admin': echo 'danger'; break;
                        case 'staff': echo 'warning'; break;
                        default: echo 'success';
                    }
                ?>">
                    <?php 
                    switch($user_data['role']) {
                        case 'admin': echo 'Администратор'; break;
                        case 'staff': echo 'Персонал'; break;
                        default: echo 'Гость';
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5>Основная информация</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Имя</label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Фамилия</label>
                                <input type="text" class="form-control" name="last_name" 
                                       value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Телефон</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Имя пользователя</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                        <small class="form-text text-muted">Имя пользователя нельзя изменить</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Смена пароля</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Текущий пароль</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Новый пароль</label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Подтверждение пароля</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">Сменить пароль</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Последние бронирования</h5>
                <a href="bookings.php" class="btn btn-sm btn-outline-primary">Все бронирования</a>
            </div>
            <div class="card-body">
                <?php if ($user_bookings->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Номер</th>
                                    <th>Даты</th>
                                    <th>Стоимость</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                while ($booking = $user_bookings->fetch(PDO::FETCH_ASSOC) and $count < 3): 
                                    $count++;
                                ?>
                                    <tr>
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
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p>У вас пока нет бронирований</p>
                        <a href="../pages/rooms.php" class="btn btn-primary">Забронировать номер</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>