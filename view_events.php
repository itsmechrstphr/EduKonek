<?php
/**
 * View Events Page
 * Displays all events for faculty and students
 * Adapts styling from admin dashboard
 */

// Start session if not already started
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

// Get user role
$user_role = $_SESSION['role'] ?? '';

// Get filter parameters
$filter_date = $_GET['filter_date'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT e.*, u.first_name, u.last_name 
          FROM events e 
          LEFT JOIN users u ON e.created_by = u.id 
          WHERE 1=1";

$params = [];

// Add date filter
if (!empty($filter_date)) {
    if ($filter_date === 'upcoming') {
        $query .= " AND e.event_date >= CURDATE()";
    } elseif ($filter_date === 'past') {
        $query .= " AND e.event_date < CURDATE()";
    } elseif ($filter_date === 'this_week') {
        $query .= " AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($filter_date === 'this_month') {
        $query .= " AND MONTH(e.event_date) = MONTH(CURDATE()) AND YEAR(e.event_date) = YEAR(CURDATE())";
    }
}

// Add search filter
if (!empty($search_query)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $search_param = "%{$search_query}%";
    $params = [$search_param, $search_param, $search_param];
}

$query .= " ORDER BY e.event_date DESC, e.event_time DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get event statistics
$total_events = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();
$past_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date < CURDATE()")->fetchColumn();
$this_week_events = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

// Set page title and include template
$page_title = 'View Events';
require_once 'includes/admin_template.php';
require_once 'includes/cards.php';
?>

<!-- Page Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-calendar-alt text-primary me-2"></i>Events Calendar
        </h2>
    </div>

    <!-- Event Statistics Cards -->
    <section class="dashboard-section mb-4">
        <div class="row">
            <?php
            render_metric_card('Total Events', $total_events, 'fa-calendar-check', 'text-primary');
            render_metric_card('Upcoming Events', $upcoming_events, 'fa-calendar-plus', 'text-success');
            render_metric_card('This Week', $this_week_events, 'fa-calendar-week', 'text-warning');
            render_metric_card('Past Events', $past_events, 'fa-calendar-minus', 'text-secondary');
            ?>
        </div>
    </section>

    <!-- Filters and Search -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="view_events.php" class="row g-3">
                <div class="col-md-4">
                    <label for="filter_date" class="form-label">
                        <i class="fas fa-filter me-1"></i>Filter by Date
                    </label>
                    <select name="filter_date" id="filter_date" class="form-select" onchange="this.form.submit()">
                        <option value="">All Events</option>
                        <option value="upcoming" <?php echo $filter_date === 'upcoming' ? 'selected' : ''; ?>>Upcoming Events</option>
                        <option value="this_week" <?php echo $filter_date === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="this_month" <?php echo $filter_date === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="past" <?php echo $filter_date === 'past' ? 'selected' : ''; ?>>Past Events</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">
                        <i class="fas fa-search me-1"></i>Search Events
                    </label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search by title, description, or location..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                </div>
            </form>
            <?php if (!empty($filter_date) || !empty($search_query)): ?>
                <div class="mt-3">
                    <a href="view_events.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Events Table -->
    <section class="dashboard-section mb-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    <?php 
                    if (!empty($search_query)) {
                        echo "Search Results for: " . htmlspecialchars($search_query);
                    } elseif ($filter_date === 'upcoming') {
                        echo "Upcoming Events";
                    } elseif ($filter_date === 'past') {
                        echo "Past Events";
                    } elseif ($filter_date === 'this_week') {
                        echo "Events This Week";
                    } elseif ($filter_date === 'this_month') {
                        echo "Events This Month";
                    } else {
                        echo "All Events";
                    }
                    ?>
                    <span class="badge bg-light text-dark ms-2"><?php echo count($events); ?> event(s)</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="25%">
                                    <i class="fas fa-heading me-1"></i>Event Title
                                </th>
                                <th width="30%">
                                    <i class="fas fa-align-left me-1"></i>Description
                                </th>
                                <th width="12%">
                                    <i class="fas fa-calendar me-1"></i>Date
                                </th>
                                <th width="10%">
                                    <i class="fas fa-clock me-1"></i>Time
                                </th>
                                <th width="13%">
                                    <i class="fas fa-map-marker-alt me-1"></i>Location
                                </th>
                                <th width="10%">
                                    <i class="fas fa-user me-1"></i>Created By
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted fs-5 mb-1">No events found</p>
                                        <p class="text-muted small">
                                            <?php if (!empty($search_query) || !empty($filter_date)): ?>
                                                Try adjusting your filters or search terms
                                            <?php else: ?>
                                                Check back later for upcoming events
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                    <?php
                                    // Determine if event is past, today, or upcoming
                                    $event_date = new DateTime($event['event_date']);
                                    $today = new DateTime();
                                    $is_past = $event_date < $today->setTime(0, 0, 0);
                                    $is_today = $event_date->format('Y-m-d') === (new DateTime())->format('Y-m-d');
                                    
                                    // Apply styling based on status
                                    $row_class = '';
                                    $badge_class = '';
                                    $badge_text = '';
                                    
                                    if ($is_today) {
                                        $row_class = 'table-warning';
                                        $badge_class = 'bg-warning text-dark';
                                        $badge_text = 'Today';
                                    } elseif ($is_past) {
                                        $row_class = 'table-light text-muted';
                                        $badge_class = 'bg-secondary';
                                        $badge_text = 'Past';
                                    } else {
                                        $badge_class = 'bg-success';
                                        $badge_text = 'Upcoming';
                                    }
                                    ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calendar-day text-primary me-2"></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                                    <?php if ($badge_text): ?>
                                                        <br><span class="badge <?php echo $badge_class; ?> mt-1"><?php echo $badge_text; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($event['description'] ?: 'No description provided'); ?></small>
                                        </td>
                                        <td>
                                            <i class="far fa-calendar me-1"></i>
                                            <?php echo date('M j, Y', strtotime($event['event_date'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($event['event_time']): ?>
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">All day</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-map-pin me-1"></i>
                                            <?php echo htmlspecialchars($event['location'] ?: 'TBA'); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-user-circle me-1"></i>
                                            <small><?php echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (!empty($events)): ?>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <?php echo count($events); ?> event(s)
                        <?php if (!empty($filter_date) || !empty($search_query)): ?>
                            with active filters
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Legend -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h6 class="card-title mb-3">
                <i class="fas fa-info-circle me-2"></i>Legend
            </h6>
            <div class="d-flex flex-wrap gap-3">
                <div>
                    <span class="badge bg-warning text-dark">Today</span>
                    <small class="text-muted ms-2">Events happening today</small>
                </div>
                <div>
                    <span class="badge bg-success">Upcoming</span>
                    <small class="text-muted ms-2">Future events</small>
                </div>
                <div>
                    <span class="badge bg-secondary">Past</span>
                    <small class="text-muted ms-2">Events that have occurred</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Styles -->
<style>
    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
        transition: background-color 0.2s ease;
    }
    
    .card {
        border: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
    }
    
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .form-select:focus,
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #5568d3 0%, #65408b 100%);
        transform: translateY(-1px);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>