<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes
require 'vendor/autoload.php'; // or the path to PHPMailer if not using Composer

function sendNotificationEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOUR_GMAIL@gmail.com'; // Your Gmail address
        $mail->Password   = 'YOUR_APP_PASSWORD';    // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('YOUR_GMAIL@gmail.com', 'Your Name or System');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // You can log $mail->ErrorInfo for debugging
        return false;
    }
}

// Usage example:
sendNotificationEmail(
    'visitacionredjanphils@gmail.com',
    'Test Notification',
    'This is a test notification from the voting system.'
);
