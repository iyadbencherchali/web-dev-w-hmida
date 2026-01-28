<?php
session_start();
require_once 'db_connect.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'Étudiant';

// Fetch enrolled courses
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.first_name as instructor_fname, u.last_name as instructor_lname, e.enrolled_at, e.progress_percentage
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN users u ON c.instructor_id = u.id
        WHERE e.student_id = ?
    ");
    $stmt->execute([$user_id]);
    $my_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Average Progress
    $total_progress = 0;
    if (count($my_courses) > 0) {
        foreach ($my_courses as $c) {
            $total_progress += $c['progress_percentage'];
        }
        $avg_progress = round($total_progress / count($my_courses));
    } else {
        $avg_progress = 0;
    }

} catch (PDOException $e) {
    die("Error fetching courses: " . $e->getMessage());
}

// ... (Image Mapping Remains Same) ...

$success_msg = "";
$error_msg = "";

// Handle Review Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5 || empty($comment)) {
        $error_msg = "Veuillez donner une note et un commentaire valide.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if ($stmt->fetch()) {
                $error_msg = "Vous avez déjà soumis un avis.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, rating, comment, is_approved) VALUES (?, ?, ?, 0)");
                $stmt->execute([$user_id, $rating, $comment]);
                $success_msg = "Merci pour votre avis ! Il sera publié après validation.";
            }
        } catch (PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}

// Handle Support Question Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_question'])) {
    $subject = trim($_POST['subject']);
    $question = trim($_POST['question']);

    if (empty($subject) || empty($question)) {
        $error_msg = "Veuillez remplir tous les champs du support.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO questions (user_id, subject, question, status) VALUES (?, ?, ?, 'new')");
            $stmt->execute([$user_id, $subject, $question]);
            $success_msg = "Votre question a bien été envoyée au support !";
        } catch (PDOException $e) {
            $error_msg = "Erreur support : " . $e->getMessage();
        }
    }
}

// Fetch user's questions
try {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $my_questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_questions = [];
}

