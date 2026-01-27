<?php
session_start();
require_once 'config.php';

// Check Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get Receipt ID
if (!isset($_GET['id'])) {
    die("ID de reçu manquant.");
}

$receipt_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch Payment Details
$stmt = $pdo->prepare("
    SELECT p.*, u.first_name, u.last_name, u.email
    FROM payments p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->execute([$receipt_id, $user_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die("Reçu introuvable ou accès refusé.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement #<?php echo $payment['id']; ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f1f5f9; padding: 40px; }
        .receipt-card {
            background: white;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header { background: #0ea5e9; color: white; padding: 2rem; text-align: center; }
        .content { padding: 2rem; }
        .row { display: flex; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px dashed #e2e8f0; padding-bottom: 0.5rem; }
        .row:last-child { border: none; }
        .total { font-size: 1.5rem; font-weight: bold; color: #0f172a; margin-top: 1rem; border-top: 2px solid #0f172a; padding-top: 1rem; }
        .btn { display: inline-block; background: #0f172a; color: white; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 4px; margin-top: 2rem; }
        .print-btn { background: #64748b; }
    </style>
</head>
<body>

    <div class="receipt-card">
        <div class="header">
            <h1>Reçu de Paiement</h1>
            <p>Réf: REF-<?php echo date('Y'); ?>-<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        
        <div class="content">
            <div class="row">
                <span>Date</span>
                <strong><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></strong>
            </div>
            <div class="row">
                <span>Client</span>
                <strong><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></strong>
            </div>
            <div class="row">
                <span>Mode de Paiement</span>
                <strong><?php echo strtoupper($payment['payment_method']); ?></strong>
            </div>
            
            <div class="row total">
                <span>Total Payé</span>
                <span><?php echo number_format($payment['amount'], 0, '.', ','); ?> DA</span>
            </div>

            <div style="text-align: center;">
                <button onclick="window.print()" class="btn print-btn">🖨️ Imprimer</button>
                <a href="dashboard.php" class="btn">Retour au Tableau de Bord</a>
            </div>
        </div>
    </div>

</body>
</html>
