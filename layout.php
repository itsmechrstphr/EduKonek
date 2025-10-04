<?php
// Start session to access user preferences
session_start();

// Include database connection
require_once 'config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch user appearance preferences from session or database
$theme = $_SESSION['theme'] ?? 'light';
$color_scheme = $_SESSION['color_scheme'] ?? 'default';
$font_family = $_SESSION['font_family'] ?? 'default';
$font_size = $_SESSION['font_size'] ?? 'medium';
$layout_preference = $_SESSION['layout_preference'] ?? 'standard';

// Optionally, fetch from database if not in session
if (!isset($_SESSION['theme'])) {
    $stmt = $pdo->prepare("SELECT theme, color_scheme, font_family, font_size, layout_preference FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $settings = $stmt->fetch();
    if ($settings) {
        $theme = $settings['theme'] ?? $theme;
        $color_scheme = $settings['color_scheme'] ?? $color_scheme;
        $font_family = $settings['font_family'] ?? $font_family;
        $font_size = $settings['font_size'] ?? $font_size;
        $layout_preference = $settings['layout_preference'] ?? $layout_preference;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Information System</title>
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/custom_dashboard.css" />
    <link rel="stylesheet" href="css/theme.css" />
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body data-theme="<?php echo htmlspecialchars($theme); ?>"
      data-color-scheme="<?php echo htmlspecialchars($color_scheme); ?>"
      data-font-family="<?php echo htmlspecialchars($font_family); ?>"
      data-font-size="<?php echo htmlspecialchars($font_size); ?>"
      data-layout="<?php echo htmlspecialchars($layout_preference); ?>">

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <main class="dashboard-content container-fluid p-4">
        <?php
        // Content of the page will be injected here by the including page
        ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/script.js"></script>
    <script src="js/theme.js"></script>
</body>
</html>
