<?php
// send_report_email.php
header('Content-Type: application/json; charset=utf-8');

// Включить отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Подключаем необходимые файлы
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Подключаем PHPMailer
// require_once '../vendor/autoload.php'; // Если используете Composer
// Или если скачали вручную:
require_once '../phpmailer/src/Exception.php';
require_once '../phpmailer/src/PHPMailer.php';
require_once '../phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);

    // Проверка авторизации
    if (!$auth->isLoggedIn() || $auth->getUserRole() !== 'admin') {
        throw new Exception('Доступ запрещен');
    }

    // Проверка метода запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Неверный метод запроса');
    }

    // Получение данных
    $type = $_POST['type'] ?? '';
    $email = $_POST['email'] ?? '';
    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-t');

    // Валидация
    if (empty($type)) {
        throw new Exception('Не указан тип отчета');
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Неверный email адрес');
    }

    // Генерация данных отчета
    $report_data = generateReportData($db, $type, $start_date, $end_date);
    $html_content = generateHtmlReport($type, $report_data, $start_date, $end_date);
    
    // Отправка email через SMTP mail.ru
    if (sendEmailSMTP($email, $report_data['subject'], $html_content)) {
        echo json_encode([
            'success' => true,
            'message' => "Отчет '{$report_data['title']}' успешно отправлен на {$email}"
        ]);
    } else {
        throw new Exception('Не удалось отправить email через SMTP.');
    }

} catch (Exception $e) {
    error_log("Report sending error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

/**
 * Отправка email через SMTP mail.ru
 */
function sendEmailSMTP($to, $subject, $html_content) {
    $mail = new PHPMailer(true);
    
    try {
        // Настройки сервера
        $mail->isSMTP();
        $mail->Host = 'smtp.mail.ru';
        $mail->SMTPAuth = true;
        $mail->Username = 'diksan2004@mail.ru';
        $mail->Password = '03njVOQL2Vc33LwH40PI';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Кодировка
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // От кого
        $mail->setFrom('diksan2004@mail.ru', 'Гостиница - Система отчетов');
        $mail->addReplyTo('diksan2004@mail.ru', 'Гостиница - Система отчетов');
        
        // Кому
        $mail->addAddress($to);
        
        // Контент
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_content;
        $mail->AltBody = strip_tags($html_content);
        
        // Отправка
        $result = $mail->send();
        
        if ($result) {
            error_log("SMTP Email успешно отправлен на: {$to}");
            return true;
        } else {
            error_log("SMTP Email ошибка: {$mail->ErrorInfo}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("SMTP Exception: " . $e->getMessage());
        error_log("SMTP Error Info: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Генерация данных отчета
 */
function generateReportData($db, $type, $start_date, $end_date) {
    switch($type) {
        case 'bookings':
            $stmt = $db->prepare("
                SELECT 
                    b.id, b.total_price, b.status, b.created_at,
                    r.room_number, rt.name as room_type, u.email as guest_email
                FROM bookings b
                JOIN rooms r ON b.room_id = r.id
                JOIN room_types rt ON r.room_type_id = rt.id
                JOIN users u ON b.user_id = u.id
                WHERE b.created_at BETWEEN :start_date AND :end_date
                ORDER BY b.created_at DESC
                LIMIT 50
            ");
            $title = 'Отчет по бронированиям';
            $subject = "Отчет по бронированиям за {$start_date} - {$end_date}";
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
            $title = 'Финансовый отчет';
            $subject = "Финансовый отчет за {$start_date} - {$end_date}";
            break;
            
        case 'guests':
            $stmt = $db->prepare("
                SELECT 
                    u.email,
                    COUNT(b.id) as total_bookings,
                    SUM(b.total_price) as total_spent,
                    MAX(b.created_at) as last_booking
                FROM users u
                JOIN bookings b ON u.id = b.user_id
                WHERE b.created_at BETWEEN :start_date AND :end_date
                GROUP BY u.id, u.email
                ORDER BY total_spent DESC
                LIMIT 50
            ");
            $title = 'Отчет по гостям';
            $subject = "Отчет по гостям за {$start_date} - {$end_date}";
            break;
            
        default:
            throw new Exception('Неизвестный тип отчета');
    }
    
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'data' => $data,
        'title' => $title,
        'subject' => $subject
    ];
}

/**
 * Генерация HTML отчета
 */
function generateHtmlReport($type, $report_data, $start_date, $end_date) {
    $title = $report_data['title'];
    $data = $report_data['data'];
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>{$title}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; color: #333; }
            .header { text-align: center; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
            th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            th { background-color: #007bff; color: white; }
            .summary { background-color: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px; }
            .total { font-weight: bold; color: #28a745; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #6c757d; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Гостиница - Система отчетов</h1>
            <h2>{$title}</h2>
            <p><strong>Период:</strong> {$start_date} - {$end_date}</p>
            <p><strong>Сгенерировано:</strong> " . date('d.m.Y H:i:s') . "</p>
        </div>
    ";
    
    if (empty($data)) {
        $html .= "<p style='text-align: center; color: #6c757d; padding: 40px;'>Нет данных за выбранный период</p>";
    } else {
        $html .= generateTable($type, $data);
    }
    
    $html .= "
        <div class='footer'>
            <p>Это автоматически сгенерированный отчет.</p>
            <p>Гостиница &copy; " . date('Y') . "</p>
        </div>
    </body>
    </html>";
    
    return $html;
}

/**
 * Генерация таблицы в зависимости от типа
 */
function generateTable($type, $data) {
    switch($type) {
        case 'bookings':
            return generateBookingsTable($data);
        case 'financial':
            return generateFinancialTable($data);
        case 'guests':
            return generateGuestsTable($data);
        default:
            return '';
    }
}

function generateBookingsTable($data) {
    $html = "<table>
        <tr><th>ID</th><th>Номер</th><th>Тип</th><th>Гость</th><th>Дата</th><th>Стоимость</th><th>Статус</th></tr>";
    
    $total = 0;
    foreach ($data as $row) {
        $total += $row['total_price'];
        $status = [
            'confirmed' => 'Подтверждено',
            'pending' => 'Ожидание', 
            'cancelled' => 'Отменено'
        ][$row['status']] ?? $row['status'];
        
        $html .= "<tr>
            <td>{$row['id']}</td>
            <td>{$row['room_number']}</td>
            <td>{$row['room_type']}</td>
            <td>{$row['guest_email']}</td>
            <td>" . date('d.m.Y', strtotime($row['created_at'])) . "</td>
            <td>" . number_format($row['total_price'], 2) . " руб.</td>
            <td>{$status}</td>
        </tr>";
    }
    
    $html .= "</table>";
    $html .= "<div class='summary'>
        <p>Всего бронирований: <span class='total'>" . count($data) . "</span></p>
        <p>Общий доход: <span class='total'>" . number_format($total, 2) . " руб.</span></p>
    </div>";
    
    return $html;
}

function generateFinancialTable($data) {
    $html = "<table>
        <tr><th>Дата</th><th>Бронирований</th><th>Доход</th><th>Средний чек</th></tr>";
    
    $total_revenue = 0;
    $total_bookings = 0;
    foreach ($data as $row) {
        $total_revenue += $row['daily_revenue'];
        $total_bookings += $row['bookings_count'];
        
        $html .= "<tr>
            <td>" . date('d.m.Y', strtotime($row['date'])) . "</td>
            <td>{$row['bookings_count']}</td>
            <td>" . number_format($row['daily_revenue'], 2) . " руб.</td>
            <td>" . number_format($row['avg_booking'], 2) . " руб.</td>
        </tr>";
    }
    
    $html .= "</table>";
    $html .= "<div class='summary'>
        <p>Общий доход: <span class='total'>" . number_format($total_revenue, 2) . " руб.</span></p>
        <p>Всего бронирований: <span class='total'>{$total_bookings}</span></p>
    </div>";
    
    return $html;
}

function generateGuestsTable($data) {
    $html = "<table>
        <tr><th>Гость</th><th>Бронирований</th><th>Потрачено</th><th>Последнее</th></tr>";
    
    $total_revenue = 0;
    foreach ($data as $row) {
        $total_revenue += $row['total_spent'];
        
        $html .= "<tr>
            <td>{$row['email']}</td>
            <td>{$row['total_bookings']}</td>
            <td>" . number_format($row['total_spent'], 2) . " руб.</td>
            <td>" . date('d.m.Y', strtotime($row['last_booking'])) . "</td>
        </tr>";
    }
    
    $html .= "</table>";
    $html .= "<div class='summary'>
        <p>Всего гостей: <span class='total'>" . count($data) . "</span></p>
        <p>Общий доход: <span class='total'>" . number_format($total_revenue, 2) . " руб.</span></p>
    </div>";
    
    return $html;
}
?>