<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Главная</a></li>
                    <li class="nav-item"><a class="nav-link" href="../pages/rooms.php">Номера</a></li>
                    <li class="nav-item"><a class="nav-link" href="../pages/about.php">О нас</a></li>
                    <li class="nav-item"><a class="nav-link" href="../pages/contact.php">Контакты</a></li>
                    
                    <?php if (isset($_SESSION['logged_in'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="../admin/">Админ-панель</a></li>
                        <?php elseif ($_SESSION['role'] === 'staff'): ?>
                            <li class="nav-item"><a class="nav-link" href="../staff/">Персонал</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="../user/profile.php">Личный кабинет</a></li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['logged_in'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">Добро пожаловать, <?php echo $_SESSION['username']; ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/logout.php">Выйти</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/login.php">Войти</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../pages/register.php">Регистрация</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4"></main>