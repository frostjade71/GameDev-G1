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
            $pdo->beginTransaction();
            
            $user_data = $_SESSION['temp_user_data'];
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, grade_level, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $user_data['username'],
                $user_data['email'],
                $user_data['grade_level'],
                $user_data['password']
            ]);
            
            $new_user_id = $pdo->lastInsertId();
            
            // Seed default character ownership (Ethan and Emma)
            $default_characters = [
                ['type' => 'boy', 'name' => 'Ethan', 'path' => '../assets/characters/boy_char/character_ethan.png'],
                ['type' => 'girl', 'name' => 'Emma', 'path' => '../assets/characters/girl_char/character_emma.png']
            ];
            
            foreach ($default_characters as $char) {
                // Check if ownership table has username column
                $stmt = $pdo->prepare("INSERT IGNORE INTO character_ownership (user_id, username, character_type, character_name, character_image_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$new_user_id, $user_data['username'], $char['type'], $char['name'], $char['path']]);
            }
            
            // Set default character selection (Ethan) - Store "Ethan" in selected_character as per game logic
            $stmt = $pdo->prepare("INSERT IGNORE INTO character_selections (user_id, username, game_type, selected_character, character_image_path) VALUES (?, ?, 'vocabworld', 'Ethan', '../assets/characters/boy_char/character_ethan.png')");
            $stmt->execute([$new_user_id, $user_data['username']]);
            
            // Initialize shard account
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_shards (user_id, username, current_shards, total_earned) VALUES (?, ?, 0, 0)");
            $stmt->execute([$new_user_id, $user_data['username']]);
            
            $pdo->commit();
            
            // Clear temporary session data
            unset($_SESSION['temp_user_data']);
            unset($_SESSION['otp']);
            unset($_SESSION['otp_expiry']);
            
            // Redirect to login page with success message
            header('Location: ../login.php?success=1');
            exit();
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = 'Registration failed. Please try again.';
            error_log("Registration Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/favicon.php'; ?>
    <title>Verify Email - Word Weavers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&family=Press+Start+2P&display=swap" rel="stylesheet">
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
            color: #ffffff;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            font-family: 'Press Start 2P', cursive;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
            letter-spacing: 0.5px;
            line-height: 1.4;
            white-space: nowrap;
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
            margin-bottom: 0;
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

        .button-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            position: relative;
        }

        .verify-button {
            margin-bottom: 0.5rem !important;
        }

        .resend-button {
            background: transparent;
            border: 1px solid rgba(96, 239, 255, 0.3);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: rgba(96, 239, 255, 0.8);
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            width: auto;
            min-width: 120px;
            position: relative;
            overflow: hidden;
            margin-bottom: 0.8rem;
        }

        .resend-button:hover {
            background: rgba(96, 239, 255, 0.1);
            border-color: rgba(96, 239, 255, 0.5);
            color: rgba(96, 239, 255, 1);
            transform: translateY(-1px);
        }

        .resend-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .resend-button i {
            transition: transform 0.3s ease;
            font-size: 0.85rem;
        }

        .resend-button:hover i {
            transform: rotate(180deg);
        }

        @media (max-width: 480px) {
            .resend-button {
                font-size: 0.75rem;
                padding: 0.4rem 0.8rem;
            }
            
            .resend-button i {
                font-size: 0.75rem;
            }
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

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .toast {
            background: rgba(255, 107, 107, 0.95);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out forwards;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toast i {
            font-size: 1.2rem;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
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
                padding: 1.5rem 1rem;
                margin: 1rem;
            }

            .verification-box {
                padding: 1.2rem 0.8rem;
            }

            .otp-digit {
                width: 35px;
                height: 45px;
                font-size: 1.1rem;
            }

            .otp-input-container {
                gap: 0.35rem;
            }

            .verification-title {
                font-size: 0.7rem;
                letter-spacing: 0.3px;
            }

            .verification-message {
                font-size: 0.75rem;
                line-height: 1.4;
                padding: 0 0.5rem;
            }

            .verification-message strong {
                font-size: 0.7rem;
                display: block;
                margin-top: 0.2rem;
                word-break: break-all;
            }

            .verify-button {
                padding: 0.8rem 1.5rem;
                font-size: 0.95rem;
            }

            .logo {
                max-width: 150px;
            }
        }

        @media (max-width: 360px) {
            .verification-container {
                padding: 1.2rem 0.8rem;
            }

            .otp-digit {
                width: 32px;
                height: 40px;
                font-size: 1rem;
            }

            .otp-input-container {
                gap: 0.25rem;
            }

            .verification-title {
                font-size: 0.6rem;
                letter-spacing: 0.2px;
            }

            .verification-message {
                font-size: 0.7rem;
            }

            .verification-message strong {
                font-size: 0.65rem;
            }

            .logo {
                max-width: 130px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/page-loader.php'; ?>
    <div class="toast-container"></div>
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

                <div class="button-container">
                    <button type="submit" class="verify-button">
                        <i class="fas fa-check-circle"></i>
                        Verify Code
                    </button>

                    <button type="button" class="resend-button" id="resend-button">
                        <i class="fas fa-redo"></i>
                        Resend Code
                    </button>
                </div>
            </form>

            <div class="verification-footer">
                <p class="time-remaining" id="timer">Code expires in: 05:00</p>
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

        // Focus first input on load and check existing timer
        window.onload = function() {
            otpInputs[0].focus();
            
            // Check if there's an existing timer
            const existingExpiryTime = localStorage.getItem('otpExpiryTime');
            if (existingExpiryTime) {
                const timeLeft = Math.max(0, Math.floor((parseInt(existingExpiryTime) - Date.now()) / 1000));
                if (timeLeft > 0) {
                    startTimer(timeLeft);
                } else {
                    // If expired, clear it and start new timer
                    localStorage.removeItem('otpExpiryTime');
                    startTimer();
                }
            } else {
                startTimer();
            }
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

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Always show the response message from the server
                resendMessage.textContent = data.message || 'Verification code has been resent!';
                resendMessage.style.color = data.success ? '#00ff87' : '#ff6b6b';
                resendMessage.style.display = 'block';
                
                if (data.success) {
                    // Reset timer and clear inputs only on success
                    localStorage.removeItem('otpExpiryTime');
                    startTimer();
                    otpInputs.forEach(input => input.value = '');
                    otpInputs[0].focus();
                    
                    // Play success sound
                    const audio = new Audio('../../assets/sounds/clicks/gameopens2.mp3');
                    audio.volume = 0.5;
                    audio.play();
                }
            } catch (error) {
                console.error('Resend error:', error);
                const errorMessage = error.message || 'Failed to resend code. Please try again.';
                resendMessage.textContent = errorMessage;
                resendMessage.style.color = '#ff6b6b';
                resendMessage.style.display = 'block';
            } finally {
                resendButton.disabled = false;
                resendButton.style.opacity = '1';
            
                // Hide message after 5 seconds
                setTimeout(() => {
                    if (resendMessage.style.display !== 'none') {
                        resendMessage.style.opacity = '0';
                        setTimeout(() => {
                            resendMessage.style.display = 'none';
                            resendMessage.style.opacity = '1';
                        }, 300);
                    }
                }, 5000);
            }
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
        function startTimer(initialTime = 300) { // 5 minutes in seconds
            // Clear any existing interval
            if (window.timerInterval) {
                clearInterval(window.timerInterval);
            }

            // Set expiry time in localStorage
            const expiryTime = Date.now() + (initialTime * 1000);
            localStorage.setItem('otpExpiryTime', expiryTime.toString());
            
            function updateTimer() {
                const currentTime = Date.now();
                const expiryTimeStored = parseInt(localStorage.getItem('otpExpiryTime') || '0');
                const timeLeft = Math.max(0, Math.floor((expiryTimeStored - currentTime) / 1000));
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;

                if (timeLeft > 0) {
                    timer.textContent = `Code expires in: ${minutes}:${seconds.toString().padStart(2, '0')}`;
                    timer.style.color = 'rgba(96, 239, 255, 0.8)';
                    resendButton.disabled = true;
                } else {
                    clearInterval(window.timerInterval);
                    timer.textContent = 'Code expired';
                    timer.style.color = '#ff6b6b';
                    resendButton.disabled = false;
                    localStorage.removeItem('otpExpiryTime');
                }
            }

            window.timerInterval = setInterval(updateTimer, 1000);
            updateTimer(); // Initial update
            
            // Disable resend button when timer starts
            resendButton.disabled = true;
        }

        // Toast function
        function showToast(message) {
            const toastContainer = document.querySelector('.toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            `;
            toastContainer.appendChild(toast);

            // Play notification sound
            const audio = new Audio('../../assets/sounds/toast/toastnotifwarn.mp3');
            audio.volume = 0.5;
            audio.play();

            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => {
                    toastContainer.removeChild(toast);
                }, 300);
            }, 3000);
        }

        // Form submission
        form.addEventListener('submit', (e) => {
            if (otpValue.value.length !== 6) {
                e.preventDefault();
                showToast('Please enter all 6 digits of the verification code.');
            }
        });
    </script>
</body>
</html>