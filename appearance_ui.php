<?php
// Start session and include database connection
session_start();
require_once 'config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle POST requests for appearance settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_appearance'])) {
    try {
        $theme = $_POST['theme'] ?? 'light';
        $color_scheme = $_POST['color_scheme'] ?? 'default';
        $font_family = $_POST['font_family'] ?? 'default';
        $font_size = $_POST['font_size'] ?? 'medium';
        $layout_preference = $_POST['layout_preference'] ?? 'standard';
        
        // New customization options
        $sidebar_color = $_POST['sidebar_color'] ?? 'default';
        $header_color = $_POST['header_color'] ?? 'default';
        $page_color = $_POST['page_color'] ?? 'default';
        $font_style = $_POST['font_style'] ?? 'normal';

        // Update appearance settings in database
        $stmt = $pdo->prepare("UPDATE users SET theme = ?, color_scheme = ?, font_family = ?, font_size = ?, layout_preference = ?, sidebar_color = ?, header_color = ?, page_color = ?, font_style = ? WHERE id = ?");
        $stmt->execute([$theme, $color_scheme, $font_family, $font_size, $layout_preference, $sidebar_color, $header_color, $page_color, $font_style, $user_id]);

        $success_message = 'Appearance settings saved successfully!';
        header("Location: appearance_ui.php?success=" . urlencode($success_message));
        exit();

    } catch (Exception $e) {
        $error_message = 'Failed to save appearance settings: ' . $e->getMessage();
        header("Location: appearance_ui.php?error=" . urlencode($error_message));
        exit();
    }
}

