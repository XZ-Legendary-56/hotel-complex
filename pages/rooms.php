<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/classes/Room.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$room = new Room($db);

// Получаем все типы номеров
$room_types_query = "SELECT * FROM room_types ORDER BY price_per_night";
$room_types = $db->query($room_types_query)->fetchAll(PDO::FETCH_ASSOC);

// Получаем все номера с информацией о типах
$rooms_query = "SELECT r.*, rt.name as room_type_name, rt.price_per_night, rt.capacity, rt.amenities 
                FROM rooms r 
                JOIN room_types rt ON r.room_type_id = rt.id 
                ORDER BY r.room_number";
$rooms = $db->query($rooms_query)->fetchAll(PDO::FETCH_ASSOC);

// Параметры для быстрого бронирования
$check_in = $_GET['check_in'] ?? date('Y-m-d');
$check_out = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
$guests = $_GET['guests'] ?? 2;
?>

<?php include '../includes/header.php'; ?>

<div class="row">

</div>

<div class="row mb-5">
    <div class="col-12 text-center">
        <h1 class="display-4">Наши номера</h1>
        <p class="lead">Выберите идеальный номер для вашего отдыха</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Поиск номеров</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Заезд</label>
                        <input type="date" class="form-control" name="check_in" 
                               value="<?php echo $check_in; ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Выезд</label>
                        <input type="date" class="form-control" name="check_out" 
                               value="<?php echo $check_out; ?>" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Гости</label>
                        <select class="form-select" name="guests">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $guests == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> гост<?php echo $i == 1 ? 'ь' : 'я'; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Тип номера</label>
                        <select class="form-select" name="type">
                            <option value="">Все типы</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" 
                                    <?php echo (isset($_GET['type']) && $_GET['type'] == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Найти</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-4">Типы номеров</h2>
        <div class="row">
            <?php foreach ($room_types as $type): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="../assets/images/rooms/<?php echo $type['image_url'] ?? 'default.jpg'; ?>" 
                             class="card-img-top room-image" 
                             alt="<?php echo htmlspecialchars($type['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/300x200/007bff/ffffff?text=<?php echo urlencode($type['name']); ?>'">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($type['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($type['description']); ?></p>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    До <?php echo $type['capacity']; ?> гостей
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-ruble-sign text-primary me-2"></i>
                                    <?php echo number_format($type['price_per_night'], 2); ?> руб./ночь
                                </li>
                                <li class="list-group-item">
                                    <i class="fas fa-star text-primary me-2"></i>
                                    Удобства: <?php echo htmlspecialchars($type['amenities']); ?>
                                </li>
                            </ul>
                            <a href="#room-type-<?php echo $type['id']; ?>" class="btn btn-outline-primary w-100">
                                Посмотреть номера
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Все номера</h2>
        
        <?php if (count($rooms) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Номер</th>
                            <th>Тип</th>
                            <th>Этаж</th>
                            <th>Вместимость</th>
                            <th>Цена за ночь</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($room['room_number']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($room['room_type_name']); ?></td>
                                <td><?php echo $room['floor']; ?> этаж</td>
                                <td><?php echo $room['capacity']; ?> гостей</td>
                                <td>
                                    <span class="text-success fw-bold">
                                        <?php echo number_format($room['price_per_night'], 2); ?> руб.
                                    </span>
                                </td>
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
                                            case 'available': echo 'Свободен'; break;
                                            case 'occupied': echo 'Занят'; break;
                                            case 'maintenance': echo 'Ремонт'; break;
                                            case 'cleaning': echo 'Уборка'; break;
                                            default: echo $room['status'];
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($room['status'] === 'available'): ?>
                                        <?php if ($auth->isLoggedIn()): ?>
                                            <a href="../user/booking_form.php?check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>&guests=<?php echo $guests; ?>&room_id=<?php echo $room['id']; ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-calendar-check me-1"></i>Забронировать
                                            </a>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-sign-in-alt me-1"></i>Войти для бронирования
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Недоступен</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                <h4>Номера не найдены</h4>
                <p class="text-muted">В данный момент нет доступных номеров.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-5">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body">
                <h3 class="card-title">Дополнительная информация</h3>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                            <h5>Заселение/Выселение</h5>
                            <p>Заселение: 14:00<br>Выселение: 12:00</p>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-utensils fa-3x text-primary mb-3"></i>
                            <h5>Питание</h5>
                            <p>Завтрак включен<br>Ресторан 24/7</p>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="text-center">
                            <i class="fas fa-wifi fa-3x text-primary mb-3"></i>
                            <h5>Удобства</h5>
                            <p>Бесплатный Wi-Fi<br>Парковка</p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="contact.php" class="btn btn-primary me-2">
                        <i class="fas fa-phone me-1"></i>Связаться с нами
                    </a>
                    <a href="#search-form" class="btn btn-outline-primary">
                        <i class="fas fa-search me-1"></i>Поиск номеров
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.room-image {
    height: 200px;
    object-fit: cover;
}

.table th {
    background-color: #2c3e50;
    color: white;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.badge {
    font-size: 0.8rem;
    padding: 0.5em 0.8em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Подсветка выбранного типа номера
    const urlParams = new URLSearchParams(window.location.search);
    const selectedType = urlParams.get('type');
    
    if (selectedType) {
        const element = document.getElementById('room-type-' + selectedType);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
            element.classList.add('highlight');
        }
    }
    
    // Валидация дат
    const checkInInput = document.querySelector('input[name="check_in"]');
    const checkOutInput = document.querySelector('input[name="check_out"]');
    
    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            const minCheckOutDate = new Date(checkInDate);
            minCheckOutDate.setDate(minCheckOutDate.getDate() + 1);
            
            checkOutInput.min = minCheckOutDate.toISOString().split('T')[0];
            
            if (new Date(checkOutInput.value) <= checkInDate) {
                checkOutInput.value = minCheckOutDate.toISOString().split('T')[0];
            }
        });
    }
});

// Функция для подсветки
function highlightElement(element) {
    element.style.backgroundColor = '#fff3cd';
    element.style.transition = 'background-color 0.3s ease';
    
    setTimeout(() => {
        element.style.backgroundColor = '';
    }, 2000);
}
</script>

<?php include '../includes/footer.php'; ?>