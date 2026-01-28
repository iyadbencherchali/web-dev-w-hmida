<?php
session_start();
require_once 'config.php';

// Calculate Total and Savings
$total = 0;
$total_savings = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'];
        if (isset($item['original_price']) && $item['original_price'] > $item['price']) {
            $total_savings += ($item['original_price'] - $item['price']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Panier | Centre De Formation</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Cart Specific Styles */
    .cart-section {
      background: white;
      padding: 4rem 2rem;
      min-height: 60vh;
    }

    .cart-container {
      max-width: 1000px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 2rem;
    }

    .cart-header {
      margin-bottom: 2rem;
    }

    .cart-header h1 {
      font-family: var(--font-heading);
      color: var(--secondary);
      font-size: 2rem;
    }

    /* Cart Items Table */
    .cart-table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border);
    }

    .cart-table th {
      background: #f8fafc;
      text-align: left;
      padding: 1rem;
      font-weight: 600;
      color: var(--secondary);
      border-bottom: 1px solid var(--border);
    }

    .cart-table td {
      padding: 1.5rem 1rem;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    .cart-item-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .cart-item-thumb {
      width: 80px;
      height: 60px;
      background: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
    }

    .cart-item-thumb img {
      max-width: 40px;
      max-height: 40px;
    }

    .cart-item-details h3 {
      font-size: 1rem;
      margin-bottom: 0.25rem;
      color: var(--secondary);
    }

    .cart-item-details p {
      font-size: 0.85rem;
      color: var(--text-light);
    }

    .remove-btn {
      color: var(--danger);
      background: none;
      border: none;
      cursor: pointer;
      font-weight: 500;
      font-size: 0.9rem;
      transition: color 0.2s;
    }

    .remove-btn:hover {
      text-decoration: underline;
    }

    /* Cart Summary */
    .cart-summary {
      background: #f8fafc;
      padding: 2rem;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      height: fit-content;
      position: sticky;
      top: 100px;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 1rem;
      color: var(--text);
    }

    .summary-total {
      border-top: 2px solid var(--border);
      padding-top: 1rem;
      margin-top: 1rem;
      font-weight: 700;
      font-size: 1.2rem;
      color: var(--secondary);
    }

    .checkout-btn {
      width: 100%;
      margin-top: 1.5rem;
      display: block;
      text-align: center;
    }

    .continue-shopping {
      display: block;
      text-align: center;
      margin-top: 1rem;
      font-size: 0.9rem;
      color: var(--text-light);
      text-decoration: underline;
    }

    @media (max-width: 900px) {
      .cart-container {
        grid-template-columns: 1fr;
      }
    }
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
        <li><a href="panier.php" class="active"><b>Panier</b></a></li>
        <li><a href="paiement.php">Paiement </a></li>
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
    <section class="cart-section" id="cart-container">
      <div class="cart-header">
        <h1>Votre Panier (<?php echo count($_SESSION['cart'] ?? []); ?> cours)</h1>
      </div>

      <div class="cart-container">
        <!-- Cart Items List -->
        <div class="cart-items" id="cart-items">
            <?php if (empty($_SESSION['cart'])): ?>
                <div style="text-align: center; padding: 2rem;">
                    <h3>Votre panier est vide.</h3>
                    <a href="formation.php" class="btn btn-primary" style="margin-top: 1rem;">Parcourir les formations</a>
                </div>
            <?php else: ?>
              <table class="cart-table">
                <thead>
                  <tr>
                    <th>Formation</th>
                    <th>Prix</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                      <tr>
                        <td>
                          <div class="cart-item-info">
                            <div class="cart-item-thumb">
                              <img src="<?php echo get_course_image($item['title']); ?>" alt="Course Icon">
                            </div>
                            <div class="cart-item-details">
                              <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                              <p>ID: <?php echo $item['id']; ?></p>
                            </div>
                          </div>
                        </td>
                        <td>
                          <?php if (isset($item['original_price']) && $item['original_price'] > $item['price']): ?>
                            <span style="text-decoration: line-through; color: var(--text-light); font-size: 0.9rem; margin-right: 0.5rem;">
                              <?php echo number_format($item['original_price'], 0, '.', ','); ?> DA
                            </span>
                            <strong style="color: #10b981;"><?php echo number_format($item['price'], 0, '.', ','); ?> DA</strong>
                          <?php else: ?>
                            <strong><?php echo number_format($item['price'], 0, '.', ','); ?> DA</strong>
                          <?php endif; ?>
                        </td>
                        <td>
                            <a href="cart.php?action=remove&id=<?php echo $id; ?>" class="remove-btn">Supprimer</a>
                        </td>
                      </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              
              <div style="margin-top: 1rem; text-align: right;">
                  <a href="cart.php?action=empty" class="remove-btn">Vider le panier</a>
              </div>
            <?php endif; ?>
        </div>

        <!-- Cart Summary -->
        <?php if (!empty($_SESSION['cart'])): ?>
            <div class="cart-summary" id="cart-summary">
              <h3>Résumé de la commande</h3>

              <div class="summary-row">
                <span>Sous-total</span>
                <span><?php echo number_format($total + $total_savings, 0, '.', ','); ?> DA</span>
              </div>
              <div class="summary-row" style="color: #10b981; font-weight: 600;">
                <span>Réduction</span>
                <span>-<?php echo number_format($total_savings, 0, '.', ','); ?> DA</span>
              </div>

              <div class="summary-row summary-total">
                <span>Total</span>
                <span><?php echo number_format($total, 0, '.', ','); ?> DA</span>
              </div>

              <a href="paiement.php" class="btn btn-primary checkout-btn" id="checkout-btn">Passer au paiement</a>
              <a href="formation.php" class="continue-shopping">Continuer vos achats</a>
            </div>
        <?php endif; ?>
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
