<?php
require_once 'db_connect.php';
try {
    $pdo->exec("UPDATE courses SET max_students = 20 WHERE max_students IS NULL OR max_students = 0");
    echo "Succès : Toutes les places ont été réinitialisées à 20.";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
