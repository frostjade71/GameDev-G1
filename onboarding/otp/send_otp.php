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
                <div style='max-width: 420px; margin: 0 auto; background: rgba(20, 20, 20, 0.95); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.08); padding: 40px 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.4);'>
                    " . ($logoUrl ? "<img src='{$logoUrl}' alt='Word Weavers' style='max-width: 160px; margin-bottom: 24px; opacity: 0.9;'>" : "<h1 style='color: #ffffff; font-size: 20px; margin-bottom: 24px;'>Word Weavers</h1>") . "
                    
                    <h2 style='color: #ffffff; font-size: 18px; font-weight: 600; margin-bottom: 8px;'>Welcome, {$username}!</h2>
                    <p style='color: rgba(255, 255, 255, 0.5); font-size: 14px; line-height: 1.6; margin-bottom: 28px;'>
                        Use the verification code below to activate your account.
                    </p>
                    
                    <div style='background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 20px 15px; margin-bottom: 24px;'>
                        <span style='display: block; color: rgba(255, 255, 255, 0.4); font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px;'>Verification Code</span>
                        <h1 style='color: #ffffff; font-size: 32px; margin: 0; letter-spacing: 8px; font-weight: 700;'>{$otp}</h1>
                    </div>
                    
                    <p style='color: rgba(255, 255, 255, 0.4); font-size: 13px; margin-bottom: 28px;'>
                        This code will expire in 5 minutes.
                    </p>
                    
                    <div style='height: 1px; background: rgba(255, 255, 255, 0.08); margin-bottom: 20px;'></div>
                    
                    <p style='color: rgba(255, 255, 255, 0.3); font-size: 11px; line-height: 1.6; margin: 0;'>
                        &copy; " . date('Y') . " WordWeavers HCCCI. All rights reserved.<br>
                        This is an automated message, please do not reply.
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