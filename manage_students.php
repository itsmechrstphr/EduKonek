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
        if (isset($_POST['create_user'])) {
            // Process user creation form
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $academic_track = $_POST['academic_track'] ?? 'regular';
            $status = $_POST['status'] ?? 'not_enrolled';
            $role = 'student'; // Force role to student

            // Validation
            if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
                throw new Exception('All fields are required.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }

            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters long.');
            }

            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception('Username or email already exists.');
            }

            // Hash password securely
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new student into database
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password, role, academic_track, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $username, $email, $password_hash, $role, $academic_track, $status]);

            $success_message = 'Student created successfully!';

        } elseif (isset($_POST['restore_password'])) {
            // Reset student password to default
            $user_id_to_restore = $_POST['user_id'];

            // Get student information
            $stmt = $pdo->prepare("SELECT first_name, last_name, username FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$user_id_to_restore]);
            $student = $stmt->fetch();

            if (!$student) {
                throw new Exception('Student not found.');
            }

            // Default password
            $default_password = "student123";
            $password_hash = password_hash($default_password, PASSWORD_DEFAULT);

            // Update password in DB
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$password_hash, $user_id_to_restore]);

            // Create notification for the student
            $notification_message = "URGENT: Your password has been reset to the default password 'student123'. For security reasons, please change your password immediately by going to Settings > Account Settings.";
            
            $stmt = $pdo->prepare("INSERT INTO notifications (message, recipient_role, receiver_id, sender_id, created_at) VALUES (?, 'student', ?, ?, NOW())");
            $stmt->execute([$notification_message, $user_id_to_restore, $user_id]);

            $success_message = "Password restored to default (student123) for {$student['first_name']} {$student['last_name']}. A notification has been sent to the student.";

        } elseif (isset($_POST['update_status'])) {
            // Bulk update student status
            $student_ids = $_POST['student_ids'] ?? [];
            $new_status = $_POST['new_status'];

            if (empty($student_ids)) {
                throw new Exception('Please select at least one student.');
            }

            $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id IN ($placeholders) AND role = 'student'");
            $params = array_merge([$new_status], $student_ids);
            $stmt->execute($params);

            $count = $stmt->rowCount();
            $success_message = "$count student(s) status updated to " . ucfirst(str_replace('_', ' ', $new_status)) . "!";

        } elseif (isset($_POST['update_academic_track'])) {
            // Bulk update academic track
            $student_ids = $_POST['student_ids'] ?? [];
            $new_track = $_POST['new_track'];

            if (empty($student_ids)) {
                throw new Exception('Please select at least one student.');
            }

            $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE users SET academic_track = ? WHERE id IN ($placeholders) AND role = 'student'");
            $params = array_merge([$new_track], $student_ids);
            $stmt->execute($params);

            $count = $stmt->rowCount();
            $success_message = "$count student(s) academic track updated to " . ucfirst($new_track) . "!";

        } elseif (isset($_POST['update_profile'])) {
            // Bulk update student profile information
            $student_ids = $_POST['student_ids'] ?? [];
            $department = trim($_POST['department']);
            $year_level = trim($_POST['year_level']);
            $section = trim($_POST['section']);
            $course = trim($_POST['course']);

            if (empty($student_ids)) {
                throw new Exception('Please select at least one student.');
            }

            if (empty($department) || empty($year_level) || empty($section) || empty($course)) {
                throw new Exception('All profile fields are required.');
            }

            $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE users SET department = ?, year_level = ?, section = ?, course = ? WHERE id IN ($placeholders) AND role = 'student'");
            $params = array_merge([$department, $year_level, $section, $course], $student_ids);
            $stmt->execute($params);

            $count = $stmt->rowCount();
            $success_message = "$count student(s) profile updated successfully! (Dept: $department, Year: $year_level, Section: $section, Course: $course)";

        } elseif (isset($_POST['delete_user'])) {
            // Process user deletion
            $user_id_to_delete = $_POST['user_id'];

            // Delete user from database
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$user_id_to_delete]);

            $success_message = 'Student deleted successfully!';
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all students for display
$students = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY first_name, last_name")->fetchAll();
?>

<?php
$page_title = 'Manage Students';
require_once 'includes/admin_template.php';
?>

