<?php
/**
 * Contact Admin Page
 * Allows faculty and students to send messages to administrators
 * Adapts styling from admin dashboard
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config/database.php';

// Ensure user is logged in and role is faculty or student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty', 'student'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle POST request to send message to admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    try {
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $priority = $_POST['priority'] ?? 'normal';

        if (empty($subject) || empty($message)) {
            throw new Exception('Subject and message are required.');
        }

        if (strlen($subject) < 3) {
            throw new Exception('Subject must be at least 3 characters long.');
        }

        if (strlen($message) < 10) {
            throw new Exception('Message must be at least 10 characters long.');
        }

        // Verify database connection
        if (!$pdo) {
            throw new Exception('Database connection failed.');
        }

        // Create contact_admin_messages table if it doesn't exist
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS contact_admin_messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                role ENUM('faculty', 'student') NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
                status ENUM('pending', 'read', 'resolved') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            error_log("Table creation error: " . $e->getMessage());
        }

        // Insert message into contact_admin_messages table
        $stmt = $pdo->prepare("INSERT INTO contact_admin_messages (user_id, role, subject, message, priority, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $user_role, $subject, $message, $priority]);
        $message_id = $pdo->lastInsertId();

        // Get user details
        $user_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user_data = $user_stmt->fetch();
        $sender_name = $user_data['first_name'] . ' ' . $user_data['last_name'];

        // Format notification message with priority indicator
        $priority_emoji = '';
        switch ($priority) {
            case 'urgent':
                $priority_emoji = '🚨 [URGENT] ';
                break;
            case 'high':
                $priority_emoji = '⚠️ [HIGH] ';
                break;
            case 'low':
                $priority_emoji = 'ℹ️ [LOW] ';
                break;
            default:
                $priority_emoji = '📧 ';
        }

        $notification_message = "{$priority_emoji}New message from {$sender_name} ({$user_role}): {$subject}";

        // Send notification to ALL administrators
        $admin_stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
        $admins = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($admins)) {
            error_log("Warning: No admin users found to notify");
        }

        foreach ($admins as $admin_id) {
            // Insert notification directly into notifications table
            $notify_stmt = $pdo->prepare("INSERT INTO notifications (message, sender_id, receiver_id, recipient_role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
            $notify_stmt->execute([$notification_message, $user_id, $admin_id]);
        }

        error_log("Contact admin message sent successfully. Message ID: {$message_id}, Notified " . count($admins) . " admin(s)");

        $success_message = 'Your message has been sent to the administrators successfully!';
        header("Location: contact_admin.php?success=" . urlencode($success_message));
        exit();

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        header("Location: contact_admin.php?error=" . urlencode($error_message));
        exit();
    }
}

// Fetch user's previous messages (check if table exists first)
try {
    // Check if table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'contact_admin_messages'")->fetch();
    
    if ($table_check) {
        $stmt = $pdo->prepare("SELECT * FROM contact_admin_messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$user_id]);
        $previous_messages = $stmt->fetchAll();
    } else {
        $previous_messages = [];
        error_log("contact_admin_messages table does not exist yet");
    }
} catch (PDOException $e) {
    error_log("Error fetching previous messages: " . $e->getMessage());
    $previous_messages = [];
}

// Get message statistics (only if table exists)
$total_count = 0;
$pending_count = 0;
$resolved_count = 0;

try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'contact_admin_messages'")->fetch();
    
    if ($table_check) {
        $total_messages = $pdo->prepare("SELECT COUNT(*) FROM contact_admin_messages WHERE user_id = ?");
        $total_messages->execute([$user_id]);
        $total_count = $total_messages->fetchColumn();

        $pending_messages = $pdo->prepare("SELECT COUNT(*) FROM contact_admin_messages WHERE user_id = ? AND status = 'pending'");
        $pending_messages->execute([$user_id]);
        $pending_count = $pending_messages->fetchColumn();

        $resolved_messages = $pdo->prepare("SELECT COUNT(*) FROM contact_admin_messages WHERE user_id = ? AND status = 'resolved'");
        $resolved_messages->execute([$user_id]);
        $resolved_count = $resolved_messages->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Error fetching message statistics: " . $e->getMessage());
}

// Check for success/error messages
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// Set page title and include template
$page_title = 'Contact Admin';
require_once 'includes/admin_template.php';
require_once 'includes/cards.php';
?>

<!-- Page Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-envelope text-primary me-2"></i>Contact Administrator
        </h2>
    </div>

    <!-- Success/Error Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Message Statistics Cards -->
    <section class="dashboard-section mb-4">
        <div class="row">
            <?php
            render_metric_card('Total Messages', $total_count, 'fa-envelope', 'text-primary');
            render_metric_card('Pending', $pending_count, 'fa-clock', 'text-warning');
            render_metric_card('Resolved', $resolved_count, 'fa-check-circle', 'text-success');
            ?>
            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <div class="card p-4 rounded shadow-sm text-center" style="background-color: var(--color-card); color: var(--color-text-primary);">
                    <div class="card-body">
                        <i class="fas fa-user-shield fa-2x mb-2 text-info"></i>
                        <h5 class="card-title display-6">Admin</h5>
                        <p class="card-text text-muted fs-6">Support Team</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Message Form -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-paper-plane me-2"></i>Send a Message
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Need help?</strong> Use this form to contact the administration team. We'll respond as soon as possible.
                </div>

                <form method="POST" novalidate>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="subject" class="form-label">
                                <i class="fas fa-heading me-1"></i>Subject <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="subject" id="subject" class="form-control" 
                                   placeholder="Brief description of your inquiry..." 
                                   required minlength="3" maxlength="255">
                            <small class="form-text text-muted">Minimum 3 characters</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="priority" class="form-label">
                                <i class="fas fa-exclamation-triangle me-1"></i>Priority
                            </label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label">
                            <i class="fas fa-comment-dots me-1"></i>Message <span class="text-danger">*</span>
                        </label>
                        <textarea name="message" id="message" rows="8" class="form-control" 
                                  placeholder="Describe your inquiry or concern in detail..."
                                  required minlength="10"></textarea>
                        <small class="form-text text-muted">Minimum 10 characters</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="send_message" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>Clear Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Previous Messages -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>Message History
                </h5>
                <span class="badge bg-light text-dark"><?php echo count($previous_messages); ?> message(s)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($previous_messages)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No Previous Messages</h5>
                        <p class="text-muted">You haven't sent any messages yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%">
                                        <i class="fas fa-heading me-1"></i>Subject
                                    </th>
                                    <th width="40%">
                                        <i class="fas fa-comment me-1"></i>Message
                                    </th>
                                    <th width="10%">
                                        <i class="fas fa-flag me-1"></i>Priority
                                    </th>
                                    <th width="10%">
                                        <i class="fas fa-info-circle me-1"></i>Status
                                    </th>
                                    <th width="15%">
                                        <i class="fas fa-calendar me-1"></i>Sent Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($previous_messages as $msg): ?>
                                    <?php
                                    // Status badge styling
                                    $status_class = '';
                                    $status_icon = '';
                                    switch ($msg['status']) {
                                        case 'pending':
                                            $status_class = 'bg-warning text-dark';
                                            $status_icon = 'fa-clock';
                                            break;
                                        case 'read':
                                            $status_class = 'bg-info';
                                            $status_icon = 'fa-eye';
                                            break;
                                        case 'resolved':
                                            $status_class = 'bg-success';
                                            $status_icon = 'fa-check-circle';
                                            break;
                                    }

                                    // Priority badge styling
                                    $priority_class = '';
                                    switch ($msg['priority']) {
                                        case 'low':
                                            $priority_class = 'bg-secondary';
                                            break;
                                        case 'normal':
                                            $priority_class = 'bg-primary';
                                            break;
                                        case 'high':
                                            $priority_class = 'bg-warning text-dark';
                                            break;
                                        case 'urgent':
                                            $priority_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($msg['subject']); ?></strong>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . (strlen($msg['message']) > 100 ? '...' : ''); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $priority_class; ?>">
                                                <?php echo ucfirst($msg['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <i class="fas <?php echo $status_icon; ?> me-1"></i>
                                                <?php echo ucfirst($msg['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo date('M j, Y', strtotime($msg['created_at'])); ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Help Section -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="card-title mb-3">
                <i class="fas fa-question-circle me-2"></i>Need Help?
            </h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-clock text-primary me-3 mt-1"></i>
                        <div>
                            <strong>Response Time</strong>
                            <p class="text-muted small mb-0">We typically respond within 24-48 hours during business days.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-exclamation-triangle text-warning me-3 mt-1"></i>
                        <div>
                            <strong>Priority Levels</strong>
                            <p class="text-muted small mb-0">Use 'Urgent' only for critical issues requiring immediate attention.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-info-circle text-info me-3 mt-1"></i>
                        <div>
                            <strong>Track Messages</strong>
                            <p class="text-muted small mb-0">Check the message history below to track the status of your inquiries.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Styles -->
<style>
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #5568d3 0%, #65408b 100%);
        transform: translateY(-1px);
    }
    
    .btn-outline-secondary {
        transition: all 0.2s ease;
    }
    
    .btn-outline-secondary:hover {
        transform: translateY(-1px);
    }
    
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
        transition: background-color 0.2s ease;
    }
    
    .alert {
        border-left: 4px solid;
    }
    
    .alert-success {
        border-left-color: #28a745;
    }
    
    .alert-danger {
        border-left-color: #dc3545;
    }
    
    .alert-info {
        border-left-color: #17a2b8;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 150px;
    }
</style>

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

    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            
            if (subject.length < 3) {
                e.preventDefault();
                alert('Subject must be at least 3 characters long.');
                return false;
            }
            
            if (message.length < 10) {
                e.preventDefault();
                alert('Message must be at least 10 characters long.');
                return false;
            }
        });
    }
</script>
</body>
</html>