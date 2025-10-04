<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
            $role = isset($_POST['role']) ? $_POST['role'] : 'faculty'; // Default to faculty if not set

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

            // Validate role
            if (!in_array($role, ['faculty', 'admin'])) {
                throw new Exception('Invalid role selected.');
            }

            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                throw new Exception('Username or email already exists.');
            }

            // Hash password securely
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into database
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$first_name, $last_name, $username, $email, $password_hash, $role]);

            if (!$result) {
                $db_error = $stmt->errorInfo()[2]; // Get the specific DB error message
                error_log("Database error during user creation: " . $db_error);
                throw new Exception('Failed to create user: ' . $db_error);
            }

            $success_message = ucfirst($role) . ' created successfully!';

        } elseif (isset($_POST['update_user'])) {
            // Process user update form
            $update_user_id = $_POST['user_id'];
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            $role = $_POST['role'];

            // Validation
            if (empty($first_name) || empty($last_name) || empty($username) || empty($email)) {
                throw new Exception('All fields except password are required.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }

            // Check if username or email already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $update_user_id]);
            if ($stmt->fetch()) {
                throw new Exception('Username or email already exists.');
            }

            // Update user in database
            if (!empty($password)) {
                // Update with new password
                if (strlen($password) < 6) {
                    throw new Exception('Password must be at least 6 characters long.');
                }
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $username, $email, $password_hash, $role, $update_user_id]);
            } else {
                // Update without changing password
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $username, $email, $role, $update_user_id]);
            }

            $success_message = 'User updated successfully!';

        } elseif (isset($_POST['delete_user'])) {
            // Process user deletion
            $user_id_to_delete = $_POST['user_id'];

            // Prevent admin from deleting themselves
            if ($user_id_to_delete == $_SESSION['user_id']) {
                throw new Exception('You cannot delete your own account.');
            }

            // Delete user from database (only faculty or admin roles)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('faculty', 'admin')");
            $stmt->execute([$user_id_to_delete]);

            $success_message = 'User deleted successfully!';
        }
    } catch (Exception $e) {
        // Log the full error for debugging
        error_log("Error in manage_users.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $error_message = $e->getMessage();
    } catch (PDOException $e) {
        // Catch database-specific errors
        error_log("Database error in manage_users.php: " . $e->getMessage());
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Get all faculty and admin users for display
$users = $pdo->query("SELECT * FROM users WHERE role IN ('faculty', 'admin') ORDER BY role, first_name, last_name")->fetchAll();

// Get counts for charts - these will always be current
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_faculty = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'faculty'")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_users = $total_students + $total_faculty + $total_admins;

// Set page title and include template
$page_title = 'Manage Users';
require_once 'includes/admin_template.php';
require_once 'includes/graphs.php';
require_once 'includes/cards.php';
?>

<!-- Page content -->
<main class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Admin and Faculty</h2>
                <button class="btn btn-primary" onclick="openCreateUserModal()">Add New User</button>
            </div>

            <!-- Success/Error Messages -->
            <?php if (!empty($success_message)): ?>
                <div id="success-message" data-message="<?php echo htmlspecialchars($success_message); ?>" data-type="success" style="display: none;"></div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div id="error-message" data-message="<?php echo htmlspecialchars($error_message); ?>" data-type="error" style="display: none;"></div>
            <?php endif; ?>

            <!-- Metrics Cards -->
            <div class="row mb-4">
                <?php render_metric_card('Total Users', $total_users, 'fa-users', 'text-primary'); ?>
                <?php render_metric_card('Students', $total_students, 'fa-user-graduate', 'text-info'); ?>
                <?php render_metric_card('Faculty', $total_faculty, 'fa-chalkboard-teacher', 'text-warning'); ?>
                <?php render_metric_card('Admins', $total_admins, 'fa-user-shield', 'text-danger'); ?>
            </div>

            <!-- Registered Users Bar Chart -->
            <div class="mb-4">
                <?php render_bar_chart('registeredUsersChart', ['Students', 'Faculty', 'Admins'], [$total_students, $total_faculty, $total_admins], ['rgba(54, 162, 235, 0.8)', 'rgba(255, 206, 86, 0.8)', 'rgba(255, 99, 132, 0.8)'], 'Registered Users by Role'); ?>
            </div>

            <!-- Users Table -->
            <section class="dashboard-section">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'warning'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button onclick='editUser(<?php echo json_encode($user); ?>)' class="btn btn-warning btn-sm">Edit</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')" class="btn btn-danger btn-sm">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">Create New User</h5>
                    <button type="button" class="btn-close" onclick="closeModal('createUserModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createUserForm" method="POST" action="">
                        <div class="row mb-3">
                            <div class="col">
                                <label for="create_first_name" class="form-label">First Name</label>
                                <input type="text" name="first_name" id="create_first_name" placeholder="First Name" required class="form-control">
                            </div>
                            <div class="col">
                                <label for="create_last_name" class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="create_last_name" placeholder="Last Name" required class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="create_username" class="form-label">Username</label>
                            <input type="text" name="username" id="create_username" placeholder="Username" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="create_email" class="form-label">Email</label>
                            <input type="email" name="email" id="create_email" placeholder="Email" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="create_password" class="form-label">Password</label>
                            <input type="password" name="password" id="create_password" placeholder="Password (min. 6 characters)" required class="form-control">
                            <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                        </div>
                        <div class="mb-3">
                            <label for="create_role" class="form-label">Role</label>
                            <select name="role" id="create_role" required class="form-select">
                                <option value="faculty" selected>Faculty</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('createUserModal')">Cancel</button>
                            <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" onclick="closeModal('editUserModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm" method="POST">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="row mb-3">
                            <div class="col">
                                <input type="text" name="first_name" id="edit_first_name" placeholder="First Name" required class="form-control">
                            </div>
                            <div class="col">
                                <input type="text" name="last_name" id="edit_last_name" placeholder="Last Name" required class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="username" id="edit_username" placeholder="Username" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" id="edit_email" placeholder="Email" required class="form-control">
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" id="edit_password" placeholder="New Password (leave blank to keep current)" class="form-control">
                            <small class="form-text text-muted">Leave blank to keep current password</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select name="role" id="edit_role" required class="form-select">
                                <option value="faculty">Faculty</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Cancel</button>
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
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
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" onclick="closeModal('deleteUserModal')" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user "<span id="delete_user_name"></span>"?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                    <form id="deleteUserForm" method="POST">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')">Cancel</button>
                            <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/script.js"></script>
    <script src="js/theme.js"></script>
    <script>
        function openCreateUserModal() {
            document.getElementById('createUserModal').classList.add('show');
            document.getElementById('createUserModal').style.display = 'block';
        }

        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_password').value = ''; // Clear password field
            document.getElementById('editUserModal').classList.add('show');
            document.getElementById('editUserModal').style.display = 'block';
        }

        function deleteUser(id, name) {
            document.getElementById('delete_user_id').value = id;
            document.getElementById('delete_user_name').textContent = name;
            document.getElementById('deleteUserModal').classList.add('show');
            document.getElementById('deleteUserModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.getElementById(modalId).style.display = 'none';
        }

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