<?php
/**
 * Admin Template - Universal Header and Sidebar Navigation
 * Provides consistent header and sidebar navigation for all admin pages
 * Includes role-based access for all user types (admin, faculty, student)
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user role
$user_role = $_SESSION['role'] ?? '';

// Fetch user appearance preferences
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT theme, color_scheme, font_family, font_size, layout_preference FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_settings = $stmt->fetch();
$theme = $user_settings['theme'] ?? 'light';
$color_scheme = $user_settings['color_scheme'] ?? 'default';
$font_family = $user_settings['font_family'] ?? 'default';
$font_size = $user_settings['font_size'] ?? 'medium';
$layout_preference = $user_settings['layout_preference'] ?? 'standard';


// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND created_at = 0");
$stmt->execute([$_SESSION['user_id']]);
$unread_notifications = $stmt->fetchColumn();

// Determine dashboard link based on role
$dashboard_link = 'admin_dashboard.php';
if ($user_role === 'faculty') {
    $dashboard_link = 'faculty_dashboard.php';
} elseif ($user_role === 'student') {
    $dashboard_link = 'student_dashboard.php';
}

// Define navigation items based on role
$nav_items = [];
if ($user_role === 'admin') {
    $nav_items = [
        ['url' => 'admin_dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ['url' => 'manage_users.php', 'icon' => 'fa-users-cog', 'label' => 'Manage Faculty'],
        ['url' => 'manage_students.php', 'icon' => 'fa-user-graduate', 'label' => 'Manage Students'],
        ['url' => 'manage_events.php', 'icon' => 'fa-calendar-alt', 'label' => 'Events'],
        ['url' => 'manage_schedules.php', 'icon' => 'fa-clock', 'label' => 'Schedules'],
        ['url' => 'manage_notifications.php', 'icon' => 'fa-bell', 'label' => 'Notifications'],
        ['separator' => true],
        ['url' => 'settings.php', 'icon' => 'fa-cog', 'label' => 'Settings'],
        ['url' => 'profile_settings.php', 'icon' => 'fa-user-edit', 'label' => 'Profile Settings'],
        ['url' => 'appearance_ui.php', 'icon' => 'fa-palette', 'label' => 'Appearance'],
        ['url' => 'privacy_security.php', 'icon' => 'fa-shield-alt', 'label' => 'Privacy & Security'],
    ];
} elseif ($user_role === 'faculty') {
    $nav_items = [
        ['url' => 'faculty_dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ['url' => 'my_classes.php', 'icon' => 'fa-chalkboard-teacher', 'label' => 'My Classes'],
        ['url' => 'view_schedule.php', 'icon' => 'fa-clock', 'label' => 'Schedule'],
        ['url' => 'view_events.php', 'icon' => 'fa-calendar-alt', 'label' => 'Events'],
        ['separator' => true],
        ['url' => 'settings.php', 'icon' => 'fa-cog', 'label' => 'Settings'],
        ['url' => 'profile_settings.php', 'icon' => 'fa-user-edit', 'label' => 'Profile Settings'],
        ['url' => 'appearance_ui.php', 'icon' => 'fa-palette', 'label' => 'Appearance'],
        ['url' => 'privacy_security.php', 'icon' => 'fa-shield-alt', 'label' => 'Privacy & Security'],
        ['url' => 'contact_admin.php', 'icon' => 'fa-envelope', 'label' => 'Contact Admin'],
    ];
} elseif ($user_role === 'student') {
    $nav_items = [
        ['url' => 'student_dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
        ['url' => 'my_courses.php', 'icon' => 'fa-book', 'label' => 'My Courses'],
        ['url' => 'view_schedule.php', 'icon' => 'fa-clock', 'label' => 'Schedule'],
        ['url' => 'view_events.php', 'icon' => 'fa-calendar-alt', 'label' => 'Events'],
        ['separator' => true],
        ['url' => 'settings.php', 'icon' => 'fa-cog', 'label' => 'Settings'],
        ['url' => 'profile_settings.php', 'icon' => 'fa-user-edit', 'label' => 'Profile Settings'],
        ['url' => 'appearance_ui.php', 'icon' => 'fa-palette', 'label' => 'Appearance'],
        ['url' => 'privacy_security.php', 'icon' => 'fa-shield-alt', 'label' => 'Privacy & Security'],
        ['url' => 'contact_admin.php', 'icon' => 'fa-envelope', 'label' => 'Contact Admin'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Edu.Konek</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/custom_dashboard.css">
    <link rel="stylesheet" href="css/appearance.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f7fa;
            overflow-x: hidden;
            padding-top: 70px;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 200% 200%;
            animation: gradientShift 8s ease infinite;
            color: white;
            padding: 0 1.5rem;
            height: 70px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Logo Section */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .logo-container img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .logo-container h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* Header Right Section */
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .welcome-text {
            font-size: 0.95rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.95);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            white-space: nowrap;
        }

        /* Notification Button */
        .btn-notification {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.25);
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            position: relative;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            cursor: pointer;
        }

        .btn-notification:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }

        /* Logout Button */
        .btn-logout {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-logout:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            color: white;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 70px;
            width: 260px;
            height: calc(100vh - 70px);
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            z-index: 999;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            list-style: none;
            margin-bottom: 8px;
        }

        .nav-separator {
            height: 1px;
            background: #e5e7eb;
            margin: 0.75rem 1.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 1.5rem;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, transparent 100%);
            color: #667eea;
            border-left-color: #667eea;
        }

        .nav-link:hover i {
            transform: scale(1.15);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.15) 0%, transparent 100%);
            color: #667eea;
            border-left-color: #667eea;
            font-weight: 600;
        }

        .nav-link.active i {
            transform: scale(1.1);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 1.5rem 2rem;
            min-height: calc(100vh - 70px);
        }

        /* Mobile Toggle Button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            left: 1rem;
            top: 15px;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Sidebar Border */
        .sidebar::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            background: linear-gradient(180deg, transparent, #e5e7eb 20%, #e5e7eb 80%, transparent);
        }

        /* Fix sidebar overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 998;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* Fix modal z-index to appear above sidebar overlay */
        .modal {
            z-index: 1055 !important;
        }

        .modal-backdrop {
            z-index: 1050 !important;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 260px;
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
            }

            .logo-container {
                margin-left: 50px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 0 1rem;
            }

            body {
                padding-top: 70px;
            }

            .logo-container h1 {
                font-size: 1.25rem;
            }

            .logo-container img {
                width: 38px;
                height: 38px;
            }

            .header-right {
                gap: 12px;
            }

            .btn-logout span {
                display: none;
            }

            .main-content {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .logo-container h1 {
                display: none;
            }

            .logo-container {
                margin-left: 45px;
            }
        }
    </style>
