<?php
// Start session to manage admin authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration for PDO connection
require_once 'config/database.php';

// Verify user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Initialize variables for messages
$success_message = '';
$error_message = '';

// Handle POST requests for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // User management
        if (isset($_POST['create_user'])) {
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $role = $_POST['role'];

            if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
                throw new Exception('All fields are required.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }

            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters long.');
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception('Username or email already exists.');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$first_name, $last_name, $username, $email, $password_hash, $role]);

            createNotification($pdo, "New {$role} account created: {$first_name} {$last_name}", $_SESSION['user_id'], 'admin');

            $success_message = 'User created successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['update_user'])) {
            $user_id = $_POST['user_id'];
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $password = $_POST['password'];

            if (empty($first_name) || empty($last_name) || empty($username) || empty($email)) {
                throw new Exception('All fields except password are required.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception('Username or email already exists.');
            }

            if (!empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception('Password must be at least 6 characters long.');
                }
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $username, $email, $password_hash, $role, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $username, $email, $role, $user_id]);
            }

            createNotification($pdo, "User account updated: {$first_name} {$last_name}", $_SESSION['user_id'], 'admin');

            $success_message = 'User updated successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['delete_user'])) {
            $user_id = $_POST['user_id'];

            if ($user_id == $_SESSION['user_id']) {
                throw new Exception('You cannot delete your own account.');
            }

            $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $deleted_user = $stmt->fetch();

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            if ($deleted_user) {
                createNotification($pdo, "User account deleted: {$deleted_user['first_name']} {$deleted_user['last_name']}", $_SESSION['user_id'], 'admin');
            }

            $success_message = 'User deleted successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();
        }

        // Event management
        elseif (isset($_POST['create_event'])) {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $event_date = $_POST['event_date'];
            $event_time = $_POST['event_time'];
            $location = trim($_POST['location']);

            if (empty($title) || empty($event_date)) {
                throw new Exception('Title and event date are required.');
            }

            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $_SESSION['user_id']]);

            createNotification($pdo, "New event created: {$title}", $_SESSION['user_id'], 'all');

            $success_message = 'Event created successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['update_event'])) {
            $event_id = $_POST['event_id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $event_date = $_POST['event_date'];
            $event_time = $_POST['event_time'];
            $location = trim($_POST['location']);

            if (empty($title) || empty($event_date)) {
                throw new Exception('Title and event date are required.');
            }

            $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ? WHERE id = ?");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $event_id]);

            createNotification($pdo, "Event updated: {$title}", $_SESSION['user_id'], 'all');

            $success_message = 'Event updated successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['delete_event'])) {
            $event_id = $_POST['event_id'];

            $stmt = $pdo->prepare("SELECT title FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch();

            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$event_id]);

            if ($event) {
                createNotification($pdo, "Event deleted: {$event['title']}", $_SESSION['user_id'], 'all');
            }

            $success_message = 'Event deleted successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();
        }

        // Schedule management
        elseif (isset($_POST['create_schedule'])) {
            $subject = trim($_POST['subject']);
            $teacher_id = $_POST['teacher_id'];
            $student_ids = $_POST['student_ids'] ?? '';
            $duration = $_POST['duration'];
            $days = $_POST['days'];

            if (empty($subject) || empty($teacher_id) || empty($duration) || empty($days)) {
                throw new Exception('Subject, teacher, duration, and days are required.');
            }

            $stmt = $pdo->prepare("INSERT INTO schedules (subject, teacher_id, student_ids, duration, days, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$subject, $teacher_id, $student_ids, $duration, $days]);

            createNotification($pdo, "New schedule created: {$subject}", $_SESSION['user_id'], 'all');

            $success_message = 'Schedule created successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['update_schedule'])) {
            $schedule_id = $_POST['schedule_id'];
            $subject = trim($_POST['subject']);
            $teacher_id = $_POST['teacher_id'];
            $student_ids = $_POST['student_ids'] ?? '';
            $duration = $_POST['duration'];
            $days = $_POST['days'];

            if (empty($subject) || empty($teacher_id) || empty($duration) || empty($days)) {
                throw new Exception('Subject, teacher, duration, and days are required.');
            }

            $stmt = $pdo->prepare("UPDATE schedules SET subject = ?, teacher_id = ?, student_ids = ?, duration = ?, days = ? WHERE id = ?");
            $stmt->execute([$subject, $teacher_id, $student_ids, $duration, $days, $schedule_id]);

            createNotification($pdo, "Schedule updated: {$subject}", $_SESSION['user_id'], 'all');

            $success_message = 'Schedule updated successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['delete_schedule'])) {
            $schedule_id = $_POST['schedule_id'];

            $stmt = $pdo->prepare("SELECT subject FROM schedules WHERE id = ?");
            $stmt->execute([$schedule_id]);
            $schedule = $stmt->fetch();

            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->execute([$schedule_id]);

            if ($schedule) {
                createNotification($pdo, "Schedule deleted: {$schedule['subject']}", $_SESSION['user_id'], 'all');
            }

            $success_message = 'Schedule deleted successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();
        }

        // Notification management
        elseif (isset($_POST['send_notification'])) {
            $notification_title = trim($_POST['notification_title']);
            $notification_message = trim($_POST['notification_message']);
            $receiver_type = $_POST['receiver_type'];

            if (empty($notification_title) || empty($notification_message)) {
                throw new Exception('Title and message are required.');
            }

            $recipients = [];
            if ($receiver_type === 'all') {
                $stmt = $pdo->query("SELECT id FROM users WHERE id != " . $_SESSION['user_id']);
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($receiver_type === 'students') {
                $stmt = $pdo->query("SELECT id FROM users WHERE role = 'student'");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } elseif ($receiver_type === 'faculty') {
                $stmt = $pdo->query("SELECT id FROM users WHERE role = 'faculty'");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            foreach ($recipients as $recipient_id) {
                $stmt = $pdo->prepare("INSERT INTO notifications (message, sender_id, receiver_id, recipient_role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$notification_message, $_SESSION['user_id'], $recipient_id, $receiver_type]);
            }

            $success_message = 'Notification sent successfully to ' . count($recipients) . ' ' . ($receiver_type === 'all' ? 'users' : $receiver_type) . '!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['update_notification'])) {
            $notification_id = $_POST['notification_id'];
            $notification_title = trim($_POST['update_title']);
            $notification_message = trim($_POST['update_message']);

            if (empty($notification_title) || empty($notification_message)) {
                throw new Exception('Title and message are required.');
            }

            $stmt = $pdo->prepare("UPDATE notifications SET message = ? WHERE id = ? AND sender_id = ?");
            $stmt->execute([$notification_message, $notification_id, $_SESSION['user_id']]);

            $success_message = 'Notification updated successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['delete_notification'])) {
            $notification_id = $_POST['notification_id'];

            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND sender_id = ?");
            $stmt->execute([$notification_id, $_SESSION['user_id']]);

            $success_message = 'Notification deleted successfully!';
            header("Location: admin_dashboard.php?success=" . urlencode($success_message));
            exit();
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
        header("Location: admin_dashboard.php?error=" . urlencode($error_message));
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

// Get REAL-TIME metrics - queries run fresh on every page load
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_faculty = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'faculty'")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_users = $total_students + $total_faculty + $total_admins;
$upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$scheduled_classes = $pdo->query("SELECT COUNT(*) FROM schedules WHERE days LIKE CONCAT('%', DAYNAME(CURDATE()), '%')")->fetchColumn();
$active_notifications = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();

// Get user registrations over time for OCEAN WAVE CHART (last 30 days, daily)
// This includes ALL users registered via signup.php
$registration_data = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fill in missing dates with zero counts for smooth wave effect
$wave_dates = [];
$wave_counts = [];
$start_date = new DateTime('-30 days');
$end_date = new DateTime();

// Create a map of existing data
$data_map = [];
foreach ($registration_data as $row) {
    $data_map[$row['date']] = (int)$row['count'];
}

// Fill all 30 days
for ($date = clone $start_date; $date <= $end_date; $date->modify('+1 day')) {
    $date_str = $date->format('Y-m-d');
    $wave_dates[] = $date->format('M j');
    $wave_counts[] = isset($data_map[$date_str]) ? $data_map[$date_str] : 0;
}

// Get user registrations over time (last 6 months, monthly)
// This includes ALL users registered via signup.php
$registrations_over_time = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Prepare labels and data for line chart
$months = array_keys($registrations_over_time);
$registration_counts = array_values($registrations_over_time);

// Format months to readable labels
$month_labels = [];
foreach ($months as $month) {
    $date = DateTime::createFromFormat('Y-m', $month);
    $month_labels[] = $date->format('M Y');
}

// Get recent data for tables - includes ALL users from signup
// Ordered by created_at DESC to show newest signups first
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC, role, first_name LIMIT 10")->fetchAll();
$events = $pdo->query("SELECT e.*, u.first_name, u.last_name FROM events e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.event_date DESC LIMIT 10")->fetchAll();
$schedules = $pdo->query("SELECT s.*, u.first_name, u.last_name FROM schedules s LEFT JOIN users u ON s.teacher_id = u.id ORDER BY s.days, s.duration LIMIT 10")->fetchAll();

// Set page title and include template
$page_title = 'Dashboard Overview';
require_once 'includes/admin_template.php';
require_once 'includes/cards.php';
require_once 'includes/graphs.php';
?>

<!-- Page content -->
<main class="container-fluid py-4">
    <h2 class="mb-4">Dashboard Overview</h2>

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

    <!-- Metric Cards -->
    <section class="dashboard-section mb-4">
        <div class="d-flex align-items-center mb-3">
            <span class="badge bg-success me-2">
                <i class="fas fa-sync-alt fa-spin me-1"></i>LIVE DATA
            </span>
            <small class="text-muted">All statistics update automatically when new users sign up</small>
        </div>
        <div class="row">
            <?php
            render_metric_card('Total Students', $total_students, 'fa-user-graduate', 'text-info');
            render_metric_card('Total Faculty', $total_faculty, 'fa-chalkboard-teacher', 'text-warning');
            render_metric_card('Upcoming Events', $upcoming_events, 'fa-calendar-alt', 'text-success');
            render_metric_card('Scheduled Classes', $scheduled_classes, 'fa-book', 'text-primary');
            render_metric_card('Active Notifications', $active_notifications, 'fa-bell', 'text-danger');
            ?>
        </div>
    </section>

    <!-- Ocean Wave Chart - User Registrations Over Time (Last 30 Days) -->
    <section class="dashboard-section mb-4">
        <?php render_ocean_wave_chart('registrationWaveChart', $wave_dates, $wave_counts, 'User Registrations Over Time (Last 30 Days)'); ?>
    </section>

    <!-- Combined Charts: Registered Users by Role (Bar) and Registrations Over Time (Line) -->
    <section class="dashboard-section mb-5">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <?php
                render_bar_chart('registeredUsersChart', ['Students', 'Faculty', 'Admins'], [$total_students, $total_faculty, $total_admins], [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ], 'Registered Users by Role');
                ?>
            </div>
            <div class="col-lg-6 mb-4">
                <?php
                render_line_chart('registrationsOverTimeChart', $month_labels, $registration_counts, 'User Registrations Over Time');
                ?>
            </div>
        </div>
    </section>

    <!-- Users Table -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Recent Users
                    <span class="badge bg-info ms-2"><?php echo $total_users; ?> Total</span>
                </h5>
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>Showing latest registrations
                </small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $now = new DateTime();
                            foreach ($users as $user): 
                                $created = new DateTime($user['created_at']);
                                $diff = $now->diff($created);
                                $isNew = ($diff->days < 1); // New if registered within 24 hours
                            ?>
                            <tr <?php if ($isNew) echo 'class="table-success"'; ?>>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    <?php if ($isNew): ?>
                                        <span class="badge bg-success ms-1">NEW</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $user['role'] === 'admin' ? 'danger' :
                                             ($user['role'] === 'faculty' ? 'warning' : 'info');
                                    ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?>
                                    <?php if ($isNew): ?>
                                        <br><small class="text-success fw-bold">
                                            <i class="fas fa-circle fa-xs me-1"></i>Just now
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light text-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Data includes all users registered via signup form. Green rows indicate registrations within last 24 hours.
                </small>
            </div>
        </div>
    </section>

    <!-- Events Table -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Events</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No events found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['description']); ?></td>
                                    <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                    <td><?php echo htmlspecialchars($event['event_time']); ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td><?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedules Table -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Schedules</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Students</th>
                                <th>Duration</th>
                                <th>Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schedules)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No schedules found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['student_ids'] ?: 'All Students'); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['duration']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['days']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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