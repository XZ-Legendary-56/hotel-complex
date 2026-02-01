<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

$auth = new Auth();
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-8">
        <div class="jumbotron bg-light p-5 rounded">
            <h1 class="display-4">Добро пожаловать в гостиничный комплекс "Престиж"</h1>
            <p class="lead">Комфортабельные номера, превосходный сервис и незабываемый отдых</p>
            <hr class="my-4">
            <p>Забронируйте номер онлайн и получите специальное предложение</p>
            <a class="btn btn-primary btn-lg" href="pages/rooms.php" role="button">Посмотреть номера</a>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Быстрое бронирование</h5>
            </div>
            <div class="card-body">
                <form action="user/booking_form.php" method="GET">
                    <div class="mb-3">
                        <label class="form-label">Заезд</label>
                        <input type="date" class="form-control" name="check_in" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Выезд</label>
                        <input type="date" class="form-control" name="check_out" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Гости</label>
                        <select class="form-select" name="guests">
                            <option value="1">1 гость</option>
                            <option value="2" selected>2 гостя</option>
                            <option value="3">3 гостя</option>
                            <option value="4">4 гостя</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Найти номер</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-concierge-bell fa-3x text-primary mb-3"></i>
                <h5>Высокий сервис</h5>
                <p>Квалифицированный персонал и качественное обслуживание</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-wifi fa-3x text-primary mb-3"></i>
                <h5>Современные удобства</h5>
                <p>Wi-Fi, кондиционеры, спутниковое TV в каждом номере</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-map-marker-alt fa-3x text-primary mb-3"></i>
                <h5>Удобное расположение</h5>
                <p>Центр города, рядом с основными достопримечательностями</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>