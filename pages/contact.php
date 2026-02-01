<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Пожалуйста, заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Укажите корректный email адрес';
    } else {
        // Здесь можно добавить отправку email
        $success = 'Ваше сообщение отправлено! Мы свяжемся с вами в ближайшее время.';
        
        // Очищаем форму
        $_POST = [];
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row">
</div>

<div class="row mb-5">
    <div class="col-12 text-center">
        <h1 class="display-4">Свяжитесь с нами</h1>
        <p class="lead">Мы всегда рады помочь и ответить на ваши вопросы</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Форма обратной связи</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ваше имя *</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Тема *</label>
                        <input type="text" class="form-control" name="subject" 
                               value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Сообщение *</label>
                        <textarea class="form-control" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Отправить сообщение</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4>Контактная информация</h4>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h5><i class="fas fa-map-marker-alt text-primary me-2"></i> Адрес</h5>
                    <p>г. Москва, ул. Центральная, д. 123</p>
                </div>
                
                <div class="mb-4">
                    <h5><i class="fas fa-phone text-primary me-2"></i> Телефоны</h5>
                    <p>+7 (495) 123-45-67 (бронирование)</p>
                    <p>+7 (495) 123-45-68 (администрация)</p>
                </div>
                
                <div class="mb-4">
                    <h5><i class="fas fa-envelope text-primary me-2"></i> Email</h5>
                    <p>booking@prestige-hotel.ru (бронирование)</p>
                    <p>info@prestige-hotel.ru (общие вопросы)</p>
                </div>
                
                <div class="mb-4">
                    <h5><i class="fas fa-clock text-primary me-2"></i> Режим работы</h5>
                    <p>Круглосуточно, без выходных</p>
                    <p>Ресепшен: 24/7</p>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h4>Как добраться</h4>
            </div>
            <div class="card-body">
                <p>От метро "Центральная": 5 минут пешком</p>
                <p>От аэропорта: 30 минут на такси</p>
                <p>От вокзала: 15 минут на общественном транспорте</p>
                
                <div class="mt-3">
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-map-marked-alt me-2"></i>Посмотреть на карте
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>