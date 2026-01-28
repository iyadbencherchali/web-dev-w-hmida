<?php
/**
 * ENROLL.PHP
 * handles one-click enrollment for free courses or direct registrations.
 */
session_start();
require_once 'config.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=auth_required");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['course_id'])) {
    header("Location: formation.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = $_POST['course_id'];

try {
    $pdo->beginTransaction();

    // 2. Ensure Student Profile Exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO students (user_id) VALUES (?)");
    $stmt->execute([$user_id]);

    // 3. Check Course Status and Seats
    $stmt = $pdo->prepare("SELECT title, max_students FROM courses WHERE id = ? FOR UPDATE");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        $pdo->rollBack();
        header("Location: formation.php?error=not_found");
        exit();
    }

    // Capacity Logic
    if ($course['max_students'] !== null && $course['max_students'] <= 0) {
        $pdo->rollBack();
        header("Location: formation.php?error=course_full&title=" . urlencode($course['title']));
        exit();
    }

    // 4. Enroll User
    $stmt = $pdo->prepare("INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)");
    if ($stmt->execute([$user_id, $course_id])) {
        // Only decrement if a new row was actually inserted
        if ($stmt->rowCount() > 0 && $course['max_students'] !== null) {
            $stmt = $pdo->prepare("UPDATE courses SET max_students = max_students - 1 WHERE id = ?");
            $stmt->execute([$course_id]);
        }
    }

    $pdo->commit();
    header("Location: dashboard.php?msg=enroll_success");
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    header("Location: formation.php?error=internal_error");
    exit();
}