<style>
    .btn-group-custom {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .badge {
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }
    
    .status-enrolled { background-color: #28a745; }
    .status-not_enrolled { background-color: #6c757d; }
    .status-pending { background-color: #ffc107; color: #000; }
    .status-dropped { background-color: #dc3545; }
    .status-graduate { background-color: #17a2b8; }
    
    .track-regular { background-color: #007bff; }
    .track-irregular { background-color: #fd7e14; }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
</style>

<!-- Page content -->
<main class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2><i class="fas fa-user-graduate me-2"></i>Manage Students</h2>
        <div class="btn-group-custom">
            <button class="btn btn-primary" onclick="openCreateStudentModal()">
                <i class="fas fa-user-plus me-1"></i>Add New Student
            </button>
            <button class="btn btn-success" onclick="openUpdateStatusModal()">
                <i class="fas fa-check-circle me-1"></i>Update Status
            </button>
            <button class="btn btn-info" onclick="openUpdateTrackModal()">
                <i class="fas fa-graduation-cap me-1"></i>Update Academic Track
            </button>
            <button class="btn btn-warning" onclick="openUpdateProfileModal()">
                <i class="fas fa-user-edit me-1"></i>Update Profile
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (!empty($success_message)): ?>
        <div id="success-message" data-message="<?php echo htmlspecialchars($success_message); ?>" data-type="success" style="display: none;"></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div id="error-message" data-message="<?php echo htmlspecialchars($error_message); ?>" data-type="error" style="display: none;"></div>
    <?php endif; ?>

    <!-- Info Alert -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Profile Information:</strong> Department, Year Level, Section, and Course determine which schedules appear in students' "My Courses" page via Smart Targeting.
    </div>

    <!-- Students Table -->
    <section class="dashboard-section">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>All Students
                    <span class="badge bg-primary ms-2"><?php echo count($students); ?> Total</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Year/Section</th>
                                <th>Course</th>
                                <th>Track</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">No students found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="student-checkbox" value="<?php echo $student['id']; ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($student['id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <?php if (!empty($student['department'])): ?>
                                            <span class="badge bg-dark"><?php echo htmlspecialchars($student['department']); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($student['year_level']) && !empty($student['section'])): ?>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($student['year_level'] . ' - ' . strtoupper($student['section'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($student['course'])): ?>
                                            <small><?php echo htmlspecialchars($student['course']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge track-<?php echo $student['academic_track']; ?>">
                                            <?php echo ucfirst($student['academic_track']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo $student['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $student['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="restorePassword(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" class="btn btn-warning btn-sm" title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button onclick="deleteUser(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')" class="btn btn-danger btn-sm" title="Delete Student">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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

<!-- Create Student Modal -->
<div class="modal fade" id="createStudentModal" tabindex="-1" aria-labelledby="createStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createStudentModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Create New Student
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModal('createStudentModal')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createStudentForm" method="POST">
                    <div class="row mb-3">
                        <div class="col">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" required class="form-control">
                        </div>
                        <div class="col">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" required class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="academic_track" class="form-label">Academic Track</label>
                        <select name="academic_track" id="academic_track" class="form-select">
                            <option value="regular">Regular</option>
                            <option value="irregular">Irregular</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="not_enrolled">Not Enrolled</option>
                            <option value="pending">Pending</option>
                            <option value="enrolled">Enrolled</option>
                            <option value="dropped">Dropped</option>
                            <option value="graduate">Graduate</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('createStudentModal')">Cancel</button>
                        <button type="submit" name="create_user" class="btn btn-primary">Create Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="updateStatusModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Update Student Status
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModal('updateStatusModal')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm" method="POST">
                    <p class="text-muted">Select students from the table and choose a new status:</p>
                    <div class="mb-3">
                        <label for="new_status" class="form-label">New Status</label>
                        <select name="new_status" id="new_status" class="form-select" required>
                            <option value="">-- Select Status --</option>
                            <option value="enrolled">Enrolled</option>
                            <option value="not_enrolled">Not Enrolled</option>
                            <option value="pending">Pending</option>
                            <option value="dropped">Dropped</option>
                            <option value="graduate">Graduate</option>
                        </select>
                    </div>
                    <div id="selected-students-status"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('updateStatusModal')">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Academic Track Modal -->
<div class="modal fade" id="updateTrackModal" tabindex="-1" aria-labelledby="updateTrackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="updateTrackModalLabel">
                    <i class="fas fa-graduation-cap me-2"></i>Update Academic Track
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModal('updateTrackModal')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateTrackForm" method="POST">
                    <p class="text-muted">Select students from the table and choose a new academic track:</p>
                    <div class="mb-3">
                        <label for="new_track" class="form-label">New Academic Track</label>
                        <select name="new_track" id="new_track" class="form-select" required>
                            <option value="">-- Select Track --</option>
                            <option value="regular">Regular</option>
                            <option value="irregular">Irregular</option>
                        </select>
                    </div>
                    <div id="selected-students-track"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('updateTrackModal')">Cancel</button>
                        <button type="submit" name="update_academic_track" class="btn btn-info">Update Track</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Profile Modal -->
<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="updateProfileModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Update Student Profile
                </h5>
                <button type="button" class="btn-close" onclick="closeModal('updateProfileModal')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateProfileForm" method="POST">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Smart Targeting:</strong> These profile fields determine which schedules appear in students' "My Courses" page.
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="profile_department" class="form-label">Department <span class="text-danger">*</span></label>
                            <select name="department" id="profile_department" required class="form-select" onchange="updateProfileYearLevels()">
                                <option value="">Select Department</option>
                                <option value="CBAT.COM">CBAT.COM</option>
                                <option value="COTE">COTE</option>
                                <option value="CRIM">CRIM</option>
                                <option value="HS">HS (High School)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="profile_year_level" class="form-label">Year Level <span class="text-danger">*</span></label>
                            <select name="year_level" id="profile_year_level" required class="form-select" disabled onchange="updateProfileSections()">
                                <option value="">Select Department First</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="profile_section" class="form-label">Section <span class="text-danger">*</span></label>
                            <select name="section" id="profile_section" required class="form-select" disabled>
                                <option value="">Select Year Level First</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="profile_course" class="form-label">Course/Program <span class="text-danger">*</span></label>
                            <input type="text" name="course" id="profile_course" required class="form-control" placeholder="e.g., BS Computer Science, ABM">
                        </div>
                    </div>

                    <div id="selected-students-profile"></div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('updateProfileModal')">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeModal('deleteUserModal')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete student "<span id="delete_user_name"></span>"?</p>
                <p class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>This action cannot be undone.</p>
                <form id="deleteUserForm" method="POST">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Restore Password Modal -->
<div class="modal fade" id="restorePasswordModal" tabindex="-1" aria-labelledby="restorePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="restorePasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Restore Password
                </h5>
                <button type="button" class="btn-close" onclick="closeModal('restorePasswordModal')" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to restore password for "<span id="restore_user_name"></span>"?</p>
                <p class="text-info"><strong>The password will be reset to: student123</strong></p>
                <p class="text-warning"><i class="fas fa-bell me-1"></i>A notification will be sent to the student urging them to change their password immediately.</p>
                <form id="restorePasswordForm" method="POST">
                    <input type="hidden" name="user_id" id="restore_user_id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('restorePasswordModal')">Cancel</button>
                        <button type="submit" name="restore_password" class="btn btn-warning">Restore Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Link to external JavaScript file -->
<script src="js/script.js"></script>
<script src="js/theme.js"></script>
<script>
    function openCreateStudentModal() {
        document.getElementById('createStudentModal').classList.add('show');
        document.getElementById('createStudentModal').style.display = 'block';
        document.body.classList.add('modal-open');
        
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    function openUpdateStatusModal() {
        const selectedIds = getSelectedStudentIds();
        if (selectedIds.length === 0) {
            showEnhancedToast('Please select at least one student', 'error');
            return;
        }

        const container = document.getElementById('selected-students-status');
        container.innerHTML = '<p class="alert alert-info"><strong>Selected: ' + selectedIds.length + ' student(s)</strong></p>';
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = id;
            container.appendChild(input);
        });

        document.getElementById('updateStatusModal').classList.add('show');
        document.getElementById('updateStatusModal').style.display = 'block';
        document.body.classList.add('modal-open');
        
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    function openUpdateTrackModal() {
        const selectedIds = getSelectedStudentIds();
        if (selectedIds.length === 0) {
            showEnhancedToast('Please select at least one student', 'error');
            return;
        }

        const container = document.getElementById('selected-students-track');
        container.innerHTML = '<p class="alert alert-info"><strong>Selected: ' + selectedIds.length + ' student(s)</strong></p>';
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = id;
            container.appendChild(input);
        });

        document.getElementById('updateTrackModal').classList.add('show');
        document.getElementById('updateTrackModal').style.display = 'block';
        document.body.classList.add('modal-open');
        
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    function openUpdateProfileModal() {
        const selectedIds = getSelectedStudentIds();
        if (selectedIds.length === 0) {
            showEnhancedToast('Please select at least one student', 'error');
            return;
        }

        const container = document.getElementById('selected-students-profile');
        container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-users me-2"></i><strong>Selected: ' + selectedIds.length + ' student(s)</strong><br><small>All selected students will be assigned to the same profile criteria.</small></div>';
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = id;
            container.appendChild(input);
        });

        document.getElementById('updateProfileModal').classList.add('show');
        document.getElementById('updateProfileModal').style.display = 'block';
        document.body.classList.add('modal-open');
        
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    function updateProfileYearLevels() {
        const department = document.getElementById('profile_department').value;
        const yearLevelSelect = document.getElementById('profile_year_level');
        const sectionSelect = document.getElementById('profile_section');
        
        yearLevelSelect.innerHTML = '<option value="">Select Year Level</option>';
        sectionSelect.innerHTML = '<option value="">Select Year Level First</option>';
        sectionSelect.disabled = true;
        
        if (!department) {
            yearLevelSelect.disabled = true;
            return;
        }
        
        yearLevelSelect.disabled = false;
        
        if (department === 'HS') {
            for (let i = 7; i <= 12; i++) {
                const option = document.createElement('option');
                option.value = 'Grade ' + i;
                option.textContent = 'Grade ' + i;
                yearLevelSelect.appendChild(option);
            }
        } else {
            const years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearLevelSelect.appendChild(option);
            });
        }
    }

    function updateProfileSections() {
        const department = document.getElementById('profile_department').value;
        const yearLevel = document.getElementById('profile_year_level').value;
        const sectionSelect = document.getElementById('profile_section');
        
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        
        if (!yearLevel) {
            sectionSelect.disabled = true;
            return;
        }
        
        sectionSelect.disabled = false;
        
        if (department === 'HS') {
            ['a', 'b'].forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section.toUpperCase();
                sectionSelect.appendChild(option);
            });
        } else {
            const yearNum = yearLevel.charAt(0);
            ['a', 'b'].forEach(letter => {
                const option = document.createElement('option');
                option.value = yearNum + letter;
                option.textContent = yearNum + letter.toUpperCase();
                sectionSelect.appendChild(option);
            });
        }
    }

    function deleteUser(id, name) {
        document.getElementById('delete_user_id').value = id;
        document.getElementById('delete_user_name').textContent = name;
        document.getElementById('deleteUserModal').classList.add('show');
        document.getElementById('deleteUserModal').style.display = 'block';
        document.body.classList.add('modal-open');
        
        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    function restorePassword(id, name) {
        document.getElementById('restore_user_id').value = id;
        document.getElementById('restore_user_name').textContent = name;
        document.getElementById('restorePasswordModal').classList.add('show');
        document.getElementById('restorePasswordModal').style.display = 'block';
        document.body.classList.add('modal-open');

        if (!document.querySelector('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
        }
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('show');
        document.getElementById(modalId).style.display = 'none';
        document.body.classList.remove('modal-open');
        
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
    }

    function getSelectedStudentIds() {
        const checkboxes = document.querySelectorAll('.student-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    // Close modals on backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });

    // Show toast notifications
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        if (successMessage) {
            const message = successMessage.getAttribute('data-message');
            showEnhancedToast(message, 'success');
        }

        if (errorMessage) {
            const message = errorMessage.getAttribute('data-message');
            showEnhancedToast(message, 'error');
        }
    });
</script>
</body>
</html>