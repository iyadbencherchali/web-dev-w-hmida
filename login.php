<?php
session_start();
require_once 'db_connect.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'instructor') {
        header("Location: instructor_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_msg = "Veuillez remplir tous les champs.";
    } else {
        // Fetch user
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, password_hash, role FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role']; // Store role
            
            // Save first name for dashboard
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            
            // Clear cart to ensure isolation between profiles
            $_SESSION['cart'] = [];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === 'instructor') {
                header("Location: instructor_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            // Invalid credentials
            $error_msg = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion | Centre De Formation</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Login Page Specific Styles */
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
      max-width: 450px;
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
      margin-bottom: 1.5rem;
      text-align: left;
    }

    .auth-form label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: var(--secondary);
      font-size: 0.9rem;
    }

    .auth-form input {
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

    .forgot-password {
      display: block;
      text-align: right;
      font-size: 0.85rem;
      color: var(--primary);
      margin-top: 0.5rem;
      font-weight: 500;
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
    <a href="login.php" class="logo-link" style="margin-left: 20px;">
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
        <?php if (isset($_SESSION['user_id'])): ?>
                <?php 
                    $dash = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : (($_SESSION['role'] == 'instructor') ? 'instructor_dashboard.php' : 'dashboard.php');
                ?>
                <li><a href="<?php echo $dash; ?>">Mon Espace</a></li>
            <li><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="login.php" class="active"><b>Connexion</b></a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- MAIN CONTENT -->
  <main>
    <section class="auth-section" id="auth-container">
      <div class="auth-card">
        <div class="auth-header">
          <h1>Bon retour !</h1>
          <p>Connectez-vous pour accéder à vos cours</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Note: action is empty string to submit to self -->
        <form class="auth-form" id="login-form" action="" method="POST">
          <div class="form-group">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                   placeholder="exemple@email.com" required>
          </div>

          <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <a href="#" class="forgot-password">Mot de passe oublié ?</a>
          </div>

          <button type="submit" class="btn btn-primary login-btn">Se connecter</button>
        </form>

        <div class="divider">
          <span>Ou continuer avec</span>
        </div>

        <div class="social-login" id="social-login">
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
          Pas encore de compte ? <a href="signup.php">S'inscrire gratuitement</a>
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
