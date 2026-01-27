<?php
session_start();
require_once 'config.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Action Handler
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];

    // ADD ITEM
    if ($action === 'add' && isset($_POST['course_id'])) {
        $course_id = $_POST['course_id'];
        
        // Check if already in cart
        if (isset($_SESSION['cart'][$course_id])) {
            // Optional: Increment quantity or just redirect if unique courses
            // For courses, usually unique is better
            header("Location: panier.php?msg=exists");
            exit();
        }

        // Fetch Course Details
        try {
            $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($course) {
                $_SESSION['cart'][$course_id] = [
                    'id' => $course['id'],
                    'title' => $course['title'],
                    'price' => $course['price'],
                    'instructor_id' => $course['instructor_id'] // You might want to fetch instructor name too
                ];
                header("Location: panier.php?msg=added");
                exit();
            }
        } catch (PDOException $e) {
            die("Error adding to cart: " . $e->getMessage());
        }
    }

    // REMOVE ITEM
    if ($action === 'remove' && isset($_GET['id'])) {
        $id = $_GET['id'];
        unset($_SESSION['cart'][$id]);
        header("Location: panier.php?msg=removed");
        exit();
    }

    // EMPTY CART
    if ($action === 'empty') {
        $_SESSION['cart'] = [];
        header("Location: panier.php?msg=cleared");
        exit();
    }
}

// Default redirect
header("Location: formation.php");
exit();
?>
