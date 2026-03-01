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

                // AUDIT LOG: Login Success
                require_once '../includes/Logger.php';
                logAudit('Login Success', $user['id'], $user['username']);
                
                // Redirect to index page
                header('Location: ../menu.php');
                exit();
            } else {
                $error_message = 'Invalid email or password.';
                // AUDIT LOG: Login Failed
                require_once '../includes/Logger.php';
                // Try to find user exists to log who attempted
                $failedUserId = $user['id'] ?? null;
                $failedUsername = $user['username'] ?? ($email ? "Email: $email" : 'Unknown');
                logAudit('Login Failed', $failedUserId, $failedUsername, "Invalid password for email: $email");
            }
        } catch (PDOException $e) {
            $error_message = 'Login failed. Please try again.';
            // AUDIT LOG: Login Error
            require_once '../includes/Logger.php';
             logAudit('Login Error', null, "Email: $email", $e->getMessage());
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

        .form-group input {
            display: block;
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-group input:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .form-group input:focus {
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
                padding: 2rem 1.5rem;
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
    </style>
</head>
<body>
    <?php include '../includes/page-loader.php'; ?>
    <div class="auth-container">
        
        <div class="auth-header">
            <img src="../assets/menu/ww_logo_main.webp" alt="Word Weavers" class="auth-logo">
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
            <p style="margin-top: 1rem;">
                <a href="../index.php" style="color: #ffffff;">
                    <i class="fas fa-arrow-left"></i> Back to Homepage
                </a>
            </p>
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
