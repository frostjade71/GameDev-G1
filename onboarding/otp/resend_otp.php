<?php
require_once '../config.php';
require_once 'send_otp.php';
session_start();

if (!isset($_SESSION['temp_user_data'])) {
    header('Location: ../register.php');
    exit();
}

$email = $_SESSION['temp_user_data']['email'];
$username = $_SESSION['temp_user_data']['username'];

// Generate new OTP
$new_otp = generateOTP();
$_SESSION['otp'] = $new_otp;
$_SESSION['otp_expiry'] = time() + (10 * 60); // 10 minutes expiry

if (sendOTP($email, $username, $new_otp)) {
    header('Location: verify.php?resent=1');
} else {
    header('Location: verify.php?error=failed_to_send');
}
exit();