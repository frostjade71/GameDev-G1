<?php
require_once 'config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $grade_level = $_POST['grade_level'] ?? '';
    $teacher_unlock_password = $_POST['teacher_unlock_password'] ?? '';
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
    } elseif ($grade_level === 'Teacher') {
        $teacher_hash = '$2y$10$l6VUPLAFCfjhnesAHY/ACuWqI7I5LDlazSG..3PpqQZD7aT6Beeny';
        if (empty($teacher_unlock_password)) {
            $errors[] = 'Teacher unlock password is required.';
        } elseif (!password_verify($teacher_unlock_password, $teacher_hash)) {
            $errors[] = 'Incorrect Teacher unlock password.';
        }
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
                    // AUDIT LOG: Registered Account (Initial Step)
                    require_once '../includes/Logger.php';
                    logAudit('Registered Account', null, $username, "Email: $email - OTP Sent");

                    header('Location: otp/verify.php');
                    exit();
                } else {
                    $errors[] = 'Failed to send verification email. Please try again.';
                    // AUDIT LOG: Registration Failed (Email)
                    require_once '../includes/Logger.php';
                    logAudit('Registration Failed', null, $username, "Failed to send OTP to $email");
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
            // AUDIT LOG: Registration Error
            require_once '../includes/Logger.php';
            logAudit('Registration Error', null, $username, $e->getMessage());
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
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.4s ease-out;
            max-height: 90vh;
            overflow-y: auto;
        }

        .auth-container::-webkit-scrollbar {
            width: 6px;
        }
        .auth-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .auth-container::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
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
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group label {
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group label i {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }

        .agreement-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 8px;
        }
        .agreement-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-top: 3px;
            accent-color: #009bd9;
            cursor: pointer;
            flex-shrink: 0;
        }
        .agreement-group label {
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.5;
            cursor: pointer;
        }
        .agreement-group label a {
            color: #0492c9;
            text-decoration: none;
        }
        .agreement-group label a:hover {
            text-decoration: underline;
        }

        .form-group input,
        .form-group select {
            display: block;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='rgba(255, 255, 255, 0.4)' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }

        .form-group select option {
            background-color: #1a1a2e;
            color: white;
            padding: 0.8rem;
        }

        .form-group input:hover,
        .form-group select:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
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

        .auth-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .auth-links p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 0.5rem;
        }

        .auth-links a {
            color: #0492c9;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .auth-links a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .error-message,
        .success-message {
            padding: 0.8rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message {
            background: rgba(255, 87, 87, 0.15);
            color: #ff7676;
            border: 1px solid rgba(255, 87, 87, 0.2);
        }

        .success-message {
            background: rgba(46, 204, 113, 0.15);
            color: #4ade80;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 1.5rem;
                border-radius: 12px;
            }
            .auth-logo {
                max-width: 100px;
            }
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
            transition: opacity 0.2s;
        }

        .form-group .fa-eye,
        .form-group .fa-eye-slash {
            color: rgba(255, 255, 255, 0.4);
            transition: color 0.2s ease;
            padding: 0.5rem;
            cursor: pointer;
        }

        .form-group .fa-eye:hover,
        .form-group .fa-eye-slash:hover {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <?php include '../includes/page-loader.php'; ?>
    <div class="auth-container">
        
        <div class="auth-header">
            <img src="../assets/menu/ww_logo_main.webp" alt="Word Weavers" class="auth-logo">
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
                    <option value="Teacher" <?php echo ($_POST['grade_level'] ?? '') === 'Teacher' ? 'selected' : ''; ?>>Teacher</option>
                </select>
            </div>

            <div class="form-group" id="teacher_unlock_group" style="display: <?php echo ($_POST['grade_level'] ?? '') === 'Teacher' ? 'block' : 'none'; ?>;">
                <label for="teacher_unlock_password">
                    <i class="fas fa-key"></i>
                    Teacher Unlock Password
                </label>
                <input 
                    type="password" 
                    id="teacher_unlock_password" 
                    name="teacher_unlock_password" 
                    placeholder="Enter unlock password"
                >
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
                    <i class="fas fa-check-circle" id="password-match-icon" style="color: #4ade80; display: none;"></i>
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

            <div class="agreement-group">
                <input type="checkbox" id="agree_terms" name="agree_terms" required>
                <label for="agree_terms">
                    I have read and agree to the 
                    <a href="../docs/privacy.php" target="_blank">Privacy Policy</a>, 
                    <a href="../docs/terms.php" target="_blank">Terms of Service</a> and 
                    <a href="../docs/student-guide.php" target="_blank">Student Guide</a>
                </label>
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
            const matchIcon = document.getElementById('password-match-icon');
            if (password.value && confirmPassword.value && password.value === confirmPassword.value) {
                confirmPassword.setCustomValidity('');
                matchIcon.style.display = 'inline';
            } else {
                confirmPassword.setCustomValidity(password.value !== confirmPassword.value ? "Passwords don't match" : '');
                matchIcon.style.display = 'none';
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

        // Toggle Teacher Unlock Password field
        const gradeLevelSelect = document.getElementById('grade_level');
        const teacherUnlockGroup = document.getElementById('teacher_unlock_group');
        const teacherUnlockInput = document.getElementById('teacher_unlock_password');

        gradeLevelSelect.addEventListener('change', function() {
            if (this.value === 'Teacher') {
                teacherUnlockGroup.style.display = 'block';
                teacherUnlockInput.setAttribute('required', 'required');
            } else {
                teacherUnlockGroup.style.display = 'none';
                teacherUnlockInput.removeAttribute('required');
                teacherUnlockInput.value = '';
            }
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
