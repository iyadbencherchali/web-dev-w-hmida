<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$first_name = $_SESSION['first_name'];

// Handle event actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $eid = $_GET['id'];
    if ($_GET['action'] == 'publish') {
        $pdo->prepare("UPDATE events SET is_published = 1 WHERE id = ?")->execute([$eid]);
    } elseif ($_GET['action'] == 'unpublish') {
        $pdo->prepare("UPDATE events SET is_published = 0 WHERE id = ?")->execute([$eid]);
    } elseif ($_GET['action'] == 'delete') {
        $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$eid]);
    }
    header("Location: admin_events.php?msg=success");
    exit();
}

// Fetch all events
$events = $pdo->query("SELECT * FROM events ORDER BY is_published ASC, event_date DESC")->fetchAll();

// Separate pending and published
$pending_events = array_filter($events, fn($e) => $e['is_published'] == 0);
$published_events = array_filter($events, fn($e) => $e['is_published'] == 1);

// Stats
$total_events = count($events);
$upcoming_events = count(array_filter($events, fn($e) => $e['event_date'] >= date('Y-m-d')));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Événements | Centre De Formation</title>
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
        .events-stats-row {
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

        /* --- EVENTS CARD --- */
        .events-card {
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

        .btn-create {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-create:hover {
            background: var(--secondary);
            transform: scale(1.05);
        }

        /* --- EVENTS TABLE --- */
        .events-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .events-table thead th {
            text-align: left;
            padding: 0.75rem 1rem;
            color: var(--text-light);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .events-table tbody tr {
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .events-table tbody tr:hover {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: scale(1.01);
        }

        .events-table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .events-table tbody td:first-child { border-radius: 10px 0 0 10px; }
        .events-table tbody td:last-child { border-radius: 0 10px 10px 0; }

        .event-title {
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 4px;
        }

        .event-desc {
            font-size: 0.8rem;
            color: var(--text-light);
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

        .status-draft {
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

        .btn-edit {
            background: #e0f2fe;
            color: #0369a1;
        }

        .btn-edit:hover {
            background: #bae6fd;
        }

        .btn-publish {
            background: #10b981;
            color: white;
        }

        .btn-publish:hover {
            background: #059669;
        }

        .btn-unpublish {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-delete {
            background: #fef2f2;
            color: #ef4444;
        }

        .btn-delete:hover {
            background: #fee2e2;
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
                <li><a href="admin_events.php" class="active"><b>📅 Événements</b></a></li>
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
                    Gestion des Événements 📅
                </h1>
                <p style="color:#e2e8f0; font-size:1.2rem; margin-bottom:3rem; font-weight:300;">
                    Créez et gérez les événements et ateliers de votre centre.
                </p>

                <div class="stats-container" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: var(--primary);"><?php echo $total_events; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Total Événements</div>
                    </div>
                
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #10b981;"><?php echo $upcoming_events; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">À Venir</div>
                    </div>

                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #f59e0b;"><?php echo count($pending_events); ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">En Attente de Validation</div>
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

                <!-- PENDING EVENTS SECTION -->
                <?php if (!empty($pending_events)): ?>
                    <div class="events-card" style="margin-bottom: 2rem; border: 2px solid #f59e0b; background: #fffbeb;">
                        <div class="card-header">
                            <h3 style="color: #92400e;">⚠️ Événements en Attente de Validation (<?php echo count($pending_events); ?>)</h3>
                        </div>

                        <div style="overflow-x: auto;">
                            <table class="events-table">
                                <thead>
                                    <tr>
                                        <th>Événement</th>
                                        <th>Date</th>
                                        <th>Proposé par</th>
                                        <th>Lieu</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_events as $e): ?>
                                    <tr style="background: white;">
                                        <td style="max-width: 400px;">
                                            <div class="event-title"><?php echo htmlspecialchars($e['title']); ?></div>
                                            <div class="event-desc"><?php echo htmlspecialchars(substr($e['description'], 0, 80)); ?>...</div>
                                        </td>
                                        <td style="font-weight: 600; color: var(--text-light);">
                                            <?php echo date('d/m/Y', strtotime($e['event_date'])); ?>
                                            <?php if ($e['event_time']): ?>
                                                <br><small><?php echo date('H:i', strtotime($e['event_time'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: var(--text-light);">
                                            <?php 
                                            if ($e['created_by']) {
                                                $user_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                                                $user_stmt->execute([$e['created_by']]);
                                                $user = $user_stmt->fetch();
                                                echo $user ? htmlspecialchars($user['first_name'].' '.$user['last_name']) : 'N/A';
                                            } else {
                                                echo 'Admin';
                                            }
                                            ?>
                                        </td>
                                        <td style="color: var(--text-light);">📍 <?php echo htmlspecialchars($e['location']); ?></td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="?action=publish&id=<?php echo $e['id']; ?>" class="btn-action btn-publish" style="font-size: 0.85rem; padding: 8px 16px;">✅ APPROUVER</a>
                                                <a href="?action=delete&id=<?php echo $e['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Rejeter cet événement ?');">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ALL EVENTS TABLE -->
                <div class="events-stats-row">
                    <div class="stat-box">
                        <div class="stat-icon">📅</div>
                        <div class="stat-number"><?php echo $total_events; ?></div>
                        <div class="stat-label">Événements au total</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">🗓️</div>
                        <div class="stat-number"><?php echo $upcoming_events; ?></div>
                        <div class="stat-label">Événements à venir</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?php echo $published_events; ?></div>
                        <div class="stat-label">Publiés sur le site</div>
                    </div>
                </div>

                <!-- EVENTS TABLE -->
                <div class="events-card">
                    <div class="card-header">
                        <h3>Liste des Événements</h3>
                        <a href="create_event.php" class="btn-create">+ Créer un événement</a>
                    </div>

                    <?php if (empty($events)): ?>
                        <div style="text-align: center; padding: 4rem; opacity: 0.5;">
                            <div style="font-size: 4rem;">📅</div>
                            <p>Aucun événement créé pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="events-table">
                                <thead>
                                    <tr>
                                        <th>Événement</th>
                                        <th>Date</th>
                                        <th>Lieu</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $e): ?>
                                    <tr>
                                        <td style="max-width: 400px;">
                                            <div class="event-title"><?php echo htmlspecialchars($e['title']); ?></div>
                                            <div class="event-desc"><?php echo htmlspecialchars(substr($e['description'], 0, 80)); ?>...</div>
                                        </td>
                                        <td style="font-weight: 600; color: var(--text-light);">
                                            <?php echo date('d/m/Y', strtotime($e['event_date'])); ?>
                                            <?php if ($e['event_time']): ?>
                                                <br><small><?php echo date('H:i', strtotime($e['event_time'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: var(--text-light);">📍 <?php echo htmlspecialchars($e['location']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $e['is_published'] ? 'status-published' : 'status-draft'; ?>">
                                                <?php echo $e['is_published'] ? 'PUBLIÉ' : 'BROUILLON'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="edit_event.php?id=<?php echo $e['id']; ?>" class="btn-action btn-edit">✏️</a>
                                                <?php if (!$e['is_published']): ?>
                                                    <a href="?action=publish&id=<?php echo $e['id']; ?>" class="btn-action btn-publish">✅</a>
                                                <?php else: ?>
                                                    <a href="?action=unpublish&id=<?php echo $e['id']; ?>" class="btn-action btn-unpublish">📴</a>
                                                <?php endif; ?>
                                                <a href="?action=delete&id=<?php echo $e['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer cet événement ?');">🗑️</a>
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
