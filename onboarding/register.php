<?php
require_once 'config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $grade_level = $_POST['grade_level'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username must be less than 50 characters.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($grade_level)) {
        $errors[] = 'Grade level is required.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Store user data temporarily in session
                $_SESSION['temp_user_data'] = [
                    'username' => $username,
                    'email' => $email,
                    'grade_level' => $grade_level,
                    'password' => $hashed_password
                ];
                
                // Generate and send OTP
                require_once 'otp/send_otp.php';
                $otp = generateOTP();
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_expiry'] = time() + (5 * 60); // 5 minutes expiry
                
                if (sendOTP($email, $username, $otp)) {
                    header('Location: otp/verify.php');
                    exit();
                } else {
                    $errors[] = 'Failed to send verification email. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../includes/favicon.php'; ?>
    <title>Register - Word Weavers</title>
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
            background-image: url('../assets/menu/menubg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            font-family: 'Quicksand', sans-serif;
            margin-top : 2rem  ;
        }

        .auth-container {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.85), rgba(20, 20, 20, 0.95));
            backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            border: 2px solid rgba(96, 239, 255, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 20px rgba(96, 239, 255, 0.1);
            position: relative;
            overflow: hidden;
            animation: containerFadeIn 0.5s ease-out;
        }

        @keyframes containerFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-container::before {
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

        .auth-container::after {
            content: '';
            position: absolute;
            top: 4px;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .auth-logo {
            max-width: 220px;
            width: 100%;
            height: auto;
            margin-bottom: 1.2rem;
            filter: drop-shadow(0 2px 15px rgba(0, 255, 135, 0.4));
            transition: transform 0.3s ease;
        }

        .auth-logo:hover {
            transform: scale(1.02);
            filter: drop-shadow(0 2px 20px rgba(0, 255, 135, 0.6));
        }

        .auth-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            font-weight: 500;
            position: relative;
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(96, 239, 255, 0.1);
            border-radius: 20px;
            border: 1px solid rgba(96, 239, 255, 0.2);
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.8rem;
            position: relative;
        }

        .form-container {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 0 20px rgba(96, 239, 255, 0.1);
            border: 1px solid rgba(96, 239, 255, 0.1);
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            position: relative;
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid rgba(96, 239, 255, 0.15);
            margin: 0;
            transition: all 0.3s ease;
        }

        .form-group:hover {
            background: rgba(0, 0, 0, 0.3);
            border-color: rgba(96, 239, 255, 0.25);
        }

        .form-group::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: linear-gradient(to bottom, rgba(96, 239, 255, 0.6), rgba(0, 255, 135, 0.6));
            border-radius: 3px 0 0 3px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .form-group:hover::before {
            opacity: 1;
        }

        .form-group label {
            color: #ffffff;
            font-size: 0.8rem;
            font-family: 'Press Start 2P', cursive;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
            letter-spacing: 1px;
            padding-bottom: 0.5rem;
        }

        .form-group label i {
            color: rgba(96, 239, 255, 1);
            font-size: 1.1rem;
            filter: drop-shadow(0 0 5px rgba(96, 239, 255, 0.3));
            transition: transform 0.3s ease;
        }

        .form-group:hover label i {
            transform: scale(1.1);
        }

        .form-group input {
            display: block;
            width: 100%;
            background: rgba(0, 0, 0, 0.6);
            border: 2px solid rgba(96, 239, 255, 0.15);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        /* Enhanced styling for grade level select */
        .form-group select {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(0, 0, 0, 0.6);
            border: 2px solid rgba(96, 239, 255, 0.15);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            font-family: inherit;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='rgba(96, 239, 255, 0.8)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }

        .form-group select:hover {
            background-color: rgba(96, 239, 255, 0.05);
            border-color: rgba(96, 239, 255, 0.3);
        }

        .form-group select:focus {
            outline: none;
            background-color: rgba(0, 0, 0, 0.7);
            border-color: rgba(0, 255, 135, 0.5);
            box-shadow: 0 0 25px rgba(0, 255, 135, 0.15);
            transform: scale(1.01);
        }

        .form-group select option {
            background-color: #1a1a2e;
            color: white;
            padding: 0.8rem;
        }

        .form-group input:hover,
        .form-group select:hover {
            background: rgba(96, 239, 255, 0.05);
            border-color: rgba(96, 239, 255, 0.3);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            background: rgba(0, 0, 0, 0.7);
            border-color: rgba(0, 255, 135, 0.5);
            box-shadow: 0 0 25px rgba(0, 255, 135, 0.15);
            transform: scale(1.01);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.9rem;
            font-style: italic;
        }

        .auth-button {
            background: linear-gradient(45deg, rgba(96, 239, 255, 0.8), rgba(0, 255, 135, 0.8));
            border: none;
            border-radius: 12px;
            padding: 1rem;
            color: white;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .auth-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .auth-button:hover {
            transform: translateY(-2px);
            background: linear-gradient(45deg, rgba(96, 239, 255, 0.9), rgba(0, 255, 135, 0.9));
            box-shadow: 0 5px 20px rgba(0, 255, 135, 0.3);
        }

        .auth-button:hover::before {
            left: 100%;
        }

        .auth-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(96, 239, 255, 0.1);
            position: relative;
        }

        .auth-links p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .auth-links::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background: linear-gradient(90deg, rgba(96, 239, 255, 0.8), rgba(0, 255, 135, 0.8));
        }

        .auth-links a {
            color: rgba(96, 239, 255, 0.8);
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            padding-bottom: 2px;
        }

        .auth-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 1px;
            background: rgba(0, 255, 135, 0.8);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .auth-links a:hover {
            color: rgba(0, 255, 135, 1);
            text-shadow: 0 0 10px rgba(0, 255, 135, 0.3);
        }

        .auth-links a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .error-message,
        .success-message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
            border-radius: 12px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            animation: messageFadeIn 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }

        @keyframes messageFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
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
        }

        .success-message {
            background: rgba(0, 255, 135, 0.1);
            border: 1px solid rgba(0, 255, 135, 0.3);
            color: #00ff87;
        }

        .error-message i,
        .success-message i {
            font-size: 1.1rem;
        }

        .error-message::before,
        .success-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
        }

        .error-message::before {
            background: linear-gradient(to bottom, #ff6b6b, #ff8585);
        }

        .success-message::before {
            background: linear-gradient(to bottom, #00ff87, #00ffaa);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .auth-container {
                padding: 2rem;
                border-radius: 20px;
                max-width: 400px;
            }
            
            .auth-logo {
                max-width: 180px;
            }
            
            .auth-subtitle {
                font-size: 0.9rem;
                padding: 0.4rem 0.8rem;
            }
            
            .form-group {
                padding: 1rem;
            }
            
            .form-group label {
                font-size: 0.7rem;
            }
            
            .form-group input,
            .form-group select {
                padding: 0.8rem;
                font-size: 0.95rem;
            }
            
            .auth-button {
                padding: 0.7rem;
                font-size: 0.85rem;
                margin-top: -0.5rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.8rem;
            }

            .auth-container {
                padding: 1.5rem;
                border-radius: 15px;
                max-width: 100%;
            }
            
            .auth-logo {
                max-width: 150px;
            }
            
            .auth-subtitle {
                font-size: 0.85rem;
                padding: 0.3rem 0.6rem;
            }
            
            .form-group {
                padding: 0.8rem;
            }
            
            .form-group label {
                font-size: 0.65rem;
            }
            
            .form-group input,
            .form-group select {
                padding: 0.7rem;
                font-size: 0.9rem;
            }
            
            .auth-button {
                padding: 0.6rem;
                font-size: 0.8rem;
                margin-top: -1rem;
            }
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(2px);
            border-radius: inherit;
            z-index: 1;
        }

        .loading .auth-button {
            background: rgba(96, 239, 255, 0.5);
            cursor: not-allowed;
            position: relative;
        }

        .loading .auth-button::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s infinite linear;
            margin-left: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Password Strength Indicator */
        .form-group input[type="password"] {
            position: relative;
            transition: all 0.3s ease;
        }

        .form-group input[type="password"]:focus {
            border-image: linear-gradient(to right, #ff6b6b, #ffd93d, #00ff87) 1;
        }

        /* Eye Icon for Password Toggle */
        .form-group .fa-eye,
        .form-group .fa-eye-slash {
            color: rgba(96, 239, 255, 0.8);
            transition: all 0.3s ease;
            padding: 0.5rem;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.2);
        }

        .form-group .fa-eye:hover,
        .form-group .fa-eye-slash:hover {
            color: rgba(0, 255, 135, 1);
            background: rgba(0, 0, 0, 0.4);
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <?php include '../includes/page-loader.php'; ?>
    <div class="auth-container">
        
        <div class="auth-header">
            <img src="../assets/menu/Word-Weavers.png" alt="Word Weavers" class="auth-logo">
            <p class="auth-subtitle">Create an Account</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        
        <form class="auth-form" method="POST" action="">
            <div class="form-container">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Full Name
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="Enter your full name"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                        minlength="3"
                        maxlength="50"
                    >
                </div>
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    Email Address
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="grade_level">
                    <i class="fas fa-graduation-cap"></i>
                    Grade Level
                </label>
                <select 
                    id="grade_level" 
                    name="grade_level" 
                    required>
                    <option value="">Select your grade level</option>
                    <option value="Grade 7" <?php echo ($_POST['grade_level'] ?? '') === 'Grade 7' ? 'selected' : ''; ?>>Grade 7</option>
                    <option value="Grade 8" <?php echo ($_POST['grade_level'] ?? '') === 'Grade 8' ? 'selected' : ''; ?>>Grade 8</option>
                    <option value="Grade 9" <?php echo ($_POST['grade_level'] ?? '') === 'Grade 9' ? 'selected' : ''; ?>>Grade 9</option>
                    <option value="Grade 10" <?php echo ($_POST['grade_level'] ?? '') === 'Grade 10' ? 'selected' : ''; ?>>Grade 10</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i>
                    Password
                </label>
                <div style="position: relative;">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Create a password (min 6 characters)"
                        required
                        minlength="6"
                        style="width: 100%; padding-right: 35px;"
                    >
                    <i class="fas fa-eye" 
                       onclick="togglePassword('password')"
                       style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                    ></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">
                    <i class="fas fa-lock"></i>
                    Confirm Password
                </label>
                <div style="position: relative;">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirm your password"
                        required
                        minlength="6"
                        style="width: 100%; padding-right: 35px;"
                    >
                    <i class="fas fa-eye" 
                       onclick="togglePassword('confirm_password')"
                       style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                    ></i>
                </div>
            </div>
            
            </div>
            <button type="submit" class="auth-button">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
        </form>
        
        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>
    
    <script>
        // Add loading state to form
        document.querySelector('.auth-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const loader = document.getElementById('pageLoader');
            const loaderText = document.getElementById('loaderText');

            if (loader) {
                loader.classList.remove('hidden');
                if (loaderText) {
                    loaderText.textContent = "Creating Account";
                    
                    setTimeout(() => {
                        loaderText.textContent = "Sending Code";
                        
                        setTimeout(() => {
                            form.submit();
                        }, 2000);
                    }, 2000);
                } else {
                    form.submit();
                }
            } else {
                form.submit();
            }
        });
        
        // Password confirmation validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        password.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
        
        // Add focus effects to inputs
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
        
        // Real-time password strength indicator
        password.addEventListener('input', function() {
            const strength = this.value.length;
            if (strength < 6) {
                this.style.borderColor = '#ff6b6b';
            } else if (strength < 8) {
                this.style.borderColor = '#ffd93d';
            } else {
                this.style.borderColor = '#00ff87';
            }
        });

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
