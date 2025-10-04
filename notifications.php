<?php
/**
 * Notifications Page
 * Displays all notifications for the logged-in user
 * Includes reply functionality for admins
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

// Handle notification deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notification_id = $_POST['notification_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND receiver_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        
        $success_message = 'Notification deleted successfully!';
        header("Location: notifications.php?success=" . urlencode($success_message));
        exit();
    } catch (Exception $e) {
        $error_message = 'Failed to delete notification.';
        header("Location: notifications.php?error=" . urlencode($error_message));
        exit();
    }
}

// Handle reply to notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_notification'])) {
    $original_sender_id = $_POST['original_sender_id'];
    $reply_message = trim($_POST['reply_message']);
    $original_message = trim($_POST['original_message']);
    
    try {
        if (empty($reply_message)) {
            throw new Exception('Reply message cannot be empty.');
        }
        
        // Format the reply to include the original message context
        $formatted_reply = "Re: " . substr($original_message, 0, 50) . (strlen($original_message) > 50 ? '...' : '') . "\n\n";
        $formatted_reply .= "Your message: \"" . $original_message . "\"\n\n";
        $formatted_reply .= "Reply: " . $reply_message;
        
        // Insert reply as new notification to the original sender
        $stmt = $pdo->prepare("INSERT INTO notifications (message, sender_id, receiver_id, recipient_role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$formatted_reply, $user_id, $original_sender_id, 'admin']);
        
        $success_message = 'Reply sent successfully!';
        header("Location: notifications.php?success=" . urlencode($success_message));
        exit();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        header("Location: notifications.php?error=" . urlencode($error_message));
        exit();
    }
}

// Get filter parameter
$filter_type = $_GET['filter'] ?? 'all';

// Build query based on filter
$query = "SELECT n.*, u.first_name, u.last_name, u.role as sender_role
          FROM notifications n 
          LEFT JOIN users u ON n.sender_id = u.id 
          WHERE n.receiver_id = ?";

$params = [$user_id];

// Add filter logic
if ($filter_type === 'recent') {
    $query .= " AND n.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter_type === 'older') {
    $query .= " AND n.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

$query .= " ORDER BY n.created_at DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$user_notifications = $stmt->fetchAll();

// Get notification statistics
$total_notifications = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ?");
$total_notifications->execute([$user_id]);
$total_count = $total_notifications->fetchColumn();

$recent_notifications = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$recent_notifications->execute([$user_id]);
$recent_count = $recent_notifications->fetchColumn();

$older_notifications = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
$older_notifications->execute([$user_id]);
$older_count = $older_notifications->fetchColumn();

// Check for success/error messages
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';

// Set page title and include template
$page_title = 'Notifications';
require_once 'includes/admin_template.php';
require_once 'includes/cards.php';
?>

<!-- Page Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-bell text-primary me-2"></i>My Notifications
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

    <!-- Notification Statistics Cards -->
    <section class="dashboard-section mb-4">
        <div class="row">
            <?php
            render_metric_card('Total Notifications', $total_count, 'fa-bell', 'text-primary');
            render_metric_card('Recent (7 days)', $recent_count, 'fa-bell-on', 'text-success');
            render_metric_card('Older', $older_count, 'fa-bell-slash', 'text-secondary');
            ?>
            <div class="col-12 col-sm-6 col-md-3 col-lg-3">
                <div class="card p-4 rounded shadow-sm text-center" style="background-color: var(--color-card); color: var(--color-text-primary);">
                    <div class="card-body">
                        <i class="fas fa-info-circle fa-2x mb-2 text-info"></i>
                        <h5 class="card-title display-6"><?php echo ucfirst($user_role); ?></h5>
                        <p class="card-text text-muted fs-6">Your Role</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Options -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="btn-group" role="group">
                    <a href="notifications.php?filter=all" 
                       class="btn btn-<?php echo $filter_type === 'all' ? 'primary' : 'outline-primary'; ?>">
                        <i class="fas fa-list me-1"></i>All Notifications
                    </a>
                    <a href="notifications.php?filter=recent" 
                       class="btn btn-<?php echo $filter_type === 'recent' ? 'primary' : 'outline-primary'; ?>">
                        <i class="fas fa-clock me-1"></i>Recent (7 days)
                    </a>
                    <a href="notifications.php?filter=older" 
                       class="btn btn-<?php echo $filter_type === 'older' ? 'primary' : 'outline-primary'; ?>">
                        <i class="fas fa-history me-1"></i>Older
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <section class="dashboard-section mb-5">
        <?php if (empty($user_notifications)): ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted mb-2">No Notifications</h4>
                    <p class="text-muted">
                        <?php if ($filter_type === 'recent'): ?>
                            You don't have any notifications from the past 7 days.
                        <?php elseif ($filter_type === 'older'): ?>
                            You don't have any older notifications.
                        <?php else: ?>
                            You don't have any notifications at the moment.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($user_notifications as $notification): ?>
                    <?php
                    // Determine how old the notification is
                    $created_date = new DateTime($notification['created_at']);
                    $now = new DateTime();
                    $diff = $now->diff($created_date);
                    
                    $is_new = $diff->days < 1;
                    $is_recent = $diff->days <= 7;
                    
                    // Determine card styling
                    $card_class = '';
                    $badge_class = '';
                    $badge_text = '';
                    
                    if ($is_new) {
                        $card_class = 'border-primary';
                        $badge_class = 'bg-primary';
                        $badge_text = 'New';
                    } elseif ($is_recent) {
                        $card_class = 'border-success';
                        $badge_class = 'bg-success';
                        $badge_text = 'Recent';
                    } else {
                        $badge_class = 'bg-secondary';
                        $badge_text = 'Old';
                    }
                    
                    // Format time ago
                    if ($diff->days == 0) {
                        if ($diff->h == 0) {
                            $time_ago = $diff->i . ' minute' . ($diff->i != 1 ? 's' : '') . ' ago';
                        } else {
                            $time_ago = $diff->h . ' hour' . ($diff->h != 1 ? 's' : '') . ' ago';
                        }
                    } elseif ($diff->days == 1) {
                        $time_ago = 'Yesterday';
                    } elseif ($diff->days < 7) {
                        $time_ago = $diff->days . ' days ago';
                    } else {
                        $time_ago = date('M j, Y', strtotime($notification['created_at']));
                    }
                    
                    // Check if sender exists and is not the system
                    $can_reply = !empty($notification['sender_id']) && $notification['sender_id'] != $user_id;
                    ?>
                    <div class="col-12 col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm notification-card <?php echo $card_class; ?>">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-envelope text-primary me-2"></i>
                                    <strong>Notification</strong>
                                </div>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                            </div>
                            <div class="card-body">
                                <p class="card-text mb-3">
                                    <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                </p>
                                
                                <div class="notification-meta">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-user-circle text-muted me-2"></i>
                                        <small class="text-muted">
                                            From: <strong><?php echo htmlspecialchars(($notification['first_name'] ?? '') . ' ' . ($notification['last_name'] ?? '') ?: 'System'); ?></strong>
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="far fa-clock text-muted me-2"></i>
                                        <small class="text-muted"><?php echo $time_ago; ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </small>
                                    <div class="btn-group">
                                        <?php if ($can_reply): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#replyModal<?php echo $notification['id']; ?>"
                                                title="Reply">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this notification?');">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reply Modal -->
                    <?php if ($can_reply): ?>
                    <div class="modal fade" id="replyModal<?php echo $notification['id']; ?>" tabindex="-1" aria-labelledby="replyModalLabel<?php echo $notification['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content shadow-lg" style="border-radius: 0.5rem; overflow: hidden; border: none;">
                                <form method="POST" action="notifications.php">
                                    <div class="modal-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.25rem 1.5rem;">
                                        <h5 class="modal-title mb-0" id="replyModalLabel<?php echo $notification['id']; ?>" style="font-size: 1.25rem; font-weight: 600;">
                                            <i class="fas fa-reply me-2"></i> Reply to <?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4" style="background-color: #ffffff;">
                                        <input type="hidden" name="reply_notification" value="1">
                                        <input type="hidden" name="original_sender_id" value="<?php echo $notification['sender_id']; ?>">
                                        <input type="hidden" name="original_message" value="<?php echo htmlspecialchars($notification['message']); ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold text-dark mb-2" style="font-size: 0.95rem;">
                                                <i class="fas fa-envelope me-2 text-muted"></i> Original Message
                                            </label>
                                            <div class="p-3 bg-light rounded" style="border-left: 4px solid #667eea;">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?>
                                                    <span class="ms-2">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?php echo $time_ago; ?>
                                                    </span>
                                                </small>
                                                <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="reply_message_<?php echo $notification['id']; ?>" class="form-label fw-semibold text-dark mb-2" style="font-size: 0.95rem;">
                                                <i class="fas fa-comment-dots me-2 text-muted"></i> Your Reply
                                            </label>
                                            <textarea class="form-control" name="reply_message" id="reply_message_<?php echo $notification['id']; ?>" rows="5" required placeholder="Type your reply here..." style="border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.625rem 0.875rem; font-size: 1rem;"></textarea>
                                            <small class="text-muted mt-1 d-block">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Your reply will include the original message for context
                                            </small>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light" style="padding: 1rem 1.5rem; border-top: 1px solid #dee2e6;">
                                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 0.375rem; font-weight: 500; padding: 0.625rem 1.25rem;">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </button>
                                        <button type="submit" class="btn text-white px-4 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0.375rem; font-weight: 500; padding: 0.625rem 1.25rem;">
                                            <i class="fas fa-paper-plane me-1"></i> Send Reply
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Info -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Showing <?php echo count($user_notifications); ?> notification(s)
                            <?php if ($filter_type !== 'all'): ?>
                                with active filter
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Legend -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="card-title mb-3">
                <i class="fas fa-info-circle me-2"></i>Legend
            </h6>
            <div class="d-flex flex-wrap gap-3">
                <div>
                    <span class="badge bg-primary">New</span>
                    <small class="text-muted ms-2">Less than 24 hours old</small>
                </div>
                <div>
                    <span class="badge bg-success">Recent</span>
                    <small class="text-muted ms-2">Within the last 7 days</small>
                </div>
                <div>
                    <span class="badge bg-secondary">Old</span>
                    <small class="text-muted ms-2">Older than 7 days</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Styles -->
<style>
    .notification-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-width: 2px;
    }
    
    .notification-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
    }
    
    .notification-card.border-primary {
        border-left-width: 4px;
    }
    
    .notification-card.border-success {
        border-left-width: 4px;
    }
    
    .card {
        border: none;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    .btn-group .btn {
        transition: all 0.2s ease;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #5568d3 0%, #65408b 100%);
        transform: translateY(-1px);
    }
    
    .btn-outline-primary {
        border-color: #667eea;
        color: #667eea;
    }
    
    .btn-outline-primary:hover {
        background: #667eea;
        border-color: #667eea;
        color: white;
    }
    
    .btn-outline-danger:hover {
        transform: scale(1.05);
    }
    
    .notification-meta {
        padding-top: 0.75rem;
        border-top: 1px solid #e5e7eb;
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

    .btn-group {
        gap: 0.25rem;
    }

    .btn-group .btn-sm {
        padding: 0.375rem 0.75rem;
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
</script>
</body>
</html>