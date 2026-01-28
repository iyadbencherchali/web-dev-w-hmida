<?php
session_start();
require_once 'config.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$first_name = $_SESSION['first_name'];

// Fetch Global Stats
try {
    $stats = [];
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['courses'] = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $stats['revenue'] = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn() ?: 0;
    $stats['students'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['instructors'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'instructor'")->fetchColumn();
    $stats['pending_courses'] = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_published = 0")->fetchColumn();

    // Recent Sales
    $stmt = $pdo->query("
        SELECT p.*, u.first_name, u.last_name, u.email 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC LIMIT 5
    ");
    $recent_sales = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body.no-sidebar { margin: 0; }
        
        /* --- HERO ANIMATED (Same as dashboard.php) --- */
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

        /* --- STATS CONTAINER --- */
        .admin-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-box {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
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

        /* --- MAIN CONTENT GRID --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border: 1px solid #e2e8f0;
        }

        .card-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .card-title-row h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary);
            border-left: 4px solid var(--primary);
            padding-left: 1rem;
        }

        .view-all-link {
            font-size: 0.85rem;
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            background: rgba(14, 165, 233, 0.1);
            transition: all 0.3s ease;
        }

        .view-all-link:hover { background: var(--primary); color: white; }

        /* --- TABLE STYLE (Inspired by dashboard.php cards) --- */
        .transactions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .transactions-table thead th {
            text-align: left;
            padding: 0.75rem 1rem;
            color: var(--text-light);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .transactions-table tbody tr {
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .transactions-table tbody tr:hover {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: scale(1.02);
        }

        .transactions-table tbody td {
            padding: 1rem;
        }

        .transactions-table tbody td:first-child { border-radius: 10px 0 0 10px; }
        .transactions-table tbody td:last-child { border-radius: 0 10px 10px 0; }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* --- QUICK ACTIONS (Side Panel) --- */
        .action-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 15px;
            text-decoration: none;
            color: var(--secondary);
            font-weight: 700;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: white;
            border-color: var(--primary);
            transform: translateX(5px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            font-size: 1.5rem;
        }

        .promo-card {
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 15px;
            color: white;
        }

        .promo-card strong {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .promo-card p {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.5;
            opacity: 0.95;
        }

        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: 1fr; }
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
                <li><a href="admin_dashboard.php" class="active"><b>Dashboard Admin</b></a></li>
                <li><a href="admin_users.php">👥 Utilisateurs</a></li>
                <li><a href="admin_courses.php">� Modération</a></li>
                <li><a href="admin_sales.php">💳 Finances</a></li>
                <li id="logout"><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- HERO GLASSMORPHISM (Inspired by dashboard.php) -->
        <section class="section-padding bg-dark hero-animated" style="background-color: #0f172a !important;">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            
            <div style="position:relative; z-index:2; max-width: 1200px; margin: 0 auto;">
                <h1 style="color:#fff; font-size:3rem; margin-bottom:1rem; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                    Bienvenue Admin, <?php echo htmlspecialchars($first_name); ?>! 👨‍💼
                </h1>
                <p style="color:#e2e8f0; font-size:1.2rem; margin-bottom:3rem; font-weight:300;">
                    Vue d'ensemble de votre centre de formation professionnelle.
                </p>

                <div class="stats-container" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <!-- Stat Card 1 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: var(--primary);"><?php echo $stats['users']; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Utilisateurs Totaux</div>
                    </div>
                
                    <!-- Stat Card 2 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #10b981;"><?php echo $stats['courses']; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Formations Actives</div>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #f59e0b;"><?php echo $stats['pending_courses']; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">En Attente</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MAIN CONTENT -->
        <section class="section-padding">
            <div class="container" style="max-width: 1400px; margin: 0 auto;">
                
                <!-- DETAILED STATS GRID -->
                <div class="admin-stats-row">
                    <div class="stat-box">
                        <div class="stat-icon">💰</div>
                        <div class="stat-number"><?php echo number_format($stats['revenue'], 0, '.', ','); ?> DA</div>
                        <div class="stat-label">Chiffre d'affaires</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">👨‍🎓</div>
                        <div class="stat-number"><?php echo $stats['students']; ?></div>
                        <div class="stat-label">Étudiants Inscrits</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">�‍🏫</div>
                        <div class="stat-number"><?php echo $stats['instructors']; ?></div>
                        <div class="stat-label">Formateurs Actifs</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">�</div>
                        <div class="stat-number"><?php echo $stats['courses']; ?></div>
                        <div class="stat-label">Total Formations</div>
                    </div>
                </div>

                <!-- DASHBOARD GRID -->
                <div class="dashboard-grid">
                    <!-- RECENT TRANSACTIONS -->
                    <div class="content-card">
                        <div class="card-title-row">
                            <h3>Dernières Transactions</h3>
                            <a href="admin_sales.php" class="view-all-link">Voir tout →</a>
                        </div>

                        <?php if (empty($recent_sales)): ?>
                            <div style="text-align: center; padding: 3rem; opacity: 0.5;">
                                <div style="font-size: 3rem;">�</div>
                                <p>Aucune transaction récente.</p>
                            </div>
                        <?php else: ?>
                            <table class="transactions-table">
                                <thead>
                                    <tr>
                                        <th>Utilisateur</th>
                                        <th>Montant</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_sales as $sale): ?>
                                    <tr>
                                        <td>
                                            <div class="user-badge">
                                                <div class="user-avatar"><?php echo strtoupper($sale['first_name'][0].$sale['last_name'][0]); ?></div>
                                                <div>
                                                    <div style="font-weight: 700; color: var(--secondary);"><?php echo htmlspecialchars($sale['first_name'].' '.$sale['last_name']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-light);"><?php echo htmlspecialchars($sale['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="font-weight: 800; color: var(--secondary);"><?php echo number_format($sale['amount'], 0); ?> DA</td>
                                        <td style="color: var(--text-light);"><?php echo date('d M Y', strtotime($sale['created_at'])); ?></td>
                                        <td><span class="badge-success">PAYÉ</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- QUICK ACTIONS -->
                    <div>
                        <div class="content-card" style="margin-bottom: 0;">
                            <div class="card-title-row">
                                <h3>Actions Rapides</h3>
                            </div>

                            <div class="action-list">
                                <a href="admin_courses.php" class="action-btn">
                                    <span class="action-icon">✅</span>
                                    <div>
                                        <div>Modérer les cours</div>
                                        <div style="font-size: 0.75rem; color: var(--text-light); font-weight: 500;"><?php echo $stats['pending_courses']; ?> en attente</div>
                                    </div>
                                </a>

                                <a href="admin_users.php" class="action-btn">
                                    <span class="action-icon">👥</span>
                                    <div>
                                        <div>Gérer utilisateurs</div>
                                        <div style="font-size: 0.75rem; color: var(--text-light); font-weight: 500;">Contrôle des accès</div>
                                    </div>
                                </a>

                                <a href="admin_sales.php" class="action-btn">
                                    <span class="action-icon">📊</span>
                                    <div>
                                        <div>Rapport financier</div>
                                        <div style="font-size: 0.75rem; color: var(--text-light); font-weight: 500;">Analyse des revenus</div>
                                    </div>
                                </a>
                            </div>

                            <div class="promo-card">
                                <strong>💡 Conseil Admin</strong>
                                <p>Vérifiez régulièrement les nouveaux cours postés par les formateurs pour maintenir la qualité de votre plateforme.</p>
                            </div>
                        </div>
                    </div>
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
