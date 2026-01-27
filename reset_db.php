<?php
// reset_db.php
// Resetting to original DB name: cours_db

$host = 'localhost';
$username = 'root';
$password = ''; 

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Resetting Database...</h1>";

    // 1. Drop Database
    $pdo->exec("DROP DATABASE IF EXISTS cours_db");
    echo "Dropped database 'cours_db'.<br>";

    // 2. Create Database
    $pdo->exec("CREATE DATABASE cours_db");
    echo "Created database 'cours_db'.<br>";
    $pdo->exec("USE cours_db");

    // 3. Create Tables
    $pdo->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'student',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE instructors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        bio TEXT,
        expertise VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        instructor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        difficulty_level VARCHAR(50),
        is_published TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (instructor_id) REFERENCES users(id)
    )");

    $pdo->exec("CREATE TABLE enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        progress_percentage INT DEFAULT 0,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id)
    )");

    $pdo->exec("CREATE TABLE payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    echo "<h2>Schema v3 Created. Finding setup_data.php...</h2>";

} catch (PDOException $e) {
    die("Error during reset: " . $e->getMessage());
}

require_once 'setup_data.php';
require_once 'update_db_roles.php'; 
?>
