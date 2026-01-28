<?php
session_start();
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=auth_required_for_payment");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $amount = 0;
    
    // Calculate total again for security
    foreach ($_SESSION['cart'] as $item) {
        $amount += $item['price'];
    }

    try {
        // 1. Ensure table exists (DDL outside transaction to avoid implicit commit)
        $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            payment_method VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");

        $pdo->beginTransaction();

        // 2. Create Payment Record (Simulated "Receipt")
        $payment_method = $_POST['payment_method'] ?? 'card';
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $amount, $payment_method]);
        $payment_id = $pdo->lastInsertId();

        // 1.b Ensure student profile exists
        $stmt = $pdo->prepare("INSERT IGNORE INTO students (user_id) VALUES (?)");
        $stmt->execute([$user_id]);

        // 3. Enroll User in Courses
        foreach ($_SESSION['cart'] as $item) {
            $course_id = $item['id'];

            // 3.a Check if seats are available
            $courseStmt = $pdo->prepare("SELECT max_students FROM courses WHERE id = ? FOR UPDATE");
            $courseStmt->execute([$course_id]);
            $courseInfo = $courseStmt->fetch();

            if ($courseInfo && $courseInfo['max_students'] <= 0) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                header("Location: panier.php?error=course_full&title=" . urlencode($item['title']));
                exit();
            }

            // Check if already enrolled
            $check = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $check->execute([$user_id, $course_id]);
            
            if (!$check->fetch()) {
                $enroll = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                $enroll->execute([$user_id, $course_id]);

                // Decrement seats
                $updateSeats = $pdo->prepare("UPDATE courses SET max_students = max_students - 1 WHERE id = ?");
                $updateSeats->execute([$course_id]);
            }
        }

        $pdo->commit();

        // 4. Clear Cart
        unset($_SESSION['cart']);

        // 5. Redirect to Dashboard
        header("Location: dashboard.php?msg=payment_success&receipt=" . $payment_id);
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Transaction failed: " . $e->getMessage());
    }

} else {
    // Invalid access
    header("Location: panier.php");
    exit();
}
?>
