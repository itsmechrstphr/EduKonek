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

// Handle POST requests for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_schedule'])) {
            // Create new schedule
            $department = trim($_POST['department']);
            $year_level = trim($_POST['year_level']);
            $section = trim($_POST['section']);
            $course = trim($_POST['course']);
            $subject = trim($_POST['subject']);
            $academic_track = trim($_POST['academic_track']);
            $status = trim($_POST['status']);
            $teacher_id = $_POST['teacher_id'];
            $day_of_week = $_POST['day_of_week'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $room = trim($_POST['room']);
            
            // Create class name from department, year level, and section
            $class_name = $department . '-' . $year_level . '-' . $section;
            
            // Create duration string
            $duration = date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));

            // Validation
            if (empty($department) || empty($year_level) || empty($section) || empty($course) || empty($subject) || empty($academic_track) || empty($status) || empty($teacher_id) || empty($day_of_week) || empty($start_time) || empty($end_time) || empty($room)) {
                throw new Exception('All fields are required.');
            }

            // Insert new schedule into database with targeting criteria
            $stmt = $pdo->prepare("INSERT INTO schedules (class_name, subject, teacher_id, day_of_week, start_time, end_time, room, duration, course, year_level, days, target_department, target_year_level, target_section, target_course, target_academic_track, target_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$class_name, $subject, $teacher_id, $day_of_week, $start_time, $end_time, $room, $duration, $department, $year_level, $day_of_week, $department, $year_level, $section, $course, $academic_track, $status]);

            $success_message = 'Schedule created successfully! Students matching the criteria will see this in their enrolled classes.';
            header("Location: manage_schedules.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['update_schedule'])) {
            // Update schedule
            $schedule_id = $_POST['schedule_id'];
            $department = trim($_POST['department']);
            $year_level = trim($_POST['year_level']);
            $section = trim($_POST['section']);
            $course = trim($_POST['course']);
            $subject = trim($_POST['subject']);
            $academic_track = trim($_POST['academic_track']);
            $status = trim($_POST['status']);
            $teacher_id = $_POST['teacher_id'];
            $day_of_week = $_POST['day_of_week'];
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $room = trim($_POST['room']);
            
            // Create class name from department, year level, and section
            $class_name = $department . '-' . $year_level . '-' . $section;
            
            // Create duration string
            $duration = date('g:i A', strtotime($start_time)) . ' - ' . date('g:i A', strtotime($end_time));

            // Validation
            if (empty($department) || empty($year_level) || empty($section) || empty($course) || empty($subject) || empty($academic_track) || empty($status) || empty($teacher_id) || empty($day_of_week) || empty($start_time) || empty($end_time) || empty($room)) {
                throw new Exception('All fields are required.');
            }

            // Update schedule in database with targeting criteria
            $stmt = $pdo->prepare("UPDATE schedules SET class_name = ?, subject = ?, teacher_id = ?, day_of_week = ?, start_time = ?, end_time = ?, room = ?, duration = ?, course = ?, year_level = ?, days = ?, target_department = ?, target_year_level = ?, target_section = ?, target_course = ?, target_academic_track = ?, target_status = ? WHERE id = ?");
            $stmt->execute([$class_name, $subject, $teacher_id, $day_of_week, $start_time, $end_time, $room, $duration, $department, $year_level, $day_of_week, $department, $year_level, $section, $course, $academic_track, $status, $schedule_id]);

            $success_message = 'Schedule updated successfully!';
            header("Location: manage_schedules.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['delete_schedule'])) {
            // Delete schedule
            $schedule_id = $_POST['schedule_id'];

            // Delete schedule from database
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->execute([$schedule_id]);

            $success_message = 'Schedule deleted successfully!';
            header("Location: manage_schedules.php?success=" . urlencode($success_message));
            exit();
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        header("Location: manage_schedules.php?error=" . urlencode($error_message));
        exit();
    }
}

// Check for success/error messages
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Get all schedules for display
$schedules = $pdo->query("SELECT s.*, u.first_name, u.last_name FROM schedules s LEFT JOIN users u ON s.teacher_id = u.id ORDER BY s.day_of_week, s.start_time")->fetchAll();

// Get all faculty for dropdown
$faculty = $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'faculty' ORDER BY first_name")->fetchAll();

$page_title = 'Schedule Management';
require_once 'includes/admin_template.php';
?>

<!-- Page content -->
<main class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock me-2"></i>Schedule Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createScheduleModal">
            <i class="fas fa-plus me-2"></i>Add New Schedule
        </button>
    </div>

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

    <!-- Info Alert -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Student Targeting:</strong> Schedules will only appear in students' "My Courses" if they match ALL criteria: Department, Year Level, Section, Course, Academic Track, and Status.
    </div>

    <!-- Schedules Table -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>All Schedules
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Faculty</th>
                                <th>Target Criteria</th>
                                <th>Day & Time</th>
                                <th>Room</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schedules)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No schedules found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($schedule['class_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></td>
                                    <td>
                                        <small>
                                            <strong>Dept:</strong> <?php echo htmlspecialchars($schedule['target_department'] ?? 'N/A'); ?><br>
                                            <strong>Year:</strong> <?php echo htmlspecialchars($schedule['target_year_level'] ?? 'N/A'); ?><br>
                                            <strong>Section:</strong> <?php echo htmlspecialchars($schedule['target_section'] ?? 'N/A'); ?><br>
                                            <strong>Course:</strong> <?php echo htmlspecialchars($schedule['target_course'] ?? 'N/A'); ?><br>
                                            <strong>Track:</strong> <?php echo htmlspecialchars($schedule['target_academic_track'] ?? 'N/A'); ?><br>
                                            <strong>Status:</strong> <?php echo htmlspecialchars($schedule['target_status'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($schedule['day_of_week']); ?><br>
                                        <small><?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($schedule['room']); ?></td>
                                    <td>
                                        <button onclick="editSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)" class="btn btn-warning btn-sm me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteSchedule(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars(addslashes($schedule['class_name'])); ?>')" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
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
    </section>
</main>

<!-- Create Schedule Modal -->
<div class="modal fade" id="createScheduleModal" tabindex="-1" aria-labelledby="createScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createScheduleModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Create New Schedule
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <h6 class="mb-3 text-primary"><i class="fas fa-bullseye me-2"></i>Student Targeting Criteria</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department" id="department" required class="form-select" onchange="updateYearLevels('department', 'year_level')">
                                <option value="">Select Department</option>
                                <option value="CBAT.COM">CBAT.COM</option>
                                <option value="COTE">COTE</option>
                                <option value="CRIM">CRIM</option>
                                <option value="HS">HS (High School)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="year_level" class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select name="year_level" id="year_level" required class="form-select" disabled>
                                <option value="">Select Department First</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                            <select name="section" id="section" required class="form-select" disabled>
                                <option value="">Select Year Level First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="course" class="form-label">Course/Program <span class="text-danger">*</span></label>
                            <input type="text" name="course" id="course" required class="form-control" placeholder="e.g., BS Computer Science, ABM">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="academic_track" class="form-label">Academic Track <span class="text-danger">*</span></label>
                            <select name="academic_track" id="academic_track" required class="form-select">
                                <option value="">Select Track</option>
                                <option value="regular">Regular</option>
                                <option value="irregular">Irregular</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Student Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" required class="form-select">
                                <option value="">Select Status</option>
                                <option value="enrolled">Enrolled</option>
                                <option value="not_enrolled">Not Enrolled</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3 text-primary"><i class="fas fa-calendar-alt me-2"></i>Schedule Details</h6>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subject" required class="form-control" placeholder="e.g., Mathematics, Science">
                    </div>

                    <div class="mb-3">
                        <label for="teacher_id" class="form-label">Faculty <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="teacher_id" required class="form-select">
                            <option value="">Select Faculty</option>
                            <?php foreach ($faculty as $f): ?>
                            <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="day_of_week" class="form-label">Day <span class="text-danger">*</span></label>
                            <select name="day_of_week" id="day_of_week" required class="form-select">
                                <option value="">Select Day</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="start_time" required class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="end_time" required class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="room" class="form-label">Room <span class="text-danger">*</span></label>
                        <input type="text" name="room" id="room" required class="form-control" placeholder="e.g., Room 101, Lab 1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="create_schedule" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editScheduleModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Schedule
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="schedule_id" id="edit_schedule_id">
                <div class="modal-body">
                    <h6 class="mb-3 text-primary"><i class="fas fa-bullseye me-2"></i>Student Targeting Criteria</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_department" class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department" id="edit_department" required class="form-select" onchange="updateYearLevels('edit_department', 'edit_year_level')">
                                <option value="">Select Department</option>
                                <option value="CBAT.COM">CBAT.COM</option>
                                <option value="COTE">COTE</option>
                                <option value="CRIM">CRIM</option>
                                <option value="HS">HS (High School)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_year_level" class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select name="year_level" id="edit_year_level" required class="form-select">
                                <option value="">Select Department First</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_section" class="form-label">Section <span class="text-danger">*</span></label>
                            <select name="section" id="edit_section" required class="form-select">
                                <option value="">Select Year Level First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_course" class="form-label">Course/Program <span class="text-danger">*</span></label>
                            <input type="text" name="course" id="edit_course" required class="form-control">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_academic_track" class="form-label">Academic Track <span class="text-danger">*</span></label>
                            <select name="academic_track" id="edit_academic_track" required class="form-select">
                                <option value="">Select Track</option>
                                <option value="regular">Regular</option>
                                <option value="irregular">Irregular</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">Student Status <span class="text-danger">*</span></label>
                            <select name="status" id="edit_status" required class="form-select">
                                <option value="">Select Status</option>
                                <option value="enrolled">Enrolled</option>
                                <option value="not_enrolled">Not Enrolled</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3 text-primary"><i class="fas fa-calendar-alt me-2"></i>Schedule Details</h6>

                    <div class="mb-3">
                        <label for="edit_subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="edit_subject" required class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="edit_teacher_id" class="form-label">Faculty <span class="text-danger">*</span></label>
                        <select name="teacher_id" id="edit_teacher_id" required class="form-select">
                            <option value="">Select Faculty</option>
                            <?php foreach ($faculty as $f): ?>
                            <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="edit_day_of_week" class="form-label">Day <span class="text-danger">*</span></label>
                            <select name="day_of_week" id="edit_day_of_week" required class="form-select">
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" id="edit_start_time" required class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="edit_end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="edit_end_time" required class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_room" class="form-label">Room <span class="text-danger">*</span></label>
                        <input type="text" name="room" id="edit_room" required class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="update_schedule" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Update Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteScheduleModal" tabindex="-1" aria-labelledby="deleteScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteScheduleModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="schedule_id" id="delete_schedule_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete schedule for class "<strong><span id="delete_schedule_class"></span></strong>"?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="delete_schedule" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Update year levels based on department selection
    function updateYearLevels(departmentId, yearLevelId) {
        const department = document.getElementById(departmentId).value;
        const yearLevelSelect = document.getElementById(yearLevelId);
        const sectionSelect = document.getElementById(yearLevelId.replace('year_level', 'section'));
        
        // Clear existing options
        yearLevelSelect.innerHTML = '<option value="">Select Year Level</option>';
        sectionSelect.innerHTML = '<option value="">Select Year Level First</option>';
        sectionSelect.disabled = true;
        
        if (!department) {
            yearLevelSelect.disabled = true;
            return;
        }
        
        yearLevelSelect.disabled = false;
        
        // Add year levels based on department
        if (department === 'HS') {
            // High School: Grade 7-12
            for (let i = 7; i <= 12; i++) {
                const option = document.createElement('option');
                option.value = 'Grade ' + i;
                option.textContent = 'Grade ' + i;
                yearLevelSelect.appendChild(option);
            }
        } else {
            // College: 1st Year - 4th Year
            const years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearLevelSelect.appendChild(option);
            });
        }
        
        // Add onchange event to year level
        yearLevelSelect.onchange = function() {
            updateSections(departmentId, yearLevelId);
        };
    }
    
    // Update sections based on department
    function updateSections(departmentId, yearLevelId) {
        const department = document.getElementById(departmentId).value;
        const yearLevel = document.getElementById(yearLevelId).value;
        const sectionSelect = document.getElementById(yearLevelId.replace('year_level', 'section'));
        
        // Clear existing options
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        
        if (!yearLevel) {
            sectionSelect.disabled = true;
            return;
        }
        
        sectionSelect.disabled = false;
        
        if (department === 'HS') {
            // High School: sections a, b
            ['a', 'b'].forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section.toUpperCase();
                sectionSelect.appendChild(option);
            });
        } else {
            // College: sections 1a, 1b, 2a, 2b, 3a, 3b, 4a, 4b
            const yearNum = yearLevel.charAt(0);
            ['a', 'b'].forEach(letter => {
                const option = document.createElement('option');
                option.value = yearNum + letter;
                option.textContent = yearNum + letter.toUpperCase();
                sectionSelect.appendChild(option);
            });
        }
    }
    
    // Edit schedule function
    function editSchedule(schedule) {
        // Parse class_name to extract department, year level, and section
        const classNameParts = schedule.class_name.split('-');
        const department = schedule.target_department || classNameParts[0] || schedule.course || '';
        const yearLevel = schedule.target_year_level || classNameParts[1] || schedule.year_level || '';
        const section = schedule.target_section || classNameParts[2] || '';
        
        document.getElementById('edit_schedule_id').value = schedule.id;
        document.getElementById('edit_department').value = department;
        
        // Trigger year level update
        updateYearLevels('edit_department', 'edit_year_level');
        
        // Set values after a short delay to allow dropdowns to populate
        setTimeout(() => {
            document.getElementById('edit_year_level').value = yearLevel;
            updateSections('edit_department', 'edit_year_level');
            
            setTimeout(() => {
                document.getElementById('edit_section').value = section;
            }, 100);
        }, 100);
        
        document.getElementById('edit_subject').value = schedule.subject;
        document.getElementById('edit_course').value = schedule.target_course || schedule.course || '';
        document.getElementById('edit_academic_track').value = schedule.target_academic_track || 'regular';
        document.getElementById('edit_status').value = schedule.target_status || 'enrolled';
        document.getElementById('edit_teacher_id').value = schedule.teacher_id;
        document.getElementById('edit_day_of_week').value = schedule.day_of_week;
        document.getElementById('edit_start_time').value = schedule.start_time;
        document.getElementById('edit_end_time').value = schedule.end_time;
        document.getElementById('edit_room').value = schedule.room;
        
        const modal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
        modal.show();
    }
    
    // Delete schedule function
    function deleteSchedule(id, className) {
        document.getElementById('delete_schedule_id').value = id;
        document.getElementById('delete_schedule_class').textContent = className;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteScheduleModal'));
        modal.show();
    }
    
    // Auto-dismiss alerts
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