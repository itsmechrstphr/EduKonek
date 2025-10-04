<?php
/**
 * Database Configuration File
 * Provides a stable PDO connection for the Student Information System
 * This file is included by all PHP files that need database access
 */

// Database connection parameters
$host = 'localhost';
$dbname = 'edu_konek';
$username = 'root';
$password = '';

try {
    // First, connect to MySQL server without specifying database
    $pdo_temp = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the database exists
    $stmt = $pdo_temp->query("SHOW DATABASES LIKE '$dbname'");
    $database_exists = $stmt->fetch();

    // Create database if it doesn't exist
    if (!$database_exists) {
        $pdo_temp->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        error_log("Database '$dbname' created successfully");
        
        // Connect to the new database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create all tables
        createTables($pdo);
    } else {
        // Connect to existing database
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verify tables exist, create if missing
        verifyAndCreateTables($pdo);
    }
    
    // Configure PDO settings for security and reliability
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Cleanup temporary connection
    $pdo_temp = null;
    
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Unable to connect to the database. Please contact system administrator. Error: " . $e->getMessage());
}

/**
 * Create all database tables matching schema.sql
 */
function createTables($pdo) {
    try {
        // Users table with all fields including section
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'faculty', 'student') NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            phone VARCHAR(20) NULL,
            bio TEXT NULL,
            avatar VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
            department VARCHAR(50) NULL COMMENT 'Student department (e.g., CBAT.COM, COTE, CRIM, HS)',
            course VARCHAR(100) NULL COMMENT 'Student program/course (e.g., BS Computer Science)',
            year_level VARCHAR(50) NULL COMMENT 'Student year level (e.g., 1st Year, Grade 11)',
            section VARCHAR(10) NULL COMMENT 'Student section (e.g., 1a, 2b, a, b)',
            academic_track ENUM('regular', 'irregular') DEFAULT 'regular',
            status ENUM('enrolled', 'not_enrolled', 'pending', 'dropped', 'graduate') DEFAULT 'not_enrolled',
            theme ENUM('light', 'dark', 'blue', 'green') DEFAULT 'light',
            color_scheme ENUM('default', 'ocean', 'sunset', 'forest') DEFAULT 'default',
            font_family ENUM('default', 'serif', 'monospace', 'inter', 'roboto') DEFAULT 'default',
            font_size ENUM('small', 'medium', 'large', 'extra-large') DEFAULT 'medium',
            font_style ENUM('normal', 'medium', 'semibold', 'bold') DEFAULT 'normal',
            layout_preference ENUM('compact', 'standard', 'spacious', 'full') DEFAULT 'standard',
            sidebar_color ENUM('default', 'dark-slate', 'navy-blue', 'deep-purple') DEFAULT 'default',
            header_color ENUM('default', 'blue-gradient', 'dark-solid', 'teal-gradient') DEFAULT 'default',
            page_color ENUM('default', 'light-gray', 'warm-beige', 'cream') DEFAULT 'default',
            layout ENUM('compact', 'spacious') DEFAULT 'spacious',
            card_style ENUM('flat', 'shadowed') DEFAULT 'shadowed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_role (role),
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_student_status (status),
            INDEX idx_academic_track (academic_track),
            INDEX idx_department (department),
            INDEX idx_course (course),
            INDEX idx_year_level (year_level),
            INDEX idx_section (section)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Events table
        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATE NOT NULL,
            event_time TIME,
            location VARCHAR(255),
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_event_date (event_date),
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Schedules table with student targeting criteria
        $pdo->exec("CREATE TABLE IF NOT EXISTS schedules (
            id INT PRIMARY KEY AUTO_INCREMENT,
            subject VARCHAR(100) NOT NULL,
            teacher_id INT,
            class_name VARCHAR(100) NOT NULL,
            day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            room VARCHAR(50) NOT NULL,
            student_ids TEXT COMMENT 'Comma-separated list of student IDs enrolled in this class',
            duration VARCHAR(50) COMMENT 'Time duration (e.g., 8:00 AM - 9:30 AM)',
            days VARCHAR(255) COMMENT 'Comma-separated days (e.g., Monday, Wednesday, Friday)',
            department VARCHAR(50) NULL COMMENT 'Legacy department field',
            course VARCHAR(100) NULL COMMENT 'Legacy course field',
            year_level VARCHAR(50) NULL COMMENT 'Legacy year level field',
            target_department VARCHAR(50) NULL COMMENT 'Target department (e.g., CBAT.COM, COTE, CRIM, HS)',
            target_year_level VARCHAR(50) NULL COMMENT 'Target year level (e.g., 1st Year, Grade 11)',
            target_section VARCHAR(10) NULL COMMENT 'Target section (e.g., 1a, 2b, a, b)',
            target_course VARCHAR(100) NULL COMMENT 'Target course/program (e.g., BS Computer Science)',
            target_academic_track ENUM('regular', 'irregular') NULL COMMENT 'Target academic track',
            target_status ENUM('enrolled', 'not_enrolled', 'pending') NULL COMMENT 'Target student status',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_teacher (teacher_id),
            INDEX idx_day (day_of_week),
            INDEX idx_target_department (target_department),
            INDEX idx_target_course (target_course),
            INDEX idx_target_year_level (target_year_level),
            INDEX idx_target_section (target_section),
            INDEX idx_target_track (target_academic_track),
            INDEX idx_target_status (target_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Grades table
        $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT,
            subject VARCHAR(100) NOT NULL,
            grade DECIMAL(5,2) NOT NULL,
            semester VARCHAR(20),
            academic_year VARCHAR(20),
            faculty_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_student (student_id),
            INDEX idx_faculty (faculty_id),
            INDEX idx_subject (subject)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Attendance table
        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            student_id INT,
            subject VARCHAR(100) NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM('present', 'absent', 'late') NOT NULL,
            faculty_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_student (student_id),
            INDEX idx_faculty (faculty_id),
            INDEX idx_date (attendance_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Notifications table
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            recipient_role ENUM('all', 'student', 'faculty', 'admin') NOT NULL DEFAULT 'all',
            receiver_id INT NULL,
            sender_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_receiver (receiver_id),
            INDEX idx_sender (sender_id),
            INDEX idx_role (recipient_role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Insert default admin user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $adminPassword = '$2y$10$vI5nJQvKZ3YhKxFLOqLf3.wOjJ5cF9Q7G6jK8sM3hP7qN4tR5sU2W';
            $pdo->exec("INSERT INTO users (username, email, password, role, first_name, last_name, created_at) VALUES
                ('admin', 'admin@school.edu', '$adminPassword', 'admin', 'System', 'Administrator', NOW())");
            error_log("Default admin user created (Username: admin, Password: admin123)");
        }
        
        error_log("All database tables created successfully");
    } catch (PDOException $e) {
        error_log("Error creating tables: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Verify tables exist and create if missing, also add new columns to existing tables
 */
function verifyAndCreateTables($pdo) {
    $tables = ['users', 'events', 'schedules', 'grades', 'attendance', 'notifications'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetch()) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        error_log("Missing tables detected: " . implode(', ', $missingTables));
        createTables($pdo);
        return;
    }
    
    // Check and add missing columns to users table
    try {
        $columnsToAdd = [
            ['name' => 'phone', 'definition' => 'VARCHAR(20) NULL', 'after' => 'last_name'],
            ['name' => 'bio', 'definition' => 'TEXT NULL', 'after' => 'phone'],
            ['name' => 'avatar', 'definition' => "VARCHAR(255) DEFAULT 'assets/images/default-avatar.png'", 'after' => 'bio'],
            ['name' => 'department', 'definition' => "VARCHAR(50) NULL COMMENT 'Student department'", 'after' => 'avatar'],
            ['name' => 'course', 'definition' => "VARCHAR(100) NULL COMMENT 'Student program/course'", 'after' => 'department'],
            ['name' => 'year_level', 'definition' => "VARCHAR(50) NULL COMMENT 'Student year level'", 'after' => 'course'],
            ['name' => 'section', 'definition' => "VARCHAR(10) NULL COMMENT 'Student section'", 'after' => 'year_level'],
            ['name' => 'academic_track', 'definition' => "ENUM('regular', 'irregular') DEFAULT 'regular'", 'after' => 'section'],
            ['name' => 'status', 'definition' => "ENUM('enrolled', 'not_enrolled', 'pending', 'dropped', 'graduate') DEFAULT 'not_enrolled'", 'after' => 'academic_track'],
            ['name' => 'theme', 'definition' => "ENUM('light', 'dark', 'blue', 'green') DEFAULT 'light'", 'after' => 'status'],
            ['name' => 'color_scheme', 'definition' => "ENUM('default', 'ocean', 'sunset', 'forest') DEFAULT 'default'", 'after' => 'theme'],
            ['name' => 'font_family', 'definition' => "ENUM('default', 'serif', 'monospace', 'inter', 'roboto') DEFAULT 'default'", 'after' => 'color_scheme'],
            ['name' => 'font_size', 'definition' => "ENUM('small', 'medium', 'large', 'extra-large') DEFAULT 'medium'", 'after' => 'font_family'],
            ['name' => 'font_style', 'definition' => "ENUM('normal', 'medium', 'semibold', 'bold') DEFAULT 'normal'", 'after' => 'font_size'],
            ['name' => 'layout_preference', 'definition' => "ENUM('compact', 'standard', 'spacious', 'full') DEFAULT 'standard'", 'after' => 'font_style'],
            ['name' => 'sidebar_color', 'definition' => "ENUM('default', 'dark-slate', 'navy-blue', 'deep-purple') DEFAULT 'default'", 'after' => 'layout_preference'],
            ['name' => 'header_color', 'definition' => "ENUM('default', 'blue-gradient', 'dark-solid', 'teal-gradient') DEFAULT 'default'", 'after' => 'sidebar_color'],
            ['name' => 'page_color', 'definition' => "ENUM('default', 'light-gray', 'warm-beige', 'cream') DEFAULT 'default'", 'after' => 'header_color']
        ];
        
        foreach ($columnsToAdd as $column) {
            $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '{$column['name']}'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE users ADD COLUMN {$column['name']} {$column['definition']} AFTER {$column['after']}");
                error_log("Added '{$column['name']}' column to users table");
            }
        }
        
        // Create indexes if they don't exist
        $indexes = [
            ['name' => 'idx_department', 'column' => 'department'],
            ['name' => 'idx_course', 'column' => 'course'],
            ['name' => 'idx_year_level', 'column' => 'year_level'],
            ['name' => 'idx_section', 'column' => 'section'],
            ['name' => 'idx_academic_track', 'column' => 'academic_track'],
            ['name' => 'idx_student_status', 'column' => 'status']
        ];
        
        foreach ($indexes as $index) {
            $stmt = $pdo->query("SHOW INDEX FROM users WHERE Key_name = '{$index['name']}'");
            if (!$stmt->fetch()) {
                try {
                    $pdo->exec("CREATE INDEX {$index['name']} ON users({$index['column']})");
                    error_log("Created index '{$index['name']}' on users table");
                } catch (PDOException $e) {
                    // Index might already exist, ignore
                }
            }
        }
        
        // Check and add missing columns to schedules table (targeting fields)
        $scheduleColumns = [
            ['name' => 'target_department', 'definition' => "VARCHAR(50) NULL COMMENT 'Target department'", 'after' => 'year_level'],
            ['name' => 'target_year_level', 'definition' => "VARCHAR(50) NULL COMMENT 'Target year level'", 'after' => 'target_department'],
            ['name' => 'target_section', 'definition' => "VARCHAR(10) NULL COMMENT 'Target section'", 'after' => 'target_year_level'],
            ['name' => 'target_course', 'definition' => "VARCHAR(100) NULL COMMENT 'Target course/program'", 'after' => 'target_section'],
            ['name' => 'target_academic_track', 'definition' => "ENUM('regular', 'irregular') NULL COMMENT 'Target academic track'", 'after' => 'target_course'],
            ['name' => 'target_status', 'definition' => "ENUM('enrolled', 'not_enrolled', 'pending') NULL COMMENT 'Target student status'", 'after' => 'target_academic_track']
        ];
        
        foreach ($scheduleColumns as $column) {
            $stmt = $pdo->query("SHOW COLUMNS FROM schedules LIKE '{$column['name']}'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE schedules ADD COLUMN {$column['name']} {$column['definition']} AFTER {$column['after']}");
                error_log("Added '{$column['name']}' column to schedules table");
            }
        }
        
        // Create indexes for schedules table
        $scheduleIndexes = [
            ['name' => 'idx_target_department', 'column' => 'target_department'],
            ['name' => 'idx_target_course', 'column' => 'target_course'],
            ['name' => 'idx_target_year_level', 'column' => 'target_year_level'],
            ['name' => 'idx_target_section', 'column' => 'target_section'],
            ['name' => 'idx_target_track', 'column' => 'target_academic_track'],
            ['name' => 'idx_target_status', 'column' => 'target_status']
        ];
        
        foreach ($scheduleIndexes as $index) {
            $stmt = $pdo->query("SHOW INDEX FROM schedules WHERE Key_name = '{$index['name']}'");
            if (!$stmt->fetch()) {
                try {
                    $pdo->exec("CREATE INDEX {$index['name']} ON schedules({$index['column']})");
                    error_log("Created index '{$index['name']}' on schedules table");
                } catch (PDOException $e) {
                    // Index might already exist, ignore
                }
            }
        }
        
    } catch (PDOException $e) {
        error_log("Error checking/adding columns: " . $e->getMessage());
    }
}

/**
 * Helper function to execute queries with error handling
 */
function executeQuery($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        error_log("Query: " . $query);
        error_log("Params: " . print_r($params, true));
        return false;
    }
}

/**
 * Helper function to create system notification
 */
function createNotification($pdo, $message, $sender_id, $recipient_role = 'all', $receiver_id = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (message, sender_id, recipient_role, receiver_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$message, $sender_id, $recipient_role, $receiver_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification creation failed: " . $e->getMessage());
        return false;
    }
}
?>
