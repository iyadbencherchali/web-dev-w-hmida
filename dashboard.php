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
        SELECT c.*, i.bio, u.first_name as instructor_fname, u.last_name as instructor_lname, e.enrolled_at, e.progress_percentage
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        JOIN instructors i ON c.instructor_id = i.user_id
        JOIN users u ON i.user_id = u.id
        WHERE e.student_id = ?
    ");
    $stmt->execute([$user_id]);
    $my_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching courses: " . $e->getMessage());
}

// Image Mapping (Same as formation.php)
$course_images = [
    'Formation Python Complète' => '4375050_logo_python_icon.png',
    'Certification Cisco CCNA 200-301' => '294687_cisco_icon.png',
    'Expert en Cybersécurité' => '12983448_virus_malware_trojan_cybersecurity_icon.png',
    'Développeur Web Fullstack' => '317756_badge_css_css3_achievement_award_icon.png',
    'Microsoft Azure Fundamentals' => '4202105_microsoft_logo_social_social media_icon.png',
    'Google Analytics & Marketing Digital' => '2993685_brand_brands_google_logo_logos_icon.png'
];
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
        <a href="index.html" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
        </a>
        <nav>
            <ul>
                <li><a href="index.html">Accueil</a></li>
                <li><a href="formation.php">Formations</a></li>
                <li><a href="evenements.html">Évènements</a></li>
                <li><a href="blog.html">Blog</a></li>
                <li><a href="panier.php">Panier</a></li>
                <li><a href="paiement.php">Paiement</a></li>
                <li><a href="dashboard.php" class="active"><b>Mon Espace</b></a></li>
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
                        <div style="font-size: 3rem; font-weight: bold; color: #10b981;">0%</div>
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
                                        <button class="btn btn-primary" style="width: 100%;">Continuer la formation</button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
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

    <div style="text-align:center; color: #94a3b8; padding: 1rem;">
        <!-- Debug Marker -->
        <small>System loaded. ID: <?php echo $_SESSION['user_id']; ?></small>
    </div>
</body>
</html>
