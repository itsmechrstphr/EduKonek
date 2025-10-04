<?php
// Database setup script
require_once 'config/database.php';

try {
    // Read the schema file
    $sql = file_get_contents('database/schema.sql');
    
    if ($sql === false) {
        die("Error: Could not read schema file");
    }
    
    // Split the SQL file into individual statements
    $queries = explode(';', $sql);
    
    // Execute each query
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    echo "Database setup completed successfully!<br>";
    echo "Default accounts created:<br>";
    echo "- Admin: admin / password<br>";
    echo "- Faculty: prof.smith / password, dr.jones / password<br>";
    echo "- Students: student1, student2, student3 / password<br>";
    echo "<br>You can now <a href='index.php'>login to the system</a>.";
    
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>
