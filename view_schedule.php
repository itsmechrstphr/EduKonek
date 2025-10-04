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
$user_role = $_SESSION['role'];

// Fetch all schedules with teacher information
$stmt = $pdo->query("
    SELECT s.*, 
           u.first_name as teacher_first_name, 
           u.last_name as teacher_last_name,
           u.email as teacher_email
    FROM schedules s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    ORDER BY 
        CASE s.day_of_week
            WHEN 'Monday' THEN 1
            WHEN 'Tuesday' THEN 2
            WHEN 'Wednesday' THEN 3
            WHEN 'Thursday' THEN 4
            WHEN 'Friday' THEN 5
            WHEN 'Saturday' THEN 6
            WHEN 'Sunday' THEN 7
        END,
        s.start_time ASC
");
$all_schedules = $stmt->fetchAll();

// Group schedules by day of week
$schedules_by_day = [];
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

foreach ($days_of_week as $day) {
    $schedules_by_day[$day] = [];
}

foreach ($all_schedules as $schedule) {
    $day = $schedule['day_of_week'];
    if (isset($schedules_by_day[$day])) {
        $schedules_by_day[$day][] = $schedule;
    }
}

// Calculate metrics
$total_schedules = count($all_schedules);
$unique_subjects = count(array_unique(array_column($all_schedules, 'subject')));
$unique_teachers = count(array_unique(array_filter(array_column($all_schedules, 'teacher_id'))));
$unique_rooms = count(array_unique(array_column($all_schedules, 'room')));

// Get today's schedule
$today = date('l'); // Full day name (e.g., Monday)
$today_schedules = $schedules_by_day[$today] ?? [];

// Set page title and include template
$page_title = 'Class Schedule';
require_once 'includes/admin_template.php';
require_once 'includes/cards.php';
?>

<main class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="fas fa-calendar-alt me-2"></i>Class Schedule
    </h2>

    <!-- Metric Cards -->
    <section class="dashboard-section mb-4">
        <div class="row">
            <?php
            render_metric_card('Total Classes', $total_schedules, 'fa-chalkboard', 'text-primary');
            render_metric_card('Subjects', $unique_subjects, 'fa-book', 'text-success');
            render_metric_card('Teachers', $unique_teachers, 'fa-chalkboard-teacher', 'text-info');
            render_metric_card('Rooms', $unique_rooms, 'fa-door-open', 'text-warning');
            ?>
        </div>
    </section>

    <!-- Today's Schedule Highlight -->
    <?php if (!empty($today_schedules)): ?>
    <section class="dashboard-section mb-4">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-star me-2"></i>Today's Schedule - <?php echo $today; ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($today_schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary">
                                        <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                    </strong>
                                </td>
                                <td><?php echo htmlspecialchars($schedule['class_name']); ?></td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($schedule['subject']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($schedule['teacher_first_name']) {
                                        echo htmlspecialchars($schedule['teacher_first_name'] . ' ' . $schedule['teacher_last_name']);
                                    } else {
                                        echo '<span class="text-muted">Not Assigned</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <i class="fas fa-door-open me-1"></i>
                                    <?php echo htmlspecialchars($schedule['room']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Weekly Schedule View -->
    <section class="dashboard-section mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-week me-2"></i>Weekly Schedule
                </h5>
            </div>
            <div class="card-body">
                <!-- Day Tabs -->
                <ul class="nav nav-pills mb-3" id="scheduleTabs" role="tablist">
                    <?php foreach ($days_of_week as $index => $day): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($day === $today) ? 'active' : ''; ?>" 
                                id="<?php echo strtolower($day); ?>-tab" 
                                data-bs-toggle="pill" 
                                data-bs-target="#<?php echo strtolower($day); ?>" 
                                type="button" 
                                role="tab">
                            <?php echo $day; ?>
                            <span class="badge bg-light text-dark ms-2">
                                <?php echo count($schedules_by_day[$day]); ?>
                            </span>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="scheduleTabContent">
                    <?php foreach ($days_of_week as $day): ?>
                    <div class="tab-pane fade <?php echo ($day === $today) ? 'show active' : ''; ?>" 
                         id="<?php echo strtolower($day); ?>" 
                         role="tabpanel">
                        <?php if (empty($schedules_by_day[$day])): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No classes scheduled for <?php echo $day; ?></p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="15%">Time</th>
                                            <th width="20%">Class</th>
                                            <th width="20%">Subject</th>
                                            <th width="25%">Teacher</th>
                                            <th width="10%">Room</th>
                                            <th width="10%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schedules_by_day[$day] as $schedule): ?>
                                        <tr>
                                            <td>
                                                <small class="d-block text-muted">
                                                    <?php echo date('g:i A', strtotime($schedule['start_time'])); ?>
                                                </small>
                                                <small class="text-muted">to</small>
                                                <small class="d-block text-muted">
                                                    <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($schedule['class_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <?php echo htmlspecialchars($schedule['subject']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($schedule['teacher_first_name']): ?>
                                                    <i class="fas fa-user-tie me-1"></i>
                                                    <?php echo htmlspecialchars($schedule['teacher_first_name'] . ' ' . $schedule['teacher_last_name']); ?>
                                                    <?php if ($user_role === 'admin'): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($schedule['teacher_email']); ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Not Assigned
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-door-open me-1"></i>
                                                    <?php echo htmlspecialchars($schedule['room']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewScheduleDetails(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedule Summary by Subject -->
    <section class="dashboard-section mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>Schedule Summary by Subject
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    // Group schedules by subject
                    $subjects_count = [];
                    foreach ($all_schedules as $schedule) {
                        $subject = $schedule['subject'];
                        if (!isset($subjects_count[$subject])) {
                            $subjects_count[$subject] = 0;
                        }
                        $subjects_count[$subject]++;
                    }
                    arsort($subjects_count);
                    
                    $colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
                    $color_index = 0;
                    
                    foreach ($subjects_count as $subject => $count):
                        $color = $colors[$color_index % count($colors)];
                        $color_index++;
                    ?>
                    <div class="col-md-4 col-lg-3 mb-3">
                        <div class="card bg-light h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-book-open fa-2x text-<?php echo $color; ?> mb-2"></i>
                                <h6 class="card-title"><?php echo htmlspecialchars($subject); ?></h6>
                                <p class="card-text">
                                    <span class="badge bg-<?php echo $color; ?> fs-6">
                                        <?php echo $count; ?> Session<?php echo $count > 1 ? 's' : ''; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Schedule Details Modal -->
<div class="modal fade" id="scheduleDetailsModal" tabindex="-1" aria-labelledby="scheduleDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleDetailsModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Schedule Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="scheduleDetailsContent">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // View schedule details
    function viewScheduleDetails(scheduleData) {
        const content = document.getElementById('scheduleDetailsContent');
        
        let teacherInfo = 'Not Assigned';
        if (scheduleData.teacher_first_name) {
            teacherInfo = `${scheduleData.teacher_first_name} ${scheduleData.teacher_last_name}`;
            <?php if ($user_role === 'admin'): ?>
            teacherInfo += `<br><small class="text-muted">${scheduleData.teacher_email}</small>`;
            <?php endif; ?>
        }
        
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="text-muted mb-2"><i class="fas fa-graduation-cap me-2"></i>Class Information</h6>
                            <p class="mb-1"><strong>Class Name:</strong> ${scheduleData.class_name}</p>
                            <p class="mb-0"><strong>Subject:</strong> <span class="badge bg-primary">${scheduleData.subject}</span></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="text-muted mb-2"><i class="fas fa-clock me-2"></i>Schedule</h6>
                            <p class="mb-1"><strong>Day:</strong> ${scheduleData.day_of_week}</p>
                            <p class="mb-0"><strong>Time:</strong> ${formatTime(scheduleData.start_time)} - ${formatTime(scheduleData.end_time)}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="text-muted mb-2"><i class="fas fa-chalkboard-teacher me-2"></i>Instructor</h6>
                            <p class="mb-0">${teacherInfo}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="text-muted mb-2"><i class="fas fa-door-open me-2"></i>Location</h6>
                            <p class="mb-0"><strong>Room:</strong> <span class="badge bg-secondary">${scheduleData.room}</span></p>
                        </div>
                    </div>
                </div>
            </div>
            ${scheduleData.student_ids ? `
            <div class="alert alert-info mt-3">
                <i class="fas fa-users me-2"></i><strong>Students:</strong> ${scheduleData.student_ids}
            </div>
            ` : ''}
        `;
        
        const modal = new bootstrap.Modal(document.getElementById('scheduleDetailsModal'));
        modal.show();
    }

    function formatTime(timeString) {
        const time = new Date('2000-01-01 ' + timeString);
        return time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    }

    // Highlight current time slot
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const currentDay = now.toLocaleDateString('en-US', { weekday: 'long' });
        const currentTime = now.getHours() * 60 + now.getMinutes();
        
        document.querySelectorAll('tbody tr').forEach(row => {
            const timeCell = row.querySelector('td:first-child');
            if (timeCell) {
                const timeText = timeCell.textContent.trim();
                // Add highlighting logic if needed
            }
        });
    });
</script>

<style>
    .nav-pills .nav-link {
        color: #495057;
        border-radius: 8px;
        padding: 10px 15px;
        margin-right: 5px;
        transition: all 0.3s ease;
    }

    .nav-pills .nav-link:hover {
        background-color: rgba(102, 126, 234, 0.1);
        color: #667eea;
    }

    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .nav-pills .nav-link.active .badge {
        background-color: white !important;
        color: #667eea !important;
    }

    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
    }

    .badge {
        padding: 0.5em 0.75em;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .nav-pills {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .nav-pills .nav-link {
            white-space: nowrap;
            margin-bottom: 10px;
        }
    }
</style>
</body>
</html>