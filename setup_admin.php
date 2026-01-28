<?php
require_once 'config.php';

echo "<h1>Configuration de l'administrateur...</h1>";

try {
    $email = 'admin@formationpro.dz';
    $password = 'admin123';
    
    // 1. Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // L'utilisateur existe, on le transforme en admin
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin', is_active = 1 WHERE email = ?");
        $stmt->execute([$email]);
        echo "✅ L'utilisateur existant ($email) a été promu au rang **ADMIN**.<br>";
    } else {
        // L'utilisateur n'existe pas, on le crée
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role, is_active) VALUES ('Admin', 'System', ?, ?, 'admin', 1)");
        $stmt->execute([$email, $hashed_password]);
        echo "✅ Le compte administrateur a été **CRÉÉ** avec succès.<br>";
    }

    echo "<h2>Identifiants :</h2>";
    echo "📧 Email : <strong>$email</strong><br>";
    echo "🔑 Mot de passe : <strong>$password</strong><br><br>";
    echo "<a href='login.php' style='padding: 10px 20px; background: #0ea5e9; color: white; text-decoration: none; border-radius: 5px;'>Se connecter maintenant</a>";

} catch (PDOException $e) {
    die("<h2 style='color:red'>Erreur : " . $e->getMessage() . "</h2>");
}
?>
