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

// Параметры фильтрации
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Статистика бронирований
$booking_stats = $db->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(total_price) as total_revenue,
        AVG(total_price) as avg_booking_value,
        COUNT(DISTINCT user_id) as unique_guests
    FROM bookings 
    WHERE created_at BETWEEN :start_date AND :end_date
");
$booking_stats->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$stats = $booking_stats->fetch(PDO::FETCH_ASSOC);

// Статистика по статусам
$status_stats = $db->prepare("
    SELECT status, COUNT(*) as count 
    FROM bookings 
    WHERE created_at BETWEEN :start_date AND :end_date
    GROUP BY status
");
$status_stats->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$status_data = $status_stats->fetchAll(PDO::FETCH_ASSOC);

// Популярные номера
$popular_rooms = $db->prepare("
    SELECT r.room_number, rt.name as room_type, COUNT(b.id) as booking_count,
           SUM(b.total_price) as total_revenue
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE b.created_at BETWEEN :start_date AND :end_date
    GROUP BY b.room_id
    ORDER BY booking_count DESC
    LIMIT 10
");
$popular_rooms->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$rooms_data = $popular_rooms->fetchAll(PDO::FETCH_ASSOC);

// Ежемесячная статистика
$monthly_stats = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as bookings,
        SUM(total_price) as revenue
    FROM bookings
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);
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
                    <li class="nav-item">
                        <a class="nav-link text-white" href="services.php">
                            <i class="fas fa-concierge-bell me-2"></i>
                            Услуги
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="reports.php">
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
                <h1 class="h2">Отчеты и аналитика</h1>
            </div>

            <!-- Фильтры -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Фильтр по дате</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Начальная дата</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Конечная дата</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Применить фильтр</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Основная статистика -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Всего бронирований</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_bookings'] ?? 0; ?></div>
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
                                        Общий доход</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['total_revenue'] ?? 0, 2); ?> руб.</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-ruble-sign fa-2x text-gray-300"></i>
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
                                        Средний чек</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['avg_booking_value'] ?? 0, 2); ?> руб.</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                        Уникальные гости</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['unique_guests'] ?? 0; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Детальная статистика -->
            <div class="row">
                <!-- Статистика по статусам -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Статистика по статусам бронирований</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Статус</th>
                                            <th>Количество</th>
                                            <th>Процент</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($status_data as $status): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                switch($status['status']) {
                                                    case 'confirmed': echo 'Подтвержденные'; break;
                                                    case 'pending': echo 'Ожидание'; break;
                                                    case 'cancelled': echo 'Отмененные'; break;
                                                    case 'completed': echo 'Завершенные'; break;
                                                    default: echo $status['status'];
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo $status['count']; ?></td>
                                            <td>
                                                <?php 
                                                $percentage = $stats['total_bookings'] > 0 ? ($status['count'] / $stats['total_bookings'] * 100) : 0;
                                                echo number_format($percentage, 1) . '%';
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Популярные номера -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Самые популярные номера</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Номер</th>
                                            <th>Тип</th>
                                            <th>Бронирований</th>
                                            <th>Доход</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rooms_data as $room): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                            <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                            <td><?php echo $room['booking_count']; ?></td>
                                            <td><?php echo number_format($room['total_revenue'], 2); ?> руб.</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ежемесячная статистика -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Ежемесячная статистика (последние 12 месяцев)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Месяц</th>
                                    <th>Бронирования</th>
                                    <th>Доход</th>
                                    <th>Средний чек</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthly_stats as $monthly): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($monthly['month'] . '-01')); ?></td>
                                    <td><?php echo $monthly['bookings']; ?></td>
                                    <td><?php echo number_format($monthly['revenue'], 2); ?> руб.</td>
                                    <td>
                                        <?php 
                                        $avg = $monthly['bookings'] > 0 ? $monthly['revenue'] / $monthly['bookings'] : 0;
                                        echo number_format($avg, 2); ?> руб.
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<!-- Экспорт отчетов -->
<div class="card shadow">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">Экспорт и отправка отчетов</h6>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="email" class="form-label">Email для отправки отчета:</label>
                <input type="email" class="form-control" id="report_email" value="ddikij392@gmail.com" placeholder="Введите email">
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Начальная дата:</label>
                <input type="date" class="form-control" id="report_start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Конечная дата:</label>
                <input type="date" class="form-control" id="report_end_date" value="<?php echo $end_date; ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <button type="button" class="btn btn-outline-primary w-100 mb-2 send-report" data-type="bookings">
                    <i class="fas fa-envelope me-2"></i>Отправить отчет по бронированиям
                </button>
                <a href="export_reports.php?type=bookings&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" 
                class="btn btn-outline-success w-100 btn-sm" target="_blank">
                    <i class="fas fa-download me-2"></i>Скачать Excel
                </a>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-outline-primary w-100 mb-2 send-report" data-type="financial">
                    <i class="fas fa-envelope me-2"></i>Отправить финансовый отчет
                </button>
                <a href="export_reports.php?type=financial&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" 
                 class="btn btn-outline-success w-100 btn-sm" target="_blank">
                    <i class="fas fa-download me-2"></i>Скачать Excel
                </a>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-outline-primary w-100 mb-2 send-report" data-type="guests">
                    <i class="fas fa-envelope me-2"></i>Отправить отчет по гостям
                </button>
                <a href="export_reports.php?type=guests&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" 
                class="btn btn-outline-success w-100 btn-sm" target="_blank">
                    <i class="fas fa-download me-2"></i>Скачать Excel
                </a>
            </div>
        </div>
        
        <!-- Прогресс бар и статус -->
        <div id="report_progress" class="mt-3" style="display: none;">
            <div class="progress mb-2">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
            <small class="text-muted" id="progress_text">Подготовка отчета...</small>
        </div>
        
        <div id="report_message" class="mt-3"></div>
    </div>
