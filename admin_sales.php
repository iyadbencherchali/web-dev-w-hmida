<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$first_name = $_SESSION['first_name'];

// Fetch all payments
$stmt = $pdo->query("
    SELECT p.*, u.first_name, u.last_name, u.email 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
");
$sales = $stmt->fetchAll();

// Total Stats
$total_revenue = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn() ?: 0;
$total_sales_count = count($sales);

// Monthly revenue (simplified)
$monthly_revenue = $pdo->query("
    SELECT SUM(amount) FROM payments 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
")->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finances | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body.no-sidebar { margin: 0; }
        
        /* --- HERO ANIMATED --- */
        .hero-animated {
            background-color: #0f172a !important;
            text-align: center;
            padding: 5rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            animation: float 6s ease-in-out infinite;
        }
        .orb-1 { width: 400px; height: 400px; background: rgba(14, 165, 233, 0.3); top: -100px; left: -100px; }
        .orb-2 { width: 350px; height: 350px; background: rgba(99, 102, 241, 0.2); bottom: -100px; right: -100px; animation-delay: 3s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
        }

        /* --- STATS GRID --- */
        .finance-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-box {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: white;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* --- TABLE CARD --- */
        .finance-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border: 1px solid #e2e8f0;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary);
            border-left: 4px solid var(--primary);
            padding-left: 1rem;
        }

        /* --- SALES TABLE --- */
        .sales-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .sales-table thead th {
            text-align: left;
            padding: 0.75rem 1rem;
            color: var(--text-light);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .sales-table tbody tr {
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .sales-table tbody tr:hover {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: scale(1.02);
        }

        .sales-table tbody td {
            padding: 1rem;
        }

        .sales-table tbody td:first-child { border-radius: 10px 0 0 10px; }
        .sales-table tbody td:last-child { border-radius: 0 10px 10px 0; }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .badge-paid {
            background: #dcfce7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .finance-stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="no-sidebar">

    <!-- HEADER -->
    <header>
        <a href="index.php" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
        </a>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="admin_dashboard.php">Dashboard Admin</a></li>
                <li><a href="admin_users.php">👥 Utilisateurs</a></li>
                <li><a href="admin_courses.php">📚 Modération</a></li>
                <li><a href="admin_events.php">📅 Événements</a></li>
                <li><a href="admin_sales.php" class="active"><b>💳 Finances</b></a></li>
                <li id="logout"><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- HERO GLASSMORPHISM -->
        <section class="section-padding bg-dark hero-animated" style="background-color: #0f172a !important;">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            
            <div style="position:relative; z-index:2; max-width: 1200px; margin: 0 auto;">
                <h1 style="color:#fff; font-size:3rem; margin-bottom:1rem; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                    Rapport Financier 📊
                </h1>
                <p style="color:#e2e8f0; font-size:1.2rem; margin-bottom:3rem; font-weight:300;">
                    Suivez vos revenus et analysez l'historique des transactions.
                </p>

                <div class="stats-container" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <!-- Stat Card 1 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 250px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #10b981;"><?php echo number_format($total_revenue, 0, '.', ','); ?> DA</div>
                        <div style="color: #cbd5e1; font-weight: 600;">Chiffre d'Affaires Total</div>
                    </div>
                
                    <!-- Stat Card 2 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 250px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: var(--primary);"><?php echo $total_sales_count; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Transactions Total</div>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 250px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #f59e0b;"><?php echo number_format($monthly_revenue, 0, '.', ','); ?> DA</div>
                        <div style="color: #cbd5e1; font-weight: 600;">Revenus ce Mois</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MAIN CONTENT -->
        <section class="section-padding">
            <div class="container" style="max-width: 1400px; margin: 0 auto;">
                
                <!-- DETAILED STATS -->
                <div class="finance-stats-row">
                    <div class="stat-box">
                        <div class="stat-icon">💰</div>
                        <div class="stat-number"><?php echo number_format($total_revenue, 0, '.', ','); ?> DA</div>
                        <div class="stat-label">Chiffre d'affaires cumulé</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">📈</div>
                        <div class="stat-number"><?php echo $total_sales_count; ?></div>
                        <div class="stat-label">Volume total de ventes</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-number"><?php echo number_format($total_sales_count > 0 ? $total_revenue / $total_sales_count : 0, 0); ?> DA</div>
                        <div class="stat-label">Panier moyen</div>
                    </div>
                </div>

                <!-- TRANSACTIONS TABLE -->
                <div class="finance-card">
                    <div class="card-header">
                        <h3>Journal des Transactions</h3>
                        <div style="font-size: 0.85rem; color: var(--text-light); font-weight: 600;">
                            <?php echo count($sales); ?> résultats
                        </div>
                    </div>

                    <?php if (empty($sales)): ?>
                        <div class="empty-state">
                            <div style="font-size: 4rem;">💳</div>
                            <p>Aucune vente enregistrée dans le système.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="sales-table">
                                <thead>
                                    <tr>
                                        <th>Date & Heure</th>
                                        <th>Client / Étudiant</th>
                                        <th>Montant</th>
                                        <th>Méthode</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($sales as $s): ?>
                                    <tr>
                                        <td style="color: var(--text-light); font-weight: 500;">
                                            <?php echo date('d M Y', strtotime($s['created_at'])); ?><br>
                                            <small style="opacity: 0.7;"><?php echo date('H:i', strtotime($s['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar"><?php echo strtoupper($s['first_name'][0].$s['last_name'][0]); ?></div>
                                                <div>
                                                    <div style="font-weight: 700; color: var(--secondary);"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo htmlspecialchars($s['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-weight: 800; color: var(--secondary); font-size: 1.1rem;"><?php echo number_format($s['amount'], 0); ?> DA</td>
                                        <td style="font-weight: 600; color: var(--text-light);"><?php echo strtoupper($s['payment_method']); ?></td>
                                        <td><span class="badge-paid">PAYÉ</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p><strong>Contact :</strong> 0667 81 23 51 | contact@formationpro.dz</p>
        <p>Adresse : USDB Pavillon 1, Blida</p>
        <p>&copy; 2025 Centre de Formation Professionnelle</p>
        <img src="images.jpg" alt="Image du panier" width="100">
    </footer>

</body>
</html>
