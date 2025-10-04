<?php
/**
 * Secure Login Page
 * Handles user authentication with enhanced security features
 * - Rate limiting
 * - CSRF protection
 * - Session security
 * - Input validation
 * - Brute force protection
 */

// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Regenerate session ID to prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Include database configuration
require_once 'config/database.php';

// Initialize login attempts tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Generate Math CAPTCHA if not exists
if (!isset($_SESSION['captcha_answer'])) {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['captcha_answer'] = $num1 + $num2;
    $_SESSION['captcha_question'] = "$num1 + $num2";
}

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin_dashboard.php');
    } elseif ($role === 'faculty') {
        header('Location: faculty_dashboard.php');
    } elseif ($role === 'student') {
        header('Location: student_dashboard.php');
    }
    exit();
}

// Initialize variables
$error_message = '';
$success_message = '';

// Check for lockout (rate limiting)
function isLockedOut() {
    if (!isset($_SESSION['login_attempts']) || !isset($_SESSION['last_attempt_time'])) {
        return false;
    }
    
    // Lock for 15 seconds after 5 failed attempts
    $lockout_time = 15; // 15 seconds
    $max_attempts = 5;
    
    if ($_SESSION['login_attempts'] >= $max_attempts) {
        $time_passed = time() - $_SESSION['last_attempt_time'];
        if ($time_passed < $lockout_time) {
            $remaining_time = $lockout_time - $time_passed;
            return $remaining_time;
        } else {
            // Reset attempts after lockout period
            $_SESSION['login_attempts'] = 0;
            return false;
        }
    }
    
    return false;
}

// Check for messages from URL parameters
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Check for lockout status
$lockout = isLockedOut();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Login - Edu.Konek</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
            animation: slideUp 0.6s ease;
            max-height: 95vh;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-left {
            flex: 0 0 350px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-left img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 25px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            animation: pulse 2s ease infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-left h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .login-left p {
            font-size: 1rem;
            opacity: 0.95;
            line-height: 1.6;
        }

        .login-right {
            flex: 1;
            padding: 40px 45px;
            overflow-y: auto;
        }

        .login-header {
            margin-bottom: 25px;
        }

        .login-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #718096;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 7px;
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.88rem;
        }

        .form-control {
            padding: 11px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.93rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .input-group {
            position: relative;
        }

        .input-group .form-control {
            padding-right: 50px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #718096;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin-top: 10px;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 13px 18px;
            border-radius: 10px;
            margin-bottom: 18px;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
            font-size: 0.9rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: #fee;
            color: #c53030;
            border-left: 4px solid #c53030;
        }

        .alert-success {
            background: #efe;
            color: #2f855a;
            border-left: 4px solid #2f855a;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #856404;
        }

        .security-features {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .security-features h6 {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #f7fafc;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #4a5568;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .security-badge i {
            color: #48bb78;
        }

        .attempts-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            padding: 12px;
            background: #f7fafc;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .attempts-dots {
            display: flex;
            gap: 6px;
        }

        .attempt-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.3s ease;
        }

        .attempt-dot.failed {
            background: #fc8181;
            animation: dotPulse 0.5s ease;
        }

        @keyframes dotPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        .captcha-container {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .captcha-question {
            margin-bottom: 15px;
        }

        .captcha-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            letter-spacing: 2px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .captcha-container input {
            max-width: 150px;
            margin: 0 auto;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .signup-link p {
            color: #718096;
            font-size: 0.93rem;
        }

        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .signup-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .login-left {
                flex: 0 0 300px;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-height: none;
            }

            .login-left {
                padding: 35px 25px;
            }

            .login-left h1 {
                font-size: 1.8rem;
            }

            .login-right {
                padding: 35px 25px;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <img src="assets/images/Logo.png" alt="Edu.Konek Logo">
            <h1>Edu.Konek</h1>
            <p>Your gateway to academic excellence and seamless education management</p>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back!</h2>
                <p>Please sign in to your account</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Lockout Warning -->
            <?php if ($lockout !== false): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-lock"></i>
                    <span>Account temporarily locked. Try again in <?php echo $lockout; ?> second(s).</span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login.php" id="loginForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Username Field -->
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           placeholder="Enter your username"
                           required 
                           minlength="3"
                           maxlength="50"
                           autocomplete="username"
                           <?php echo ($lockout !== false) ? 'disabled' : ''; ?>>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password"
                               <?php echo ($lockout !== false) ? 'disabled' : ''; ?>>
                        <button type="button" class="toggle-password" onclick="togglePassword()" tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Math CAPTCHA -->
                <div class="form-group">
                    <label for="captcha" class="form-label">
                        <i class="fas fa-shield-alt"></i> Security Verification
                    </label>
                    <div class="captcha-container">
                        <div class="captcha-question">
                            <span class="captcha-text">What is <?php echo $_SESSION['captcha_question']; ?> ?</span>
                        </div>
                        <input type="number" 
                               class="form-control" 
                               id="captcha" 
                               name="captcha"
                               required
                               autocomplete="off"
                               <?php echo ($lockout !== false) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <!-- Login Attempts Indicator -->
                <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 0): ?>
                    <div class="attempts-indicator">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <span>Failed attempts:</span>
                        <div class="attempts-dots">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <div class="attempt-dot <?php echo ($i < $_SESSION['login_attempts']) ? 'failed' : ''; ?>"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" <?php echo ($lockout !== false) ? 'disabled' : ''; ?>>
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>

                <!-- Forgot Password Link -->
                <div style="text-align: center; margin-top: 15px;">
                    <a href="recover_password.php" style="color: #667eea; text-decoration: none; font-size: 0.9rem; transition: color 0.2s ease;">
                        <i class="fas fa-key"></i> Forgot your password?
                    </a>
                </div>
            </form>

            <!-- Sign Up Link -->
            <div class="signup-link">
                <p>
                    Don't have an account? <a href="signup.php">Sign up here</a>
                </p>
            </div>

            <!-- Security Features -->
            <div class="security-features">
                <h6>Protected By</h6>
                <div>
                    <span class="security-badge">
                        <i class="fas fa-shield-alt"></i> CSRF Protection
                    </span>
                    <span class="security-badge">
                        <i class="fas fa-calculator"></i> Math CAPTCHA
                    </span>
                    <span class="security-badge">
                        <i class="fas fa-clock"></i> Rate Limiting
                    </span>
                    <span class="security-badge">
                        <i class="fas fa-lock"></i> Secure Sessions
                    </span>
                    <span class="security-badge">
                        <i class="fas fa-key"></i> Encrypted Passwords
                    </span>
                </div>
                <p style="margin-top: 12px; font-size: 0.8rem; color: #718096;">
                    <i class="fas fa-info-circle me-1"></i>
                    Maximum 5 login attempts per 15 seconds
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return false;
            }
            
            if (password.length < 1) {
                e.preventDefault();
                alert('Please enter your password.');
                return false;
            }
        });

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>