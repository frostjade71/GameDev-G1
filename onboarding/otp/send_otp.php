<?php
require_once dirname(__DIR__) . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function sendOTP($email, $username, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'wordweavershccci@gmail.com';
        $mail->Password = 'tteynqqiafjeimdn'; // App Password with spaces removed
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('wordweavershccci@gmail.com', 'Word Weavers');
        $mail->addAddress($email, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Word Weavers';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #1a1a2e;'>Welcome to Word Weavers!</h2>
                <p>Hello {$username},</p>
                <p>Thank you for registering with Word Weavers. To complete your registration, please use the following verification code:</p>
                <div style='background-color: #f5f5f5; padding: 15px; text-align: center; border-radius: 5px;'>
                    <h1 style='color: #1a1a2e; letter-spacing: 5px;'>{$otp}</h1>
                </div>
                <p>This code will expire in 5 minutes.</p>
                <p>If you didn't request this verification, please ignore this email.</p>
                <p>Keep Learning!,<br>Word Weavers Team</p>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed - Error: " . $e->getMessage());
        error_log("SMTP Error Info: " . $mail->ErrorInfo);
        return false;
    }
}