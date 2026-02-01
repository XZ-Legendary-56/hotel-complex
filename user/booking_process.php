<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Явно подключаем класс Booking
require_once '../includes/classes/Booking.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Включаем вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!$auth->isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Валидация данных
        $required_fields = ['room_id', 'check_in', 'check_out', 'guests'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Не заполнено обязательное поле: $field");
            }
        }

        $room_id = (int)$_POST['room_id'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $guests = (int)$_POST['guests'];
        $special_requests = $_POST['special_requests'] ?? '';

        // Проверка дат
        if (strtotime($check_in) >= strtotime($check_out)) {
            throw new Exception("Дата выезда должна быть позже даты заезда");
        }

        // Проверка доступности номера
        $availability_check = $db->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE room_id = :room_id 
            AND status IN ('pending', 'confirmed')
            AND (
                (check_in_date BETWEEN :check_in AND :check_out) OR
                (check_out_date BETWEEN :check_in AND :check_out) OR
                (:check_in BETWEEN check_in_date AND check_out_date) OR
                (:check_out BETWEEN check_in_date AND check_out_date)
            )
        ");
        
        $availability_check->execute([
            ':room_id' => $room_id,
            ':check_in' => $check_in,
            ':check_out' => $check_out
        ]);
        
        if ($availability_check->fetchColumn() > 0) {
            throw new Exception("Номер недоступен на выбранные даты");
        }

        // Создаем бронирование
        $booking = new Booking($db);
        $booking->user_id = $_SESSION['user_id'];
        $booking->room_id = $room_id;
        $booking->check_in_date = $check_in;
        $booking->check_out_date = $check_out;
        $booking->guests_count = $guests;
        $booking->special_requests = $special_requests;
        $booking->status = 'pending';

        if ($booking->create()) {
            $booking_id = $db->lastInsertId();
            
            // Перенаправляем на страницу успеха
            header('Location: booking_success.php?id=' . $booking_id);
            exit;
        } else {
            throw new Exception("Ошибка при создании бронирования");
        }

    } catch (Exception $e) {
        // Сохраняем ошибку в сессии и возвращаем на форму
        $_SESSION['booking_error'] = $e->getMessage();
        header('Location: booking_form.php?' . http_build_query($_POST));
        exit;
    }
} else {
    // Если не POST запрос, возвращаем на форму
    header('Location: booking_form.php');
    exit;
}
?>