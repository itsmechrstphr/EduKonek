<?php
// Start session and include database connection
session_start();
require_once 'config/database.php';

// Ensure user is logged in and has student role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: index.php');
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch student's grades with faculty names
$grades = $pdo->prepare("SELECT g.*, u.first_name, u.last_name FROM grades g LEFT JOIN users u ON g.faculty_id = u.id WHERE g.student_id = ? ORDER BY g.academic_year DESC, g.semester");
$grades->execute([$student_id]);
$student_grades = $grades->fetchAll();

// Fetch student's attendance records with faculty names
$attendance = $pdo->prepare("SELECT a.*, u.first_name, u.last_name FROM attendance a LEFT JOIN users u ON a.faculty_id = u.id WHERE a.student_id = ? ORDER BY a.attendance_date DESC LIMIT 20");
$attendance->execute([$student_id]);
$student_attendance = $attendance->fetchAll();

// Fetch class schedules
$schedules = $pdo->query("SELECT * FROM schedules ORDER BY days, duration")->fetchAll();

// Fetch upcoming events
$events = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 10")->fetchAll();

// Fetch notifications for student
$notifications = $pdo->prepare("SELECT n.*, u.first_name, u.last_name FROM notifications n LEFT JOIN users u ON n.sender_id = u.id WHERE n.receiver_id = ? ORDER BY n.created_at DESC LIMIT 15");
$notifications->execute([$student_id]);
$student_notifications = $notifications->fetchAll();

// Calculate attendance statistics for student
$attendance_stats = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
    FROM attendance WHERE student_id = ?");
$attendance_stats->execute([$student_id]);
$stats = $attendance_stats->fetch();
$attendance_percentage = $stats['total'] > 0 ? round(($stats['present'] + $stats['late'] * 0.5) / $stats['total'] * 100, 2) : 0;

// Calculate metrics
$total_grades = count($student_grades);
$total_attendance_records = $stats['total'];
$total_schedules = count($schedules);
$upcoming_events_count = count($events);
$unread_notifications = count($student_notifications);

// Set page title and include template
$page_title = 'Student Dashboard';
require_once 'includes/admin_template.php';
require_once 'includes/cards.php';
?>

<main class="container-fluid py-4">
    <h2 class="mb-4">Student Dashboard</h2>

    <!-- Metric Cards -->
    <section class="dashboard-section mb-4">
        <div class="row">
            <?php
            render_metric_card('Total Grades', $total_grades, 'fa-graduation-cap', 'text-success');
            render_metric_card('Attendance Rate', $attendance_percentage . '%', 'fa-calendar-check', 'text-info');
            render_metric_card('Class Schedules', $total_schedules, 'fa-clock', 'text-primary');
            render_metric_card('Upcoming Events', $upcoming_events_count, 'fa-calendar-alt', 'text-warning');
            render_metric_card('Notifications', $unread_notifications, 'fa-bell', 'text-danger');
            ?>
        </div>
    </section>

    <!-- Grades Section -->
    <section id="grades" class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">My Academic Grades</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Subject</th>
                                <th>Grade</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($student_grades)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No grades found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($student_grades as $grade): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['subject']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $grade['grade'] >= 90 ? 'success' : 
                                                 ($grade['grade'] >= 80 ? 'primary' : 
                                                 ($grade['grade'] >= 70 ? 'warning' : 'danger'));
                                        ?>">
                                            <?php echo htmlspecialchars($grade['grade']); ?>%
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['academic_year']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Attendance Section -->
    <section id="attendance" class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Attendance Record</h5>
            </div>
            <div class="card-body">
                <!-- Attendance Stats -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3 class="card-title">Overall Attendance</h3>
                                <div class="display-4 text-primary fw-bold"><?php echo $attendance_percentage; ?>%</div>
                                <div class="row mt-3">
                                    <div class="col-4">
                                        <div class="text-success">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                            <div class="mt-2">Present: <?php echo $stats['present']; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-warning">
                                            <i class="fas fa-clock fa-2x"></i>
                                            <div class="mt-2">Late: <?php echo $stats['late']; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-danger">
                                            <i class="fas fa-times-circle fa-2x"></i>
                                            <div class="mt-2">Absent: <?php echo $stats['absent']; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance Table -->
                <h5 class="mb-3">Recent Attendance</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($student_attendance)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No attendance records found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($student_attendance as $record): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['attendance_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['subject']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $record['status'] === 'present' ? 'success' : 
                                                 ($record['status'] === 'late' ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedule Section -->
    <section id="schedule" class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Class Schedule</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Subject</th>
                                <th>Duration</th>
                                <th>Days</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schedules)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No schedules found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['duration']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['days']); ?></td>
                                    <td>
                                        <?php
                                        if ($schedule['teacher_id']) {
                                            $faculty = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                                            $faculty->execute([$schedule['teacher_id']]);
                                            $fac = $faculty->fetch();
                                            if ($fac) {
                                                echo htmlspecialchars($fac['first_name'] . ' ' . $fac['last_name']);
                                            } else {
                                                echo 'N/A';
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section id="events" class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Upcoming School Events</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($events)): ?>
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No upcoming events.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                        </small>
                                        <?php if ($event['event_time']): ?>
                                        <small class="text-muted ms-2">
                                            <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                        </small>
                                        <?php endif; ?>
                                        <?php if ($event['location']): ?>
                                        <small class="text-muted ms-2">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Notifications Section -->
    <section id="notifications" class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Notifications</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($student_notifications)): ?>
                        <div class="col-12 text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                            <p class="text-muted">No notifications found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($student_notifications as $notification): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($notification['title'] ?? 'Notification'); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <div class="d-flex justify-content-between mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
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
</body>
</html>