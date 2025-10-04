<?php
/**
 * Secure Signup Page with Extended Profile Fields
 * Handles user registration with enhanced security features and complete profile setup
 * - CSRF protection
 * - Input validation
 * - Password strength requirements
 * - Email validation
 * - Student academic information (course, year_level, academic_track, status)
 * - Additional profile fields (phone, bio)
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

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

// Handle POST request (signup submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid security token. Please refresh and try again.');
        }

        // Sanitize and validate inputs
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
            // Student academic fields
            $course = trim($_POST['course'] ?? '');
            $year_level = trim($_POST['year_level'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $academic_track = $_POST['academic_track'] ?? 'regular';
            $status = $_POST['status'] ?? 'not_enrolled';
            
            $role = 'student'; // Default role for new signups

        // Validation checks
        if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
            throw new Exception('First name, last name, username, email, and password are required.');
        }

        // Name validation
        if (strlen($first_name) < 2 || strlen($first_name) > 50) {
            throw new Exception('First name must be between 2 and 50 characters.');
        }

        if (strlen($last_name) < 2 || strlen($last_name) > 50) {
            throw new Exception('Last name must be between 2 and 50 characters.');
        }

        // Only allow letters, spaces, hyphens in names
        if (!preg_match("/^[a-zA-Z\s\-]+$/", $first_name) || !preg_match("/^[a-zA-Z\s\-]+$/", $last_name)) {
            throw new Exception('Names can only contain letters, spaces, and hyphens.');
        }

        // Username validation
        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new Exception('Username must be between 3 and 50 characters.');
        }

        // Only allow alphanumeric and underscore in username
        if (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
            throw new Exception('Username can only contain letters, numbers, and underscores.');
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        // Course validation (optional but limit length)
        if (!empty($course) && strlen($course) > 100) {
            throw new Exception('Course name must not exceed 100 characters.');
        }

        // Year level validation (optional but limit length)
        if (!empty($year_level) && strlen($year_level) > 20) {
            throw new Exception('Year level must not exceed 20 characters.');
        }

        // Department validation (optional but limit length)
        if (!empty($department) && strlen($department) > 100) {
            throw new Exception('Department name must not exceed 100 characters.');
        }

        // Password validation
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long.');
        }

        // Check password strength
        if (!preg_match("/[A-Z]/", $password)) {
            throw new Exception('Password must contain at least one uppercase letter.');
        }

        if (!preg_match("/[a-z]/", $password)) {
            throw new Exception('Password must contain at least one lowercase letter.');
        }

        if (!preg_match("/[0-9]/", $password)) {
            throw new Exception('Password must contain at least one number.');
        }

        // Confirm password match
        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }

        // Check for existing username or email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            throw new Exception('Username or email already exists. Please choose another.');
        }

        // Hash password securely
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into database with extended fields
        $stmt = $pdo->prepare("INSERT INTO users 
            (username, email, password, role, first_name, last_name, 
             course, year_level, department, academic_track, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->execute([
            $username, 
            $email, 
            $password_hash, 
            $role, 
            $first_name, 
            $last_name, 
            $course ?: null,
            $year_level ?: null,
            $department ?: null,
            $academic_track,
            $status
        ]);

        // Log successful registration
        error_log("New user registered: {$username} (ID: " . $pdo->lastInsertId() . ") from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        // Send notification to admins
        $admin_stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        $admins = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($admins as $admin_id) {
            $notify_message = "New student registered: {$first_name} {$last_name} ({$username})";
            $notify_stmt = $pdo->prepare("INSERT INTO notifications (message, sender_id, receiver_id, recipient_role, created_at) VALUES (?, NULL, ?, 'admin', NOW())");
            $notify_stmt->execute([$notify_message, $admin_id]);
        }

        // Redirect to login with success message
        header('Location: index.php?success=' . urlencode('Account created successfully! Please sign in.'));
        exit();

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        // Regenerate CSRF token on error
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Check for messages from URL parameters
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Sign Up - Edu.Konek</title>
    
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

        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 1200px;
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

        .signup-left {
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

        .signup-left img {
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

        .signup-left h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .signup-left p {
            font-size: 1rem;
            opacity: 0.95;
            line-height: 1.6;
        }

        .signup-right {
            flex: 1;
            padding: 40px 45px;
            overflow-y: auto;
        }

        .signup-header {
            margin-bottom: 25px;
        }

        .signup-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .signup-header p {
            color: #718096;
            font-size: 0.95rem;
        }

        .section-divider {
            display: flex;
            align-items: center;
            margin: 25px 0 20px;
            color: #667eea;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .section-divider::before,
        .section-divider::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, transparent, #e2e8f0);
        }

        .section-divider::after {
            background: linear-gradient(to left, transparent, #e2e8f0);
        }

        .section-divider span {
            padding: 0 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
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

        .form-label .optional {
            font-weight: 400;
            color: #a0aec0;
            font-size: 0.8rem;
        }

        .form-control, .form-select {
            padding: 11px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.93rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
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

        .password-strength {
            margin-top: 8px;
            font-size: 0.82rem;
        }

        .strength-bar {
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .strength-weak { 
            width: 33%; 
            background: #fc8181; 
        }

        .strength-medium { 
            width: 66%; 
            background: #f6ad55; 
        }

        .strength-strong { 
            width: 100%; 
            background: #68d391; 
        }

        .password-requirements {
            margin-top: 10px;
            padding: 10px;
            background: #f7fafc;
            border-radius: 8px;
            font-size: 0.82rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #718096;
            margin-bottom: 3px;
        }

        .requirement.met {
            color: #48bb78;
        }

        .requirement i {
            width: 14px;
            font-size: 0.75rem;
        }

        .btn-signup {
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

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-signup:active {
            transform: translateY(0);
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

        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .login-link p {
            color: #718096;
            font-size: 0.93rem;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .signup-left {
                flex: 0 0 300px;
            }
        }

        @media (max-width: 768px) {
            .signup-container {
                flex-direction: column;
                max-height: none;
            }

            .signup-left {
                padding: 35px 25px;
            }

            .signup-left h1 {
                font-size: 1.8rem;
            }

            .signup-right {
                padding: 35px 25px;
            }

            .signup-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <!-- Left Side - Branding -->
        <div class="signup-left">
            <img src="assets/images/Logo.png" alt="Edu.Konek Logo">
            <h1>Edu.Konek</h1>
            <p>Join our community and start your journey to academic excellence</p>
        </div>

        <!-- Right Side - Signup Form -->
        <div class="signup-right">
            <div class="signup-header">
                <h2>Create Account</h2>
                <p>Fill in your details to get started</p>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Signup Form -->
            <form method="POST" action="signup.php" id="signupForm">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Personal Information Section -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">
                            <i class="fas fa-user"></i> First Name
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="first_name" 
                               name="first_name" 
                               placeholder="John"
                               required 
                               minlength="2"
                               maxlength="50"
                               pattern="[a-zA-Z\s\-]+"
                               title="Only letters, spaces, and hyphens allowed">
                    </div>

                    <div class="form-group">
                        <label for="last_name" class="form-label">
                            <i class="fas fa-user"></i> Last Name
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="last_name" 
                               name="last_name" 
                               placeholder="Doe"
                               required 
                               minlength="2"
                               maxlength="50"
                               pattern="[a-zA-Z\s\-]+"
                               title="Only letters, spaces, and hyphens allowed">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-at"></i> Username
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="johndoe123"
                               required 
                               minlength="3"
                               maxlength="50"
                               pattern="[a-zA-Z0-9_]+"
                               title="Only letters, numbers, and underscores allowed">
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="john.doe@example.com"
                               required>
                    </div>
                </div>

                <!-- Academic Information Section -->
                <div class="section-divider">
                    <span><i class="fas fa-graduation-cap me-1"></i> Academic Information</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="course" class="form-label">
                            <i class="fas fa-book"></i> Course/Program <span class="optional">(Optional)</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="course" 
                               name="course" 
                               placeholder="e.g., BS Computer Science"
                               maxlength="100">
                    </div>

                    <div class="form-group">
                        <label for="year_level" class="form-label">
                            <i class="fas fa-layer-group"></i> Year Level <span class="optional">(Optional)</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="year_level" 
                               name="year_level" 
                               placeholder="e.g., 1st Year, 2nd Year"
                               maxlength="20">
                    </div>

                    <div class="form-group">
                        <label for="department" class="form-label">
                            <i class="fas fa-building"></i> Department <span class="optional">(Optional)</span>
                        </label>
                        <select class="form-select" id="department" name="department">
                            <option value="" selected>Select your department</option>
                            <option value="CBAT.COM">CBAT.COM</option>
                            <option value="CRIM">CRIM</option>
                            <option value="COTE">COTE</option>
                            <option value="High School">High School</option>
                        </select>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="section-divider">
                    <span><i class="fas fa-lock me-1"></i> Security</span>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter a strong password"
                               required
                               minlength="8">
                        <button type="button" class="toggle-password" onclick="togglePassword('password', 'toggleIcon1')" tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                    </div>
                    <div class="password-requirements">
                        <div class="requirement" id="req-length">
                            <i class="fas fa-circle"></i>
                            <span>At least 8 characters</span>
                        </div>
                        <div class="requirement" id="req-upper">
                            <i class="fas fa-circle"></i>
                            <span>One uppercase letter</span>
                        </div>
                        <div class="requirement" id="req-lower">
                            <i class="fas fa-circle"></i>
                            <span>One lowercase letter</span>
                        </div>
                        <div class="requirement" id="req-number">
                            <i class="fas fa-circle"></i>
                            <span>One number</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Re-enter your password"
                               required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'toggleIcon2')" tabindex="-1">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-signup">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>

            <!-- Login Link -->
            <div class="login-link">
                <p>Already have an account? <a href="index.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId, iconId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(iconId);
            
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

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            // Check requirements
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);

            // Update requirement indicators
            document.getElementById('req-length').classList.toggle('met', hasLength);
            document.getElementById('req-upper').classList.toggle('met', hasUpper);
            document.getElementById('req-lower').classList.toggle('met', hasLower);
            document.getElementById('req-number').classList.toggle('met', hasNumber);

            // Calculate strength
            if (hasLength) strength++;
            if (hasUpper) strength++;
            if (hasLower) strength++;
            if (hasNumber) strength++;

            // Update strength bar
            strengthFill.className = 'strength-fill';
            if (strength >= 4) {
                strengthFill.classList.add('strength-strong');
            } else if (strength >= 2) {
                strengthFill.classList.add('strength-medium');
            } else if (strength >= 1) {
                strengthFill.classList.add('strength-weak');
            } else {
                strengthFill.style.width = '0%';
            }
        });

        // Form validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }

            if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must contain uppercase, lowercase, and numbers.');
                return false;
            }
        });

        // Auto-dismiss alerts
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