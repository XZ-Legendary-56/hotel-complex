<?php
// export_reports.php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Получение параметров
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Валидация типа отчета
$allowed_types = ['bookings', 'financial', 'guests'];
if (!in_array($type, $allowed_types)) {
    die('Неверный тип отчета');
}

// Генерация данных отчета
$data = generateReportData($db, $type, $start_date, $end_date);

// Настройки для Excel с правильной кодировкой
$filename = "report_{$type}_{$start_date}_to_{$end_date}.xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");
header("Pragma: no-cache");

// Добавляем BOM для правильного отображения кириллицы в Excel
echo "\xEF\xBB\xBF";

// Создаем Excel файл
echo generateExcelContent($type, $data, $start_date, $end_date);
exit;

/**
 * Генерация данных отчета
 */
function generateReportData($db, $type, $start_date, $end_date) {
    switch($type) {
        case 'bookings':
            $stmt = $db->prepare("
                SELECT 
                    b.id,
                    b.total_price,
                    b.status,
                    b.created_at,
                    r.room_number, 
                    rt.name as room_type, 
                    u.email as guest_email,
                    u.first_name,
                    u.last_name
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                JOIN room_types rt ON r.room_type_id = rt.id
                JOIN users u ON b.user_id = u.id
                WHERE b.created_at BETWEEN :start_date AND :end_date
                ORDER BY b.created_at DESC
            ");
            break;
            
        case 'financial':
            $stmt = $db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as bookings_count,
                    SUM(total_price) as daily_revenue,
                    AVG(total_price) as avg_booking
                FROM bookings 
                WHERE created_at BETWEEN :start_date AND :end_date
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            break;
            
        case 'guests':
            $stmt = $db->prepare("
                SELECT 
                    u.email,
                    u.first_name,
                    u.last_name,
                    COUNT(b.id) as total_bookings,
                    SUM(b.total_price) as total_spent,
                    MAX(b.created_at) as last_booking
                FROM users u
                JOIN bookings b ON u.id = b.user_id
                WHERE b.created_at BETWEEN :start_date AND :end_date
                GROUP BY u.id, u.email, u.first_name, u.last_name
                ORDER BY total_spent DESC
            ");
            break;
    }
    
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Генерация Excel контента с русскими названиями
 */
function generateExcelContent($type, $data, $start_date, $end_date) {
    $output = "";
    
    // Заголовок
    $titles = [
        'bookings' => 'Отчет по бронированиям',
        'financial' => 'Финансовый отчет',
        'guests' => 'Отчет по гостям'
    ];
    
    $title = $titles[$type] ?? 'Отчет';
    $output .= "{$title}\n";
    $output .= "Период: {$start_date} - {$end_date}\n";
    $output .= "Сгенерировано: " . date('d.m.Y H:i:s') . "\n\n";
    
    // Заголовки таблицы на русском
    switch($type) {
        case 'bookings':
            $output .= "ID бронирования\tНомер комнаты\tТип номера\tГость\tEmail\tДата создания\tСтоимость\tСтатус\n";
            foreach ($data as $row) {
                $status = [
                    'confirmed' => 'Подтверждено',
                    'pending' => 'Ожидание', 
                    'cancelled' => 'Отменено',
                    'completed' => 'Завершено'
                ][$row['status']] ?? $row['status'];
                
                $guest_name = trim($row['first_name'] . ' ' . $row['last_name']);
                if (empty($guest_name)) {
                    $guest_name = 'Не указано';
                }
                
                $output .= "{$row['id']}\t";
                $output .= "{$row['room_number']}\t";
                $output .= "{$row['room_type']}\t";
                $output .= "{$guest_name}\t";
                $output .= "{$row['guest_email']}\t";
                $output .= date('d.m.Y H:i', strtotime($row['created_at'])) . "\t";
                $output .= number_format($row['total_price'], 2) . " руб.\t";
                $output .= "{$status}\n";
            }
            break;
            
        case 'financial':
            $output .= "Дата\tКоличество бронирований\tДоход за день\tСредний чек\n";
            $total_revenue = 0;
            $total_bookings = 0;
            
            foreach ($data as $row) {
                $total_revenue += $row['daily_revenue'];
                $total_bookings += $row['bookings_count'];
                
                $output .= date('d.m.Y', strtotime($row['date'])) . "\t";
                $output .= "{$row['bookings_count']}\t";
                $output .= number_format($row['daily_revenue'], 2) . " руб.\t";
                $output .= number_format($row['avg_booking'], 2) . " руб.\n";
            }
            
            $output .= "\nИтоги:\n";
            $output .= "Общий доход: " . number_format($total_revenue, 2) . " руб.\n";
            $output .= "Всего бронирований: {$total_bookings}\n";
            $output .= "Средний чек за период: " . number_format($total_revenue / max($total_bookings, 1), 2) . " руб.\n";
            break;
            
        case 'guests':
            $output .= "Имя гостя\tФамилия гостя\tEmail\tКоличество бронирований\tВсего потрачено\tПоследнее бронирование\n";
            $total_revenue = 0;
            $total_guests = count($data);
            
            foreach ($data as $row) {
                $total_revenue += $row['total_spent'];
                
                $first_name = $row['first_name'] ?: 'Не указано';
                $last_name = $row['last_name'] ?: 'Не указано';
                
                $output .= "{$first_name}\t";
                $output .= "{$last_name}\t";
                $output .= "{$row['email']}\t";
                $output .= "{$row['total_bookings']}\t";
                $output .= number_format($row['total_spent'], 2) . " руб.\t";
                $output .= date('d.m.Y H:i', strtotime($row['last_booking'])) . "\n";
            }
            
            $output .= "\nИтоги:\n";
            $output .= "Всего гостей: {$total_guests}\n";
            $output .= "Общий доход: " . number_format($total_revenue, 2) . " руб.\n";
            $output .= "Средние затраты на гостя: " . number_format($total_revenue / max($total_guests, 1), 2) . " руб.\n";
            break;
    }
    
    return $output;
}
?>