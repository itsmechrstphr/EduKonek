# Edu.Konek Student Management System - Database Setup Guide

## 📋 Prerequisites

Before setting up the database, ensure you have:

- **XAMPP, WAMP, or LAMP** installed
- **PHP 7.4** or higher
- **MySQL 5.7** or higher
- Web browser (Chrome, Firefox, Safari, or Edge)

---

## 🚀 Installation Methods

### Method 1: Automatic Installation (Recommended)

1. **Place Files in Web Server Directory**
   - Copy all project files to your web server root directory
   - XAMPP: `C:\xampp\htdocs\edukonek\`
   - WAMP: `C:\wamp\www\edukonek\`

2. **Access Installation Script**
   - Open your browser and go to: `http://localhost/edukonek/install.php`
   - The script will automatically:
     - Create the database
     - Create all tables
     - Insert default admin user
     - Display installation status

3. **Login**
   - After successful installation, click "Go to Login Page"
   - Use these credentials:
     - **Username:** `admin`
     - **Password:** `password`

4. **Security**
   - **IMPORTANT:** Delete or rename `install.php` after installation

---

### Method 2: Manual Installation via phpMyAdmin

1. **Open phpMyAdmin**
   - Go to: `http://localhost/phpmyadmin`

2. **Import Database**
   - Click on "Import" tab
   - Click "Choose File"
   - Select `schema.sql` from your project folder
   - Click "Go" button

3. **Verify Installation**
   - Check if database `student_management_system` is created
   - Verify all 6 tables exist:
     - `users`
     - `events`
     - `schedules`
     - `grades`
     - `attendance`
     - `notifications`

4. **Access Application**
   - Go to: `http://localhost/edukonek/index.php`
   - Login with:
     - **Username:** `admin`
     - **Password:** `password`

---

## 📊 Database Structure

### Tables Overview

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| **users** | Store all users (admin, faculty, students) | id, username, email, password, role, first_name, last_name |
| **events** | School events and activities | id, title, description, event_date, event_time, location |
| **schedules** | Class schedules | id, subject, teacher_id, class_name, day_of_week, start_time, end_time |
| **grades** | Student grades | id, student_id, subject, grade, semester, academic_year, faculty_id |
| **attendance** | Student attendance records | id, student_id, subject, attendance_date, status, faculty_id |
| **notifications** | System notifications | id, message, recipient_role, receiver_id, sender_id |

---

## 🔧 Configuration

### Database Connection Settings

Edit `config/database.php` if needed:

```php
$host = 'localhost';        // MySQL host
$dbname = 'student_management_system';  // Database name
$username = 'root';         // MySQL username
$password = '';             // MySQL password (empty for default)
```

---

## 👥 Default User Accounts

### Admin Account
- **Username:** admin
- **Password:** password
- **Role:** Administrator
- **Access:** Full system access

### Sample Faculty Accounts
- **Username:** prof.smith | **Password:** password
- **Username:** dr.jones | **Password:** password

### Sample Student Accounts
- **Username:** student1 | **Password:** password
- **Username:** student2 | **Password:** password
- **Username:** student3 | **Password:** password

---

## 🎯 Feature Testing Checklist

### Admin Dashboard
- ✅ View all users (students, faculty, admins)
- ✅ Create new users (faculty/admin in manage_users.php)
- ✅ Create new students (manage_students.php)
- ✅ Update user information
- ✅ Delete users
- ✅ View charts and graphs (auto-updates after CRUD operations)

### Faculty Management (manage_users.php)
- ✅ Create faculty accounts that can login
- ✅ Create admin accounts
- ✅ Edit faculty/admin details
- ✅ Delete users (except self)
- ✅ View metrics cards
- ✅ View bar chart (updates automatically)

### Student Management (manage_students.php)
- ✅ Create student accounts
- ✅ Delete students
- ✅ View all students

### Event Management (manage_events.php)
- ✅ Create events
- ✅ Update events
- ✅ Delete events
- ✅ View event list

### Schedule Management (manage_schedules.php)
- ✅ Create class schedules
- ✅ Assign faculty to classes
- ✅ Update schedules
- ✅ Delete schedules

### Notifications (manage_notifications.php)
- ✅ Send notifications to all users
- ✅ Send to specific role (students/faculty)
- ✅ View sent notifications
- ✅ Update notifications
- ✅ Delete notifications

---

## 📈 Data Flow

### User Creation Flow
1. Admin creates user in `manage_users.php` or `manage_students.php`
2. Data saved to `users` table
3. Password hashed using `password_hash()`
4. Charts/graphs auto-update on page
5. User can login with credentials
6. Redirected to role-specific dashboard

### Login Flow
1. User enters credentials in `index.php`
2. `login.php` validates against `users` table
3. Password verified using `password_verify()`
4. Session variables set
5. Redirect to dashboard based on role:
   - Admin → `admin_dashboard.php`
   - Faculty → `faculty_dashboard.php`
   - Student → `student_dashboard.php`

---

## 🔍 Troubleshooting

### Error: "An error occurred. Please try again."

**Solution:**
1. Enable error reporting in `manage_users.php` (already enabled)
2. Check PHP error logs:
   - XAMPP: `C:\xampp\apache\logs\error.log`
   - WAMP: `C:\wamp\logs\php_error.log`
3. Verify database connection in `config/database.php`

### Charts Not Updating

**Solution:**
1. Clear browser cache
2. Hard refresh (Ctrl + F5)
3. Verify Chart.js is loaded: `<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>`

### Faculty Account Not Logging In

**Solution:**
1. Check `users` table - verify `role` column is 'faculty'
2. Check `password` column - should be hashed string
3. Verify credentials match exactly
4. Try resetting password via database

### Tables Not Created

**Solution:**
1. Run `install.php` script
2. Or manually import `schema.sql` via phpMyAdmin
3. Check MySQL user has CREATE privileges

---

## 📝 Important Notes

1. **Password Security:** All passwords are hashed using PHP's `password_hash()` function
2. **No AJAX:** All forms use traditional POST submission with page reload
3. **Toast Notifications:** Success/error messages shown via JavaScript toast after page load
4. **Chart Updates:** Charts query database on each page load, so they're always current
5. **Role-Based Access:** Each role has specific permissions and dashboard view

---

## 🔐 Security Recommendations

1. **Change Default Password:** Immediately after first login
2. **Delete install.php:** After successful installation
3. **Update Database Credentials:** Use strong passwords for production
4. **Enable HTTPS:** Use SSL certificate for production deployment
5. **Regular Backups:** Backup database regularly
6. **Update PHP:** Keep PHP and MySQL updated

---

## 📞 Support

If you encounter issues:

1. Check PHP error logs
2. Verify MySQL service is running
3. Ensure all files are in correct directory
4. Check file permissions (755 for folders, 644 for files)
5. Verify database user has sufficient privileges

---

## ✅ Installation Verification

After installation, verify these features work:

- [ ] Login with admin account
- [ ] Create new faculty account
- [ ] Login with newly created faculty account
- [ ] Create new student account
- [ ] View charts updating after adding users
- [ ] Create an event
- [ ] Create a schedule
- [ ] Send a notification
- [ ] Logout and login again

---

**Installation Complete!** 🎉

Your Edu.Konek Student Management System is now ready to use.