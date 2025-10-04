<?php
// Start session to access current session data
session_start();

// Clear all session variables
session_unset();

// Destroy the session completely
session_destroy();

// Redirect user back to login page after logout
header('Location: index.php');
exit();
?>
