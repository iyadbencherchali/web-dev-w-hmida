<?php
session_start();
require_once 'config.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$first_name = $_SESSION['first_name'];

// Fetch Stats
$stats = [];
$stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['courses'] = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$stats['enrollments'] = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
$stats['revenue'] = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn();

// Fetch Recent Enrollments
$recent_enrollments = $pdo->query("
    SELECT e.enrolled_at, u.first_name, u.last_name, c.title 
    FROM enrollments e 
    JOIN users u ON e.student_id = u.id 
    JOIN courses c ON e.course_id = c.id 
    ORDER BY e.enrolled_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-header { background: #1e293b; color: white; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: -3rem; padding: 0 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-left: 5px solid var(--primary); }
        .stat-value { font-size: 2rem; font-weight: bold; color: #0f172a; }
        .stat-label { color: #64748b; font-size: 0.9rem; font-weight: 500; }
        
        .recent-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .recent-table th, .recent-table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .recent-table th { background: #f8fafc; font-weight: 600; color: #475569; }
    </style>
</head>
<body class="no-sidebar">

    <!-- Navbar -->
    <header>
        <div class="logo-link" style="margin-left: 20px; font-weight: bold; font-size: 1.5rem; color: var(--primary);">
            ADMIN SPACE
        </div>
        <nav>
            <ul>
                <li><a href="index.html">Site Public</a></li>
                <li><a href="logout.php" style="color: var(--danger);">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="admin-header">
            <div class="container">
                <h1>Tableau de Bord Admin</h1>
                <p>Bienvenue, <?php echo htmlspecialchars($first_name); ?></p>
                <br><br>
            </div>
        </div>

        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['users']; ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
                <div class="stat-card" style="border-color: #10b981;">
                    <div class="stat-value"><?php echo $stats['enrollments']; ?></div>
                    <div class="stat-label">Inscriptions</div>
                </div>
                <div class="stat-card" style="border-color: #f59e0b;">
                    <div class="stat-value"><?php echo $stats['courses']; ?></div>
                    <div class="stat-label">Cours Actifs</div>
                </div>
                <div class="stat-card" style="border-color: #6366f1;">
                    <div class="stat-value"><?php echo number_format($stats['revenue'], 0); ?> DA</div>
                    <div class="stat-label">Chiffre d'Affaires</div>
                </div>
            </div>

            <!-- Recent Activity -->
            <section class="section-padding">
                <h2 class="section-title">Dernières Inscriptions</h2>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Étudiant</th>
                            <th>Cours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_enrollments as $enroll): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($enroll['enrolled_at'])); ?></td>
                            <td><?php echo htmlspecialchars($enroll['first_name'] . ' ' . $enroll['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($enroll['title']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>

</body>
</html>