// Check for success/error messages from redirect
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Fetch current appearance settings
$stmt = $pdo->prepare("SELECT theme, color_scheme, font_family, font_size, layout_preference, sidebar_color, header_color, page_color, font_style FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_settings = $stmt->fetch();
$theme = $user_settings['theme'] ?? 'light';
$color_scheme = $user_settings['color_scheme'] ?? 'default';
$font_family = $user_settings['font_family'] ?? 'default';
$font_size = $user_settings['font_size'] ?? 'medium';
$layout_preference = $user_settings['layout_preference'] ?? 'standard';
$sidebar_color = $user_settings['sidebar_color'] ?? 'default';
$header_color = $user_settings['header_color'] ?? 'default';
$page_color = $user_settings['page_color'] ?? 'default';
$font_style = $user_settings['font_style'] ?? 'normal';

// Set page title and include template
$page_title = 'Appearance Settings';
require_once 'includes/admin_template.php';
?>

<style>
    .theme-preview {
        border: 3px solid transparent;
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        min-height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .theme-preview:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }
    
    .theme-preview.active {
        border-color: #667eea;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    }
    
    .theme-preview input[type="radio"] {
        display: none;
    }
    
    .color-dot {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 10px;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
    
    .color-preview-box {
        width: 100%;
        height: 120px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-bottom: 10px;
        border: 2px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .color-preview-box:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .font-preview {
        font-size: 18px;
        padding: 15px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 10px;
        background: white;
    }
    
    .settings-card {
        transition: all 0.3s ease;
    }
    
    .settings-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .color-option {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .color-option input[type="radio"] {
        display: none;
    }

    .color-option.active .color-preview-box {
        border-color: #667eea;
        border-width: 3px;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
    }
</style>

<main class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-palette me-2"></i>Appearance Settings
        </h2>
        <a href="settings.php" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left me-2"></i>Back to Settings
        </a>
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

    <form method="POST" action="appearance_ui.php" id="appearanceForm">
        <!-- Theme Selection -->
        <section class="mb-5">
            <div class="card shadow-sm settings-card">
                <div class="card-header text-white" style="background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-moon me-2"></i>Theme Mode
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Choose your preferred theme for the interface</p>
                    <div class="row g-3">
                        <!-- Light Theme -->
                        <div class="col-md-4">
                            <label class="theme-preview <?php echo $theme === 'light' ? 'active' : ''; ?>" style="background: linear-gradient(135deg, #ffffff 0%, #f5f7fa 100%);">
                                <input type="radio" name="theme" value="light" <?php echo $theme === 'light' ? 'checked' : ''; ?>>
                                <div>
                                    <h6 class="mb-2" style="color: #333;">
                                        <i class="fas fa-sun me-2"></i>Light Theme
                                    </h6>
                                    <p class="text-muted small mb-3">Clean and bright interface</p>
                                    <div class="d-flex">
                                        <span class="color-dot" style="background: #ffffff;"></span>
                                        <span class="color-dot" style="background: #f5f7fa;"></span>
                                        <span class="color-dot" style="background: #667eea;"></span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Dark Theme -->
                        <div class="col-md-4">
                            <label class="theme-preview <?php echo $theme === 'dark' ? 'active' : ''; ?>" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
                                <input type="radio" name="theme" value="dark" <?php echo $theme === 'dark' ? 'checked' : ''; ?>>
                                <div>
                                    <h6 class="mb-2" style="color: #ffffff;">
                                        <i class="fas fa-moon me-2"></i>Dark Theme
                                    </h6>
                                    <p class="small mb-3" style="color: #b0b0b0;">Easy on the eyes</p>
                                    <div class="d-flex">
                                        <span class="color-dot" style="background: #1a1a2e;"></span>
                                        <span class="color-dot" style="background: #0f3460;"></span>
                                        <span class="color-dot" style="background: #e94560;"></span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Blue Theme -->
                        <div class="col-md-4">
                            <label class="theme-preview <?php echo $theme === 'blue' ? 'active' : ''; ?>" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                                <input type="radio" name="theme" value="blue" <?php echo $theme === 'blue' ? 'checked' : ''; ?>>
                                <div>
                                    <h6 class="mb-2" style="color: #1565c0;">
                                        <i class="fas fa-water me-2"></i>Blue Theme
                                    </h6>
                                    <p class="small mb-3" style="color: #1976d2;">Calm and professional</p>
                                    <div class="d-flex">
                                        <span class="color-dot" style="background: #e3f2fd;"></span>
                                        <span class="color-dot" style="background: #2196f3;"></span>
                                        <span class="color-dot" style="background: #1565c0;"></span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Color Scheme Selection -->
        <section class="mb-5">
            <div class="card shadow-sm settings-card">
                <div class="card-header text-white" style="background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-palette me-2"></i>Primary Color Scheme
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Select your preferred color palette for buttons, links, and accents</p>
                    <div class="row g-3">
                        <!-- Default Scheme -->
                        <div class="col-md-3">
                            <label class="theme-preview <?php echo $color_scheme === 'default' ? 'active' : ''; ?>" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <input type="radio" name="color_scheme" value="default" <?php echo $color_scheme === 'default' ? 'checked' : ''; ?>>
                                <div class="text-center">
                                    <h6 class="mb-2 text-white">Default</h6>
                                    <p class="small text-white-50 mb-3">Purple gradient</p>
                                    <div class="d-flex justify-content-center">
                                        <span class="color-dot" style="background: #667eea;"></span>
                                        <span class="color-dot" style="background: #764ba2;"></span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Ocean Scheme -->
                        <div class="col-md-3">
                            <label class="theme-preview <?php echo $color_scheme === 'ocean' ? 'active' : ''; ?>" style="background: linear-gradient(135deg, #2196f3 0%, #00bcd4 100%);">
                                <input type="radio" name="color_scheme" value="ocean" <?php echo $color_scheme === 'ocean' ? 'checked' : ''; ?>>
                                <div class="text-center">
                                    <h6 class="mb-2 text-white">Ocean</h6>
                                    <p class="small text-white-50 mb-3">Blue waves</p>
                                    <div class="d-flex justify-content-center">
                                        <span class="color-dot" style="background: #2196f3;"></span>
                                        <span class="color-dot" style="background: #00bcd4;"></span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Sunset Scheme -->
                        <div class="col-md-3">
                            <label class="theme-preview <?php echo $color_scheme === 'sunset' ? 'active' : ''; ?>" style="background: linear-gradient(135deg, #ff9800 0%, #ff5722 100%);">
                                <input type="radio" name="color_scheme" value="sunset" <?php echo $color_scheme === 'sunset' ? 'checked' : ''; ?>>
                                <div class="text-center">
                                    <h6 class="mb-2 text-white">Sunset</h6>
                                    <p class="small text-white-50 mb-3">Warm orange</p>
                                    <div class="d-flex justify-content-center">
                                        <span class="color-dot" style="background: #ff9800;"></span>
                                        <span class="color-dot" style="background: #ff5722;"></span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Forest Scheme -->
                        <div class="col-md-3">
                            <label class="theme-preview <?php echo $color_scheme === 'forest' ? 'active' : ''; ?>" style="background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);">
                                <input type="radio" name="color_scheme" value="forest" <?php echo $color_scheme === 'forest' ? 'checked' : ''; ?>>
                                <div class="text-center">
                                    <h6 class="mb-2 text-white">Forest</h6>
                                    <p class="small text-white-50 mb-3">Nature green</p>
                                    <div class="d-flex justify-content-center">
                                        <span class="color-dot" style="background: #4caf50;"></span>
                                        <span class="color-dot" style="background: #2e7d32;"></span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Page Background Color -->
        <section class="mb-5">
            <div class="card shadow-sm settings-card">
                <div class="card-header text-white" style="background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-file me-2"></i>Page Background Color
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Choose the background color for your main content area</p>
                    <div class="row g-3">
                        <!-- Default White -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $page_color === 'default' ? 'active' : ''; ?>">
                                <input type="radio" name="page_color" value="default" <?php echo $page_color === 'default' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #ffffff; color: #1f2937;">
                                    Default White
                                </div>
                                <small class="text-muted d-block text-center">Clean & Bright</small>
                            </label>
                        </div>

                        <!-- Light Gray -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $page_color === 'light-gray' ? 'active' : ''; ?>">
                                <input type="radio" name="page_color" value="light-gray" <?php echo $page_color === 'light-gray' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #f5f7fa; color: #1f2937;">
                                    Light Gray
                                </div>
                                <small class="text-muted d-block text-center">Soft & Subtle</small>
                            </label>
                        </div>

                        <!-- Warm Beige -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $page_color === 'warm-beige' ? 'active' : ''; ?>">
                                <input type="radio" name="page_color" value="warm-beige" <?php echo $page_color === 'warm-beige' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #faf8f5; color: #1f2937;">
                                    Warm Beige
                                </div>
                                <small class="text-muted d-block text-center">Cozy & Warm</small>
                            </label>
                        </div>

                        <!-- Cream -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $page_color === 'cream' ? 'active' : ''; ?>">
                                <input type="radio" name="page_color" value="cream" <?php echo $page_color === 'cream' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #fffef9; color: #1f2937;">
                                    Cream
                                </div>
                                <small class="text-muted d-block text-center">Easy on Eyes</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sidebar Color -->
        <section class="mb-5">
            <div class="card shadow-sm settings-card">
                <div class="card-header text-white" style="background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-bars me-2"></i>Sidebar Color
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Customize your sidebar background color</p>
                    <div class="row g-3">
                        <!-- Default -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $sidebar_color === 'default' ? 'active' : ''; ?>">
                                <input type="radio" name="sidebar_color" value="default" <?php echo $sidebar_color === 'default' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #ffffff; color: #1f2937; border-left: 4px solid #667eea;">
                                    Pure White
                                </div>
                                <small class="text-muted d-block text-center">Classic</small>
                            </label>
                        </div>

                        <!-- Dark Slate -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $sidebar_color === 'dark-slate' ? 'active' : ''; ?>">
                                <input type="radio" name="sidebar_color" value="dark-slate" <?php echo $sidebar_color === 'dark-slate' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #1e293b; color: #ffffff; border-left: 4px solid #667eea;">
                                    Dark Slate
                                </div>
                                <small class="text-muted d-block text-center">Professional</small>
                            </label>
                        </div>

                        <!-- Navy Blue -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $sidebar_color === 'navy-blue' ? 'active' : ''; ?>">
                                <input type="radio" name="sidebar_color" value="navy-blue" <?php echo $sidebar_color === 'navy-blue' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #1e40af; color: #ffffff; border-left: 4px solid #3b82f6;">
                                    Navy Blue
                                </div>
                                <small class="text-muted d-block text-center">Corporate</small>
                            </label>
                        </div>

                        <!-- Deep Purple -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $sidebar_color === 'deep-purple' ? 'active' : ''; ?>">
                                <input type="radio" name="sidebar_color" value="deep-purple" <?php echo $sidebar_color === 'deep-purple' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #4c1d95; color: #ffffff; border-left: 4px solid #8b5cf6;">
                                    Deep Purple
                                </div>
                                <small class="text-muted d-block text-center">Royal</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Header/Navbar Color -->
        <section class="mb-5">
            <div class="card shadow-sm settings-card">
                <div class="card-header text-white" style="background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-window-maximize me-2"></i>Header Bar Color
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">Choose your top navigation bar color scheme</p>
                    <div class="row g-3">
                        <!-- Default Gradient -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $header_color === 'default' ? 'active' : ''; ?>">
                                <input type="radio" name="header_color" value="default" <?php echo $header_color === 'default' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff;">
                                    Purple Gradient
                                </div>
                                <small class="text-muted d-block text-center">Vibrant</small>
                            </label>
                        </div>

                        <!-- Blue Gradient -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $header_color === 'blue-gradient' ? 'active' : ''; ?>">
                                <input type="radio" name="header_color" value="blue-gradient" <?php echo $header_color === 'blue-gradient' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: linear-gradient(135deg, #2196f3 0%, #1565c0 100%); color: #ffffff;">
                                    Blue Gradient
                                </div>
                                <small class="text-muted d-block text-center">Professional</small>
                            </label>
                        </div>

                        <!-- Dark Solid -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $header_color === 'dark-solid' ? 'active' : ''; ?>">
                                <input type="radio" name="header_color" value="dark-solid" <?php echo $header_color === 'dark-solid' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: #1f2937; color: #ffffff;">
                                    Dark Solid
                                </div>
                                <small class="text-muted d-block text-center">Modern</small>
                            </label>
                        </div>

                        <!-- Teal Gradient -->
                        <div class="col-md-3">
                            <label class="color-option <?php echo $header_color === 'teal-gradient' ? 'active' : ''; ?>">
                                <input type="radio" name="header_color" value="teal-gradient" <?php echo $header_color === 'teal-gradient' ? 'checked' : ''; ?>>
                                <div class="color-preview-box" style="background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #ffffff;">
                                    Teal Gradient
                                </div>
                                <small class="text-muted d-block text-center">Fresh</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Typography Settings -->
        <section class="mb-5">
            <div class="card shadow-sm settings-card">
                <div class="card-header text-white" style="background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-font me-2"></i>Typography
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold">Font Family</label>
                            <select name="font_family" class="form-select" id="fontFamilySelect">
                                <option value="default" <?php echo $font_family === 'default' ? 'selected' : ''; ?>>Default (Sans-serif)</option>
                                <option value="serif" <?php echo $font_family === 'serif' ? 'selected' : ''; ?>>Serif (Traditional)</option>
                                <option value="monospace" <?php echo $font_family === 'monospace' ? 'selected' : ''; ?>>Monospace (Code)</option>
                                <option value="inter" <?php echo $font_family === 'inter' ? 'selected' : ''; ?>>Inter (Modern)</option>
                                <option value="roboto" <?php echo $font_family === 'roboto' ? 'selected' : ''; ?>>Roboto (Clean)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold">Font Size</label>
                            <select name="font_size" class="form-select" id="fontSizeSelect">
                                <option value="small" <?php echo $font_size === 'small' ? 'selected' : ''; ?>>Small (14px)</option>
                                <option value="medium" <?php echo $font_size === 'medium' ? 'selected' : ''; ?>>Medium (16px)</option>
                                <option value="large" <?php echo $font_size === 'large' ? 'selected' : ''; ?>>Large (18px)</option>
                                <option value="extra-large" <?php echo $font_size === 'extra-large' ? 'selected' : ''; ?>>Extra Large (20px)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label fw-bold">Font Weight</label>
                            <select name="font_style" class="form-select" id="fontStyleSelect">
                                <option value="normal" <?php echo $font_style === 'normal' ? 'selected' : ''; ?>>Normal (400)</option>
                                <option value="medium" <?php echo $font_style === 'medium' ? 'selected' : ''; ?>>Medium (500)</option>
                                <option value="semibold" <?php echo $font_style === 'semibold' ? 'selected' : ''; ?>>Semi-Bold (600)</option>
                                <option value="bold" <?php echo $font_style === 'bold' ? 'selected' : ''; ?>>Bold (700)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Font Preview</label>
                        <div class="font-preview" id="fontPreview">
                            <div class="mb-2">The quick brown fox jumps over the lazy dog.</div>
                            <div class="mb-2">ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
                            <div>abcdefghijklmnopqrstuvwxyz 0123456789</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Layout Preferences -->
        <section class="mb-5">
            <div class="card shadow-sm settings-card">
                <div class="card-header text-white" style="background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-columns me-2"></i>Layout & Page Size
                    </h5>
                </div>
                <div class="card-body">
                    <label class="form-label fw-bold">Content Width</label>
                    <p class="text-muted mb-4">Choose how wide the content area should be on your screen</p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="theme-preview <?php echo $layout_preference === 'compact' ? 'active' : ''; ?>" style="background: #f8f9fa;">
                                <input type="radio" name="layout_preference" value="compact" <?php echo $layout_preference === 'compact' ? 'checked' : ''; ?>>
                                <div class="text-center">
                                    <i class="fas fa-compress-alt fa-2x text-primary mb-2"></i>
                                    <h6>Compact</h6>
                                    <p class="small text-muted mb-2">1000px max</p>
                                    <small class="text-muted">Best for focus</small>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="theme-preview <?php echo $layout_preference === 'standard' ? 'active' : ''; ?>" style="background: #f8f9fa;">
                                <input type="radio" name="layout_preference" value="standard" <?php echo $layout_preference === 'standard' ? 'checked' : ''; ?>>
                                <div class="text-center">
                                    <i class="fas fa-expand-alt fa-2x text-primary mb-2"></i>
                                    <h6>Standard</h6>
                                    <p class="small text-muted mb-2">1200px max</p>
                                    <small class="text-muted">Balanced</small>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="theme-preview <?php echo $layout_preference === 'spacious' ? 'active' : ''; ?>" style="background: #f8f9fa;">
                                <input type="radio" name="layout_preference" value="spacious" <?php echo $layout_preference === 'spacious' ? 'checked' : ''; ?>>
                                <div class="text-center">
                                    <i class="fas fa-arrows-alt-h fa-2x text-primary mb-2"></i>
                                    <h6>Spacious</h6>
                                    <p class="small text-muted mb-2">1400px max</p>
                                    <small class="text-muted">More content</small>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="theme-preview <?php echo $layout_preference === 'full' ? 'active' : ''; ?>" style="background: #f8f9fa;">
                                <input type="radio" name="layout_preference" value="full" <?php echo $layout_preference === 'full' ? 'checked' : ''; ?>>
                                <div class="text-center">
                                    <i class="fas fa-expand fa-2x text-primary mb-2"></i>
                                    <h6>Full Width</h6>
                                    <p class="small text-muted mb-2">100% width</p>
                                    <small class="text-muted">Maximum space</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Preview Section -->
        <section class="mb-5">
            <div class="card shadow-sm settings-card">
                <div class="card-header text-white" style="background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-eye me-2"></i>Live Preview
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">See how your selections will look</p>
                    <div id="livePreview" class="p-4 border rounded" style="background: #ffffff;">
                        <div class="mb-3">
                            <h4 id="previewHeader" class="mb-3" style="padding: 1rem; color: white; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-star me-2"></i>Sample Header
                            </h4>
                        </div>
                        <div id="previewSidebar" class="mb-3 p-3 border-start border-4" style="background: #ffffff; border-color: #667eea !important;">
                            <h6 class="mb-2"><i class="fas fa-home me-2"></i>Sidebar Navigation</h6>
                            <p class="text-muted small mb-0">Sample sidebar content</p>
                        </div>
                        <div id="previewContent" class="p-3 rounded" style="background: #ffffff;">
                            <p id="previewText" class="mb-2">This is how your text content will appear with the selected font and styling.</p>
                            <button class="btn btn-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                                Sample Button
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Save Button -->
        <div class="d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-outline-secondary btn-lg" onclick="resetToDefaults()">
                <i class="fas fa-undo me-2"></i>Reset to Defaults
            </button>
            <button type="submit" name="save_appearance" class="btn btn-primary btn-lg px-5 shadow">
                <i class="fas fa-save me-2"></i>Save Appearance Settings
            </button>
        </div>
    </form>
</main>

<script>
// Color scheme mappings
const colorSchemes = {
    'default': { start: '#667eea', end: '#764ba2' },
    'ocean': { start: '#2196f3', end: '#00bcd4' },
    'sunset': { start: '#ff9800', end: '#ff5722' },
    'forest': { start: '#4caf50', end: '#2e7d32' }
};

const pageColors = {
    'default': '#ffffff',
    'light-gray': '#f5f7fa',
    'warm-beige': '#faf8f5',
    'cream': '#fffef9'
};

const sidebarColors = {
    'default': { bg: '#ffffff', text: '#1f2937' },
    'dark-slate': { bg: '#1e293b', text: '#ffffff' },
    'navy-blue': { bg: '#1e40af', text: '#ffffff' },
    'deep-purple': { bg: '#4c1d95', text: '#ffffff' }
};

const headerColors = {
    'default': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'blue-gradient': 'linear-gradient(135deg, #2196f3 0%, #1565c0 100%)',
    'dark-solid': '#1f2937',
    'teal-gradient': 'linear-gradient(135deg, #14b8a6 0%, #0d9488 100%)'
};

const fontFamilies = {
    'default': '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    'serif': 'Georgia, "Times New Roman", serif',
    'monospace': '"Courier New", Courier, monospace',
    'inter': '"Inter", -apple-system, BlinkMacSystemFont, sans-serif',
    'roboto': '"Roboto", -apple-system, BlinkMacSystemFont, sans-serif'
};

const fontSizes = {
    'small': '14px',
    'medium': '16px',
    'large': '18px',
    'extra-large': '20px'
};

const fontWeights = {
    'normal': '400',
    'medium': '500',
    'semibold': '600',
    'bold': '700'
};

// Make theme preview cards clickable
document.querySelectorAll('.theme-preview, .color-option').forEach(preview => {
    preview.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            
            // Remove active class from siblings
            const parent = this.parentElement.parentElement;
            parent.querySelectorAll('.theme-preview, .color-option').forEach(p => {
                p.classList.remove('active');
            });
            
            // Add active class to clicked preview
            this.classList.add('active');
            
            // Update live preview
            updateLivePreview();
        }
    });
});

// Font preview updates
document.getElementById('fontFamilySelect').addEventListener('change', function() {
    const fontPreview = document.getElementById('fontPreview');
    fontPreview.style.fontFamily = fontFamilies[this.value];
    updateLivePreview();
});

document.getElementById('fontSizeSelect').addEventListener('change', function() {
    const fontPreview = document.getElementById('fontPreview');
    fontPreview.style.fontSize = fontSizes[this.value];
    updateLivePreview();
});

document.getElementById('fontStyleSelect').addEventListener('change', function() {
    const fontPreview = document.getElementById('fontPreview');
    fontPreview.style.fontWeight = fontWeights[this.value];
    updateLivePreview();
});

// Initialize font preview
document.getElementById('fontFamilySelect').dispatchEvent(new Event('change'));
document.getElementById('fontSizeSelect').dispatchEvent(new Event('change'));
document.getElementById('fontStyleSelect').dispatchEvent(new Event('change'));

// Update live preview
function updateLivePreview() {
    const colorScheme = document.querySelector('input[name="color_scheme"]:checked')?.value || 'default';
    const pageColor = document.querySelector('input[name="page_color"]:checked')?.value || 'default';
    const sidebarColor = document.querySelector('input[name="sidebar_color"]:checked')?.value || 'default';
    const headerColor = document.querySelector('input[name="header_color"]:checked')?.value || 'default';
    const fontFamily = document.getElementById('fontFamilySelect').value;
    const fontSize = document.getElementById('fontSizeSelect').value;
    const fontWeight = document.getElementById('fontStyleSelect').value;

    // Update preview elements
    const previewContainer = document.getElementById('livePreview');
    const previewHeader = document.getElementById('previewHeader');
    const previewSidebar = document.getElementById('previewSidebar');
    const previewContent = document.getElementById('previewContent');
    const previewText = document.getElementById('previewText');

    // Apply colors
    previewContainer.style.background = pageColors[pageColor];
    previewHeader.style.background = headerColors[headerColor];
    
    const scheme = colorSchemes[colorScheme];
    previewSidebar.style.background = sidebarColors[sidebarColor].bg;
    previewSidebar.style.color = sidebarColors[sidebarColor].text;
    previewSidebar.style.borderColor = scheme.start + ' !important';
    
    previewContent.style.background = pageColors[pageColor];
    
    // Apply typography
    previewText.style.fontFamily = fontFamilies[fontFamily];
    previewText.style.fontSize = fontSizes[fontSize];
    previewText.style.fontWeight = fontWeights[fontWeight];
    
    // Update button gradient
    const btn = previewContent.querySelector('.btn');
    btn.style.background = `linear-gradient(135deg, ${scheme.start} 0%, ${scheme.end} 100%)`;
}

// Reset to defaults
function resetToDefaults() {
    if (confirm('Are you sure you want to reset all appearance settings to defaults? This cannot be undone.')) {
        document.querySelector('input[name="theme"][value="light"]').checked = true;
        document.querySelector('input[name="color_scheme"][value="default"]').checked = true;
        document.querySelector('input[name="page_color"][value="default"]').checked = true;
        document.querySelector('input[name="sidebar_color"][value="default"]').checked = true;
        document.querySelector('input[name="header_color"][value="default"]').checked = true;
        document.getElementById('fontFamilySelect').value = 'default';
        document.getElementById('fontSizeSelect').value = 'medium';
        document.getElementById('fontStyleSelect').value = 'normal';
        document.querySelector('input[name="layout_preference"][value="standard"]').checked = true;
        
        // Update active classes
        document.querySelectorAll('.theme-preview, .color-option').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('input:checked').forEach(input => {
            input.closest('.theme-preview, .color-option')?.classList.add('active');
        });
        
        updateLivePreview();
    }
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
    
    // Initial preview update
    updateLivePreview();
});

// Update preview when any radio button changes
document.querySelectorAll('input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', updateLivePreview);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>