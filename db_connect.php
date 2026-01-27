<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$host = 'localhost';
$db_name = 'cours_db';
$username = 'root';
$password = ''; // XAMPP default

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connexion réussie ! 🎉"; // <-- message visible
} catch(PDOException $e) {
    die("Échec de la connexion : " . $e->getMessage());
}
?>
