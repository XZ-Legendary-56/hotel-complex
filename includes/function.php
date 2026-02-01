<?php
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatPrice($price) {
    return number_format($price, 2, '.', ' ') . ' ₽';
}

function getAvailableRooms($check_in, $check_out, $guests, $db) {
    $query = "SELECT r.*, rt.* 
              FROM rooms r 
              JOIN room_types rt ON r.room_type_id = rt.id 
              WHERE rt.capacity >= :guests 
              AND r.status = 'available'
              AND NOT EXISTS (
                  SELECT 1 FROM bookings b 
                  WHERE b.room_id = r.id 
                  AND b.status IN ('confirmed', 'pending')
                  AND (
                      (b.check_in_date BETWEEN :check_in AND :check_out) OR
                      (b.check_out_date BETWEEN :check_in AND :check_out) OR
                      (:check_in BETWEEN b.check_in_date AND b.check_out_date)
                  )
              )";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':check_in', $check_in);
    $stmt->bindParam(':check_out', $check_out);
    $stmt->bindParam(':guests', $guests);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function sendEmail($to, $subject, $message) {
    // Для реального использования нужно настроить PHPMailer или SwiftMailer
    $headers = "From: hotel@prestige.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function getBookingStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'confirmed' => 'success',
        'cancelled' => 'danger',
        'completed' => 'info'
    ];
    
    $texts = [
        'pending' => 'Ожидание',
        'confirmed' => 'Подтверждено',
        'cancelled' => 'Отменено',
        'completed' => 'Завершено'
    ];
    
    $badge = $badges[$status] ?? 'secondary';
    $text = $texts[$status] ?? $status;
    
    return "<span class='badge bg-$badge'>$text</span>";
}
?>