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
        $mail->Password = 'zojjemxxjarjszuo'; // App Password with spaces removed
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('wordweavershccci@gmail.com', 'Word Weavers');
        $mail->addAddress($email, $username);

        // Embed Logo
        $logoPath = dirname(__DIR__, 2) . '/assets/menu/Word-Weavers.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logo_ww');
            $logoUrl = 'cid:logo_ww';
        } else {
            // Fallback if image not found
            $logoUrl = ''; 
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Word Weavers';
        
        $mail->Body = "
            <div style='background-color: #1a1a2e; padding: 40px 20px; font-family: Arial, sans-serif; color: #ffffff; text-align: center;'>
                <div style='max-width: 600px; margin: 0 auto; background: #0f0f1e; border-radius: 25px; border: 2px solid rgba(96, 239, 255, 0.2); padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);'>
                    " . ($logoUrl ? "<img src='{$logoUrl}' alt='Word Weavers' style='max-width: 200px; margin-bottom: 20px;'>" : "<h1 style='color: #60efff;'>Word Weavers</h1>") . "
                    <div style='height: 3px; background: linear-gradient(90deg, #60efff, #00ff87); margin-bottom: 30px;'></div>
                    
                    <h2 style='color: #ffffff; font-size: 22px; margin-bottom: 20px;'>Welcome, {$username}!</h2>
                    <p style='color: #b0b0c0; font-size: 16px; line-height: 1.6; margin-bottom: 30px;'>
                        Your adventure is about to begin. Use the verification code below to activate your account.
                    </p>
                    
                    <div style='background: rgba(96, 239, 255, 0.05); border: 1px solid rgba(96, 239, 255, 0.2); border-radius: 15px; padding: 25px 15px; margin-bottom: 30px;'>
                        <span style='display: block; color: #60efff; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px;'>Your Code</span>
                        <h1 style='color: #00ff87; font-size: 32px; margin: 0; letter-spacing: 6px; font-weight: bold; white-space: nowrap;'>{$otp}</h1>
                    </div>
                    
                    <p style='color: #ff6b6b; font-size: 13px; margin-bottom: 25px;'>
                        This code will expire in 5 minutes.
                    </p>
                    
                    <div style='height: 1px; background: rgba(96, 239, 255, 0.1); margin-bottom: 25px;'></div>
                    
                    <p style='color: #60efff; font-weight: bold; font-size: 14px; margin: 0 0 10px 0;'>Keep Learning!</p>
                    <p style='color: rgba(255, 255, 255, 0.5); font-size: 11px; line-height: 1.5; margin: 0;'>
                        &copy; " . date('Y') . " WordWeavers HCCCI. All rights reserved.<br>
                        This is an automated message, please do not reply to this email.
                    </p>
                </div>
            </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed - Error: " . $e->getMessage());
        error_log("SMTP Error Info: " . $mail->ErrorInfo);
        return false;
    }
}