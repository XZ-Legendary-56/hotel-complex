<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: booking_form.php');
    exit;
}

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Получаем информацию о бронировании
$query = "
    SELECT b.*, r.room_number, rt.name as room_type_name 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    JOIN room_types rt ON r.room_type_id = rt.id 
    WHERE b.id = :booking_id AND b.user_id = :user_id
";

$stmt = $db->prepare($query);
$stmt->execute([':booking_id' => $booking_id, ':user_id' => $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: booking_form.php');
    exit;
}

// Рассчитываем количество ночей
$nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / (60 * 60 * 24);
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white text-center">
                <h4><i class="fas fa-check-circle me-2"></i>Бронирование успешно создано!</h4>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-calendar-check fa-5x text-success mb-3"></i>
                    <h3>Спасибо за бронирование!</h3>
                    <p class="lead">Ваш номер успешно забронирован</p>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Детали бронирования</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Номер бронирования:</strong> #<?php echo $booking['id']; ?></p>
                                <p><strong>Номер комнаты:</strong> <?php echo htmlspecialchars($booking['room_number']); ?></p>
                                <p><strong>Тип номера:</strong> <?php echo htmlspecialchars($booking['room_type_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Заезд:</strong> <?php echo date('d.m.Y', strtotime($booking['check_in_date'])); ?></p>
                                <p><strong>Выезд:</strong> <?php echo date('d.m.Y', strtotime($booking['check_out_date'])); ?></p>
                                <p><strong>Ночей:</strong> <?php echo $nights; ?></p>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <p><strong>Гости:</strong> <?php echo $booking['guests_count']; ?> человек</p>
                                <p><strong>Общая стоимость:</strong> 
                                    <span class="text-success fw-bold">
                                        <?php echo number_format($booking['total_price'], 2); ?> руб.
                                    </span>
                                </p>
                                <p><strong>Статус:</strong> 
                                    <span class="badge bg-warning">На рассмотрении</span>
                                </p>
                                
                                <?php if (!empty($booking['special_requests'])): ?>
                                <p><strong>Особые пожелания:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Что дальше?</h6>
                    <ul class="mb-0">
                        <li>Администратор свяжется с вами для подтверждения бронирования</li>
                        <li>Вы получите email с деталями бронирования</li>
                        <li>Вы можете отслеживать статус бронирования в личном кабинете</li>
                    </ul>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="bookings.php" class="btn btn-primary me-md-2">
                        <i class="fas fa-list me-1"></i>Мои бронирования
                    </a>
                    <a href="../pages/rooms.php" class="btn btn-outline-primary">
                        <i class="fas fa-bed me-1"></i>Забронировать еще
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>