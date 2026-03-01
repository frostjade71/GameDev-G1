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
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .auth-container {
            background: rgba(20, 20, 20, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 380px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.4s ease-out;
            text-align: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo {
            max-width: 120px;
            width: 100%;
            height: auto;
            margin-bottom: 0.5rem;
            opacity: 0.9;
            transition: opacity 0.3s;
        }

        .auth-logo:hover {
            opacity: 1;
        }

        .auth-subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 0.3rem;
        }

        .auth-email {
            display: block;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 0.4rem;
            word-break: break-all;
        }

        .otp-input-container {
            display: flex;
            gap: 0.6rem;
            justify-content: center;
            margin: 1.5rem 0;
        }

        .otp-digit {
            width: 42px;
            height: 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 1.4rem;
            color: #fff;
            text-align: center;
            transition: all 0.2s ease;
            font-weight: 600;
            font-family: inherit;
        }

        .otp-digit:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .auth-button {
            background: #ffffff;
            color: #000000;
            border: none;
            border-radius: 8px;
            padding: 0.85rem;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            margin-top: 0.5rem;
        }

        .auth-button:hover {
            background: #e6e6e6;
            transform: translateY(-1px);
        }

        .auth-button i {
            font-size: 0.9rem;
            color: #000;
        }

        .resend-button {
            background: transparent;
            border: none;
            padding: 0.6rem 1rem;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            width: auto;
            margin: 0.8rem auto 0;
            font-family: inherit;
        }

        .resend-button:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        .resend-button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .resend-button i {
            transition: transform 0.3s ease;
            font-size: 0.8rem;
        }

        .resend-button:hover i {
            transform: rotate(180deg);
        }

        .verification-footer {
            margin-top: 1.2rem;
            text-align: center;
        }

        .time-remaining {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.8rem;
        }

        .resend-message {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            animation: fadeIn 0.3s ease-out;
        }

        .auth-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .auth-links p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .auth-links a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .auth-links a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .error-message {
            padding: 0.8rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 87, 87, 0.15);
            color: #ff7676;
            border: 1px solid rgba(255, 87, 87, 0.2);
            text-align: left;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .toast {
            background: rgba(255, 87, 87, 0.95);
            color: white;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease-out forwards;
            font-size: 0.85rem;
        }

        .toast i {
            font-size: 1rem;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 2rem 1.5rem;
                border-radius: 12px;
            }

            .auth-logo {
                max-width: 100px;
            }

            .otp-digit {
                width: 36px;
                height: 44px;
                font-size: 1.1rem;
            }

            .otp-input-container {
                gap: 0.4rem;
            }
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
            transition: opacity 0.2s;
        }
    </style>
</head>
<body>
    <?php include '../../includes/page-loader.php'; ?>
    <div class="toast-container"></div>
    <div class="auth-container">
        <div class="auth-header">
            <img src="../../assets/menu/ww_logo_main.webp" alt="Word Weavers" class="auth-logo">
            <p class="auth-subtitle">
                <?php if (isset($_SESSION['temp_user_data']['email'])): ?>
                    We sent a verification code to
                    <span class="auth-email"><?php echo htmlspecialchars($_SESSION['temp_user_data']['email']); ?></span>
                <?php else: ?>
                    Enter your verification code
                <?php endif; ?>
            </p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="verification-form">
            <div class="otp-input-container">
                <input type="text" maxlength="1" class="otp-digit" data-index="1" inputmode="numeric">
                <input type="text" maxlength="1" class="otp-digit" data-index="2" inputmode="numeric">
                <input type="text" maxlength="1" class="otp-digit" data-index="3" inputmode="numeric">
                <input type="text" maxlength="1" class="otp-digit" data-index="4" inputmode="numeric">
                <input type="text" maxlength="1" class="otp-digit" data-index="5" inputmode="numeric">
                <input type="text" maxlength="1" class="otp-digit" data-index="6" inputmode="numeric">
                <input type="hidden" name="otp" id="otp-value">
            </div>

            <button type="submit" class="auth-button">
                <i class="fas fa-check-circle"></i>
                Verify Code
            </button>

            <button type="button" class="resend-button" id="resend-button">
                <i class="fas fa-redo"></i>
                Resend Code
            </button>
        </form>

        <div class="verification-footer">
            <p class="time-remaining" id="timer">Code expires in: 05:00</p>
            <p class="resend-message" id="resend-message" style="display: none;"></p>
        </div>

        <div class="auth-links">
            <p><a href="../register.php"><i class="fas fa-arrow-left"></i> Back to Register</a></p>
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
                resendMessage.style.color = data.success ? '#4ade80' : '#ff7676';
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
                resendMessage.style.color = '#ff7676';
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
                    timer.style.color = 'rgba(255, 255, 255, 0.4)';
                    resendButton.disabled = true;
                } else {
                    clearInterval(window.timerInterval);
                    timer.textContent = 'Code expired';
                    timer.style.color = '#ff7676';
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