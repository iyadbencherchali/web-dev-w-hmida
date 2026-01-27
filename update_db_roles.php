<?php
require_once 'config.php';

echo "<h1>Updating Database Schema for Roles...</h1>";

try {
    // 1. Add 'role' column if not exists
    // Simple approach: Try to add it, if it fails, it likely exists.
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'student'");
        echo "Added 'role' column.<br>";
    } catch (PDOException $e) {
        // Ignore error if column exists (Code 42S21 in MySQL)
        echo "Column 'role' check passed (likely already exists).<br>";
    }

    // 2. Set Instructors
    $pdo->exec("UPDATE users SET role = 'instructor' WHERE id IN (SELECT user_id FROM instructors)");
    echo "Updated Instructor roles.<br>";

    // 3. Create Admin User
    $admin_email = 'admin@formationpro.dz';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    
    if (!$stmt->fetch()) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES ('Admin', 'System', ?, ?, 'admin')");
        $stmt->execute([$admin_email, $pass]);
        echo "Created Admin user (admin@formationpro.dz / admin123)<br>";
    } else {
        $pdo->exec("UPDATE users SET role = 'admin' WHERE email = '$admin_email'");
        echo "Admin user exists, verified role.<br>";
    }

} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage());
}

echo "<h2>Database Role Update Completed!</h2>";
?>
