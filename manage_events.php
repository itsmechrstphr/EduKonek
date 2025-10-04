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
        if (isset($_POST['create_event'])) {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $event_date = $_POST['event_date'];
            $event_time = $_POST['event_time'];
            $location = trim($_POST['location']);

            if (empty($title) || empty($event_date)) {
                throw new Exception('Title and event date are required.');
            }

            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $_SESSION['user_id']]);

            $success_message = 'Event created successfully!';
            header("Location: manage_events.php?success=" . urlencode($success_message));
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

            $success_message = 'Event updated successfully!';
            header("Location: manage_events.php?success=" . urlencode($success_message));
            exit();

        } elseif (isset($_POST['delete_event'])) {
            $event_id = $_POST['event_id'];

            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$event_id]);

            $success_message = 'Event deleted successfully!';
            header("Location: manage_events.php?success=" . urlencode($success_message));
            exit();
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        header("Location: manage_events.php?error=" . urlencode($error_message));
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

// Get all events for display
$events = $pdo->query("SELECT e.*, u.first_name, u.last_name FROM events e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.event_date DESC")->fetchAll();

$page_title = 'Event Management';
require_once 'includes/admin_template.php';
?>

<!-- Page content -->
<main class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-alt me-2"></i>Event Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
            <i class="fas fa-plus me-2"></i>Add New Event
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

    <!-- Events Table -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>All Events
                </h5>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No events found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($event['description'], 0, 50)) . (strlen($event['description']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                                    <td><?php echo $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                    <td><?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?></td>
                                    <td>
                                        <button onclick="editEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars(addslashes($event['title'])); ?>', '<?php echo htmlspecialchars(addslashes($event['description'])); ?>', '<?php echo $event['event_date']; ?>', '<?php echo $event['event_time']; ?>', '<?php echo htmlspecialchars(addslashes($event['location'])); ?>')" class="btn btn-warning btn-sm me-1">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars(addslashes($event['title'])); ?>')" class="btn btn-danger btn-sm">
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

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Create New Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Event Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="event_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="event_date" required class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="event_time" class="form-label">Time</label>
                            <input type="time" name="event_time" id="event_time" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" name="location" id="location" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="create_event" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEventModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="event_id" id="edit_event_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Event Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_title" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea name="description" id="edit_description" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_event_date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" id="edit_event_date" required class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_event_time" class="form-label">Time</label>
                            <input type="time" name="event_time" id="edit_event_time" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_location" class="form-label">Location</label>
                        <input type="text" name="location" id="edit_location" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="update_event" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteEventModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="event_id" id="delete_event_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete event "<strong><span id="delete_event_title"></span></strong>"?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" name="delete_event" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editEvent(id, title, description, eventDate, eventTime, location) {
        document.getElementById('edit_event_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_event_date').value = eventDate;
        document.getElementById('edit_event_time').value = eventTime;
        document.getElementById('edit_location').value = location;
        
        const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
        modal.show();
    }

    function deleteEvent(id, title) {
        document.getElementById('delete_event_id').value = id;
        document.getElementById('delete_event_title').textContent = title;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteEventModal'));
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