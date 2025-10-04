<?php
// Start session and include database connection
session_start();
require_once 'config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user appearance preferences from database
$stmt = $pdo->prepare("SELECT theme, color_scheme, font_family, font_size, layout_preference FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_settings = $stmt->fetch();
$theme = $user_settings['theme'] ?? 'light';
$color_scheme = $user_settings['color_scheme'] ?? 'default';
$font_family = $user_settings['font_family'] ?? 'default';
$font_size = $user_settings['font_size'] ?? 'medium';
$layout_preference = $user_settings['layout_preference'] ?? 'standard';

$dashboard_link = '';
if ($_SESSION['role'] == 'faculty') {
    $dashboard_link = 'faculty_dashboard.php';
} elseif ($_SESSION['role'] == 'student') {
    $dashboard_link = 'student_dashboard.php';
} elseif ($_SESSION['role'] == 'admin') {
    $dashboard_link = 'admin_dashboard.php';
}

// Handle POST requests for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception('All fields are required.');
        }

        if ($new_password !== $confirm_password) {
            throw new Exception('New passwords do not match.');
        }

        if (strlen($new_password) < 6) {
            throw new Exception('New password must be at least 6 characters long.');
        }

        // Fetch current password hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current_password, $user['password'])) {
            throw new Exception('Current password is incorrect.');
        }

        // Update password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_password_hash, $user_id]);

        $success_message = 'Password changed successfully!';

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle session management (logout from all devices)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_all'])) {
    try {
        // For simplicity, just destroy current session. In a real app, you'd invalidate all sessions.
        session_destroy();
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<?php
$page_title = 'Settings';
require_once 'includes/admin_template.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
    .settings-card {
        transition: all 0.3s ease;
        background-color: var(--color-card);
        color: var(--color-text-primary);
    }
    .settings-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    .password-input-wrapper {
        position: relative;
    }
    .password-toggle-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        font-size: 1.1rem;
        z-index: 10;
    }
    .password-toggle-icon:hover {
        color: #495057;
    }
    .show-password-checkbox {
        margin-top: 10px;
    }
    .show-password-checkbox input {
        cursor: pointer;
    }
    .show-password-checkbox label {
        cursor: pointer;
        user-select: none;
    }
</style>

<body data-theme="<?php echo htmlspecialchars($theme); ?>" data-color-scheme="<?php echo htmlspecialchars($color_scheme); ?>" data-font-family="<?php echo htmlspecialchars($font_family); ?>" data-font-size="<?php echo htmlspecialchars($font_size); ?>" data-layout="<?php echo htmlspecialchars($layout_preference); ?>">
    <div class="dashboard-container">
  <div class="dashboard-container">
        <main class="dashboard-content container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Privacy & Security</h2>
                <a href="settings.php" class="btn btn-secondary" style="transition: all 0.3s ease;">Back to Settings</a>
            </div>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="card shadow rounded mb-4" style="background-color: var(--color-card); color: var(--color-text-primary); border: none; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4 text-center">Change Password</h5>
                            <form method="POST" novalidate>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold">Current Password</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" name="current_password" id="current_password" required class="form-control" style="border-radius: 8px; padding-right: 40px;">
                                        <i class="bi bi-eye password-toggle-icon" onclick="togglePassword('current_password', this)"></i>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-semibold">New Password</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" name="new_password" id="new_password" required class="form-control" minlength="6" style="border-radius: 8px; padding-right: 40px;">
                                        <i class="bi bi-eye password-toggle-icon" onclick="togglePassword('new_password', this)"></i>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                                    <div class="password-input-wrapper">
                                        <input type="password" name="confirm_password" id="confirm_password" required class="form-control" minlength="6" style="border-radius: 8px; padding-right: 40px;">
                                        <i class="bi bi-eye password-toggle-icon" onclick="togglePassword('confirm_password', this)"></i>
                                    </div>
                                </div>
                                
                                <!-- Show All Passwords Checkbox -->
                                <div class="show-password-checkbox mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_all_passwords" onchange="toggleAllPasswords(this)">
                                        <label class="form-check-label" for="show_all_passwords">
                                            Show all passwords
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" name="change_password" class="btn btn-primary btn-lg w-100" style="border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="card shadow rounded mb-4" style="background-color: var(--color-card); color: var(--color-text-primary); border: none; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4 text-center">Session Management</h5>
                            <p class="text-muted mb-4">Manage your active sessions and security settings.</p>
                            <form method="POST">
                                <button type="submit" name="logout_all" class="btn btn-warning btn-lg w-100" style="border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1);'" onclick="return confirm('Are you sure you want to logout from all devices?')">Logout from All Devices</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-12">
                    <div class="card shadow rounded mb-4" style="background-color: var(--color-card); color: var(--color-text-primary); border: none; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4 text-center">Two-Factor Authentication</h5>
                            <p class="text-muted mb-4">Add an extra layer of security to your account.</p>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_2fa" style="width: 3em; height: 1.5em;">
                                <label class="form-check-label fw-semibold" for="enable_2fa">Enable 2FA</label>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-lg w-100" style="border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1);'">Setup 2FA</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <a href="logout.php" class="btn btn-primary">Yes</a>
            </div>
        </div>
    </div>
    </div>

    <script>
        // Toggle individual password field visibility
        function togglePassword(fieldId, icon) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Toggle all password fields at once
        function toggleAllPasswords(checkbox) {
            const passwordFields = [
                document.getElementById('current_password'),
                document.getElementById('new_password'),
                document.getElementById('confirm_password')
            ];
            
            const icons = document.querySelectorAll('.password-toggle-icon');
            
            if (checkbox.checked) {
                // Show all passwords
                passwordFields.forEach(field => {
                    field.type = 'text';
                });
                icons.forEach(icon => {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                });
            } else {
                // Hide all passwords
                passwordFields.forEach(field => {
                    field.type = 'password';
                });
                icons.forEach(icon => {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                });
            }
        }

        // Sync the checkbox state when individual icons are clicked
        document.querySelectorAll('.password-toggle-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                setTimeout(() => {
                    const allVisible = Array.from(document.querySelectorAll('input[type="password"]')).length === 0;
                    document.getElementById('show_all_passwords').checked = allVisible;
                }, 100);
            });
        });
    </script>

    <script src="js/script.js"></script>
    <script src="js/theme.js"></script>
</body>
</html>