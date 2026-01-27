<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        die("Veuillez remplir tous les champs.");
    }

    if ($password !== $confirm_password) {
        die("Les mots de passe ne correspondent pas.");
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die("Cet email est déjà enregistré.");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 1. Insert into users table
        $sql = "INSERT INTO users (first_name, last_name, email, password_hash, is_active, role) VALUES (?, ?, ?, ?, ?, 'student')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $first_name, 
            $last_name, 
            $email, 
            $hashed_password, 
            1 // is_active
        ]);
        
        $user_id = $pdo->lastInsertId();

        // 2. Insert into students table (REMOVED - Roles are now in users table)
        // $sql_student = "INSERT INTO students (user_id) VALUES (?)";
        // ...

        $pdo->commit();

        // Redirect to login page
        header("Location: login.php?signup=success");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Erreur lors de l'inscription : " . $e->getMessage();
    }

} else {
    // If accessed directly without POST, redirect to form
    header("Location: signup.html");
    exit();
}
?>
