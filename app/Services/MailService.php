<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    public function sendMail($recipientEmail, $subject, $body, $recipientName = '')
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP(); // Send using SMTP
            $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server
            $mail->SMTPAuth   = true; // Enable SMTP authentication
            $mail->Username   = 'support@fmb52.com'; // Your Gmail address
            $mail->Password   = 'qskypetvdyxcvpb'; // Your Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Encryption type
            $mail->Port       = 587; // TCP port to connect to

            // Recipients
            $mail->setFrom('support@fmb52.com', 'FMB 52 Support Team');
            $mail->addAddress($recipientEmail, $recipientName);

            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();

            return 'Email has been sent successfully.';
        } catch (Exception $e) {
            return 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        }
    }
}
