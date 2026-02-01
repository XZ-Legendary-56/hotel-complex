<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class ReportMailer {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configureMailer();
    }
    
    private function configureMailer() {
        // Основные настройки
        $this->mail->CharSet = 'UTF-8';
        $this->mail->Encoding = 'base64';
        $this->mail->setLanguage('ru');
        
        // Настройки SMTP 
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.mail.ru';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'diksan2004@mail.ru';
        $this->mail->Password = '03njVOQL2Vc33LwH40PI';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = 25;
        
        // От кого
        $this->mail->setFrom('diksan2004@mail.ru', 'Гостиница - Система отчетов');
        
        // Опционально: отладка
        // $this->mail->SMTPDebug = 2; // Раскомментируйте для отладки
    }
    
    public function sendReport($toEmail, $toName, $subject, $htmlContent, $attachmentContent = null, $attachmentFilename = 'report.html') {
        try {
            // Очищаем получателей от предыдущих отправок
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Добавляем получателя
            $this->mail->addAddress($toEmail, $toName);
            
            // Тема и содержимое
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlContent;
            
            // Добавляем альтернативный текстовый вариант
            $this->mail->AltBody = strip_tags($htmlContent);
            
            // Если нужно вложение
            if ($attachmentContent) {
                $this->mail->addStringAttachment($attachmentContent, $attachmentFilename, 'base64', 'text/html');
            }
            
            // Отправка
            return $this->mail->send();
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$this->mail->ErrorInfo}");
            throw new Exception("Ошибка отправки email: {$this->mail->ErrorInfo}");
        }
    }
    
    public function getError() {
        return $this->mail->ErrorInfo;
    }
}
?>