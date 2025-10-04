<?php
// Start session to access user session data
session_start();

// Include database configuration
require_once 'config/database.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user role from session
$role = $_SESSION['role'];

// Redirect user to their respective dashboard based on role
switch ($role) {
    case 'admin':
        header('Location: admin_dashboard.php');
        break;
    case 'faculty':
        header('Location: faculty_dashboard.php');
        break;
    case 'student':
        header('Location: student_dashboard.php');
        break;
    default:
        // If role is invalid, redirect to login with error
        header('Location: index.php?error=Invalid user role');
        break;
}
exit();
?>
