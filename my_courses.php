<?php
// Start session to manage student authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration for PDO connection
require_once 'config/database.php';

// Verify user is logged in and has student role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: index.php');
    exit();
}

// Get student information
$student_id = $_SESSION['user_id'];

// Get COMPLETE student profile information
$stmt = $pdo->prepare("SELECT year_level, course, department, section, academic_track, status FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student_info = $stmt->fetch();

// Extract student information with defaults
$student_year = $student_info['year_level'] ?? 'Not Set';
$student_course = $student_info['course'] ?? 'Not Set';
$student_department = $student_info['department'] ?? 'Not Set';
$student_section = $student_info['section'] ?? 'Not Set';
$student_track = $student_info['academic_track'] ?? 'regular';
$student_status = $student_info['status'] ?? 'enrolled';

// Get enrolled courses using SMART TARGETING
// This query matches schedules where ALL targeting criteria match the student's profile
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        u.first_name,
        u.last_name,
        u.email as teacher_email
    FROM schedules s
    LEFT JOIN users u ON s.teacher_id = u.id
    WHERE s.target_department = :department
      AND s.target_year_level = :year_level
      AND s.target_section = :section
      AND s.target_course = :course
      AND s.target_academic_track = :academic_track
      AND s.target_status = :status
    ORDER BY s.day_of_week, s.start_time ASC
");

$stmt->execute([
    ':department' => $student_department,
    ':year_level' => $student_year,
    ':section' => $student_section,
    ':course' => $student_course,
    ':academic_track' => $student_track,
    ':status' => $student_status
]);

$enrolled_courses = $stmt->fetchAll();

// Calculate statistics
$total_courses = count($enrolled_courses);
$courses_today = 0;
$today = date('l'); // Monday, Tuesday, etc.

foreach ($enrolled_courses as $course) {
    if ($course['day_of_week'] === $today) {
        $courses_today++;
    }
}

// Group courses by day of week for better organization
$courses_by_day = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => [],
    'Saturday' => [],
    'Sunday' => []
];

foreach ($enrolled_courses as $course) {
    $day = trim($course['day_of_week']);
    if (isset($courses_by_day[$day])) {
        $courses_by_day[$day][] = $course;
    }
}

// Get unique instructors count
$unique_instructors = count(array_unique(array_filter(array_column($enrolled_courses, 'teacher_id'))));

// Set page title and include template
$page_title = 'My Courses';
require_once 'includes/admin_template.php';
require_once 'includes/cards.php';
?>

