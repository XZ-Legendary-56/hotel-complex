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

// Получаем всех пользователей
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Обработка изменения статуса пользователя
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $update_query = "UPDATE users SET role = :role, is_active = :is_active WHERE id = :id";
    $stmt = $db->prepare($update_query);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':is_active', $is_active);
    $stmt->bindParam(':id', $user_id);
    
    if ($stmt->execute()) {
        header('Location: users.php?success=1');
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
                        <a class="nav-link active text-white" href="users.php">
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
                <h1 class="h2">Управление пользователями</h1>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Настройки пользователя обновлены!</div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Имя пользователя</th>
                                    <th>Email</th>
                                    <th>Имя</th>
                                    <th>Роль</th>
                                    <th>Статус</th>
                                    <th>Дата регистрации</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($user['role']) {
                                                case 'admin': echo 'danger'; break;
                                                case 'staff': echo 'warning'; break;
                                                default: echo 'success';
                                            }
                                        ?>">
                                            <?php 
                                            switch($user['role']) {
                                                case 'admin': echo 'Админ'; break;
                                                case 'staff': echo 'Персонал'; break;
                                                default: echo 'Гость';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $user['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <div class="mb-2">
                                                <select name="role" class="form-select form-select-sm">
                                                    <option value="guest" <?php echo $user['role'] == 'guest' ? 'selected' : ''; ?>>Гость</option>
                                                    <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Персонал</option>
                                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Админ</option>
                                                </select>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" id="active_<?php echo $user['id']; ?>" 
                                                    <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="active_<?php echo $user['id']; ?>">Активен</label>
                                            </div>
                                            <button type="submit" name="update_user" class="btn btn-sm btn-primary mt-2">Сохранить</button>
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