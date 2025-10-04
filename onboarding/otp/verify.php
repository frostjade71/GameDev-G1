<?php
require_once '../config.php';
// Remove duplicate session_start() since it's already called in config.php

if (!isset($_SESSION['temp_user_data'])) {
    header('Location: ../register.php');
    exit();
}

$error_message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_otp = $_POST['otp'] ?? '';
    $stored_otp = $_SESSION['otp'] ?? '';
    $otp_expiry = $_SESSION['otp_expiry'] ?? 0;
    
    if (empty($entered_otp)) {
        $error_message = 'Please enter the verification code.';
    } elseif (time() > $otp_expiry) {
        $error_message = 'Verification code has expired. Please request a new one.';
    } elseif ($entered_otp !== $stored_otp) {
        $error_message = 'Invalid verification code. Please try again.';
    } else {
        // OTP is valid, proceed with user registration
        try {
            $user_data = $_SESSION['temp_user_data'];
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, grade_level, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $user_data['username'],
                $user_data['email'],
                $user_data['grade_level'],
                $user_data['password']
            ]);
            
            // Clear temporary session data
            unset($_SESSION['temp_user_data']);
            unset($_SESSION['otp']);
            unset($_SESSION['otp_expiry']);
            
            // Redirect to login page with success message
            header('Location: ../login.php?success=1');
            exit();
            
        } catch (PDOException $e) {
            $error_message = 'Registration failed. Please try again.';
            error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Word Weavers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Quicksand', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #1a1a2e;
            background-image: url('../../assets/menu/menubg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .verification-container {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.85), rgba(20, 20, 20, 0.95));
            backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            border: 2px solid rgba(96, 239, 255, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 20px rgba(96, 239, 255, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .verification-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(96, 239, 255, 0.8), rgba(0, 255, 135, 0.8));
            animation: gradientShift 3s infinite linear;
            background-size: 200% 100%;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .verification-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .logo {
            max-width: 180px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 2px 15px rgba(0, 255, 135, 0.4));
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.02);
        }

        .verification-title {
            color: rgba(96, 239, 255, 1);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
            text-shadow: 0 0 10px rgba(96, 239, 255, 0.3);
        }

        .verification-message {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        .verification-message strong {
            color: #00ff87;
            font-weight: 600;
        }

        .verification-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 1.5rem;
            border: 1px solid rgba(96, 239, 255, 0.2);
        }

        .otp-input-container {
            display: flex;
            gap: 0.8rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .otp-digit {
            width: 45px;
            height: 55px;
            border: 2px solid rgba(96, 239, 255, 0.3);
            background: rgba(0, 0, 0, 0.5);
            border-radius: 12px;
            font-size: 1.5rem;
            color: white;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .otp-digit:focus {
            outline: none;
            border-color: rgba(0, 255, 135, 0.5);
            box-shadow: 0 0 15px rgba(0, 255, 135, 0.2);
            transform: scale(1.05);
            background: rgba(0, 0, 0, 0.7);
        }

        .verify-button {
            background: linear-gradient(45deg, rgba(96, 239, 255, 0.8), rgba(0, 255, 135, 0.8));
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            color: white;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .verify-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 255, 135, 0.3);
        }

        .verify-button:hover::before {
            left: 100%;
        }

        .verification-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .time-remaining {
            color: rgba(96, 239, 255, 0.8);
            font-size: 0.9rem;
        }

        .resend-link {
            color: rgba(96, 239, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            padding: 0.8rem 1.5rem;
            border-radius: 20px;
            border: 1px solid rgba(96, 239, 255, 0.2);
            background: rgba(96, 239, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 auto;
        }

        .resend-link:hover {
            color: rgba(0, 255, 135, 1);
            border-color: rgba(0, 255, 135, 0.3);
            background: rgba(0, 255, 135, 0.1);
            transform: translateY(-1px);
        }
        
        .resend-link:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .resend-link i {
            font-size: 0.9rem;
        }

        .resend-message {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-message {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid rgba(255, 107, 107, 0.3);
            color: #ff6b6b;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 480px) {
            .verification-container {
                padding: 1.5rem;
            }

            .verification-box {
                padding: 1.5rem 1rem;
            }

            .otp-digit {
                width: 40px;
                height: 50px;
                font-size: 1.2rem;
            }

            .otp-input-container {
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-header">
            <img src="../../assets/menu/Word-Weavers.png" alt="Word Weavers" class="logo">
            <div class="header-content">
                <h1 class="verification-title">Verify Your Email</h1>
                <p class="verification-message">
                    <?php if (isset($_SESSION['temp_user_data']['email'])): ?>
                        We've sent a verification code to:<br>
                        <strong><?php echo htmlspecialchars($_SESSION['temp_user_data']['email']); ?></strong>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="verification-box">
            <form method="POST" action="" class="verification-form">
                <div class="otp-input-container">
                    <input type="text" maxlength="1" class="otp-digit" data-index="1">
                    <input type="text" maxlength="1" class="otp-digit" data-index="2">
                    <input type="text" maxlength="1" class="otp-digit" data-index="3">
                    <input type="text" maxlength="1" class="otp-digit" data-index="4">
                    <input type="text" maxlength="1" class="otp-digit" data-index="5">
                    <input type="text" maxlength="1" class="otp-digit" data-index="6">
                    <input type="hidden" name="otp" id="otp-value">
                </div>

                <button type="submit" class="verify-button">
                    <i class="fas fa-check-circle"></i>
                    Verify Code
                </button>
            </form>

            <div class="verification-footer">
                <p class="time-remaining" id="timer">Code expires in: 05:00</p>
                <button type="button" class="resend-link" id="resend-button" style="display: none; border: none; cursor: pointer;">
                    <i class="fas fa-redo"></i>
                    Resend Code
                </button>
                <p class="resend-message" id="resend-message" style="display: none;"></p>
            </div>
        </div>
    </div>

    <script>
        // Handle OTP input
        const otpInputs = document.querySelectorAll('.otp-digit');
        const otpValue = document.getElementById('otp-value');
        const form = document.querySelector('.verification-form');
        const timer = document.getElementById('timer');
        const resendButton = document.getElementById('resend-button');
        const resendMessage = document.getElementById('resend-message');

        // Focus first input on load
        window.onload = function() {
            otpInputs[0].focus();
            startTimer();
        };

        // Handle resend button click
        resendButton.addEventListener('click', async function() {
            resendButton.disabled = true;
            resendButton.style.opacity = '0.7';
            
            try {
                const response = await fetch('resend_otp.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                
                if (data.success) {
                    // Reset timer
                    resendMessage.textContent = data.message;
                    resendMessage.style.color = '#00ff87';
                    resendMessage.style.display = 'block';
                    startTimer();
                    resendButton.style.display = 'none';
                    
                    // Clear input fields
                    otpInputs.forEach(input => input.value = '');
                    otpInputs[0].focus();
                } else {
                    resendMessage.textContent = data.message;
                    resendMessage.style.color = '#ff6b6b';
                    resendMessage.style.display = 'block';
                }
            } catch (error) {
                resendMessage.textContent = 'Failed to resend code. Please try again.';
                resendMessage.style.color = '#ff6b6b';
                resendMessage.style.display = 'block';
            }
            
            resendButton.disabled = false;
            resendButton.style.opacity = '1';
            
            // Hide message after 5 seconds
            setTimeout(() => {
                resendMessage.style.display = 'none';
            }, 5000);
        });

        // Handle input in OTP fields
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                // Only allow numbers
                const value = e.target.value.replace(/[^0-9]/g, '');
                input.value = value;

                // Move to next input if value is entered
                if (value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }

                // Update hidden input with complete OTP
                updateOTPValue();
            });

            // Handle backspace
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            // Handle paste
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const numbers = paste.match(/\d/g);
                
                if (numbers) {
                    numbers.forEach((num, idx) => {
                        if (idx < otpInputs.length) {
                            otpInputs[idx].value = num;
                        }
                    });
                    updateOTPValue();
                    otpInputs[Math.min(numbers.length, otpInputs.length - 1)].focus();
                }
            });
        });

        // Update hidden input with complete OTP
        function updateOTPValue() {
            otpValue.value = Array.from(otpInputs)
                .map(input => input.value)
                .join('');
        }

        // Timer functionality
        function startTimer() {
            // Clear any existing interval
            if (window.timerInterval) {
                clearInterval(window.timerInterval);
            }
            
            let timeLeft = 300; // 5 minutes in seconds
            window.timerInterval = setInterval(() => {
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timer.textContent = `Code expires in: ${minutes}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeft <= 0) {
                    clearInterval(window.timerInterval);
                    timer.textContent = 'Code expired';
                    timer.style.color = '#ff6b6b';
                    resendButton.style.display = 'inline-block';
                }
            }, 1000);
            
            // Hide resend button when timer starts
            resendButton.style.display = 'none';
            timer.style.color = 'rgba(96, 239, 255, 0.8)';
        }

        // Form submission
        form.addEventListener('submit', (e) => {
            if (otpValue.value.length !== 6) {
                e.preventDefault();
                alert('Please enter all 6 digits of the verification code.');
            }
        });
    </script>
</body>
</html>