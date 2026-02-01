<?php
/**
 * Простая система отправки email
 */

class SimpleMailer {
    
    public function sendEmail($to, $subject, $html_content) {
        try {
            // Текстовая версия письма
            $text_content = strip_tags($html_content);
            
            // Заголовки
            $headers = [
                "From: Гостиница <noreply@hotel.com>",
                "Reply-To: noreply@hotel.com",
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "X-Mailer: PHP/" . phpversion()
            ];
            
            $headers_string = implode("\r\n", $headers);
            
            // Отправка
            $result = mail($to, $subject, $html_content, $headers_string);
            
            if ($result) {
                error_log("Email sent to: {$to}");
                return true;
            } else {
                error_log("Email failed to: {$to}");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Mailer error: " . $e->getMessage());
            return false;
        }
    }
}

function sendReportEmail($to, $subject, $html_content) {
    $mailer = new SimpleMailer();
    return $mailer->sendEmail($to, $subject, $html_content);
}
?>