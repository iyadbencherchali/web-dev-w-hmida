<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: instructor_dashboard.php");
    exit();
}

$lesson_id = $_GET['id'];
$instructor_id = $_SESSION['user_id'];

try {
    // Fetch lesson and verify ownership
    $stmt = $pdo->prepare("
        SELECT l.*, c.instructor_id 
        FROM lessons l 
        JOIN courses c ON l.course_id = c.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch();

    if (!$lesson || $lesson['instructor_id'] != $instructor_id) {
        die("Accès refusé.");
    }

    $course_id = $lesson['course_id'];

    // Delete lesson
    $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);

    header("Location: course_content.php?id=$course_id&msg=lesson_deleted");
    exit();

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
