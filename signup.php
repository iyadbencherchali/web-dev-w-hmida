<?php
session_start();
require_once 'db_connect.php';

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $role = $_POST['role'] ?? 'student';
    if (!in_array($role, ['student', 'instructor'])) {
        $role = 'student';
    }

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_msg = "Veuillez remplir tous les champs.";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Les mots de passe ne correspondent pas.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error_msg = "Cet email est déjà enregistré.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                $pdo->beginTransaction();

                // 1. Insert into users table
                $sql = "INSERT INTO users (first_name, last_name, email, password_hash, is_active, role) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $first_name, 
                    $last_name, 
                    $email, 
                    $hashed_password, 
                    1, // is_active
                    $role
                ]);
                
                $user_id = $pdo->lastInsertId();

                // 2. Insert into role-specific table
                if ($role === 'instructor') {
                    $stmt = $pdo->prepare("INSERT INTO instructors (user_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO students (user_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                }
                
                $pdo->commit();

                // Redirect to login page with success message
                header("Location: login.php?signup=success");
                exit();

            } catch (PDOException $e) {
                $pdo->rollBack();
                $error_msg = "Erreur lors de l'inscription : " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Reusing Login Styles */
        .auth-section {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            background: radial-gradient(circle at 50% 50%, #f8fafc 0%, #e2e8f0 100%);
            padding: 2rem;
        }

        .auth-card {
            background: white;
            padding: 3rem;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
            border: 1px solid white;
        }

        .auth-header {
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            font-family: var(--font-heading);
            color: var(--secondary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--text-light);
        }

        .auth-form .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .auth-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .auth-form input[type="text"],
        .auth-form input[type="email"],
        .auth-form input[type="password"] {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .auth-form input:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
        }

        .role-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .role-option {
            flex: 1;
            position: relative;
        }

        .role-option input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .role-label {
            display: block;
            padding: 0.75rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            text-align: center;
            color: var(--text-light);
        }

        .role-option input:checked + .role-label {
            border-color: var(--primary);
            background: #f0f9ff;
            color: var(--primary);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            margin-top: 1rem;
            box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
        }

        .divider {
            margin: 2rem 0;
            display: flex;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span {
            padding: 0 1rem;
        }

        .social-login {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
            border-color: var(--primary);
        }

        .social-btn img {
            width: 24px;
            height: 24px;
        }

        .auth-footer {
            margin-top: 2rem;
            font-size: 0.95rem;
            color: var(--text);
        }

        .auth-footer a {
            color: var(--primary);
            font-weight: 600;
        }

        .error-message {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border: 1px solid #fecaca;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <header>
        <a href="index.php" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 80px;">
        </a>

        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="formation.php">Formations</a></li>
                <li><a href="evenements.php">Évènements</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li><a href="panier.php">Panier</a></li>
                <li><a href="paiement.php">Paiement</a></li>
                <?php if (isset($_SESSION['user_id'])): 
                    $dash = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : (($_SESSION['role'] == 'instructor') ? 'instructor_dashboard.php' : 'dashboard.php');
                ?>
                    <li><a href="<?php echo $dash; ?>">Mon Espace</a></li>
                    <li><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="login.php">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- MAIN CONTENT -->
    <main>
        <section class="auth-section" id="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Créer un compte</h1>
                    <p>Rejoignez notre communauté d'apprenants</p>
                </div>

                <?php if (!empty($error_msg)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form" id="signup-form" action="" method="POST">
                    
                    <label style="display:block; text-align:left; margin-bottom:0.5rem; font-weight:600; color:var(--secondary); font-size:0.9rem;">Je m'inscris en tant que :</label>
                    <div class="role-selector">
                        <label class="role-option">
                            <input type="radio" name="role" value="student" checked>
                            <span class="role-label">Étudiant</span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="instructor">
                            <span class="role-label">Formateur</span>
                        </label>
                    </div>

                    <div class="form-group" style="display: flex; gap: 1rem;">
                        <div style="flex: 1;">
                            <label for="first_name">Prénom</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" placeholder="Prénom" required>
                        </div>
                        <div style="flex: 1;">
                            <label for="last_name">Nom</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" placeholder="Nom" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="exemple@email.com" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm-password">Confirmer le mot de passe</label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="••••••••"
                            required>
                    </div>

                    <button type="submit" class="btn btn-primary login-btn">S'inscrire</button>
                </form>

                <div class="divider">
                    <span>Ou s'inscrire avec</span>
                </div>

                <div class="social-login" id="social-signup">
                    <button class="social-btn" title="Google">
                        <img src="2993685_brand_brands_google_logo_logos_icon.png" alt="Google">
                    </button>
                    <button class="social-btn" title="Microsoft">
                        <img src="4202105_microsoft_logo_social_social media_icon.png" alt="Microsoft">
                    </button>
                    <button class="social-btn" title="LinkedIn">
                        <img src="icons8-login-50.png" alt="LinkedIn">
                    </button>
                </div>

                <div class="auth-footer">
                    Déjà un compte ? <a href="login.php">Se connecter</a>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer>
        <p><strong>Contact :</strong> 0667 81 23 51 | contact@formationpro.dz</p>
        <p>Adresse : USDB Pavillon 1, Blida</p>
        <p>&copy; 2025 Centre de Formation Professionnelle</p>
        <img src="images.jpg" alt="Image du panier" width="100">
    </footer>

</body>

</html>
