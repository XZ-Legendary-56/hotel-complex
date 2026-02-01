<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
?>

<?php include '../includes/header.php'; ?>

<div class="row">
</div>

<div class="row mb-5">
    <div class="col-12 text-center">
        <h1 class="display-4">О нашем гостиничном комплексе</h1>
        <p class="lead">Комфорт и качество обслуживания — наш приоритет</p>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-6">
        <img src="../assets/images/hotel-exterior.jpg" class="img-fluid rounded" alt="Внешний вид отеля" 
             onerror="this.src='https://via.placeholder.com/600x400/007bff/ffffff?text=Отель+Престиж'">
    </div>
    <div class="col-md-6">
        <h2>Добро пожаловать в "Престиж"</h2>
        <p>Наш гостиничный комплекс предлагает непревзойденный уровень комфорта и обслуживания. Мы гордимся тем, что создали атмосферу, которая сочетает в себе домашний уют и профессиональный сервис.</p>
        
        <div class="mt-4">
            <h4>Наши преимущества:</h4>
            <ul class="list-group list-group-flush">
                <li class="list-group-item">✅ Современные номера с дизайнерским ремонтом</li>
                <li class="list-group-item">✅ Квалифицированный и дружелюбный персонал</li>
                <li class="list-group-item">✅ Удобное расположение в центре города</li>
                <li class="list-group-item">✅ Круглосуточная служба поддержки</li>
                <li class="list-group-item">✅ Бесплатный Wi-Fi на всей территории</li>
            </ul>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-center mb-4">Наши услуги</h2>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-concierge-bell fa-3x text-primary mb-3"></i>
                        <h5>Ресторан и питание</h5>
                        <p>Вкусная кухня, завтраки "шведский стол", room service</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-swimming-pool fa-3x text-primary mb-3"></i>
                        <h5>Бассейн и SPA</h5>
                        <p>Крытый бассейн, сауна, массаж и спа-процедуры</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-business-time fa-3x text-primary mb-3"></i>
                        <h5>Бизнес-услуги</h5>
                        <p>Конференц-залы, бизнес-центр, оборудование для презентаций</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-center mb-4">История отеля</h2>
        
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-content">
                    <h5>2010</h5>
                    <p>Основание гостиничного комплекса "Престиж"</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h5>2012</h5>
                    <p>Первая крупная реконструкция и расширение</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h5>2015</h5>
                    <p>Получение 4-звездочной категории</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h5>2020</h5>
                    <p>Модернизация всех номеров и внедрение системы "умный отель"</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-content">
                    <h5>2024</h5>
                    <p>Запуск онлайн-системы бронирования</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center">
                <h3>Наши контакты</h3>
                <p>Мы всегда рады помочь и ответить на ваши вопросы</p>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                        <h5>Адрес</h5>
                        <p>г. Москва, ул. Центральная, д. 123</p>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                        <h5>Телефон</h5>
                        <p>+7 (495) 123-45-67</p>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                        <h5>Email</h5>
                        <p>info@prestige-hotel.ru</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="contact.php" class="btn btn-primary btn-lg">Связаться с нами</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    width: 2px;
    height: 100%;
    background: #007bff;
    transform: translateX(-50%);
}

.timeline-item {
    position: relative;
    margin-bottom: 40px;
    width: 50%;
    padding: 0 40px;
    box-sizing: border-box;
}

.timeline-item:nth-child(odd) {
    left: 0;
    text-align: right;
}

.timeline-item:nth-child(even) {
    left: 50%;
    text-align: left;
}

.timeline-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: relative;
}

.timeline-content::before {
    content: '';
    position: absolute;
    top: 20px;
    width: 20px;
    height: 20px;
    background: #007bff;
    border-radius: 50%;
}

.timeline-item:nth-child(odd) .timeline-content::before {
    right: -50px;
}

.timeline-item:nth-child(even) .timeline-content::before {
    left: -50px;
}

@media (max-width: 768px) {
    .timeline::before {
        left: 20px;
    }
    
    .timeline-item {
        width: 100%;
        padding-left: 60px;
        padding-right: 0;
        text-align: left;
    }
    
    .timeline-item:nth-child(even) {
        left: 0;
    }
    
    .timeline-content::before {
        left: -30px !important;
        right: auto !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>