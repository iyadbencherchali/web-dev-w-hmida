<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$first_name = $_SESSION['first_name'];

// Handle Approval / Delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $cid = $_GET['id'];
    if ($_GET['action'] == 'approve') {
        $pdo->prepare("UPDATE courses SET is_published = 1 WHERE id = ?")->execute([$cid]);
    } elseif ($_GET['action'] == 'unpublish') {
        $pdo->prepare("UPDATE courses SET is_published = 0 WHERE id = ?")->execute([$cid]);
    } elseif ($_GET['action'] == 'delete') {
        $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$cid]);
    }
    header("Location: admin_courses.php?msg=success");
    exit();
}

// Fetch all courses with instructor names
$courses = $pdo->query("
    SELECT c.*, u.first_name, u.last_name 
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id 
    ORDER BY c.created_at DESC
")->fetchAll();

// Stats
$total_courses = count($courses);
$published_courses = count(array_filter($courses, fn($c) => $c['is_published'] == 1));
$pending_courses = count(array_filter($courses, fn($c) => $c['is_published'] == 0));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modération Cours | Centre De Formation</title>
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
        .courses-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        /* --- COURSES CARD --- */
        .courses-card {
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

        /* --- COURSES TABLE --- */
        .courses-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .courses-table thead th {
            text-align: left;
            padding: 0.75rem 1rem;
            color: var(--text-light);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .courses-table tbody tr {
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .courses-table tbody tr:hover {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: scale(1.01);
        }

        .courses-table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .courses-table tbody td:first-child { border-radius: 10px 0 0 10px; }
        .courses-table tbody td:last-child { border-radius: 0 10px 10px 0; }

        .course-title {
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 4px;
        }

        .course-desc {
            font-size: 0.8rem;
            color: var(--text-light);
            line-height: 1.4;
        }

        .instructor-name {
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-published {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-approve {
            background: #10b981;
            color: white;
        }

        .btn-approve:hover {
            background: #059669;
            transform: scale(1.05);
        }

        .btn-unpublish {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-unpublish:hover {
            background: #e2e8f0;
        }

        .btn-delete {
            background: #fef2f2;
            color: #ef4444;
        }

        .btn-delete:hover {
            background: #fee2e2;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            opacity: 0.5;
        }

        .success-alert {
            background: #dcfce7;
            color: #166534;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid #bbf7d0;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .courses-stats-row { grid-template-columns: 1fr; }
            .action-btns { flex-direction: column; }
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
                <li><a href="admin_courses.php" class="active"><b>📚 Modération</b></a></li>
                <li><a href="admin_events.php">📅 Événements</a></li>
                <li><a href="admin_sales.php">💳 Finances</a></li>
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
                    Modération des Formations 📚
                </h1>
                <p style="color:#e2e8f0; font-size:1.2rem; margin-bottom:3rem; font-weight:300;">
                    Validez ou suspendez les cours proposés par les formateurs.
                </p>

                <div class="stats-container" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <!-- Stat Card 1 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: var(--primary);"><?php echo $total_courses; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Total Formations</div>
                    </div>
                
                    <!-- Stat Card 2 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #10b981;"><?php echo $published_courses; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Publiées</div>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #f59e0b;"><?php echo $pending_courses; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">En Attente</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MAIN CONTENT -->
        <section class="section-padding">
            <div class="container" style="max-width: 1400px; margin: 0 auto;">
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                    <div class="success-alert">
                        ✅ Action effectuée avec succès !
                    </div>
                <?php endif; ?>

                <!-- DETAILED STATS -->
                <div class="courses-stats-row">
                    <div class="stat-box">
                        <div class="stat-icon">📘</div>
                        <div class="stat-number"><?php echo $total_courses; ?></div>
                        <div class="stat-label">Formations au catalogue</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?php echo $published_courses; ?></div>
                        <div class="stat-label">Cours validés et publiés</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-number"><?php echo $pending_courses; ?></div>
                        <div class="stat-label">En attente de validation</div>
                    </div>
                </div>

                <!-- COURSES TABLE -->
                <div class="courses-card">
                    <div class="card-header">
                        <h3>Liste des Formations</h3>
                        <div style="font-size: 0.85rem; color: var(--text-light); font-weight: 600;">
                            <?php echo count($courses); ?> formations
                        </div>
                    </div>

                    <?php if (empty($courses)): ?>
                        <div class="empty-state">
                            <div style="font-size: 4rem;">📚</div>
                            <p>Aucune formation dans le système pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="courses-table">
                                <thead>
                                    <tr>
                                        <th>Formation</th>
                                        <th>Formateur</th>
                                        <th>Prix</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td style="max-width: 400px;">
                                            <div class="course-title"><?php echo htmlspecialchars($c['title']); ?></div>
                                            <div class="course-desc"><?php echo htmlspecialchars(substr($c['description'], 0, 100)); ?>...</div>
                                        </td>
                                        <td>
                                            <div class="instructor-name">
                                                👨‍🏫 <?php echo htmlspecialchars($c['first_name'].' '.$c['last_name']); ?>
                                            </div>
                                        </td>
                                        <td style="font-weight: 700; color: var(--secondary);"><?php echo number_format($c['price'], 0); ?> DA</td>
                                        <td>
                                            <span class="status-badge <?php echo $c['is_published'] ? 'status-published' : 'status-pending'; ?>">
                                                <?php echo $c['is_published'] ? 'PUBLIÉ' : 'EN ATTENTE'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <?php if (!$c['is_published']): ?>
                                                    <a href="?action=approve&id=<?php echo $c['id']; ?>" class="btn-action btn-approve">✅ APPROUVER</a>
                                                <?php else: ?>
                                                    <a href="?action=unpublish&id=<?php echo $c['id']; ?>" class="btn-action btn-unpublish">📴 RETIRER</a>
                                                <?php endif; ?>
                                                <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer définitivement cette formation ?');">🗑️</a>
                                            </div>
                                        </td>
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
