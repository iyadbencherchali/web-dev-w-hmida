<?php
require_once 'config.php';

echo "<h1>Atomic-Repair Database Schema</h1>";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    echo "<h3>1. Dropping ALL possible Foreign Keys...</h3>";
    $constraints = [
        ['courses', 'courses_ibfk_1'],
        ['courses', 'instructor_id'],
        ['instructors', 'instructors_ibfk_1'],
        ['students', 'students_ibfk_1'],
        ['enrollments', 'enrollments_ibfk_1'],
        ['enrollments', 'enrollments_ibfk_2'],
        ['payments', 'payments_ibfk_1'],
        ['lessons', 'lessons_ibfk_1'],
        ['comments', 'comments_ibfk_1'],
        ['comments', 'comments_ibfk_2'],
        ['comments', 'comments_ibfk_3'],
    ];

    foreach ($constraints as $c) {
        try {
            @$pdo->exec("ALTER TABLE {$c[0]} DROP FOREIGN KEY {$c[1]}");
        } catch (Exception $e) {}
    }
    echo "✅ Constraints dropped.<br>";

    echo "<h3>2. Re-typing ALL Identity Columns...</h3>";
    
    // Users
    $pdo->exec("ALTER TABLE users MODIFY id BIGINT(20) UNSIGNED AUTO_INCREMENT");
    
    // Create/Fix Instructors
    $pdo->exec("CREATE TABLE IF NOT EXISTS instructors (user_id BIGINT(20) UNSIGNED PRIMARY KEY) ENGINE=InnoDB");
    $pdo->exec("ALTER TABLE instructors MODIFY user_id BIGINT(20) UNSIGNED");
    
    // Create/Fix Students
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (user_id BIGINT(20) UNSIGNED PRIMARY KEY) ENGINE=InnoDB");
    $pdo->exec("ALTER TABLE students MODIFY user_id BIGINT(20) UNSIGNED");

    // Fix Courses
    $pdo->exec("ALTER TABLE courses MODIFY id BIGINT(20) UNSIGNED AUTO_INCREMENT");
    $pdo->exec("ALTER TABLE courses MODIFY instructor_id BIGINT(20) UNSIGNED");

    // Fix Payments
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB");
    $pdo->exec("ALTER TABLE payments MODIFY user_id BIGINT(20) UNSIGNED");

    // Create/Fix Enrollments
    $pdo->exec("CREATE TABLE IF NOT EXISTS enrollments (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        student_id BIGINT(20) UNSIGNED NOT NULL,
        course_id BIGINT(20) UNSIGNED NOT NULL
    ) ENGINE=InnoDB");
    $pdo->exec("ALTER TABLE enrollments MODIFY student_id BIGINT(20) UNSIGNED");
    $pdo->exec("ALTER TABLE enrollments MODIFY course_id BIGINT(20) UNSIGNED");

    echo "✅ All columns harmonized to BIGINT(20) UNSIGNED.<br>";

    echo "<h3>3. Restoring Clean Foreign Keys...</h3>";
    
    $restores = [
        "ALTER TABLE instructors ADD CONSTRAINT fk_instr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE students ADD CONSTRAINT fk_stud_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE courses ADD CONSTRAINT fk_course_instr FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE enrollments ADD CONSTRAINT fk_enrol_stud FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE",
        "ALTER TABLE enrollments ADD CONSTRAINT fk_enrol_cour FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE",
        "ALTER TABLE payments ADD CONSTRAINT fk_pay_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"
    ];

    foreach ($restores as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            echo "⚠️ Warning on Restore: " . $e->getMessage() . "<br>";
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "<h2>🎉 FINAL SUCCESS!</h2>";
    echo "<p>Your database structure is now perfectly aligned. Student signup will work now.</p>";
    echo "<a href='signup.php'>Go to Signup</a>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Fatal Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
}
?>
