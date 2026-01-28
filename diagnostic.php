<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Vous n'êtes pas connecté.");
}

$user_id = $_SESSION['user_id'];
echo "<h1>Diagnostic pour l'utilisateur ID: $user_id</h1>";

try {
    // 1. Check users table
    $stmt = $pdo->prepare("SELECT id, role, first_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Données Utilisateur:</h3><pre>" . print_r($user, true) . "</pre>";

    // 2. Check students table
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Entrée Table 'students':</h3><pre>" . ($student ? print_r($student, true) : "AUCUNE ENTRÉE TROUVÉE") . "</pre>";

    // 3. Check enrollments
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ?");
    $stmt->execute([$user_id]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Inscriptions (Table 'enrollments'):</h3><pre>" . (count($enrollments) > 0 ? print_r($enrollments, true) : "AUCUNE INSCRIPTION TROUVÉE") . "</pre>";

    // 5. Check courses seats
    $stmt = $pdo->query("SELECT id, title, max_students FROM courses");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>État des places (Table 'courses'):</h3><pre>" . print_r($courses, true) . "</pre>";

} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage();
}
?>
