<?php
/**
 * PROCESS_PAYMENT.PHP
 * handles course purchase, enrollment, seat management, and redirection.
 */
session_start();
require_once 'config.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=auth_required");
    exit();
}

// 2. Input Validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header("Location: panier.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$payment_method = $_POST['payment_method'] ?? 'card';
$total_amount = 0;

foreach ($_SESSION['cart'] as $item) {
    $total_amount += $item['price'];
}

try {
    // 3. Ensure Environment Is Ready (DDL outside transaction)
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50),
        status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    $pdo->beginTransaction();

    // 4. Record Payment
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $total_amount, $payment_method]);
    $payment_id = $pdo->lastInsertId();

    // 5. Ensure Student Profile Exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO students (user_id) VALUES (?)");
    $stmt->execute([$user_id]);

    // 6. Process Enrollments
    foreach ($_SESSION['cart'] as $item) {
        $course_id = $item['id'];

        // 6.a Check for available seats
        $stmt = $pdo->prepare("SELECT title, max_students FROM courses WHERE id = ? FOR UPDATE");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        // Logic: If max_students is NOT null and is 0 or less, it's full.
        // If it's NULL, we treat it as infinite or 20 for this exercise.
        if ($course && $course['max_students'] !== null && $course['max_students'] <= 0) {
            $pdo->rollBack();
            header("Location: panier.php?error=course_full&title=" . urlencode($course['title']));
            exit();
        }

        // 6.b Perform Enrollment
        $stmt = $pdo->prepare("INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $course_id]);

        // 6.c Decrement seats if applicable
        if ($course && $course['max_students'] !== null) {
            $stmt = $pdo->prepare("UPDATE courses SET max_students = max_students - 1 WHERE id = ?");
            $stmt->execute([$course_id]);
        }
    }

    $pdo->commit();

    // 7. Cleanup and Redirect
    unset($_SESSION['cart']);
    header("Location: dashboard.php?msg=payment_success&receipt=" . $payment_id);
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Log error and redirect with generic message
    error_log($e->getMessage());
    header("Location: panier.php?error=internal_error");
    exit();
}