</head>
<body data-theme="<?php echo htmlspecialchars($theme); ?>" 
      data-color-scheme="<?php echo htmlspecialchars($color_scheme); ?>" 
      data-font-family="<?php echo htmlspecialchars($font_family); ?>" 
      data-font-size="<?php echo htmlspecialchars($font_size); ?>" 
      data-layout="<?php echo htmlspecialchars($layout_preference); ?>">
    
    <!-- Header -->
    <header class="header">
        <!-- Mobile Sidebar Toggle -->
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Logo -->
        <div class="logo-container">
            <img src="assets/images/Logo.png" alt="Edu.Konek Logo">
            <h1>Edu.Konek</h1>
        </div>
        
        <!-- Header Right Section -->
        <div class="header-right">
            <span class="welcome-text">
                Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>!
            </span>
            
            <!-- Notification Button -->
            <button class="btn-notification" onclick="window.location.href='notifications.php'">
                <i class="fas fa-bell"></i>
                <?php if ($unread_notifications > 0): ?>
                    <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                <?php endif; ?>
            </button>
            
            <!-- Logout Button -->
            <a href="logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </header>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($nav_items as $item): ?>
                    <?php if (isset($item['separator']) && $item['separator']): ?>
                        <div class="nav-separator"></div>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == $item['url']) ? 'active' : ''; ?>" 
                               href="<?php echo $item['url']; ?>">
                                <i class="fas <?php echo $item['icon']; ?>"></i>
                                <span><?php echo $item['label']; ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </aside>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content Area -->
    <main class="main-content">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }

        // Close sidebar on window resize if screen is large
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });

        // Close sidebar when clicking on a link
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    toggleSidebar();
                }
            });
        });
    </script>
