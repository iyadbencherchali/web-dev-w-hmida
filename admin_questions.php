<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$first_name = $_SESSION['first_name'];

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $qid = $_GET['id'];
    
    if ($_GET['action'] == 'mark_progress') {
        $pdo->prepare("UPDATE questions SET status = 'in_progress' WHERE id = ?")->execute([$qid]);
    } elseif ($_GET['action'] == 'mark_answered') {
        $pdo->prepare("UPDATE questions SET status = 'answered', answered_at = NOW() WHERE id = ?")->execute([$qid]);
    } elseif ($_GET['action'] == 'delete') {
        $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$qid]);
    }
    
    header("Location: admin_questions.php?msg=success");
    exit();
}

// Fetch Questions
$stmt = $pdo->query("
    SELECT q.*, u.first_name, u.last_name, u.email 
    FROM questions q 
    JOIN users u ON q.user_id = u.id 
    ORDER BY 
        CASE status
            WHEN 'new' THEN 1
            WHEN 'in_progress' THEN 2
            WHEN 'answered' THEN 3
        END,
        created_at DESC
");
$questions = $stmt->fetchAll();

// Stats
$total_questions = count($questions);
$new_questions = count(array_filter($questions, fn($q) => $q['status'] == 'new'));
$pending_questions = count(array_filter($questions, fn($q) => $q['status'] == 'in_progress'));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Étudiants | Centre De Formation</title>
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
        .stats-row {
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
            transition: transform 0.3s ease;
        }

        .stat-box:hover { transform: translateY(-10px); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        /* --- QUESTIONS TABLE --- */
        .questions-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border: 1px solid #e2e8f0;
        }

        .questions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .questions-table th {
            text-align: left;
            padding: 1rem;
            color: var(--text-light);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .questions-table tr {
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .questions-table tr:hover {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: scale(1.01);
        }

        .questions-table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .questions-table td:first-child { border-radius: 10px 0 0 10px; }
        .questions-table td:last-child { border-radius: 0 10px 10px 0; }

        .status-badge {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }

        .status-new { background: #dbeafe; color: #1e40af; }
        .status-progress { background: #fef3c7; color: #92400e; }
        .status-answered { background: #dcfce7; color: #166534; }

        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .btn-email { background: var(--primary); color: white; }
        .btn-email:hover { background: var(--secondary); }

        .btn-check { background: #dcfce7; color: #166534; }
        .btn-check:hover { background: #bbf7d0; }
        
        .btn-progress { background: #fef3c7; color: #92400e; }
        .btn-progress:hover { background: #fde68a; }

        .btn-delete { background: #fef2f2; color: #ef4444; }
        .btn-delete:hover { background: #fee2e2; }

        .question-preview {
            font-style: italic;
            color: var(--text-light);
            font-size: 0.9rem;
            margin-top: 5px;
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
                <li><a href="admin_dashboard.php">Dashboard Admin</a></li>
                <li><a href="admin_users.php">👥 Utilisateurs</a></li>
                <li><a href="admin_courses.php">📚 Modération</a></li>
                <li><a href="admin_events.php">📅 Événements</a></li>
                <li><a href="admin_reviews.php">⭐ Avis</a></li>
                <li><a href="admin_sales.php">💳 Finances</a></li>
                <li><a href="admin_questions.php" class="active"><b>💬 Support</b></a></li>
                <li id="logout"><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- HERO -->
        <section class="section-padding bg-dark hero-animated" style="background-color: #0f172a !important;">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            
            <div style="position:relative; z-index:2; max-width: 1200px; margin: 0 auto;">
                <h1 style="color:#fff; font-size:3rem; margin-bottom:1rem; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                    Support Étudiants 💬
                </h1>
                <p style="color:#e2e8f0; font-size:1.2rem; margin-bottom:3rem; font-weight:300;">
                    Répondez aux questions et besoins d'aide de vos étudiants.
                </p>

                <div class="stats-container" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: var(--primary);"><?php echo $total_questions; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Total Questions</div>
                    </div>
                
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #3b82f6;"><?php echo $new_questions; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Nouvelles</div>
                    </div>

                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #f59e0b;"><?php echo $pending_questions; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">En Cours</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CONTENT -->
        <section class="section-padding">
            <div class="container" style="max-width: 1400px; margin: 0 auto;">
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600;">
                        ✅ Action effectuée avec succès !
                    </div>
                <?php endif; ?>

                <div class="questions-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem;">
                        <h3 style="margin: 0; font-size: 1.3rem; color: var(--secondary); border-left: 4px solid var(--primary); padding-left: 1rem;">Boîte de Réception</h3>
                        <div style="font-size: 0.85rem; color: var(--text-light);"><?php echo count($questions); ?> messages</div>
                    </div>

                    <?php if (empty($questions)): ?>
                        <div style="text-align: center; padding: 4rem; opacity: 0.5;">
                            <div style="font-size: 4rem;">📭</div>
                            <p>Aucune question pour le moment.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="questions-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Étudiant</th>
                                        <th style="width: 40%;">Sujet & Question</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questions as $q): ?>
                                    <tr>
                                        <td style="color: var(--text-light); font-weight: 600;">
                                            <?php echo date('d/m/Y', strtotime($q['created_at'])); ?><br>
                                            <small><?php echo date('H:i', strtotime($q['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--secondary);">
                                                <?php echo htmlspecialchars($q['first_name'].' '.$q['last_name']); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-light);">
                                                <?php echo htmlspecialchars($q['email']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--secondary); margin-bottom: 5px;">
                                                <?php echo htmlspecialchars($q['subject']); ?>
                                            </div>
                                            <div class="question-preview">
                                                "<?php echo htmlspecialchars(substr($q['question'], 0, 100)) . (strlen($q['question']) > 100 ? '...' : ''); ?>"
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $badge_class = 'status-new';
                                            $status_text = 'NOUVEAU';
                                            if ($q['status'] == 'in_progress') { $badge_class = 'status-progress'; $status_text = 'EN COURS'; }
                                            elseif ($q['status'] == 'answered') { $badge_class = 'status-answered'; $status_text = 'RÉPONDU'; }
                                            ?>
                                            <span class="status-badge <?php echo $badge_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                                <!-- MAILTO LINK -->
                                                <a href="mailto:<?php echo $q['email']; ?>?subject=Re: <?php echo urlencode($q['subject']); ?>&body=Bonjour <?php echo urlencode($q['first_name']); ?>,%0D%0A%0D%0ASuite à votre question concernant : <?php echo urlencode($q['subject']); ?>...%0D%0A%0D%0ACordialement,%0D%0AL'équipe du Centre de Formation" 
                                                   class="btn-action btn-email" target="_blank">
                                                    📧 Répondre
                                                </a>

                                                <?php if ($q['status'] != 'answered'): ?>
                                                    <a href="?action=mark_answered&id=<?php echo $q['id']; ?>" class="btn-action btn-check">✅ Fini</a>
                                                <?php endif; ?>
                                                
                                                <?php if ($q['status'] == 'new'): ?>
                                                    <a href="?action=mark_progress&id=<?php echo $q['id']; ?>" class="btn-action btn-progress">⏳ Traiter</a>
                                                <?php endif; ?>
                                                
                                                <a href="?action=delete&id=<?php echo $q['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer ce message ?');">🗑️</a>
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
        <p>&copy; 2025 Centre de Formation Professionnelle</p>
    </footer>

</body>
</html>
