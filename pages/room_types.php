<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: rooms.php');
    exit;
}

$type_id = $_GET['id'];
$query = "SELECT * FROM room_types WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $type_id);
$stmt->execute();
$room_type = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room_type) {
    header('Location: rooms.php');
    exit;
}

// Получаем номера этого типа
$rooms_query = "SELECT * FROM rooms WHERE room_type_id = :type_id ORDER BY room_number";
$rooms_stmt = $db->prepare($rooms_query);
$rooms_stmt->bindParam(':type_id', $type_id);
$rooms_stmt->execute();
$rooms = $rooms_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/header.php'; ?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Главная</a></li>
                <li class="breadcrumb-item"><a href="rooms.php">Номера</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($room_type['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <img src="../assets/images/rooms/<?php echo $room_type['image_url'] ?? 'default.jpg'; ?>" 
             class="img-fluid rounded" 
             alt="<?php echo htmlspecialchars($room_type['name']); ?>"
             onerror="this.src='https://via.placeholder.com/600x400/007bff/ffffff?text=<?php echo urlencode($room_type['name']); ?>'">
    </div>
    <div class="col-md-6">
        <h1><?php echo htmlspecialchars($room_type['name']); ?></h1>
        <p class="lead"><?php echo htmlspecialchars($room_type['description']); ?></p>
        
        <div class="mb-4">
            <h4>Характеристики:</h4>
            <ul class="list-group">
                <li class="list-group-item">
                    <i class="fas fa-users text-primary me-2"></i>
                    <strong>Вместимость:</strong> до <?php echo $room_type['capacity']; ?> гостей
                </li>
                <li class="list-group-item">
                    <i class="fas fa-ruble-sign text-primary me-2"></i>
                    <strong>Цена за ночь:</strong> <?php echo number_format($room_type['price_per_night'], 2); ?> руб.
                </li>
                <li class="list-group-item">
                    <i class="fas fa-star text-primary me-2"></i>
                    <strong>Удобства:</strong> <?php echo htmlspecialchars($room_type['amenities']); ?>
                </li>
            </ul>
        </div>
        
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Назад к номерам
        </a>
        <a href="../user/booking_form.php" class="btn btn-primary">
            <i class="fas fa-calendar-check me-1"></i>Забронировать
        </a>
    </div>
</div>

<?php if (count($rooms) > 0): ?>
<div class="row">
    <div class="col-12">
        <h3>Номера этого типа</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Номер</th>
                        <th>Этаж</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                        <td><?php echo $room['floor']; ?> этаж</td>
                        <td>
                            <span class="badge bg-<?php 
                                switch($room['status']) {
                                    case 'available': echo 'success'; break;
                                    case 'occupied': echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?>">
                                <?php echo $room['status'] === 'available' ? 'Свободен' : 'Занят'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>