<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=auth_required");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];

    if (isset($_POST['action']) && $_POST['action'] === 'enroll') {
        try {
            // 1. Check if seats are available
            $courseStmt = $pdo->prepare("SELECT max_students FROM courses WHERE id = ?");
            $courseStmt->execute([$course_id]);
            $course = $courseStmt->fetch();

            if ($course && $course['max_students'] <= 0) {
                header("Location: formation.php?error=no_seats");
                exit();
            }

            // 2. Check if already enrolled
            $checkStmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $checkStmt->execute([$user_id, $course_id]);
            
            if ($checkStmt->fetch()) {
                // Already enrolled
                header("Location: dashboard.php?msg=already_enrolled");
                exit();
            }

            // 3. Enroll user and decrement seats
            $pdo->beginTransaction();
            
            // 3.a Ensure student profile exists
            $stmt = $pdo->prepare("INSERT IGNORE INTO students (user_id) VALUES (?)");
            $stmt->execute([$user_id]);

            $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $course_id]);

            $updateStmt = $pdo->prepare("UPDATE courses SET max_students = max_students - 1 WHERE id = ?");
            $updateStmt->execute([$course_id]);

            $pdo->commit();

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
