<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Создаем экземпляр класса Auth
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db); // Передаем соединение с БД

$error = '';
$success = '';

// Проверяем авторизацию
if ($auth->isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'phone' => trim($_POST['phone'])
    ];
    
    // Валидация данных
    if (empty($userData['username']) || empty($userData['email']) || empty($userData['password'])) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($userData['password'] !== $_POST['confirm_password']) {
        $error = 'Пароли не совпадают';
    } else {
        // Проверяем, существует ли пользователь
        $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $db->prepare($checkQuery);
        $stmt->bindParam(':username', $userData['username']);
        $stmt->bindParam(':email', $userData['email']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = 'Пользователь с таким именем или email уже существует';
        } else {
            // Регистрируем пользователя
            if ($auth->register($userData)) {
                $success = 'Регистрация прошла успешно! Теперь вы можете войти.';
                // Очищаем форму
                $userData = array_fill_keys(array_keys($userData), '');
            } else {
                $error = 'Ошибка при регистрации. Попробуйте еще раз.';
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="text-center">Регистрация нового пользователя</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-primary">Перейти к входу</a>
                    </div>
                <?php else: ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Имя пользователя *</label>
                                <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" 
                                       required pattern="[a-zA-Z0-9_]{3,20}" 
                                       title="Только буквы, цифры и подчеркивание (3-20 символов)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Пароль *</label>
                                <input type="password" class="form-control" name="password" 
                                       required minlength="6" 
                                       title="Не менее 6 символов">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Подтверждение пароля *</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Имя</label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Фамилия</label>
                                <input type="text" class="form-control" name="last_name" 
                                       value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Телефон</label>
                        <input type="tel" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>"
                               pattern="[\+]{0,1}[0-9]{10,15}" 
                               title="Формат: +71234567890 или 81234567890">
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                        <label class="form-check-label" for="agree_terms">
                            Я согласен с <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">условиями использования</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                    
                    <div class="text-center mt-3">
                        <p>Уже есть аккаунт? <a href="login.php">Войдите здесь</a></p>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно с условиями -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Условия использования</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6>1. Общие положения</h6>
                <p>Регистрируясь на сайте, вы соглашаетесь с правилами бронирования и политикой конфиденциальности.</p>
                
                <h6>2. Бронирование номеров</h6>
                <p>Бронирование подтверждается после получения подтверждения от администрации отеля.</p>
                
                <h6>3. Отмена бронирования</h6>
                <p>Отмена бронирования возможна не менее чем за 24 часа до заезда.</p>
                
                <h6>4. Конфиденциальность</h6>
                <p>Ваши личные данные защищены и не передаются третьим лицам.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>ы