<!-- Page content -->
<main class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="fas fa-book me-2"></i>My Courses
    </h2>

    <!-- Student Information Banner -->
    <section class="dashboard-section mb-4">
        <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-white py-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2">
                            <i class="fas fa-user-graduate me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </h4>
                        <div class="d-flex gap-4 flex-wrap">
                            <div>
                                <i class="fas fa-building me-2"></i>
                                <strong>Department:</strong> <?php echo htmlspecialchars($student_department); ?>
                            </div>
                            <div>
                                <i class="fas fa-graduation-cap me-2"></i>
                                <strong>Course:</strong> <?php echo htmlspecialchars($student_course); ?>
                            </div>
                            <div>
                                <i class="fas fa-layer-group me-2"></i>
                                <strong>Year:</strong> <?php echo htmlspecialchars($student_year); ?>
                            </div>
                            <div>
                                <i class="fas fa-users me-2"></i>
                                <strong>Section:</strong> <?php echo htmlspecialchars($student_section); ?>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-light text-dark me-2">
                                <i class="fas fa-route me-1"></i><?php echo htmlspecialchars(ucfirst($student_track)); ?>
                            </span>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-check-circle me-1"></i><?php echo htmlspecialchars(ucfirst($student_status)); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="badge bg-white text-primary px-3 py-2 fs-6">
                            <i class="fas fa-calendar-check me-2"></i>
                            Academic Year <?php echo date('Y') . '-' . (date('Y') + 1); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Course Statistics Cards -->
    <section class="dashboard-section mb-4">
        <div class="d-flex align-items-center mb-3">
            <span class="badge bg-success me-2">
                <i class="fas fa-sync-alt fa-spin me-1"></i>LIVE DATA
            </span>
            <small class="text-muted">Your enrollment information updates in real-time based on smart targeting</small>
        </div>
        <div class="row">
            <?php
            render_metric_card('Total Enrolled Courses', $total_courses, 'fa-book-open', 'text-primary');
            render_metric_card('Classes Today', $courses_today, 'fa-calendar-day', 'text-success');
            render_metric_card('Total Instructors', $unique_instructors, 'fa-chalkboard-teacher', 'text-warning');
            render_metric_card('Year Level', $student_year, 'fa-layer-group', 'text-info');
            ?>
        </div>
    </section>

    <?php if ($student_department === 'Not Set' || $student_course === 'Not Set'): ?>
    <!-- Profile Incomplete Warning -->
    <section class="dashboard-section mb-4">
        <div class="alert alert-warning">
            <h5 class="alert-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>Profile Incomplete
            </h5>
            <p class="mb-0">Your student profile is incomplete. Please contact the administrator to update your Department, Year Level, Section, Course, Academic Track, and Status to view your enrolled courses.</p>
        </div>
    </section>
    <?php endif; ?>

    <!-- All Enrolled Courses Table -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>All Enrolled Courses
                    <span class="badge bg-primary ms-2"><?php echo $total_courses; ?> Total</span>
                </h5>
                <small class="text-muted">
                    <i class="fas fa-bullseye me-1"></i>Matched by Smart Targeting
                </small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Class Name</th>
                                <th>Subject</th>
                                <th>Instructor</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($enrolled_courses)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted mb-1">No courses found matching your profile.</p>
                                        <small class="text-muted">
                                            Your Profile: <?php echo htmlspecialchars($student_department); ?> | 
                                            <?php echo htmlspecialchars($student_year); ?> | 
                                            <?php echo htmlspecialchars($student_section); ?> | 
                                            <?php echo htmlspecialchars($student_course); ?><br>
                                            Contact your administrator if you believe this is an error.
                                        </small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($enrolled_courses as $index => $course): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($course['class_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($course['subject']); ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-user-tie me-1 text-muted"></i>
                                        <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo htmlspecialchars($course['day_of_week']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-clock me-1 text-muted"></i>
                                        <?php echo date('g:i A', strtotime($course['start_time'])) . ' - ' . date('g:i A', strtotime($course['end_time'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($course['room']); ?></span>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($course['teacher_email']); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-envelope me-1"></i>Email
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Courses are automatically assigned based on your profile: 
                    <strong><?php echo htmlspecialchars($student_department); ?></strong> | 
                    <strong><?php echo htmlspecialchars($student_year); ?></strong> | 
                    <strong><?php echo htmlspecialchars($student_section); ?></strong> | 
                    <strong><?php echo htmlspecialchars($student_course); ?></strong> | 
                    <strong><?php echo htmlspecialchars(ucfirst($student_track)); ?></strong> | 
                    <strong><?php echo htmlspecialchars(ucfirst($student_status)); ?></strong>
                </small>
            </div>
        </div>
    </section>

    <!-- Courses by Day of Week -->
    <section class="dashboard-section mb-5">
        <h4 class="mb-4">
            <i class="fas fa-calendar-week me-2"></i>Weekly Schedule Overview
        </h4>
        <div class="row">
            <?php foreach ($courses_by_day as $day => $day_courses): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header <?php echo ($day === date('l')) ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar-day me-2"></i><?php echo $day; ?>
                                <?php if ($day === date('l')): ?>
                                    <span class="badge bg-warning text-dark ms-2">Today</span>
                                <?php endif; ?>
                                <span class="badge bg-light text-dark float-end"><?php echo count($day_courses); ?></span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($day_courses)): ?>
                                <p class="text-muted mb-0 text-center py-3">
                                    <i class="fas fa-coffee me-2"></i>No classes scheduled
                                </p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($day_courses as $course): ?>
                                        <div class="list-group-item px-0 border-bottom">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($course['subject']); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-tie me-1"></i>
                                                        <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-door-open me-1"></i>
                                                        <?php echo htmlspecialchars($course['room']); ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-primary">
                                                    <?php echo date('g:i A', strtotime($course['start_time'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Course Summary -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Course Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>Your Program Details
                            </h6>
                            <hr>
                            <p class="mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($student_department); ?></p>
                            <p class="mb-2"><strong>Course:</strong> <?php echo htmlspecialchars($student_course); ?></p>
                            <p class="mb-2"><strong>Year Level:</strong> <?php echo htmlspecialchars($student_year); ?></p>
                            <p class="mb-2"><strong>Section:</strong> <?php echo htmlspecialchars($student_section); ?></p>
                            <p class="mb-0"><strong>Total Courses:</strong> <?php echo $total_courses; ?> enrolled</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <h6 class="alert-heading">
                                <i class="fas fa-trophy me-2"></i>Academic Progress
                            </h6>
                            <hr>
                            <p class="mb-2"><strong>Track:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($student_track)); ?></span></p>
                            <p class="mb-2"><strong>Status:</strong> <span class="badge bg-success"><?php echo htmlspecialchars(ucfirst($student_status)); ?></span></p>
                            <p class="mb-2"><strong>Semester:</strong> <?php echo (date('m') >= 6) ? '1st Semester' : '2nd Semester'; ?></p>
                            <p class="mb-0"><strong>Academic Year:</strong> <?php echo date('Y') . '-' . (date('Y') + 1); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light text-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Courses are automatically matched using Smart Targeting. For enrollment changes, contact your academic adviser.
                </small>
            </div>
        </div>
    </section>
</main>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>