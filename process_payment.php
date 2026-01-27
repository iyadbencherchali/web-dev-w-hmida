<?php
session_start();
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=auth_required_for_payment");
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
        $pdo->beginTransaction();

        // 1. Create Payment Record (Simulated "Receipt")
        // We'll create the payments table if it doesn't exist for the purpose of this TP
        $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            payment_method VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");

        $payment_method = $_POST['payment_method'] ?? 'card';
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, payment_method) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $amount, $payment_method]);
        $payment_id = $pdo->lastInsertId();

        // 2. Enroll User in Courses
        foreach ($_SESSION['cart'] as $item) {
            $course_id = $item['id'];

            // Check if already enrolled to avoid duplicates
            $check = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $check->execute([$user_id, $course_id]);
            
            if (!$check->fetch()) {
                $enroll = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
                $enroll->execute([$user_id, $course_id]);
            }
        }

        $pdo->commit();

        // 3. Clear Cart
        unset($_SESSION['cart']);

        // 4. Redirect to Dashboard with success message
        header("Location: dashboard.php?msg=payment_success&receipt=" . $payment_id);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Transaction failed: " . $e->getMessage());
    }

} else {
    // Invalid access
    header("Location: panier.php");
    exit();
}
?>
