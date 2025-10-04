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

$role = $_SESSION['role'] ?? '';

$dashboard_link = '';
if ($role === 'faculty') {
    $dashboard_link = 'faculty_dashboard.php';
} elseif ($role === 'student') {
    $dashboard_link = 'student_dashboard.php';
} elseif ($role === 'admin') {
    $dashboard_link = 'admin_dashboard.php';
} else {
    $dashboard_link = 'dashboard.php'; // fallback
}

$current_page = basename(__FILE__);

// Settings page container with links to subpages
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
</style>

<main class="dashboard-content container-fluid p-4">
    <h2 class="mb-4">Settings</h2>
    <div class="row g-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow rounded h-100 settings-card">
                <div class="card-body p-4 text-center">
                    <i class="bi bi-shield-lock fs-1 mb-3" style="color: var(--color-primary);"></i>
                    <h5 class="card-title">Privacy & Security</h5>
                    <p class="card-text">Update your password and manage login sessions.</p>
                    <a href="privacy_security.php" class="btn btn-primary">Go to Privacy & Security</a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow rounded h-100 settings-card">
                <div class="card-body p-4 text-center">
                    <i class="bi bi-palette fs-1 mb-3" style="color: var(--color-primary);"></i>
                    <h5 class="card-title">Appearance & UI Preferences</h5>
                    <p class="card-text">Customize theme, color preferences, and layout options.</p>
                    <a href="appearance_ui.php" class="btn btn-primary">Go to Appearance & UI</a>
                </div>
            </div>
        </div>

        <?php if ($role === 'faculty' || $role === 'student'): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow rounded h-100 settings-card">
                <div class="card-body p-4 text-center">
                    <i class="bi bi-envelope fs-1 mb-3" style="color: var(--color-primary);"></i>
                    <h5 class="card-title">Contact Admin</h5>
                    <p class="card-text">Send messages or requests to the administrator.</p>
                    <a href="contact_admin.php" class="btn btn-primary">Go to Contact Admin</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Link to external JavaScript file -->
<script src="js/script.js"></script>
<script src="js/theme.js"></script>
</body>
</html>
