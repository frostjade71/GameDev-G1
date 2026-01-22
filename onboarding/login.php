<?php
require_once 'config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error_message = '';
$success_message = '';

// Check for registration success message
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = 'Registration successful! You can now sign in.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Prepare and execute query
            $stmt = $pdo->prepare("SELECT id, username, email, grade_level, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['grade_level'] = $user['grade_level'];
                
                // Update last login timestamp
                try {
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                } catch (PDOException $e) {
                    // Log error but don't prevent login
                    error_log("Failed to update last_login: " . $e->getMessage());
                }
                
                // Create session record for active user tracking
                try {
                    $sessionStmt = $pdo->prepare("
                        INSERT INTO user_sessions (user_id, session_id, login_time, last_activity, ip_address, user_agent) 
                        VALUES (?, ?, NOW(), NOW(), ?, ?)
                    ");
                    $sessionStmt->execute([
                        $user['id'],
                        session_id(),
                        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                } catch (PDOException $e) {
                    // Log error but don't prevent login
                    error_log("Failed to create session record: " . $e->getMessage());
                }
                
                // Redirect to index page
                header('Location: ../menu.php');
                exit();
            } else {
                $error_message = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../includes/favicon.php'; ?>
    <title>Login - Word Weavers</title>
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
            margin-bottom: 1rem;
            filter: drop-shadow(0 2px 15px rgba(0, 255, 135, 0.4));
            transition: transform 0.3s ease;
        }

        .auth-logo:hover {
            transform: scale(1.02);
            filter: drop-shadow(0 2px 20px rgba(0, 255, 135, 0.6));
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

        .form-group input:hover {
            background: rgba(96, 239, 255, 0.05);
            border-color: rgba(96, 239, 255, 0.3);
        }

        .form-group input:focus {
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
            
            .form-group {
                padding: 1rem;
            }
            
            .form-group label {
                font-size: 0.7rem;
            }
            
            .form-group input {
                padding: 0.8rem;
                font-size: 0.95rem;
            }
            
            .auth-button {
                padding: 0.7rem;
                font-size: 0.85rem;
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
            
            .form-group {
                padding: 0.8rem;
            }
            
            .form-group label {
                font-size: 0.65rem;
            }
            
            .form-group input {
                padding: 0.7rem;
                font-size: 0.9rem;
            }
            
            .auth-button {
                padding: 0.6rem;
                font-size: 0.8rem;
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
    </style>
</head>
<body>
    <?php include '../includes/page-loader.php'; ?>
    <div class="auth-container">
        
        <div class="auth-header">
            <img src="../assets/menu/Word-Weavers.png" alt="Word Weavers" class="auth-logo">
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form class="auth-form" method="POST" action="">
            <div class="form-container">
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
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required
                        style="width: 100%; padding-right: 35px;"
                    >
                    <i class="fas fa-eye" 
                       onclick="togglePassword('password')"
                       style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"
                    ></i>
                </div>
            </div>
            
            <button type="submit" class="auth-button">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Create one here</a></p>
        </div>
    </div>
    
    <script>
        // Add loading state to form
        document.querySelector('.auth-form').addEventListener('submit', function() {
            this.classList.add('loading');
        });
        
        // Add focus effects to inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
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
