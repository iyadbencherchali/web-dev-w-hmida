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

// Handle promotion update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_promotion'])) {
    $course_id = intval($_POST['course_id']);
    $discount_percentage = floatval($_POST['discount_percentage']);
    $is_on_promotion = isset($_POST['is_on_promotion']) ? 1 : 0;
    
    try {
        // Get current price
        $stmt = $pdo->prepare("SELECT price FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();
        
        if ($course) {
            $original_price = $course['price'];
            $discounted_price = $original_price * (1 - ($discount_percentage / 100));
            
            // Update promotion
            $stmt = $pdo->prepare("
                UPDATE courses 
                SET discount_percentage = ?,
                    discounted_price = ?,
                    is_on_promotion = ?
                WHERE id = ?
            ");
            $stmt->execute([$discount_percentage, $discounted_price, $is_on_promotion, $course_id]);
            
            $success_msg = "Promotion mise à jour avec succès !";
        }
    } catch (PDOException $e) {
        $error_msg = "Erreur : " . $e->getMessage();
    }
}

// Handle end promotion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['end_promotion'])) {
    $course_id = intval($_POST['course_id']);
    
    try {
        // End promotion by setting is_on_promotion to 0 and discount to 0
        $stmt = $pdo->prepare("
            UPDATE courses 
            SET discount_percentage = 0,
                discounted_price = price,
                is_on_promotion = 0
            WHERE id = ?
        ");
        $stmt->execute([$course_id]);
        
        $success_msg = "Promotion terminée avec succès !";
    } catch (PDOException $e) {
        $error_msg = "Erreur : " . $e->getMessage();
    }
}

// Fetch all courses with promotion info
try {
    $stmt = $pdo->query("
        SELECT c.*, u.first_name, u.last_name 
        FROM courses c 
        JOIN instructors i ON c.instructor_id = i.user_id 
        JOIN users u ON i.user_id = u.id 
        ORDER BY c.is_on_promotion DESC, c.created_at DESC
    ");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Promotions | Administration</title>
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

        .promo-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .promo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
        }

        .promo-card.active-promo {
            border-left: 5px solid #10b981;
            background: #f0fdf4;
        }

        .promo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .course-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .course-instructor {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .promo-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-group input[type="number"],
        .form-group input[type="text"] {
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 600;
        }

        .price-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .original-price {
            color: var(--text-light);
            text-decoration: line-through;
        }

        .discounted-price {
            color: #10b981;
            font-size: 1.5rem;
        }

        .discount-badge {
            background: #fbbf24;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .btn-save {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .btn-end-promo {
            background: #ef4444;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-end-promo:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        }

        .promo-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .section-header {
            margin-bottom: 2rem;
        }

        .section-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary);
            border-left: 5px solid var(--primary);
            padding-left: 1rem;
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
                <li><a href="admin_reviews.php">⭐ Avis</a></li>
                <li><a href="admin_promotions.php" class="active"><b>🎁 Promotions</b></a></li>
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
                    🎁 Gestion des Promotions
                </h1>
                <p style="color:#e2e8f0; font-size:1.1rem; font-weight:300;">
                    Créez et gérez les promotions pour augmenter les inscriptions
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

        <!-- PROMOTIONS LIST -->
        <section class="section-padding">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <div class="section-header">
                    <h2>Liste des Formations</h2>
                </div>

                <?php foreach ($courses as $course): 
                    $is_active = $course['is_on_promotion'] == 1;
                    $discount = $course['discount_percentage'] ?? 0;
                    $original_price = $course['price'];
                    $discounted_price = $course['discounted_price'] ?? $original_price;
                ?>
                    <div class="promo-card <?php echo $is_active ? 'active-promo' : ''; ?>">
                        <div class="promo-header">
                            <div>
                                <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                                <div class="course-instructor">
                                    👨‍🏫 <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                                </div>
                            </div>
                            <div class="price-display">
                                <?php if ($is_active && $discount > 0): ?>
                                    <span class="original-price"><?php echo number_format($original_price, 0); ?> DA</span>
                                    <span class="discounted-price"><?php echo number_format($discounted_price, 0); ?> DA</span>
                                    <span class="discount-badge">-<?php echo $discount; ?>%</span>
                                <?php else: ?>
                                    <span style="color: var(--secondary); font-size: 1.3rem;">
                                        <?php echo number_format($original_price, 0); ?> DA
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" class="promo-form">
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                            
                            <div class="form-group">
                                <label for="discount_<?php echo $course['id']; ?>">Réduction (%)</label>
                                <input 
                                    type="number" 
                                    id="discount_<?php echo $course['id']; ?>"
                                    name="discount_percentage" 
                                    value="<?php echo $discount; ?>"
                                    min="0" 
                                    max="100" 
                                    step="0.01"
                                    placeholder="Ex: 20"
                                >
                            </div>

                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input 
                                        type="checkbox" 
                                        id="active_<?php echo $course['id']; ?>"
                                        name="is_on_promotion"
                                        <?php echo $is_active ? 'checked' : ''; ?>
                                    >
                                    <label for="active_<?php echo $course['id']; ?>">
                                        Promotion Active
                                    </label>
                                </div>
                            </div>

                            <div class="form-group promo-actions">
                                <button type="submit" name="update_promotion" class="btn-save">
                                    💾 Enregistrer
                                </button>
                            </div>
                        </form>
                        
                        <?php if ($is_active): ?>
                            <form method="POST" style="margin-top: 1rem;" onsubmit="return confirm('Êtes-vous sûr de vouloir terminer cette promotion ?');">
                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                <button type="submit" name="end_promotion" class="btn-end-promo">
                                    ⛔ Terminer la promotion
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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