</div>
        
        <!-- Прогресс бар и статус -->
        <div id="report_progress" class="mt-3" style="display: none;">
            <div class="progress mb-2">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
            </div>
            <small class="text-muted" id="progress_text">Подготовка отчета...</small>
        </div>
        
        <div id="report_message" class="mt-3"></div>
    </div>
</div>

<!-- JavaScript для отправки отчетов -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sendButtons = document.querySelectorAll('.send-report');
    const messageDiv = document.getElementById('report_message');
    const progressDiv = document.getElementById('report_progress');
    const progressBar = progressDiv.querySelector('.progress-bar');
    const progressText = document.getElementById('progress_text');
    
    sendButtons.forEach(button => {
        button.addEventListener('click', function() {
            const type = this.dataset.type;
            const emailInput = document.getElementById('report_email');
            const startDateInput = document.getElementById('report_start_date');
            const endDateInput = document.getElementById('report_end_date');
            
            const email = emailInput.value.trim();
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            // Валидация
            if (!email) {
                showMessage('Введите email адрес', 'danger');
                emailInput.focus();
                return;
            }
            
            if (!isValidEmail(email)) {
                showMessage('Введите корректный email адрес', 'danger');
                emailInput.focus();
                return;
            }
            
            if (!startDate || !endDate) {
                showMessage('Выберите период дат', 'danger');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                showMessage('Начальная дата не может быть больше конечной', 'danger');
                return;
            }
            
            // Показать прогресс
            showProgress(0, 'Подготовка отчета...');
            
            // Блокировка кнопок
            sendButtons.forEach(btn => btn.disabled = true);
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Отправка...';
            
            // Обновление прогресса
            setTimeout(() => showProgress(30, 'Генерация данных...'), 500);
            setTimeout(() => showProgress(60, 'Формирование отчета...'), 1000);
            setTimeout(() => showProgress(80, 'Отправка email...'), 1500);
            
            // Создаем данные для отправки
            const formData = new FormData();
            formData.append('type', type);
            formData.append('email', email);
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            
            console.log('Отправка отчета:', {
                type: type,
                email: email,
                start_date: startDate,
                end_date: endDate
            });
            
            // Отправка AJAX запроса
            fetch('send_report_email.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Статус ответа:', response.status);
                
                if (!response.ok) {
                    throw new Error('Ошибка сети: ' + response.status);
                }
                
                return response.text().then(text => {
                    console.log('Полученный ответ:', text);
                    
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Ошибка парсинга JSON:', e);
                        
                        // Пытаемся найти JSON в тексте
                        const jsonMatch = text.match(/\{.*\}/);
                        if (jsonMatch) {
                            return JSON.parse(jsonMatch[0]);
                        }
                        
                        throw new Error('Сервер вернул невалидный JSON: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                console.log('Успешный ответ:', data);
                showProgress(100, 'Отчет отправлен!');
                
                setTimeout(() => {
                    if (data.success) {
                        showMessage(`
                            <strong>Успешно!</strong> ${data.message}
                            <br><small class="text-muted">Отчет должен прийти на email в течение нескольких минут.</small>
                        `, 'success');
                    } else {
                        showMessage(`
                            <strong>Ошибка!</strong> ${data.message}
                            <br><small class="text-muted">Попробуйте еще раз или обратитесь к администратору.</small>
                        `, 'danger');
                    }
                    hideProgress();
                }, 1000);
            })
            .catch(error => {
                console.error('Ошибка запроса:', error);
                showProgress(100, 'Ошибка!');
                
                setTimeout(() => {
                    showMessage(`
                        <strong>Ошибка отправки!</strong> ${error.message}
                        <br><small class="text-muted">Проверьте подключение к интернету и настройки сервера.</small>
                    `, 'danger');
                    hideProgress();
                }, 1000);
            })
            .finally(() => {
                // Восстановить кнопки
                setTimeout(() => {
                    sendButtons.forEach(btn => btn.disabled = false);
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    });
    
    function showProgress(percent, text) {
        progressDiv.style.display = 'block';
        progressBar.style.width = percent + '%';
        progressText.textContent = text;
        
        if (percent === 100) {
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
        }
    }
    
    function hideProgress() {
        setTimeout(() => {
            progressDiv.style.display = 'none';
            progressBar.style.width = '0%';
            progressBar.classList.remove('bg-success');
            progressBar.classList.add('progress-bar-animated');
        }, 2000);
    }
    
    function showMessage(message, type) {
        messageDiv.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
        
        // Автоматически скрыть сообщение через 8 секунд
        setTimeout(() => {
            const alert = messageDiv.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 8000);
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Установка дат по умолчанию, если не выбраны
    const today = new Date().toISOString().split('T')[0];
    const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    
    if (!document.getElementById('report_start_date').value) {
        document.getElementById('report_start_date').value = firstDay;
    }
    if (!document.getElementById('report_end_date').value) {
        document.getElementById('report_end_date').value = today;
    }
});
</script>

<?php include '../includes/footer.php'; ?>