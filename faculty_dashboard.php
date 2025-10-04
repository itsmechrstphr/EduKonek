<?php
// Start session to manage faculty authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration for PDO connection
require_once 'config/database.php';

// Verify user is logged in and has faculty role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header('Location: index.php');
    exit();
}

$faculty_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle POST requests for grade and attendance input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['input_grade'])) {
            $student_id = $_POST['student_id'];
            $subject = trim($_POST['subject']);
            $grade = $_POST['grade'];
            $semester = trim($_POST['semester']);
            $academic_year = trim($_POST['academic_year']);

            $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject, grade, semester, academic_year, faculty_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $subject, $grade, $semester, $academic_year, $faculty_id]);
            
            $success_message = 'Grade added successfully!';
            header("Location: faculty_dashboard.php?success=" . urlencode($success_message));
            exit();
            
        } elseif (isset($_POST['edit_grade'])) {
            $grade_id = $_POST['grade_id'];
            $subject = trim($_POST['subject']);
            $grade = $_POST['grade'];
            $semester = trim($_POST['semester']);
            $academic_year = trim($_POST['academic_year']);

            $stmt = $pdo->prepare("UPDATE grades SET subject = ?, grade = ?, semester = ?, academic_year = ? WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$subject, $grade, $semester, $academic_year, $grade_id, $faculty_id]);
            
            $success_message = 'Grade updated successfully!';
            header("Location: faculty_dashboard.php?success=" . urlencode($success_message));
            exit();
            
        } elseif (isset($_POST['mark_attendance'])) {
            $student_id = $_POST['student_id'];
            $subject = trim($_POST['subject']);
            $attendance_date = $_POST['attendance_date'];
            $status = $_POST['status'];

            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, subject, attendance_date, status, faculty_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $subject, $attendance_date, $status, $faculty_id]);
            
            $success_message = 'Attendance marked successfully!';
            header("Location: faculty_dashboard.php?success=" . urlencode($success_message));
            exit();
            
        } elseif (isset($_POST['send_notification'])) {
            $notification_title = trim($_POST['notification_title']);
            $notification_message = trim($_POST['notification_message']);
            $receiver_id = $_POST['receiver_id'] ?? null;

            if (empty($notification_title) || empty($notification_message)) {
                throw new Exception('Title and message are required.');
            }

            $recipients = [];
            if ($receiver_id) {
                $recipients = [$receiver_id];
            } else {
                $stmt = $pdo->query("SELECT id FROM users WHERE role = 'student'");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            foreach ($recipients as $recipient_id) {
                $stmt = $pdo->prepare("INSERT INTO notifications (message, sender_id, receiver_id, recipient_role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$notification_message, $faculty_id, $recipient_id, 'student']);
            }

            $success_message = 'Notification sent successfully to ' . count($recipients) . ' student(s)!';
            header("Location: faculty_dashboard.php?success=" . urlencode($success_message));
            exit();
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        header("Location: faculty_dashboard.php?error=" . urlencode($error_message));
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

// Fetch all students for dropdowns
$students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY first_name, last_name")->fetchAll();

// Fetch faculty's schedules
$schedules = $pdo->prepare("SELECT * FROM schedules WHERE teacher_id = ? ORDER BY days, duration");
$schedules->execute([$faculty_id]);
$faculty_schedules = $schedules->fetchAll();

// Fetch upcoming events
$events = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 5")->fetchAll();

// Fetch metrics data
$total_students_assigned = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM grades WHERE faculty_id = ?");
$total_students_assigned->execute([$faculty_id]);
$total_students = $total_students_assigned->fetchColumn();

$attendance_today = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE faculty_id = ? AND attendance_date = CURDATE()");
$attendance_today->execute([$faculty_id]);
$attendance_records_today = $attendance_today->fetchColumn();

$attendance_week = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE faculty_id = ? AND YEARWEEK(attendance_date) = YEARWEEK(CURDATE())");
$attendance_week->execute([$faculty_id]);
$attendance_records_week = $attendance_week->fetchColumn();

$grades_pending = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE faculty_id = ? AND grade IS NULL");
$grades_pending->execute([$faculty_id]);
$pending_grades = $grades_pending->fetchColumn();

$notifications_sent = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE sender_id = ?");
$notifications_sent->execute([$faculty_id]);
$sent_notifications = $notifications_sent->fetchColumn();

// Fetch grades for table
$grades = $pdo->prepare("SELECT g.*, u.first_name, u.last_name FROM grades g LEFT JOIN users u ON g.student_id = u.id WHERE g.faculty_id = ? ORDER BY u.last_name LIMIT 10");
$grades->execute([$faculty_id]);
$faculty_grades = $grades->fetchAll();

// Fetch ALL grades for the edit modal (not limited)
$all_grades = $pdo->prepare("SELECT g.*, u.first_name, u.last_name FROM grades g LEFT JOIN users u ON g.student_id = u.id WHERE g.faculty_id = ? ORDER BY u.last_name, g.subject");
$all_grades->execute([$faculty_id]);
$all_faculty_grades = $all_grades->fetchAll();

// Fetch attendance for table
$attendance_records = $pdo->prepare("SELECT a.*, u.first_name, u.last_name FROM attendance a LEFT JOIN users u ON a.student_id = u.id WHERE a.faculty_id = ? ORDER BY a.attendance_date DESC LIMIT 10");
$attendance_records->execute([$faculty_id]);
$faculty_attendance = $attendance_records->fetchAll();

// Set page title and include template
$page_title = 'Faculty Dashboard';
require_once 'includes/admin_template.php';
require_once 'includes/cards.php';
?>

<!-- Page content -->
<main class="container-fluid py-4">
    <h2 class="mb-4">Faculty Dashboard</h2>

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
        <div class="row">
            <?php
            render_metric_card('Total Students', $total_students, 'fa-user-graduate', 'text-info');
            render_metric_card('Attendance Today', $attendance_records_today, 'fa-calendar-check', 'text-success');
            render_metric_card('Attendance This Week', $attendance_records_week, 'fa-calendar-week', 'text-warning');
            render_metric_card('Pending Grades', $pending_grades, 'fa-clipboard-list', 'text-danger');
            render_metric_card('Notifications Sent', $sent_notifications, 'fa-bell', 'text-primary');
            ?>
        </div>
    </section>

    <!-- Grades Management Section -->
    <section id="grades" class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Grades</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editGradeModal">
                    <i class="fas fa-edit"></i> Edit Grades
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Student Name</th>
                                <th>Subject</th>
                                <th>Grade</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($faculty_grades)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No grades found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faculty_grades as $grade): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['grade'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['academic_year']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Attendance Management Section -->
    <section id="attendance" class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Attendance</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Student Name</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($faculty_attendance)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No attendance records found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faculty_attendance as $attendance): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($attendance['subject']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($attendance['attendance_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $attendance['status'] === 'present' ? 'success' : 
                                                 ($attendance['status'] === 'absent' ? 'danger' : 'warning');
                                        ?>">
                                            <?php echo htmlspecialchars(ucfirst($attendance['status'])); ?>
                                        </span>
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

    <!-- Schedule Section -->
    <section id="schedules" class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">My Class Schedule</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Subject</th>
                                <th>Students</th>
                                <th>Duration</th>
                                <th>Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($faculty_schedules)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No schedules found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faculty_schedules as $schedule): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
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
</main>

<!-- Edit Grade Modal -->
<div class="modal fade" id="editGradeModal" tabindex="-1" aria-labelledby="editGradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="editGradeModalLabel">
                    <i class="fas fa-edit"></i> Edit Student Grades
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="gradeSearchInput" placeholder="Search by student name, subject, semester, or academic year...">
                    </div>
                </div>

                <!-- Grades Table -->
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th style="width: 20%;">Student Name</th>
                                <th style="width: 25%;">Subject</th>
                                <th style="width: 10%;">Grade</th>
                                <th style="width: 15%;">Semester</th>
                                <th style="width: 15%;">Academic Year</th>
                                <th style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="gradesTableBody">
                            <?php if (empty($all_faculty_grades)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No grades found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_faculty_grades as $grade): ?>
                                <tr class="grade-row" data-student-name="<?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?>" 
                                    data-subject="<?php echo htmlspecialchars($grade['subject']); ?>"
                                    data-semester="<?php echo htmlspecialchars($grade['semester']); ?>"
                                    data-academic-year="<?php echo htmlspecialchars($grade['academic_year']); ?>">
                                    <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['grade'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($grade['semester']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['academic_year']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="openEditForm(<?php echo $grade['id']; ?>, '<?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($grade['subject'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($grade['grade'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($grade['semester'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($grade['academic_year'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Grade Form Modal -->
<div class="modal fade" id="editGradeFormModal" tabindex="-1" aria-labelledby="editGradeFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content shadow-lg" style="border-radius: 0.5rem; overflow: hidden; border: none;">
            <form method="POST" action="faculty_dashboard.php">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.25rem 1.5rem;">
                    <h5 class="modal-title mb-0" id="editGradeFormModalLabel" style="font-size: 1.25rem; font-weight: 600;">
                        <i class="fas fa-edit me-2"></i> Update Grade
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background-color: #ffffff;">
                    <input type="hidden" name="edit_grade" value="1">
                    <input type="hidden" name="grade_id" id="edit_grade_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark mb-2" style="font-size: 0.95rem;">
                            <i class="fas fa-user me-2 text-muted"></i> Student Name
                        </label>
                        <input type="text" class="form-control bg-light" id="edit_student_name" readonly style="border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.625rem 0.875rem; font-size: 1rem;">
                    </div>

                    <div class="mb-3">
                        <label for="edit_subject" class="form-label fw-semibold text-dark mb-2" style="font-size: 0.95rem;">
                            <i class="fas fa-book me-2 text-muted"></i> Subject
                        </label>
                        <input type="text" class="form-control" name="subject" id="edit_subject" required style="border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.625rem 0.875rem; font-size: 1rem;">
                    </div>

                    <div class="mb-3">
                        <label for="edit_grade_value" class="form-label fw-semibold text-dark mb-2" style="font-size: 0.95rem;">
                            <i class="fas fa-star me-2 text-muted"></i> Grade
                        </label>
                        <input type="number" class="form-control" name="grade" id="edit_grade_value" step="0.01" min="0" max="100" required style="border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.625rem 0.875rem; font-size: 1rem;">
                    </div>

                    <div class="mb-3">
                        <label for="edit_semester" class="form-label fw-semibold text-dark mb-2" style="font-size: 0.95rem;">
                            <i class="fas fa-calendar-alt me-2 text-muted"></i> Semester
                        </label>
                        <input type="text" class="form-control" name="semester" id="edit_semester" required style="border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.625rem 0.875rem; font-size: 1rem;">
                    </div>

                    <div class="mb-3">
                        <label for="edit_academic_year" class="form-label fw-semibold text-dark mb-2" style="font-size: 0.95rem;">
                            <i class="fas fa-calendar me-2 text-muted"></i> Academic Year
                        </label>
                        <input type="text" class="form-control" name="academic_year" id="edit_academic_year" required style="border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 0.625rem 0.875rem; font-size: 1rem;">
                    </div>
                </div>
                <div class="modal-footer bg-light" style="padding: 1rem 1.5rem; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 0.375rem; font-weight: 500; padding: 0.625rem 1.25rem;">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn text-white px-4 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0.375rem; font-weight: 500; padding: 0.625rem 1.25rem;">
                        <i class="fas fa-save me-1"></i> Update Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    // Search functionality for grades table
    document.getElementById('gradeSearchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('.grade-row');
        
        rows.forEach(function(row) {
            const studentName = row.getAttribute('data-student-name').toLowerCase();
            const subject = row.getAttribute('data-subject').toLowerCase();
            const semester = row.getAttribute('data-semester').toLowerCase();
            const academicYear = row.getAttribute('data-academic-year').toLowerCase();
            
            if (studentName.includes(searchValue) || 
                subject.includes(searchValue) || 
                semester.includes(searchValue) || 
                academicYear.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Function to open edit form
    function openEditForm(gradeId, studentName, subject, grade, semester, academicYear) {
        document.getElementById('edit_grade_id').value = gradeId;
        document.getElementById('edit_student_name').value = studentName;
        document.getElementById('edit_subject').value = subject;
        document.getElementById('edit_grade_value').value = grade;
        document.getElementById('edit_semester').value = semester;
        document.getElementById('edit_academic_year').value = academicYear;
        
        // Close the grades list modal and open the edit form modal
        const gradesModal = bootstrap.Modal.getInstance(document.getElementById('editGradeModal'));
        gradesModal.hide();
        
        const editFormModal = new bootstrap.Modal(document.getElementById('editGradeFormModal'));
        editFormModal.show();
    }

    // When edit form modal is closed, show the grades list modal again
    document.getElementById('editGradeFormModal').addEventListener('hidden.bs.modal', function() {
        const editGradeModal = new bootstrap.Modal(document.getElementById('editGradeModal'));
        editGradeModal.show();
    });
</script>
</body>
</html>