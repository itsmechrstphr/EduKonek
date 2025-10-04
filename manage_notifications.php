<?php
// Start session to manage admin authentication
session_start();

// Include database configuration for PDO connection
require_once 'config/database.php';

// Verify user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize variables for messages
$success_message = '';
$error_message = '';

// Handle POST requests for sending notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['send_notification'])) {
            $notification_title = trim($_POST['notification_title']);
            $notification_message = trim($_POST['notification_message']);
            $receiver_type = $_POST['receiver_type'];

            // Validation
            if (empty($notification_title) || empty($notification_message)) {
                throw new Exception('Title and message are required.');
            }

            // Get recipients based on type
            $recipients = [];
            if ($receiver_type === 'all') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id != ?");
                $stmt->execute([$_SESSION['user_id']]);
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($receiver_type === 'students') {
                $stmt = $pdo->query("SELECT id FROM users WHERE role = 'student'");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($receiver_type === 'faculty') {
                $stmt = $pdo->query("SELECT id FROM users WHERE role = 'faculty'");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            // Insert notification for each recipient using helper function
            foreach ($recipients as $recipient_id) {
                createNotification($pdo, $notification_message, $_SESSION['user_id'], $receiver_type, $recipient_id);
            }

            $success_message = 'Notification sent successfully to ' . count($recipients) . ' ' . ($receiver_type === 'all' ? 'users' : $receiver_type) . '!';

        } elseif (isset($_POST['delete_notification'])) {
            $notification_id = $_POST['notification_id'];

            // Delete notification using prepared statement
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND sender_id = ?");
            $stmt->execute([$notification_id, $_SESSION['user_id']]);

            $success_message = 'Notification deleted successfully!';

        } elseif (isset($_POST['update_notification'])) {
            $notification_id = $_POST['notification_id'];
            $notification_message = trim($_POST['update_message']);

            // Validation
            if (empty($notification_message)) {
                throw new Exception('Message is required.');
            }

            // Update notification using prepared statement
            $stmt = $pdo->prepare("UPDATE notifications SET message = ? WHERE id = ? AND sender_id = ?");
            $stmt->execute([$notification_message, $notification_id, $_SESSION['user_id']]);

            $success_message = 'Notification updated successfully!';
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get sent notifications with proper JOIN
$stmt = $pdo->prepare("SELECT n.*, u.first_name, u.last_name FROM notifications n LEFT JOIN users u ON n.receiver_id = u.id WHERE n.sender_id = ? ORDER BY n.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>

<?php
$page_title = 'Send Notifications';
require_once 'includes/admin_template.php';
?>

<!-- Page content -->
 <main class="container-fluid py-4">
            <h2 class="mb-4">Send Notifications</h2>

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

            <!-- Notification Form -->
            <section class="dashboard-section">
                <form method="POST" class="p-4 border rounded">
                    <div class="mb-3">
                        <label for="notification_title" class="form-label">Notification Title</label>
                        <input type="text" name="notification_title" id="notification_title" placeholder="Notification Title" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="notification_message" class="form-label">Message</label>
                        <textarea name="notification_message" id="notification_message" placeholder="Message" rows="4" required class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="receiver_type" class="form-label">Send To</label>
                        <select name="receiver_type" id="receiver_type" class="form-select">
                            <option value="all">All Users</option>
                            <option value="students">Students Only</option>
                            <option value="faculty">Faculty Only</option>
                        </select>
                    </div>
                    <button type="submit" name="send_notification" class="btn btn-primary">Send Notification</button>
                </form>
            </section>

            <!-- Sent Notifications Table -->
            <section class="dashboard-section mt-5">
                <h3>Sent Notifications</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Message</th>
                                <th>Recipient</th>
                                <th>Role</th>
                                <th data-sort="date">Sent At <span class="sort-icon">↕</span></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                            <tr data-date="<?php echo strtotime($notification['created_at']); ?>">
                                <td><?php echo htmlspecialchars(substr($notification['message'], 0, 50)) . (strlen($notification['message']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars(ucfirst($notification['recipient_role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></td>
                                <td>
                                    <button onclick="updateNotification(<?php echo $notification['id']; ?>, '<?php echo addslashes(htmlspecialchars($notification['message'])); ?>')" class="btn btn-warning btn-sm me-1">Update</button>
                                    <button onclick="deleteNotification(<?php echo $notification['id']; ?>, '<?php echo addslashes(htmlspecialchars(substr($notification['message'], 0, 30))); ?>...')" class="btn btn-danger btn-sm">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" onclick="closeModal('logoutModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to logout?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('logoutModal')">No</button>
                    <a href="logout.php" class="btn btn-primary">Yes</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Link to external JavaScript file -->
    <script src="js/script.js"></script>
    <script>
        function deleteNotification(id, title) {
            if (confirm('Are you sure you want to delete notification "' + title + '"?\nThis action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="notification_id" value="' + id + '">' +
                                '<input type="hidden" name="delete_notification" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function updateNotification(id, message) {
            const newMessage = prompt('Update notification message:', message);
            if (newMessage !== null && newMessage.trim() !== '') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="notification_id" value="' + id + '">' +
                                '<input type="hidden" name="update_message" value="' + newMessage.replace(/"/g, '&quot;') + '">' +
                                '<input type="hidden" name="update_notification" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Initialize table sorting
        document.addEventListener('DOMContentLoaded', function() {
            initializeTableSorting();
        });

        function initializeTableSorting() {
            const table = document.querySelector('table');
            if (!table) return;

            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    const sortBy = this.getAttribute('data-sort');
                    sortTable(table, sortBy);
                });
            });
        }

        function sortTable(table, sortBy) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort((a, b) => {
                if (sortBy === 'date') {
                    const aVal = parseInt(a.getAttribute('data-date'));
                    const bVal = parseInt(b.getAttribute('data-date'));
                    return bVal - aVal; // Newest first
                }
                return 0;
            });

            rows.forEach(row => tbody.appendChild(row));

            const sortIcon = table.querySelector('th[data-sort="' + sortBy + '"] .sort-icon');
            if (sortIcon) {
                sortIcon.textContent = '↓';
                setTimeout(() => sortIcon.textContent = '↕', 1000);
            }
        }
    </script>
</body>
</html>