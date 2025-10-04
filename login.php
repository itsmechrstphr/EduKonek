<?php
/**
 * Secure Login Handler
 * Processes login requests with enhanced security features
 * - Rate limiting with session-based tracking
 * - CSRF protection
 * - Input validation
 * - Brute force protection
 * - Session security
 */

// Start session with secure settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Include database configuration
require_once 'config/database.php';

// Initialize login attempts tracking if not exists
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Function to check if account is locked out
function isLockedOut() {
    if (!isset($_SESSION['login_attempts']) || !isset($_SESSION['last_attempt_time'])) {
        return false;
    }
    
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

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

try {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        throw new Exception('Invalid security token. Please refresh and try again.');
    }
    
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid security token. Please refresh and try again.');
    }
    
    // Check for lockout
    $lockout = isLockedOut();
    if ($lockout !== false) {
        throw new Exception("Too many failed login attempts. Please try again in {$lockout} minute(s).");
    }
    
    // Sanitize and validate inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_input = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
    
    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required.');
    }
    
    // Verify CAPTCHA
    if (!isset($_SESSION['captcha_answer']) || $captcha_input != $_SESSION['captcha_answer']) {
        // Increment failed attempts for wrong CAPTCHA
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        
        // Generate new CAPTCHA
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $_SESSION['captcha_answer'] = $num1 + $num2;
        $_SESSION['captcha_question'] = "$num1 + $num2";
        
        throw new Exception('Invalid security verification. Please try again.');
    }
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        throw new Exception('Invalid username format.');
    }
    
    // Rate limiting check (prevent rapid fire attempts)
    if (isset($_SESSION['last_login_check']) && (time() - $_SESSION['last_login_check']) < 2) {
        throw new Exception('Please wait a moment before trying again.');
    }
    $_SESSION['last_login_check'] = time();
    
    // Query database for user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    // Verify user exists and password is correct
    if (!$user || !password_verify($password, $user['password'])) {
        // Increment failed attempts
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        
        // Generate new CAPTCHA for next attempt
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $_SESSION['captcha_answer'] = $num1 + $num2;
        $_SESSION['captcha_question'] = "$num1 + $num2";
        
        // Log failed attempt (optional - uncomment if you want logging)
        // error_log("Failed login attempt for username: {$username} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        throw new Exception('Invalid username or password.');
    }
    
    // Successful login - reset attempts
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['last_attempt_time']);
    unset($_SESSION['last_login_check']);
    unset($_SESSION['captcha_answer']);
    unset($_SESSION['captcha_question']);
    
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['theme'] = $user['theme'] ?? 'light';
    $_SESSION['layout'] = $user['layout'] ?? 'standard';
    $_SESSION['card_style'] = $user['card_style'] ?? 'shadowed';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['initiated'] = true;
    
    // Log successful login (optional - uncomment if you want logging)
    // error_log("Successful login for user: {$username} (ID: {$user['id']}) from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // Update last login time in database (optional)
    try {
        $update_stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$user['id']]);
    } catch (PDOException $e) {
        // Continue even if update fails
    }
    
    // Redirect to role-specific dashboard
    if ($user['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } elseif ($user['role'] === 'faculty') {
        header('Location: faculty_dashboard.php');
    } elseif ($user['role'] === 'student') {
        header('Location: student_dashboard.php');
    } else {
        throw new Exception('Invalid user role.');
    }
    exit();
    
} catch (Exception $e) {
    // Generate new CAPTCHA for next attempt
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['captcha_answer'] = $num1 + $num2;
    $_SESSION['captcha_question'] = "$num1 + $num2";
    
    // Regenerate CSRF token on error for security
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // Redirect back to login page with error
    $error_message = urlencode($e->getMessage());
    header("Location: index.php?error={$error_message}");
    exit();
}

// If we somehow get here, redirect to login
header('Location: index.php');
exit();
?>