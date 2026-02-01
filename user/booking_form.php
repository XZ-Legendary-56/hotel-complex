<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/classes/Room.php';


$error = '';
if (isset($_SESSION['booking_error'])) {
    $error = $_SESSION['booking_error'];
    unset($_SESSION['booking_error']);
}


$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$room = new Room($db);

$check_in = $_GET['check_in'] ?? date('Y-m-d');
$check_out = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
$guests = $_GET['guests'] ?? 2;

$available_rooms = $room->readAvailable($check_in, $check_out, $guests);
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <h2>Выбор номера</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Параметры поиска</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Заезд</label>
                        <input type="date" class="form-control" name="check_in" value="<?php echo $check_in; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Выезд</label>
                        <input type="date" class="form-control" name="check_out" value="<?php echo $check_out; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Гости</label>
                        <select class="form-select" name="guests">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> гост<?php echo $i == 1 ? 'ь' : 'я'; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Поиск</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($available_rooms->rowCount() > 0): ?>
            <div class="row">
                <?php while ($room = $available_rooms->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <!-- <img src="../assets/images/rooms/<?php echo $room['image_url'] ?? 'default.jpg'; ?>"  -->
                                 <!-- class="card-img-top" alt="<?php echo $room['name']; ?>" style="height: 200px; object-fit: cover;"> -->
                            <div class="card-body">
                                <h5 class="card-title">№<?php echo $room['room_number']; ?></h5>
                                <p class="card-text"><?php echo $room['description']; ?></p>
                                <ul class="list-group list-group-flush mb-3">
                                    <li class="list-group-item">Этаж: <?php echo $room['floor']; ?></li>
                                    <li class="list-group-item">Вместимость: до <?php echo $room['capacity']; ?> гостей</li>
                                    <li class="list-group-item">Цена за ночь: <?php echo number_format($room['price_per_night'], 2); ?> руб.</li>
                                    <li class="list-group-item">Удобства: <?php echo $room['amenities']; ?></li>
                                </ul>
                                <form action="booking_process.php" method="POST">
                                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                    <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                                    <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                                    <input type="hidden" name="guests" value="<?php echo $guests; ?>">
                                    <button type="submit" class="btn btn-success w-100">Забронировать</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <h4>Нет доступных номеров</h4>
                <p>К сожалению, на выбранные даты нет свободных номеров. Попробуйте изменить параметры поиска.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>