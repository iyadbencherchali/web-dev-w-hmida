<?php
session_start();
require_once 'config.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Handle Approve Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_review'])) {
    $review_id = intval($_POST['review_id']);
    
    try {
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        $success_msg = "Avis approuvé avec succès !";
    } catch (PDOException $e) {
        $error_msg = "Erreur : " . $e->getMessage();
    }
}

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_review'])) {
    $review_id = intval($_POST['review_id']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $success_msg = "Avis supprimé avec succès !";
    } catch (PDOException $e) {
        $error_msg = "Erreur : " . $e->getMessage();
    }
}

// Fetch All Reviews (Pending + Approved)
try {
    $stmt = $pdo->query("
        SELECT r.*, u.first_name, u.last_name, u.email 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.is_approved ASC, r.created_at DESC
    ");
    $all_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separate pending and approved
    $pending_reviews = array_filter($all_reviews, fn($r) => $r['is_approved'] == 0);
    $approved_reviews = array_filter($all_reviews, fn($r) => $r['is_approved'] == 1);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modération des Avis | Administration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body.no-sidebar { margin: 0; }
        
        .hero-animated {
            background-color: #0f172a !important;
            text-align: center;
            padding: 4rem 2rem;
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

        .review-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .review-card.pending {
            border-left: 5px solid #f59e0b;
            background: #fffbeb;
        }

        .review-card.approved {
            border-left: 5px solid #10b981;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .user-info-admin {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .review-rating {
            font-size: 1.5rem;
        }

        .review-comment {
            font-size: 1.1rem;
            color: var(--secondary);
            line-height: 1.6;
            margin-bottom: 1rem;
            font-style: italic;
        }

        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .review-date {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .review-actions {
            display: flex;
            gap: 0.75rem;
        }

        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-approved {
            background: #dcfce7;
            color: #166534;
        }

        .btn-approve {
            background: #10b981;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary);
            border-left: 5px solid var(--primary);
            padding-left: 1rem;
        }

        .stats-badge {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
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
                <li><a href="admin_reviews.php" class="active"><b>⭐ Avis</b></a></li>
                <li><a href="admin_sales.php">💳 Finances</a></li>
                <li><a href="admin_questions.php">💬 Support</a></li>
                <li id="logout"><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- HERO SECTION -->
        <section class="section-padding bg-dark hero-animated">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            
            <div style="position:relative; z-index:2; max-width: 1200px; margin: 0 auto;">
                <h1 style="color:#fff; font-size:2.5rem; margin-bottom:1rem; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                    ⭐ Modération des Avis
                </h1>
                <p style="color:#e2e8f0; font-size:1.1rem; font-weight:300;">
                    Gérez et approuvez les témoignages de vos étudiants
                </p>
            </div>
        </section>

        <!-- ALERTS -->
        <?php if (!empty($success_msg)): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; text-align: center; margin: 2rem auto; max-width: 1200px; border-radius: 12px; border: 1px solid #bbf7d0;">
                ✅ <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; text-align: center; margin: 2rem auto; max-width: 1200px; border-radius: 12px; border: 1px solid #fecaca;">
                ❌ <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- PENDING REVIEWS -->
        <section class="section-padding" style="background: #f8fafc;">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <div class="section-header">
                    <h2>⏳ Avis en Attente</h2>
                    <span class="stats-badge"><?php echo count($pending_reviews); ?></span>
                </div>

                <?php if (empty($pending_reviews)): ?>
                    <div class="empty-state">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
                        <h3 style="margin-bottom: 1rem; color: #1e293b;">Aucun avis en attente</h3>
                        <p style="color: #64748b;">Tous les avis ont été traités !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_reviews as $review): 
                        $stars = str_repeat('⭐', $review['rating']);
                        $initials = strtoupper($review['first_name'][0] . $review['last_name'][0]);
                    ?>
                        <div class="review-card pending">
                            <div class="review-header">
                                <div class="user-info-admin">
                                    <div class="user-avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--secondary);">
                                            <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-light);">
                                            <?php echo htmlspecialchars($review['email']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-rating"><?php echo $stars; ?></div>
                            </div>

                            <div class="review-comment">
                                "<?php echo htmlspecialchars($review['comment']); ?>"
                            </div>

                            <div class="review-meta">
                                <div class="review-date">
                                    📅 <?php echo date('d/m/Y à H:i', strtotime($review['created_at'])); ?>
                                </div>
                                <div class="review-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="approve_review" class="btn-approve">
                                            ✅ Approuver
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="delete_review" class="btn-delete">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- APPROVED REVIEWS -->
        <section class="section-padding">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <div class="section-header">
                    <h2>✅ Avis Approuvés</h2>
                    <span class="stats-badge" style="background: #10b981;"><?php echo count($approved_reviews); ?></span>
                </div>

                <?php if (empty($approved_reviews)): ?>
                    <div class="empty-state">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">💬</div>
                        <h3 style="margin-bottom: 1rem; color: #1e293b;">Aucun avis approuvé</h3>
                        <p style="color: #64748b;">Commencez par approuver des avis ci-dessus !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($approved_reviews as $review): 
                        $stars = str_repeat('⭐', $review['rating']);
                        $initials = strtoupper($review['first_name'][0] . $review['last_name'][0]);
                    ?>
                        <div class="review-card approved">
                            <div class="review-header">
                                <div class="user-info-admin">
                                    <div class="user-avatar"><?php echo $initials; ?></div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--secondary);">
                                            <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-light);">
                                            <?php echo htmlspecialchars($review['email']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-rating"><?php echo $stars; ?></div>
                            </div>

                            <div class="review-comment">
                                "<?php echo htmlspecialchars($review['comment']); ?>"
                            </div>

                            <div class="review-meta">
                                <div class="review-date">
                                    📅 <?php echo date('d/m/Y à H:i', strtotime($review['created_at'])); ?>
                                </div>
                                <div class="review-actions">
                                    <span class="badge-approved">✅ Publié</span>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="delete_review" class="btn-delete">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
