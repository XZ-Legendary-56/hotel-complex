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
$services = [];
$categories = [];
$staff = [];
$rooms = [];
$service_stats = [];
$error = '';
$success = '';

try {
    // Получаем все услуги с информацией о персонале и номерах
    $services_query = $db->query("
        SELECT s.*, 
               GROUP_CONCAT(DISTINCT st.name) as staff_names,
               GROUP_CONCAT(DISTINCT r.room_number) as room_numbers
        FROM services s
        LEFT JOIN service_staff ss ON s.id = ss.service_id
        LEFT JOIN staff st ON ss.staff_id = st.id
        LEFT JOIN service_rooms sr ON s.id = sr.service_id
        LEFT JOIN rooms r ON sr.room_id = r.id
        GROUP BY s.id
        ORDER BY s.category, s.name
    ");
    
    if ($services_query) {
        $services = $services_query->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получаем категории услуг
    $categories_query = $db->query("SELECT DISTINCT category FROM services WHERE category IS NOT NULL ORDER BY category");
    if ($categories_query) {
        $categories = $categories_query->fetchAll(PDO::FETCH_COLUMN);
    }

    // Получаем персонал для назначения
    $staff_query = $db->query("SELECT * FROM staff WHERE status='active' ORDER BY name");
    if ($staff_query) {
        $staff = $staff_query->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получаем номера для привязки
    $rooms_query = $db->query("SELECT * FROM rooms ORDER BY room_number");
    if ($rooms_query) {
        $rooms = $rooms_query->fetchAll(PDO::FETCH_ASSOC);
    }

    // Обработка добавления/редактирования услуги
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_service'])) {
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = (float)$_POST['price'];
            $category = trim($_POST['category']);
            
            // Валидация
            if (empty($name) || $price <= 0) {
                $error = 'Название услуги и цена обязательны для заполнения';
            } else {
                $insert_query = "INSERT INTO services (name, description, price, category, status) 
                                 VALUES (:name, :description, :price, :category, 'active')";
                $stmt = $db->prepare($insert_query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':category', $category);
                
                if ($stmt->execute()) {
                    $service_id = $db->lastInsertId();
                    
                    // Назначаем персонал если выбран
                    if (!empty($_POST['staff_ids'])) {
                        $staff_stmt = $db->prepare("INSERT INTO service_staff (service_id, staff_id) VALUES (?, ?)");
                        foreach ($_POST['staff_ids'] as $staff_id) {
                            $staff_stmt->execute([$service_id, $staff_id]);
                        }
                    }
                    
                    // Привязываем к номерам если выбраны
                    if (!empty($_POST['room_ids'])) {
                        $room_stmt = $db->prepare("INSERT INTO service_rooms (service_id, room_id) VALUES (?, ?)");
                        foreach ($_POST['room_ids'] as $room_id) {
                            $room_stmt->execute([$service_id, $room_id]);
                        }
                    }
                    
                    $success = 'Услуга успешно добавлена!';
                } else {
                    $error = 'Ошибка при добавлении услуги';
                }
            }
        }
        
        if (isset($_POST['update_service'])) {
            $service_id = (int)$_POST['service_id'];
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = (float)$_POST['price'];
            $category = trim($_POST['category']);
            $status = $_POST['status'];
            
            // Валидация
            if (empty($name) || $price <= 0) {
                $error = 'Название услуги и цена обязательны для заполнения';
            } else {
                $update_query = "UPDATE services SET name = :name, description = :description, 
                                 price = :price, category = :category, status = :status 
                                 WHERE id = :id";
                $stmt = $db->prepare($update_query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':category', $category);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':id', $service_id);
                
                if ($stmt->execute()) {
                    // Обновляем персонал
                    $db->prepare("DELETE FROM service_staff WHERE service_id = ?")->execute([$service_id]);
                    if (!empty($_POST['staff_ids'])) {
                        $staff_stmt = $db->prepare("INSERT INTO service_staff (service_id, staff_id) VALUES (?, ?)");
                        foreach ($_POST['staff_ids'] as $staff_id) {
                            $staff_stmt->execute([$service_id, $staff_id]);
                        }
                    }
                    
                    // Обновляем номера
                    $db->prepare("DELETE FROM service_rooms WHERE service_id = ?")->execute([$service_id]);
                    if (!empty($_POST['room_ids'])) {
                        $room_stmt = $db->prepare("INSERT INTO service_rooms (service_id, room_id) VALUES (?, ?)");
                        foreach ($_POST['room_ids'] as $room_id) {
                            $room_stmt->execute([$service_id, $room_id]);
                        }
                    }
                    
                    $success = 'Услуга успешно обновлена!';
                } else {
                    $error = 'Ошибка при обновлении услуги';
                }
            }
        }
        
        if (isset($_POST['delete_service'])) {
            $service_id = (int)$_POST['service_id'];
            
            try {
                // Начинаем транзакцию для безопасного удаления
                $db->beginTransaction();
                
                // Удаляем связи с персоналом и номерами
                $db->prepare("DELETE FROM service_staff WHERE service_id = ?")->execute([$service_id]);
                $db->prepare("DELETE FROM service_rooms WHERE service_id = ?")->execute([$service_id]);
                
                // Удаляем саму услугу
                $delete_query = "DELETE FROM services WHERE id = :id";
                $stmt = $db->prepare($delete_query);
                $stmt->bindParam(':id', $service_id);
                
                if ($stmt->execute()) {
                    $db->commit();
                    $success = 'Услуга успешно удалена!';
                } else {
                    $db->rollBack();
                    $error = 'Ошибка при удалении услуги';
                }
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Ошибка при удалении услуги: ' . $e->getMessage();
            }
        }
        
        // После обработки POST запроса обновляем данные
        if ($success || $error) {
            $services_query = $db->query("
                SELECT s.*, 
                       GROUP_CONCAT(DISTINCT st.name) as staff_names,
                       GROUP_CONCAT(DISTINCT r.room_number) as room_numbers
                FROM services s
                LEFT JOIN service_staff ss ON s.id = ss.service_id
                LEFT JOIN staff st ON ss.staff_id = st.id
                LEFT JOIN service_rooms sr ON s.id = sr.service_id
                LEFT JOIN rooms r ON sr.room_id = r.id
                GROUP BY s.id
                ORDER BY s.category, s.name
            ");
            
            if ($services_query) {
                $services = $services_query->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }

    // Получаем статистику по заказам услуг
    $stats_query = $db->query("
        SELECT s.name, COUNT(so.id) as order_count, SUM(s.price) as total_revenue
        FROM services s
        LEFT JOIN service_orders so ON s.id = so.service_id
        GROUP BY s.id
        ORDER BY order_count DESC
    ");
    
    if ($stats_query) {
        $service_stats = $stats_query->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Управление услугами</title>
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
        .service-actions .btn {
            margin: 0 2px;
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
                            <a class="nav-link active text-white" href="services.php">
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
                        <li class="nav-item">
                            <a class="nav-link text-white" href="staff.php">
                                <i class="fas fa-user-tie me-2"></i>
                                Персонал
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
                    <h1 class="h2">Управление услугами</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="fas fa-plus me-1"></i>Добавить услугу
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

                <!-- Список услуг -->
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Список услуг</h6>
                        <span class="badge bg-primary">Всего: <?php echo count($services); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (count($services) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Название</th>
                                            <th>Категория</th>
                                            <th>Цена</th>
                                            <th>Персонал</th>
                                            <th>Номера</th>
                                            <th>Статус</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($service['name']); ?></strong>
                                                <?php if (!empty($service['description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($service['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($service['category'] ?? 'Без категории'); ?></td>
                                            <td><strong><?php echo number_format($service['price'], 2); ?> руб.</strong></td>
                                            <td>
                                                <?php if (!empty($service['staff_names'])): ?>
                                                    <small><?php echo htmlspecialchars($service['staff_names']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Не назначен</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($service['room_numbers'])): ?>
                                                    <small><?php echo htmlspecialchars($service['room_numbers']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Все номера</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $service['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo $service['status'] == 'active' ? 'Активна' : 'Неактивна'; ?>
                                                </span>
                                            </td>
                                            <td class="service-actions">
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editServiceModal<?php echo $service['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteServiceModal<?php echo $service['id']; ?>">
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
                                <i class="fas fa-concierge-bell fa-4x text-muted mb-3"></i>
                                <h4>Услуги не найдены</h4>
                                <p class="text-muted">Добавьте первую услугу используя кнопку выше.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Статистика услуг -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">Статистика заказов услуг</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($service_stats) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Услуга</th>
                                            <th>Количество заказов</th>
                                            <th>Общий доход</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($service_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo $stat['order_count']; ?></span></td>
                                            <td><strong><?php echo number_format($stat['total_revenue'] ?? 0, 2); ?> руб.</strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">Нет данных о заказах услуг.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальное окно добавления услуги -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addServiceModalLabel">Добавить новую услугу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addServiceForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Название услуги *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Категория</label>
                                    <input type="text" class="form-control" name="category" list="categories">
                                    <datalist id="categories">
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Описание услуги..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Цена (руб.) *</label>
                                    <input type="number" class="form-control" name="price" step="0.01" min="0" required placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Назначение персонала -->
                        <div class="mb-3">
                            <label class="form-label">Назначить персонал</label>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
                                <?php if (count($staff) > 0): ?>
                                    <?php foreach ($staff as $employee): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="staff_ids[]" 
                                                   value="<?php echo $employee['id']; ?>" id="new_staff_<?php echo $employee['id']; ?>">
                                            <label class="form-check-label" for="new_staff_<?php echo $employee['id']; ?>">
                                                <?php echo htmlspecialchars($employee['name']); ?> (<?php echo $employee['position']; ?>)
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Нет доступного персонала</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Привязка к номерам -->
                        <div class="mb-3">
                            <label class="form-label">Привязать к номерам</label>
                            <small class="text-muted d-block">Оставьте пустым для всех номеров</small>
                            <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
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
                        <button type="submit" name="add_service" class="btn btn-primary">Добавить услугу</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна редактирования и удаления для каждой услуги -->
    <?php foreach ($services as $service): ?>
        <!-- Модальное окно редактирования -->
        <div class="modal fade" id="editServiceModal<?php echo $service['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Редактировать услугу</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Название услуги *</label>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo htmlspecialchars($service['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Категория</label>
                                        <input type="text" class="form-control" name="category" 
                                               value="<?php echo htmlspecialchars($service['category'] ?? ''); ?>" 
                                               list="categories">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Описание</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($service['description']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Цена (руб.) *</label>
                                        <input type="number" class="form-control" name="price" step="0.01" min="0" 
                                               value="<?php echo $service['price']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Статус</label>
                                        <select class="form-select" name="status">
                                            <option value="active" <?php echo $service['status'] == 'active' ? 'selected' : ''; ?>>Активна</option>
                                            <option value="inactive" <?php echo $service['status'] == 'inactive' ? 'selected' : ''; ?>>Неактивна</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Назначение персонала -->
                            <div class="mb-3">
                                <label class="form-label">Назначить персонал</label>
                                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
                                    <?php 
                                    // Получаем назначенный персонал для этой услуги
                                    $assigned_staff = $db->prepare("SELECT staff_id FROM service_staff WHERE service_id = ?");
                                    $assigned_staff->execute([$service['id']]);
                                    $assigned_staff_ids = $assigned_staff->fetchAll(PDO::FETCH_COLUMN);
                                    ?>
                                    <?php if (count($staff) > 0): ?>
                                        <?php foreach ($staff as $employee): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="staff_ids[]" 
                                                       value="<?php echo $employee['id']; ?>"
                                                       id="staff_<?php echo $service['id']; ?>_<?php echo $employee['id']; ?>"
                                                       <?php echo in_array($employee['id'], $assigned_staff_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="staff_<?php echo $service['id']; ?>_<?php echo $employee['id']; ?>">
                                                    <?php echo htmlspecialchars($employee['name']); ?> (<?php echo $employee['position']; ?>)
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">Нет доступного персонала</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Привязка к номерам -->
                            <div class="mb-3">
                                <label class="form-label">Привязать к номерам</label>
                                <small class="text-muted d-block">Оставьте пустым для всех номеров</small>
                                <div style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px;">
                                    <?php 
                                    // Получаем привязанные номера для этой услуги
                                    $assigned_rooms = $db->prepare("SELECT room_id FROM service_rooms WHERE service_id = ?");
                                    $assigned_rooms->execute([$service['id']]);
                                    $assigned_room_ids = $assigned_rooms->fetchAll(PDO::FETCH_COLUMN);
                                    ?>
                                    <?php if (count($rooms) > 0): ?>
                                        <?php foreach ($rooms as $room): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="room_ids[]" 
                                                       value="<?php echo $room['id']; ?>"
                                                       id="room_<?php echo $service['id']; ?>_<?php echo $room['id']; ?>"
                                                       <?php echo in_array($room['id'], $assigned_room_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="room_<?php echo $service['id']; ?>_<?php echo $room['id']; ?>">
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
                            <button type="submit" name="update_service" class="btn btn-primary">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Модальное окно удаления -->
        <div class="modal fade" id="deleteServiceModal<?php echo $service['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Удалить услугу</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                            <p>Вы уверены, что хотите удалить услугу <strong>"<?php echo htmlspecialchars($service['name']); ?>"</strong>?</p>
                            <p class="text-danger"><small>Это действие нельзя отменить. Все связанные данные будут удалены.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" name="delete_service" class="btn btn-danger">Удалить</button>
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
            const addModal = document.getElementById('addServiceModal');
            if (addModal) {
                addModal.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('addServiceForm').reset();
                });
            }
        });
    </script>
</body>
</html>