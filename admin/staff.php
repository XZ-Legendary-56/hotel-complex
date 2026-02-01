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

// Инициализация переменных
$staff = [];
$rooms = [];
$error = '';
$success = '';

try {
    // Получаем весь персонал с информацией о назначенных номерах
    $staff_query = $db->query("
        SELECT s.*, 
               GROUP_CONCAT(DISTINCT r.room_number) as assigned_rooms,
               GROUP_CONCAT(DISTINCT r.id) as assigned_room_ids
        FROM staff s
        LEFT JOIN staff_rooms sr ON s.id = sr.staff_id
        LEFT JOIN rooms r ON sr.room_id = r.id
        GROUP BY s.id
        ORDER BY s.name
    ");
    
    if ($staff_query) {
        $staff = $staff_query->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получаем все номера для назначения - убираем поле type
    $rooms_query = $db->query("SELECT id, room_number, status FROM rooms ORDER BY room_number");
    if ($rooms_query) {
        $rooms = $rooms_query->fetchAll(PDO::FETCH_ASSOC);
    }

    // Обработка добавления/редактирования персонала
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_staff'])) {
            $name = trim($_POST['name']);
            $position = trim($_POST['position']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            
            if (empty($name) || empty($position)) {
                $error = 'Имя и должность обязательны для заполнения';
            } else {
                $insert_query = "INSERT INTO staff (name, position, phone, email, status) 
                                 VALUES (:name, :position, :phone, :email, 'active')";
                $stmt = $db->prepare($insert_query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':position', $position);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':email', $email);
                
                if ($stmt->execute()) {
                    $staff_id = $db->lastInsertId();
                    
                    // Назначаем номера если выбраны
                    if (!empty($_POST['room_ids'])) {
                        $room_stmt = $db->prepare("INSERT INTO staff_rooms (staff_id, room_id) VALUES (?, ?)");
                        foreach ($_POST['room_ids'] as $room_id) {
                            $room_stmt->execute([$staff_id, $room_id]);
                        }
                    }
                    
                    $success = 'Сотрудник успешно добавлен!';
                } else {
                    $error = 'Ошибка при добавлении сотрудника';
                }
            }
        }
        
        if (isset($_POST['update_staff'])) {
            $staff_id = (int)$_POST['staff_id'];
            $name = trim($_POST['name']);
            $position = trim($_POST['position']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $status = $_POST['status'];
            
            if (empty($name) || empty($position)) {
                $error = 'Имя и должность обязательны для заполнения';
            } else {
                $update_query = "UPDATE staff SET name = :name, position = :position, 
                                 phone = :phone, email = :email, status = :status 
                                 WHERE id = :id";
                $stmt = $db->prepare($update_query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':position', $position);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':id', $staff_id);
                
                if ($stmt->execute()) {
                    // Обновляем назначенные номера
                    $db->prepare("DELETE FROM staff_rooms WHERE staff_id = ?")->execute([$staff_id]);
                    if (!empty($_POST['room_ids'])) {
                        $room_stmt = $db->prepare("INSERT INTO staff_rooms (staff_id, room_id) VALUES (?, ?)");
                        foreach ($_POST['room_ids'] as $room_id) {
                            $room_stmt->execute([$staff_id, $room_id]);
                        }
                    }
                    
                    $success = 'Данные сотрудника обновлены!';
                } else {
                    $error = 'Ошибка при обновлении данных сотрудника';
                }
            }
        }
        
        if (isset($_POST['delete_staff'])) {
            $staff_id = (int)$_POST['staff_id'];
            
            try {
                $db->beginTransaction();
                
                // Удаляем связи с номерами
                $db->prepare("DELETE FROM staff_rooms WHERE staff_id = ?")->execute([$staff_id]);
                // Удаляем связи с услугами
                $db->prepare("DELETE FROM service_staff WHERE staff_id = ?")->execute([$staff_id]);
                
                // Удаляем самого сотрудника
                $delete_query = "DELETE FROM staff WHERE id = :id";
                $stmt = $db->prepare($delete_query);
                $stmt->bindParam(':id', $staff_id);
                
                if ($stmt->execute()) {
                    $db->commit();
                    $success = 'Сотрудник успешно удален!';
                } else {
                    $db->rollBack();
                    $error = 'Ошибка при удалении сотрудника';
                }
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Ошибка при удалении сотрудника: ' . $e->getMessage();
            }
        }
        
        // Обновляем данные после POST запроса
        if ($success || $error) {
            $staff_query = $db->query("
                SELECT s.*, 
                       GROUP_CONCAT(DISTINCT r.room_number) as assigned_rooms,
                       GROUP_CONCAT(DISTINCT r.id) as assigned_room_ids
                FROM staff s
                LEFT JOIN staff_rooms sr ON s.id = sr.staff_id
                LEFT JOIN rooms r ON sr.room_id = r.id
                GROUP BY s.id
                ORDER BY s.name
            ");
            
            if ($staff_query) {
                $staff = $staff_query->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

} catch (Exception $e) {
    $error = 'Ошибка базы данных: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление персоналом</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .nav-link {
            border-radius: 5px;
            margin: 2px 0;
        }
        .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            background-color: #0d6efd;
        }
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .staff-actions .btn {
            margin: 0 2px;
        }
        .assigned-rooms {
            max-width: 200px;
        }
        .rooms-checkbox-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
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
                        <li class="nav-item">
                            <a class="nav-link text-white" href="services.php">
                                <i class="fas fa-concierge-bell me-2"></i>
                                Услуги
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="staff.php">
                                <i class="fas fa-user-tie me-2"></i>
                                Персонал
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
                    <h1 class="h2">Управление персоналом</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                        <i class="fas fa-plus me-1"></i>Добавить сотрудника
                    </button>
                </div>

                <!-- Вывод сообщений -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Список персонала -->
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Список сотрудников</h6>
                        <span class="badge bg-primary">Всего: <?php echo count($staff); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (count($staff) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ФИО</th>
                                            <th>Должность</th>
                                            <th>Контакты</th>
                                            <th>Назначенные номера</th>
                                            <th>Статус</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staff as $employee): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($employee['name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                            <td>
                                                <?php if (!empty($employee['phone'])): ?>
                                                    <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($employee['phone']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($employee['email'])): ?>
                                                    <div><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($employee['email']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="assigned-rooms">
                                                <?php if (!empty($employee['assigned_rooms'])): ?>
                                                    <small class="text-success"><?php echo htmlspecialchars($employee['assigned_rooms']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Не назначен</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $employee['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo $employee['status'] == 'active' ? 'Активен' : 'Неактивен'; ?>
                                                </span>
                                            </td>
                                            <td class="staff-actions">
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editStaffModal<?php echo $employee['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteStaffModal<?php echo $employee['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-tie fa-4x text-muted mb-3"></i>
                                <h4>Сотрудники не найдены</h4>
                                <p class="text-muted">Добавьте первого сотрудника используя кнопку выше.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальное окно добавления сотрудника -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStaffModalLabel">Добавить нового сотрудника</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addStaffForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ФИО *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Должность *</label>
                                    <input type="text" class="form-control" name="position" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Телефон</label>
                                    <input type="tel" class="form-control" name="phone" placeholder="+7 (XXX) XXX-XX-XX">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Назначение номеров -->
                        <div class="mb-3">
                            <label class="form-label">Назначить на номера</label>
                            <small class="text-muted d-block">Выберите номера, за которые отвечает сотрудник</small>
                            <div class="rooms-checkbox-container">
                                <?php if (count($rooms) > 0): ?>
                                    <?php foreach ($rooms as $room): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="room_ids[]" 
                                                   value="<?php echo $room['id']; ?>" id="new_room_<?php echo $room['id']; ?>">
                                            <label class="form-check-label" for="new_room_<?php echo $room['id']; ?>">
                                                Номер <?php echo htmlspecialchars($room['room_number']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Нет доступных номеров</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="add_staff" class="btn btn-primary">Добавить сотрудника</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна редактирования и удаления для каждого сотрудника -->
    <?php foreach ($staff as $employee): ?>
        <!-- Модальное окно редактирования -->
        <div class="modal fade" id="editStaffModal<?php echo $employee['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Редактировать сотрудника</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="staff_id" value="<?php echo $employee['id']; ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">ФИО *</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo htmlspecialchars($employee['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Должность *</label>
                                        <input type="text" class="form-control" name="position" 
                                               value="<?php echo htmlspecialchars($employee['position']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Телефон</label>
                                        <input type="tel" class="form-control" name="phone" 
                                               value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email"
                                               value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Статус</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo $employee['status'] == 'active' ? 'selected' : ''; ?>>Активен</option>
                                    <option value="inactive" <?php echo $employee['status'] == 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                                </select>
                            </div>
                            
                            <!-- Назначение номеров -->
                            <div class="mb-3">
                                <label class="form-label">Назначить на номера</label>
                                <small class="text-muted d-block">Выберите номера, за которые отвечает сотрудник</small>
                                <div class="rooms-checkbox-container">
                                    <?php 
                                    // Получаем назначенные номера для этого сотрудника
                                    $assigned_room_ids = $employee['assigned_room_ids'] ? explode(',', $employee['assigned_room_ids']) : [];
                                    ?>
                                    <?php if (count($rooms) > 0): ?>
                                        <?php foreach ($rooms as $room): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="room_ids[]" 
                                                       value="<?php echo $room['id']; ?>"
                                                       id="room_<?php echo $employee['id']; ?>_<?php echo $room['id']; ?>"
                                                       <?php echo in_array($room['id'], $assigned_room_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="room_<?php echo $employee['id']; ?>_<?php echo $room['id']; ?>">
                                                    Номер <?php echo htmlspecialchars($room['room_number']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Нет доступных номеров</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" name="update_staff" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Модальное окно удаления -->
        <div class="modal fade" id="deleteStaffModal<?php echo $employee['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Удалить сотрудника</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="staff_id" value="<?php echo $employee['id']; ?>">
                            <p>Вы уверены, что хотите удалить сотрудника <strong>"<?php echo htmlspecialchars($employee['name']); ?>"</strong>?</p>
                            <p class="text-danger"><small>Это действие нельзя отменить. Все связанные данные будут удалены.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" name="delete_staff" class="btn btn-danger">Удалить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Автоматическое скрытие alert через 5 секунд
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Очистка формы добавления при закрытии модального окна
            const addModal = document.getElementById('addStaffModal');
            if (addModal) {
                addModal.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('addStaffForm').reset();
                });
            }
        });
    </script>
</body>
</html>