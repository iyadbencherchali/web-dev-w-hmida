<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=auth_required");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];

    if (isset($_POST['action']) && $_POST['action'] === 'enroll') {
        try {
            // Check if already enrolled
            $checkStmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $checkStmt->execute([$user_id, $course_id]);
            
            if ($checkStmt->fetch()) {
                // Already enrolled
                header("Location: dashboard.php?msg=already_enrolled");
                exit();
            }

            // Enroll user
            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $course_id]);

            header("Location: dashboard.php?msg=enroll_success");
            exit();

        } catch (PDOException $e) {
            die("Error enrolling: " . $e->getMessage());
        }
    }
} else {
    // Invalid request
    header("Location: formation.php");
    exit();
}
?>