// Fetch user's payments
try {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $my_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_payments = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 3rem 2rem;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        .dashboard-welcome h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .dashboard-stats { display: flex; gap: 2rem; margin-top: 2rem; }
        .stat-card { background: rgba(255,255,255,0.1); padding: 1rem 1.5rem; border-radius: 12px; backdrop-filter: blur(5px); }
        .stat-number { font-size: 1.5rem; font-weight: bold; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
        
        .progress-bar { width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 1rem; }
        .progress-fill { height: 100%; background: var(--success); transition: width 0.3s ease; }
        
        .empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: var(--radius); border: 1px solid var(--border); }
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
                <li><a href="formation.php">Formations</a></li>
                <li><a href="evenements.php">Évènements</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li><a href="panier.php">Panier</a></li>
                <li><a href="paiement.php">Paiement</a></li>
                <?php 
                    $dash = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : (($_SESSION['role'] == 'instructor') ? 'instructor_dashboard.php' : 'dashboard.php');
                ?>
                <li><a href="<?php echo $dash; ?>" class="active"><b>Mon Espace</b></a></li>
                <li id="logout"><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <!-- Dashboard Hero (Animated & Glassmorphism) -->


        <section class="section-padding bg-dark hero-animated" style="background-color: #0f172a !important; text-align:center; padding: 6rem 2rem; position: relative; overflow: hidden;">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            
            <div style="position:relative; z-index:2; max-width: 1200px; margin: 0 auto;">
                <h1 class="section-title-center" style="color:#fff; font-size:3rem; margin-bottom:1rem; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                    Bonjour, <?php echo htmlspecialchars($first_name); ?>! 👋
                </h1>
                <p style="color:#e2e8f0; font-size:1.2rem; margin-bottom:3rem; font-weight:300;">
                    Prêt à continuer votre apprentissage aujourd'hui ?
                </p>

                <div class="stats-container" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <!-- Stat Card 1 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 250px; text-align: center; border: 1px solid rgba(255,255,255,0.2);">
                        <div style="font-size: 3rem; font-weight: bold; color: var(--primary);"><?php echo count($my_courses); ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Cours Inscrits</div>
                    </div>
                
                    <!-- Stat Card 2 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 250px; text-align: center; border: 1px solid rgba(255,255,255,0.2);">
                            <div style="font-size: 3rem; font-weight: bold; color: #10b981;"><?php echo $avg_progress; ?>%</div>
                            <div style="color: #cbd5e1; font-weight: 600;">Progression Moyenne</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Course List -->
        <section class="section-padding">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <h2 class="section-title" style="margin-bottom: 2rem; border-left: 5px solid var(--primary); padding-left: 1rem;">Mes Formations</h2>

                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'payment_success' && isset($_GET['receipt'])): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #bbf7d0;">
                        🎉 Paiement réussi ! Vous êtes inscrit. 
                        <a href="recu.php?id=<?php echo $_GET['receipt']; ?>" target="_blank" style="font-weight:bold; margin-left: 10px; color: #166534; text-decoration: underline;">Voir le reçu</a>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'enroll_success'): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #bbf7d0;">
                        🎉 Félicitations ! Vous êtes inscrit avec succès.
                    </div>
                <?php endif; ?>

                <?php if (empty($my_courses)): ?>
                    <div class="empty-state" style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                        <h3 style="margin-bottom: 1rem; color: #1e293b;">Vous n'êtes inscrit à aucun cours pour le moment.</h3>
                        <p style="margin-bottom: 2rem; color: #64748b;">Explorez notre catalogue pour commencer à apprendre.</p>
                        <a href="formation.php" class="btn btn-primary">Voir les formations</a>
                    </div>
                <?php else: ?>
                    <div class="formations-grid">
                        <?php foreach ($my_courses as $course): ?>
                            <?php 
                                $image = $course_images[$course['title']] ?? 'default_course.png';
                            ?>
                            <article class="formation" style="border-radius: 20px; border: none; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                                <div class="formation-thumb">
                                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    <span class="badge" style="background:var(--success)">Inscrit</span>
                                </div>
                                <div class="formation-content">
                                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p class="formation-instructor">👨‍🏫 <?php echo htmlspecialchars($course['instructor_fname']); ?></p>
                                    
                                    <div class="progress-bar" style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 1rem;">
                                        <div class="progress-fill" style="height: 100%; background: var(--success); width: <?php echo $course['progress_percentage']; ?>%"></div>
                                    </div>
                                    <p style="font-size: 0.9rem; color: var(--text-light); margin-top: 0.5rem;">
                                        Progression : <?php echo $course['progress_percentage']; ?>%
                                    </p>

                                    <div class="formation-buttons" style="margin-top: 1.5rem;">
                                        <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary" style="width: 100%; text-decoration: none; display: block; text-align: center;">Continuer la formation</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- PAYMENT HISTORY SECTION -->
        <section class="section-padding" style="background: #f8fafc;">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <h2 class="section-title" style="margin-bottom: 2rem; border-left: 5px solid #10b981; padding-left: 1rem;">Historique de Paiements</h2>
                
                <?php if (empty($my_payments)): ?>
                    <div style="background: white; padding: 3rem; text-align: center; border-radius: 20px; border: 1px solid #e2e8f0; color: var(--text-light);">
                        <p>Vous n'avez pas encore effectué d'achats.</p>
                    </div>
                <?php else: ?>
                    <div style="background: white; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead style="background: #f1f5f9;">
                                <tr>
                                    <th style="padding: 1.25rem 1.5rem; color: var(--secondary); font-weight: 700;">Date</th>
                                    <th style="padding: 1.25rem 1.5rem; color: var(--secondary); font-weight: 700;">Méthode</th>
                                    <th style="padding: 1.25rem 1.5rem; color: var(--secondary); font-weight: 700;">Montant</th>
                                    <th style="padding: 1.25rem 1.5rem; color: var(--secondary); font-weight: 700;">Statut</th>
                                    <th style="padding: 1.25rem 1.5rem; color: var(--secondary); font-weight: 700;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_payments as $payment): ?>
                                    <tr style="border-top: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                        <td style="padding: 1.25rem 1.5rem; color: var(--text-light); font-weight: 500;">
                                            <?php echo date('d/m/Y', strtotime($payment['created_at'])); ?>
                                            <div style="font-size: 0.75rem; opacity: 0.7;"><?php echo date('H:i', strtotime($payment['created_at'])); ?></div>
                                        </td>
                                        <td style="padding: 1.25rem 1.5rem; text-transform: uppercase; font-size: 0.85rem; font-weight: 700; color: var(--secondary);">
                                            <?php 
                                                $icons = ['card' => '💳', 'paypal' => '🅿️', 'bank' => '🏦'];
                                                echo ($icons[$payment['payment_method']] ?? '💰') . ' ' . $payment['payment_method']; 
                                            ?>
                                        </td>
                                        <td style="padding: 1.25rem 1.5rem; font-weight: 800; color: var(--secondary);">
                                            <?php echo number_format($payment['amount'], 0, '.', ','); ?> DA
                                        </td>
                                        <td style="padding: 1.25rem 1.5rem;">
                                            <span style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">PAYÉ</span>
                                        </td>
                                        <td style="padding: 1.25rem 1.5rem;">
                                            <a href="recu.php?id=<?php echo $payment['id']; ?>" class="view-all-link" style="font-size: 0.75rem;">Voir reçu</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <!-- REVIEW SUBMISSION SECTION -->
        <section class="section-padding" style="background: #f8fafc;">
            <div class="container" style="max-width: 800px; margin: 0 auto;">
                <h2 class="section-title" style="margin-bottom: 2rem; text-align: center;">⭐ Votre Avis Compte</h2>
                
                <?php if (!empty($success_msg)): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #bbf7d0; text-align: center;">
                        ✅ <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca; text-align: center;">
                        ❌ <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div style="background: white; padding: 3rem; border-radius: 24px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0;">
                    <form method="POST" action="">
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <label style="display: block; font-weight: 700; font-size: 1.2rem; color: var(--secondary); margin-bottom: 1rem;">Votre Note</label>
                            <div class="rating-group" style="display: inline-flex; flex-direction: row-reverse; gap: 0.5rem;">
                                <?php for($i=5; $i>=1; $i--): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" style="display: none;" required>
                                    <label for="star<?php echo $i; ?>" style="font-size: 2.5rem; cursor: pointer; color: #e2e8f0; transition: color 0.2s;">★</label>
                                <?php endfor; ?>
                            </div>
                            <style>
                                .rating-group input:checked ~ label,
                                .rating-group label:hover,
                                .rating-group label:hover ~ label {
                                    color: #fbbf24 !important;
                                }
                            </style>
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <label style="display: block; font-weight: 700; color: var(--secondary); margin-bottom: 0.5rem;">Votre Témoignage</label>
                            <textarea name="comment" required rows="4" placeholder="Partagez votre expérience avec nos formations..." style="width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 16px; font-size: 1rem; resize: vertical; transition: border-color 0.3s;"></textarea>
                        </div>

                        <button type="submit" name="submit_review" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                            Publier mon avis 🚀
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- SUPPORT SECTION -->
        <section class="section-padding" style="background: white;">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <h2 class="section-title" style="margin-bottom: 3rem; text-align: center;">💬 Support & Assistance</h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 3rem;">
                    <!-- Question Form -->
                    <div style="background: #f8fafc; padding: 2.5rem; border-radius: 20px; border: 1px solid #e2e8f0; height: fit-content;">
                        <h3 style="margin-bottom: 1.5rem; color: var(--secondary);">Besoin d'aide ?</h3>
                        <p style="color: var(--text-light); margin-bottom: 2rem; font-size: 0.95rem;">Posez votre question et notre équipe vous répondra par email dans les plus brefs délais.</p>
                        
                        <form method="POST" action="">
                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Sujet</label>
                                <input type="text" name="subject" required placeholder="Ex: Problème d'accès au contenu" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 12px; font-size: 1rem;">
                            </div>
                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Votre Question</label>
                                <textarea name="question" required rows="4" placeholder="Détaillez votre demande ici..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 12px; font-size: 1rem; resize: vertical;"></textarea>
                            </div>
                            <button type="submit" name="submit_question" class="btn btn-secondary" style="width: 100%; padding: 0.85rem;">Envoyer au support</button>
                        </form>
                    </div>

                    <!-- Question History -->
                    <div>
                        <h3 style="margin-bottom: 2rem; color: var(--secondary);">🕒 Mes Demandes Précédentes</h3>
                        
                        <?php if (empty($my_questions)): ?>
                            <div style="background: #f8fafc; padding: 3rem; text-align: center; border-radius: 20px; border: 1px dashed #cbd5e1;">
                                <p style="color: var(--text-light);">Vous n'avez pas encore posé de question.</p>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                                <?php foreach ($my_questions as $q): ?>
                                    <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0; border-left: 5px solid <?php 
                                        echo ($q['status'] == 'new') ? '#3b82f6' : (($q['status'] == 'in_progress') ? '#f59e0b' : '#10b981'); 
                                    ?>; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                            <strong style="color: var(--secondary);"><?php echo htmlspecialchars($q['subject']); ?></strong>
                                            <?php
                                                $status_labels = ['new' => 'Nouveau', 'in_progress' => 'En cours', 'answered' => 'Répondu'];
                                                $status_colors = ['new' => '#dbeafe', 'in_progress' => '#fef3c7', 'answered' => '#dcfce7'];
                                                $status_text_colors = ['new' => '#1e40af', 'in_progress' => '#92400e', 'answered' => '#166534'];
                                            ?>
                                            <span style="padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; background: <?php echo $status_colors[$q['status']]; ?>; color: <?php echo $status_text_colors[$q['status']]; ?>;">
                                                <?php echo $status_labels[$q['status']]; ?>
                                            </span>
                                        </div>
                                        <p style="font-size: 0.9rem; color: var(--text-light); font-style: italic; margin-bottom: 1rem;">"<?php echo htmlspecialchars($q['question']); ?>"</p>
                                        <div style="font-size: 0.8rem; color: #94a3b8; display: flex; align-items: center; gap: 15px; border-top: 1px solid #f1f5f9; padding-top: 0.75rem;">
                                            <span>📅 <?php echo date('d/m/Y à H:i', strtotime($q['created_at'])); ?></span>
                                            <?php if ($q['status'] == 'answered'): ?>
                                                <span style="color: #10b981; font-weight: 600;">✅ Une réponse vous a été envoyée</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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

    <div style="text-align:center; color: #94a3b8; padding: 1rem;">
        <!-- System loaded -->
    </div>
</body>
</html>
