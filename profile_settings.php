<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config/database.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            
            // Student-specific fields
            $course = trim($_POST['course'] ?? '');
            $year_level = trim($_POST['year_level'] ?? '');
            $academic_track = $_POST['academic_track'] ?? 'regular';
            $status = $_POST['status'] ?? 'not_enrolled';

            if (empty($first_name) || empty($last_name) || empty($email)) {
                throw new Exception('First name, last name, and email are required.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }

            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception('Email is already in use by another account.');
            }

            // Get user role to determine which fields to update
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_role = $stmt->fetchColumn();

            // Update profile based on role
            if ($user_role === 'student') {
                $stmt = $pdo->prepare("UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    bio = ?,
                    course = ?,
                    year_level = ?,
                    academic_track = ?,
                    status = ?
                    WHERE id = ?");
                $stmt->execute([
                    $first_name, 
                    $last_name, 
                    $email, 
                    $phone, 
                    $bio,
                    $course,
                    $year_level,
                    $academic_track,
                    $status,
                    $user_id
                ]);
            } else {
                // For admin and faculty, update without student-specific fields
                $stmt = $pdo->prepare("UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    bio = ?
                    WHERE id = ?");
                $stmt->execute([
                    $first_name, 
                    $last_name, 
                    $email, 
                    $phone, 
                    $bio,
                    $user_id
                ]);
            }

            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;

            $success_message = 'Profile updated successfully!';
            header("Location: profile_settings.php?success=" . urlencode($success_message));
            exit();
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        header("Location: profile_settings.php?error=" . urlencode($error_message));
        exit();
    }
}

// Check for success/error messages from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Fetch complete user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Generate color based on first letter
function getColorFromInitial($letter) {
    $colors = [
        'A' => '#667eea', 'B' => '#764ba2', 'C' => '#f093fb', 'D' => '#4facfe',
        'E' => '#00f2fe', 'F' => '#43e97b', 'G' => '#38f9d7', 'H' => '#fa709a',
        'I' => '#fee140', 'J' => '#30cfd0', 'K' => '#a8edea', 'L' => '#fed6e3',
        'M' => '#667eea', 'N' => '#f093fb', 'O' => '#4facfe', 'P' => '#43e97b',
        'Q' => '#fa709a', 'R' => '#fee140', 'S' => '#30cfd0', 'T' => '#764ba2',
        'U' => '#00f2fe', 'V' => '#38f9d7', 'W' => '#fed6e3', 'X' => '#a8edea',
        'Y' => '#fee140', 'Z' => '#667eea'
    ];
    $letter = strtoupper($letter);
    return isset($colors[$letter]) ? $colors[$letter] : '#667eea';
}

$initial = strtoupper(substr($user['first_name'], 0, 1));
$avatarColor = getColorFromInitial($initial);

// Format status for display
function formatStatus($status) {
    $statusMap = [
        'enrolled' => ['text' => 'Enrolled', 'class' => 'success'],
        'not_enrolled' => ['text' => 'Not Enrolled', 'class' => 'secondary'],
        'pending' => ['text' => 'Pending', 'class' => 'warning'],
        'dropped' => ['text' => 'Dropped', 'class' => 'danger'],
        'graduate' => ['text' => 'Graduate', 'class' => 'info']
    ];
    return $statusMap[$status] ?? ['text' => ucfirst($status), 'class' => 'secondary'];
}

// Set page title and include template
$page_title = 'Profile Settings';
require_once 'includes/admin_template.php';
?>

<main class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="fas fa-user-edit me-2"></i>Profile Settings
    </h2>

    <!-- Success/Error Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Overview Card -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>Profile Overview
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <!-- Initial Avatar -->
                        <div class="avatar-initial mb-3" style="background: <?php echo $avatarColor; ?>;">
                            <?php echo $initial; ?>
                        </div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p class="text-muted mb-2">
                            <i class="fas fa-id-badge me-1"></i>
                            <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-envelope me-1"></i>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        <?php if (!empty($user['phone'])): ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-phone me-1"></i>
                            <?php echo htmlspecialchars($user['phone']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($user['bio'])): ?>
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="text-muted mb-2"><i class="fas fa-quote-left me-1"></i>Bio</h6>
                        <p class="text-start small"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                    </small>
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Quick Stats
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($user['role'] === 'student'): ?>
                        <?php 
                        $statusInfo = formatStatus($user['status']); 
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <i class="fas fa-graduation-cap text-primary me-2"></i>
                                <strong>Enrollment Status</strong>
                            </div>
                            <span class="badge bg-<?php echo $statusInfo['class']; ?>">
                                <?php echo $statusInfo['text']; ?>
                            </span>
                        </div>
                        <?php if (!empty($user['course'])): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <i class="fas fa-book text-info me-2"></i>
                                <strong>Course</strong>
                            </div>
                            <small class="text-muted text-end"><?php echo htmlspecialchars($user['course']); ?></small>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($user['year_level'])): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <i class="fas fa-layer-group text-warning me-2"></i>
                                <strong>Year Level</strong>
                            </div>
                            <span class="badge bg-info"><?php echo htmlspecialchars($user['year_level']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-route text-success me-2"></i>
                                <strong>Track</strong>
                            </div>
                            <span class="badge bg-<?php echo $user['academic_track'] === 'regular' ? 'success' : 'warning'; ?>">
                                <?php echo htmlspecialchars(ucfirst($user['academic_track'])); ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <i class="fas fa-user-check text-success me-2"></i>
                                <strong>Status</strong>
                            </div>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div>
                                <i class="fas fa-clock text-info me-2"></i>
                                <strong>Last Login</strong>
                            </div>
                            <small class="text-muted">Today</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-shield-alt text-warning me-2"></i>
                                <strong>Account Security</strong>
                            </div>
                            <span class="badge bg-warning">Good</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Forms -->
        <div class="col-lg-8">
            <!-- Personal Information -->
            <section class="dashboard-section mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Personal Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile_settings.php">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">
                                        <i class="fas fa-user me-1"></i>First Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="first_name" 
                                           name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">
                                        <i class="fas fa-user me-1"></i>Last Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="last_name" 
                                           name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-at me-1"></i>Username
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       disabled>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>
                            
                            <?php if ($user['role'] === 'student'): ?>
                            <!-- Student-specific fields -->
                            <hr class="my-4">
                            <h6 class="mb-3"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="course" class="form-label">
                                        <i class="fas fa-book me-1"></i>Course/Program
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="course" 
                                           name="course" 
                                           value="<?php echo htmlspecialchars($user['course'] ?? ''); ?>" 
                                           placeholder="e.g., BS Computer Science">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="year_level" class="form-label">
                                            <i class="fas fa-book me-1"></i>Year Level
                                        </label>
                                        <input type="text" 
                                            class="form-control" 
                                            id="year_level" 
                                            name="year_level" 
                                            value="<?php echo htmlspecialchars($user['year_level'] ?? ''); ?>" 
                                            placeholder="e.g., 1st Year">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="section" class="form-label">
                                                <i class="fas fa-book me-1"></i>Section
                                            </label>
                                            <input type="text" 
                                                class="form-control" 
                                                id="section" 
                                                name="section" 
                                                value="<?php echo htmlspecialchars($user['section'] ?? ''); ?>" 
                                                placeholder="e.g., 1-A">
                                        </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Account Information -->
            <section class="dashboard-section mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-id-card me-2"></i>Account Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold" style="width: 200px;">
                                            <i class="fas fa-hashtag me-2 text-muted"></i>User ID
                                        </td>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">
                                            <i class="fas fa-user-tag me-2 text-muted"></i>Account Type
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $user['role'] === 'admin' ? 'danger' : 
                                                     ($user['role'] === 'faculty' ? 'warning' : 'success');
                                            ?>">
                                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">
                                            <i class="fas fa-calendar-plus me-2 text-muted"></i>Account Created
                                        </td>
                                        <td><?php echo date('F j, Y \a\t g:i A', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">
                                            <i class="fas fa-clock me-2 text-muted"></i>Last Updated
                                        </td>
                                        <td><?php echo date('F j, Y \a\t g:i A', strtotime($user['updated_at'])); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>

<style>
    /* Avatar Initial Styling */
    .avatar-initial {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        font-weight: 700;
        color: white;
        text-transform: uppercase;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        margin: 0 auto;
        animation: avatarPulse 2s ease-in-out infinite;
        position: relative;
        overflow: hidden;
    }

    .avatar-initial::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transform: rotate(45deg);
        animation: shimmer 3s infinite;
    }

    @keyframes avatarPulse {
        0%, 100% {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        50% {
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%) translateY(-100%) rotate(45deg);
        }
        100% {
            transform: translateX(100%) translateY(100%) rotate(45deg);
        }
    }

    .card {
        border: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .table td {
        padding: 1rem;
        vertical-align: middle;
    }
</style>
</body>
</html>