<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=auth_required");
    exit();
}

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: formation.php");
    exit();
}

// Calculate Total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'];
}

// Pre-fill user info if logged in
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$email = ''; // We didn't store email in session during login, only ID and Name. 
// In a real app we would fetch it from DB or store it in session.
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Paiement | Centre De Formation</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Payment Page Specific Styles (Copied from HTML) */
    .checkout-section { background: white; padding: 4rem 2rem; min-height: 80vh; }
    .checkout-container { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr 380px; gap: 3rem; }
    .checkout-form-container h2 { font-family: var(--font-heading); color: var(--secondary); font-size: 1.75rem; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--secondary); }
    .form-group input, .form-group select { width: 100%; padding: 0.85rem; border: 1px solid var(--border); border-radius: var(--radius); font-size: 1rem; transition: border-color 0.2s; }
    .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    .payment-methods { margin-top: 2rem; }
    .payment-option { border: 1px solid var(--border); border-radius: var(--radius); padding: 1rem; margin-bottom: 1rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 1rem; }
    .payment-option:hover, .payment-option.selected { border-color: var(--primary); background: #f0f9ff; }
    .payment-option input { width: auto; }
    .order-summary-card { background: #f8fafc; padding: 2rem; border-radius: var(--radius); border: 1px solid var(--border); position: sticky; top: 100px; }
    .order-summary-card h3 { font-family: var(--font-heading); font-size: 1.25rem; margin-bottom: 1.5rem; color: var(--secondary); }
    .summary-item { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.95rem; color: var(--text); }
    .summary-item strong { color: var(--secondary); }
    .total-line { border-top: 2px solid var(--border); padding-top: 1rem; margin-top: 1rem; font-size: 1.25rem; font-weight: 700; color: var(--secondary); display: flex; justify-content: space-between; }
    .pay-btn { width: 100%; margin-top: 2rem; padding: 1rem; font-size: 1.1rem; }
    .secure-badge { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1rem; font-size: 0.85rem; color: var(--text-light); }
    @media (max-width: 900px) { .checkout-container { grid-template-columns: 1fr; } }
  </style>
</head>

<body>

  <!-- HEADER -->
  <header>
    <a href="index.php" class="logo-link" style="margin-left: 20px;">
      <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
    </a>

    <nav>
      <ul>
        <li><a href="index.php">Accueil </a></li>
        <li><a href="formation.php">Formations </a></li>
        <li><a href="evenements.php">Évènements </a></li>
        <li><a href="blog.php">Blog </a></li>
        <li><a href="panier.php">Panier </a></li>
        <li><a href="paiement.php" class="active"><b>Paiement</b></a></li>
        <?php if (isset($_SESSION['user_id'])): 
            $dash = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : (($_SESSION['role'] == 'instructor') ? 'instructor_dashboard.php' : 'dashboard.php');
        ?>
            <li><a href="<?php echo $dash; ?>">Mon Espace</a></li>
            <li id="logout"><a href="logout.php">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="login.php">Connexion</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- MAIN CONTENT -->
  <main>
    <section class="checkout-section" id="checkout-container">
      <div class="checkout-container">

        <!-- Left Column: Billing & Payment -->
        <div class="checkout-form-container">
          <h2>Détails de facturation</h2>
          <form id="billing-form" action="process_payment.php" method="POST">
            <div class="form-row">
              <div class="form-group">
                <label for="firstname">Prénom</label>
                <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($first_name); ?>" placeholder="Votre prénom" required>
              </div>
              <div class="form-group">
                <label for="lastname">Nom</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($last_name); ?>" placeholder="Votre nom" required>
              </div>
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="exemple@email.com" required>
            </div>

            <div class="form-group">
              <label for="address">Adresse</label>
              <input type="text" id="address" name="address" placeholder="123 Rue de la Formation" required>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="city">Ville</label>
                <input type="text" id="city" name="city" placeholder="Blida" required>
              </div>
              <div class="form-group">
                <label for="zip">Code Postal</label>
                <input type="text" id="zip" name="zip" placeholder="09000" required>
              </div>
            </div>

            <!-- Payment Methods -->
            <div class="payment-methods" id="payment-methods">
              <h2>Mode de paiement</h2>

              <label class="payment-option selected">
                <input type="radio" name="payment_method" value="card" checked>
                <span>💳 Carte Bancaire (CIB / Edahabia)</span>
              </label>

              <label class="payment-option">
                <input type="radio" name="payment_method" value="paypal">
                <span>🅿️ PayPal</span>
              </label>

              <label class="payment-option">
                <input type="radio" name="payment_method" value="bank_transfer">
                <span>🏦 Virement Bancaire</span>
              </label>
            </div>
          </form>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="order-summary-card" id="order-summary">
          <h3>Résumé de la commande</h3>

          <?php foreach ($_SESSION['cart'] as $item): ?>
            <div class="summary-item">
                <span><?php echo htmlspecialchars($item['title']); ?></span>
                <strong><?php echo number_format($item['price'], 0, '.', ','); ?> DA</strong>
            </div>
          <?php endforeach; ?>

          <hr style="border: 0; border-top: 1px solid var(--border); margin: 1rem 0;">

          <div class="summary-item">
            <span>Sous-total</span>
            <span><?php echo number_format($total, 0, '.', ','); ?> DA</span>
          </div>
          <div class="summary-item">
            <span>TVA (19%)</span>
            <span>Inclus</span>
          </div>

          <div class="total-line">
            <span>Total à payer</span>
            <span><?php echo number_format($total, 0, '.', ','); ?> DA</span>
          </div>

          <button type="submit" form="billing-form" class="btn btn-primary pay-btn" id="confirm-payment-btn">Confirmer le paiement</button>

          <div class="secure-badge">
            🔒 Paiement 100% Sécurisé et Crypté
          </div>
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
