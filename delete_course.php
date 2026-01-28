<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $course_id = $_GET['id'];
    $instructor_id = $_SESSION['user_id'];

    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->execute([$course_id, $instructor_id]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            header("Location: instructor_dashboard.php?msg=course_deleted");
            exit();
        } else {
            die("Accès non autorisé ou cours inexistant.");
        }
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
} else {
    header("Location: instructor_dashboard.php");
    exit();
}
?>
