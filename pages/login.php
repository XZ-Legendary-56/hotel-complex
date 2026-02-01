<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Создаем экземпляр класса Auth
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($auth->login($username, $password)) {
        header('Location: ../index.php');
        exit;
    } else {
        $error = 'Неверное имя пользователя или пароль';
    }
}

if ($auth->isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="text-center">Вход в систему</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Имя пользователя</label>
                        <input type="text" class="form-control" name="username" required 
                               value="<?php echo $_COOKIE['user_auth'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember" checked>
                        <label class="form-check-label" for="remember">Запомнить меня</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Войти</button>
                </form>
                
                <div class="text-center mt-3">
                    <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>