<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isStaff()) {
    header('Location: ../pages/login.php');
    exit;
}

$staff_id = $auth->getStaffId();
$staff_data = $auth->getStaffData();

// Получаем активные заказы услуг для этого сотрудника
$service_orders_query = $db->prepare("
    SELECT 
        so.*,
        s.name as service_name,
        s.category as service_category,
        s.price,
        r.room_number,
        u.first_name,
        u.last_name,
        u.phone as guest_phone,
        b.check_in_date,
        b.check_out_date
    FROM service_orders so
    JOIN services s ON so.service_id = s.id
    JOIN service_staff ss ON s.id = ss.service_id
    JOIN rooms r ON so.room_id = r.id
    JOIN bookings b ON so.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    WHERE ss.staff_id = :staff_id 
    AND so.status IN ('pending', 'in_progress')
    AND so.order_date >= CURDATE()
    ORDER BY so.priority DESC, so.requested_time ASC
");
$service_orders_query->bindParam(':staff_id', $staff_id);
$service_orders_query->execute();
$service_orders = $service_orders_query->fetchAll(PDO::FETCH_ASSOC);

// Получаем номера, требующие уборки
$cleaning_tasks_query = $db->prepare("
    SELECT 
        r.room_number,
        rt.name as room_type,
        r.floor,
        r.status,
        b.check_out_date,
        TIMESTAMPDIFF(HOUR, b.check_out_date, NOW()) as hours_since_checkout
    FROM rooms r
    JOIN staff_rooms sr ON r.id = sr.room_id
    JOIN room_types rt ON r.room_type_id = rt.id
    LEFT JOIN bookings b ON r.id = b.room_id 
        AND b.status = 'completed'
        AND b.check_out_date = (
            SELECT MAX(check_out_date) 
            FROM bookings 
            WHERE room_id = r.id 
            AND status = 'completed'
        )
    WHERE sr.staff_id = :staff_id
    AND r.status IN ('cleaning', 'maintenance')
    ORDER BY r.status, hours_since_checkout DESC
");
$cleaning_tasks_query->bindParam(':staff_id', $staff_id);
$cleaning_tasks_query->execute();
$cleaning_tasks = $cleaning_tasks_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои задачи - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .task-card {
            border-left: 4px solid #007bff;
            margin-bottom: 1rem;
        }
        .task-urgent {
            border-left-color: #dc3545;
            background-color: #fff5f5;
        }
        .task-in-progress {
            border-left-color: #ffc107;
        }
        .task-completed {
            border-left-color: #28a745;
            opacity: 0.7;
        }
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-medium { color: #ffc107; font-weight: bold; }
        .priority-low { color: #28a745; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="staff_profile.php">
                <i class="fas fa-user-tie me-2"></i>Персонал
            </a>
            <div class="navbar-nav">
                <a class="nav-link" href="staff_profile.php">
                    <i class="fas fa-user me-1"></i>Профиль
                </a>
                <a class="nav-link active" href="staff_tasks.php">
                    <i class="fas fa-tasks me-1"></i>Мои задачи
                </a>
                <a class="nav-link" href="../pages/logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Выйти
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">
            <i class="fas fa-tasks me-2"></i>Мои задачи
            <small class="text-muted"><?php echo htmlspecialchars($staff_data['name']); ?></small>
        </h1>

        <!-- Заказы услуг -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-concierge-bell me-2"></i>Заказы услуг
                    <span class="badge bg-light text-dark"><?php echo count($service_orders); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($service_orders) > 0): ?>
                    <?php foreach ($service_orders as $order): ?>
                        <div class="card task-card <?php echo $order['priority'] === 'high' ? 'task-urgent' : ''; ?>">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="card-title mb-1">
                                            <?php echo htmlspecialchars($order['service_name']); ?>
                                            <span class="badge bg-<?php echo $order['status'] === 'pending' ? 'warning' : 'info'; ?>">
                                                <?php echo $order['status'] === 'pending' ? 'Ожидает' : 'В работе'; ?>
                                            </span>
                                        </h6>
                                        <p class="mb-1">
                                            <i class="fas fa-door-open me-1"></i>
                                            Номер <?php echo $order['room_number']; ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-user me-1"></i>
                                            Гость: <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-clock me-1"></i>
                                            Заказано на: <?php echo date('H:i', strtotime($order['requested_time'])); ?>
                                        </p>
                                        <?php if (!empty($order['notes'])): ?>
                                            <p class="mb-1"><small>Примечание: <?php echo htmlspecialchars($order['notes']); ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="mb-2">
                                            <span class="priority-<?php echo $order['priority']; ?>">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                <?php 
                                                switch($order['priority']) {
                                                    case 'high': echo 'Срочно'; break;
                                                    case 'medium': echo 'Средний'; break;
                                                    case 'low': echo 'Низкий'; break;
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed')">
                                                <i class="fas fa-check me-1"></i>Выполнено
                                            </button>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'in_progress')">
                                                <i class="fas fa-play me-1"></i>В работе
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="text-muted">Нет активных заказов услуг</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Задачи по уборке -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-broom me-2"></i>Задачи по уборке
                    <span class="badge bg-light text-dark"><?php echo count($cleaning_tasks); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($cleaning_tasks) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Номер</th>
                                    <th>Тип</th>
                                    <th>Этаж</th>
                                    <th>Статус</th>
                                    <th>Время после выезда</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cleaning_tasks as $task): ?>
                                    <tr>
                                        <td><strong><?php echo $task['room_number']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($task['room_type']); ?></td>
                                        <td><?php echo $task['floor']; ?> этаж</td>
                                        <td>
                                            <span class="badge bg-<?php echo $task['status'] === 'cleaning' ? 'warning' : 'danger'; ?>">
                                                <?php echo $task['status'] === 'cleaning' ? 'Уборка' : 'Ремонт'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($task['hours_since_checkout']): ?>
                                                <?php echo $task['hours_since_checkout']; ?> ч. назад
                                            <?php else: ?>
                                                <span class="text-muted">Неизвестно</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="markRoomCleaned(<?php echo $task['room_number']; ?>)">
                                                <i class="fas fa-check me-1"></i>Убрано
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-broom fa-3x text-success mb-3"></i>
                        <p class="text-muted">Нет задач по уборке</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function updateOrderStatus(orderId, status) {
        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        });
    }

    function markRoomCleaned(roomNumber) {
        if (confirm(`Отметить номер ${roomNumber} как убранный?`)) {
            fetch('mark_room_cleaned.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `room_number=${roomNumber}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        }
    }
    </script>
</body>
</html>