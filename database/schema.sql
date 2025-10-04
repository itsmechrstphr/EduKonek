-- Combined Student Management System Database Schema
-- Drop database if exists and recreate for fresh start
DROP DATABASE IF EXISTS edu_konek;
CREATE DATABASE edu_konek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edu_konek;

-- Users table for authentication with course, year_level, department, section, and appearance settings
CREATE TABLE users (
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
    
    -- Student academic information
    department VARCHAR(50) NULL COMMENT 'Student department (e.g., CBAT.COM, COTE, CRIM, HS)',
    course VARCHAR(100) NULL COMMENT 'Student program/course (e.g., BS Computer Science)',
    year_level VARCHAR(50) NULL COMMENT 'Student year level (e.g., 1st Year, Grade 11)',
    section VARCHAR(10) NULL COMMENT 'Student section (e.g., 1a, 2b, a, b)',
    academic_track ENUM('regular', 'irregular') DEFAULT 'regular',
    status ENUM('enrolled', 'not_enrolled', 'pending', 'dropped', 'graduate') DEFAULT 'not_enrolled',
    
    -- Appearance settings
    theme ENUM('light', 'dark', 'blue', 'green') DEFAULT 'light',
    color_scheme ENUM('default', 'ocean', 'sunset', 'forest') DEFAULT 'default',
    font_family ENUM('default', 'serif', 'monospace', 'inter', 'roboto') DEFAULT 'default',
    font_size ENUM('small', 'medium', 'large', 'extra-large') DEFAULT 'medium',
    font_style ENUM('normal', 'medium', 'semibold', 'bold') DEFAULT 'normal',
    layout_preference ENUM('compact', 'standard', 'spacious', 'full') DEFAULT 'standard',
    
    -- New appearance customization options
    sidebar_color ENUM('default', 'dark-slate', 'navy-blue', 'deep-purple') DEFAULT 'default',
    header_color ENUM('default', 'blue-gradient', 'dark-solid', 'teal-gradient') DEFAULT 'default',
    page_color ENUM('default', 'light-gray', 'warm-beige', 'cream') DEFAULT 'default',
    
    -- Legacy appearance fields (keeping for backward compatibility)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table for school events
CREATE TABLE events (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Class schedules table with student targeting criteria
CREATE TABLE schedules (
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
    
    -- Legacy fields (kept for backward compatibility)
    department VARCHAR(50) NULL COMMENT 'Legacy department field',
    course VARCHAR(100) NULL COMMENT 'Legacy course field',
    year_level VARCHAR(50) NULL COMMENT 'Legacy year level field',
    
    -- NEW: Student targeting criteria - schedules only show to students matching ALL criteria
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grades table
CREATE TABLE grades (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance table
CREATE TABLE attendance (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE notifications (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role, first_name, last_name, created_at) VALUES
('admin', 'admin@school.edu', '$2y$10$vI5nJQvKZ3YhKxFLOqLf3.wOjJ5cF9Q7G6jK8sM3hP7qN4tR5sU2W', 'admin', 'System', 'Administrator', NOW());

-- Insert sample faculty members (password: password)
INSERT INTO users (username, email, password, role, first_name, last_name, created_at) VALUES
('prof.smith', 'smith@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'John', 'Smith', NOW()),
('dr.jones', 'jones@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Sarah', 'Jones', NOW()),
('prof.williams', 'williams@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'faculty', 'Michael', 'Williams', NOW());

-- Insert sample students with complete profile (password: password)
INSERT INTO users (username, email, password, role, first_name, last_name, department, course, year_level, section, academic_track, status, created_at) VALUES
('student1', 'student1@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Alice', 'Johnson', 'CBAT.COM', 'BS Computer Science', '1st Year', '1a', 'regular', 'enrolled', NOW()),
('student2', 'student2@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Bob', 'Williams', 'CBAT.COM', 'BS Information Technology', '2nd Year', '2b', 'regular', 'enrolled', NOW()),
('student3', 'student3@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Charlie', 'Brown', 'CRIM', 'BS Criminology', '3rd Year', '3a', 'irregular', 'enrolled', NOW()),
('student4', 'student4@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Diana', 'Martinez', 'COTE', 'Bachelor of Elementary Education', '1st Year', '1b', 'regular', 'pending', NOW()),
('student5', 'student5@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Ethan', 'Davis', 'HS', 'General Education', 'Grade 11', 'a', 'regular', 'not_enrolled', NOW());

-- Insert sample events
INSERT INTO events (title, description, event_date, event_time, location, created_by, created_at) VALUES
('Welcome Assembly', 'Opening ceremony for new academic year', '2025-01-15', '09:00:00', 'Main Auditorium', 1, NOW()),
('Sports Day', 'Annual inter-school sports competition', '2025-02-20', '08:00:00', 'Sports Complex', 1, NOW()),
('Science Fair', 'Student science project exhibition', '2025-03-10', '10:00:00', 'Science Building', 1, NOW());

-- Insert sample schedules with targeting criteria
INSERT INTO schedules (subject, teacher_id, class_name, day_of_week, start_time, end_time, room, duration, days, target_department, target_year_level, target_section, target_course, target_academic_track, target_status, created_at) VALUES
('Introduction to Programming', 2, 'CBAT.COM-1st Year-1a', 'Monday', '08:00:00', '10:00:00', 'Room 101', '8:00 AM - 10:00 AM', 'Monday', 'CBAT.COM', '1st Year', '1a', 'BS Computer Science', 'regular', 'enrolled', NOW()),
('Data Structures', 3, 'CBAT.COM-2nd Year-2b', 'Tuesday', '10:00:00', '12:00:00', 'Lab 201', '10:00 AM - 12:00 PM', 'Tuesday', 'CBAT.COM', '2nd Year', '2b', 'BS Information Technology', 'regular', 'enrolled', NOW()),
('Criminology Theory', 2, 'CRIM-3rd Year-3a', 'Wednesday', '13:00:00', '15:00:00', 'Room 105', '1:00 PM - 3:00 PM', 'Wednesday', 'CRIM', '3rd Year', '3a', 'BS Criminology', 'irregular', 'enrolled', NOW()),
('Teaching Methods', 4, 'COTE-1st Year-1b', 'Thursday', '09:00:00', '11:00:00', 'Room 102', '9:00 AM - 11:00 AM', 'Thursday', 'COTE', '1st Year', '1b', 'Bachelor of Elementary Education', 'regular', 'pending', NOW()),
('General Mathematics', 3, 'HS-Grade 11-a', 'Friday', '14:00:00', '16:00:00', 'Room 203', '2:00 PM - 4:00 PM', 'Friday', 'HS', 'Grade 11', 'a', 'General Education', 'regular', 'not_enrolled', NOW());

-- Insert sample grades
INSERT INTO grades (student_id, subject, grade, semester, academic_year, faculty_id, created_at) VALUES
(4, 'Introduction to Programming', 85.50, 'First Semester', '2024-2025', 2, NOW()),
(5, 'Data Structures', 78.00, 'First Semester', '2024-2025', 3, NOW()),
(6, 'Criminology Theory', 92.75, 'First Semester', '2024-2025', 2, NOW());

-- Insert sample attendance records
INSERT INTO attendance (student_id, subject, attendance_date, status, faculty_id, created_at) VALUES
(4, 'Introduction to Programming', '2025-01-10', 'present', 2, NOW()),
(5, 'Data Structures', '2025-01-10', 'absent', 3, NOW()),
(6, 'Criminology Theory', '2025-01-10', 'late', 2, NOW());

-- Insert sample notifications
INSERT INTO notifications (message, recipient_role, sender_id, created_at) VALUES
('Welcome to the new academic year!', 'all', 1, NOW()),
('Faculty meeting scheduled for next week', 'faculty', 1, NOW()),
('Reminder: Submit assignments by Friday', 'student', 2, NOW());

-- Display success message
SELECT 'Database schema created successfully!' AS Status;
SELECT 'Default admin credentials - Username: admin, Password: admin123' AS Info;
SELECT 'New targeting fields added to schedules table for precise student enrollment' AS Update_Info;
