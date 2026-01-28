<?php
// diagnostic.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>System Diagnostic Tool</h1>";

// 1. Check PHP Version
echo "<h3>1. PHP Version</h3>";
echo "PHP Version: " . phpversion() . " ✅<br>";

// 2. Check Database Connection
echo "<h3>2. Database Connection</h3>";
require_once 'config.php'; // Uses db_connect.php internally

if (isset($pdo)) {
    echo "Database Connection: SUCCESS ✅<br>";
    echo "Connected to: $db_name <br>";
} else {
    echo "Database Connection: FAILED ❌<br>";
    die();
}

// 3. Check Tables
echo "<h3>3. Table Status</h3>";
$tables = ['users', 'courses', 'enrollments', 'payments', 'instructors'];

foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "Table <strong>$table</strong>: EXISTS ($count rows) ✅<br>";
    } catch (PDOException $e) {
        echo "Table <strong>$table</strong>: MISSING or ERROR ❌ (" . $e->getMessage() . ")<br>";
    }
}

// 4. Check Config/Constants
echo "<h3>4. Configuration</h3>";
if (defined('SITE_NAME')) {
    echo "SITE_NAME: " . SITE_NAME . " ✅<br>";
} else {
    echo "SITE_NAME: Not Defined ❌<br>";
}

echo "<h3>5. Session Check</h3>";
session_start();
$_SESSION['test_key'] = 'Hello';
if (isset($_SESSION['test_key']) && $_SESSION['test_key'] === 'Hello') {
     echo "Session Write/Read: SUCCESS ✅<br>";
} else {
     echo "Session Write/Read: FAILED ❌<br>";
}

echo "<br><hr>";
echo "<strong>DIAGNOSTIC COMPLETE. If all checkmarks are green, your system is healthy.</strong>";
echo "<br><a href='index.php'>Go to Home</a>";
?>
