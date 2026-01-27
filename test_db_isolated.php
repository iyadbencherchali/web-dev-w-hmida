<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Testing Database Connection...</h1>";

try {
    // Manually define vars to avoid dependency issues
    $host = 'localhost';
    $db_name = 'cours_db';
    $username = 'root';
    $password = '';

    echo "Attempting to connect to <strong>$db_name</strong>...<br>";

    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2 style='color:green'>SUCCESS: Connected to Database!</h2>";
    
} catch(PDOException $e) {
    echo "<h2 style='color:red'>FAILED: " . $e->getMessage() . "</h2>";
}
?>
