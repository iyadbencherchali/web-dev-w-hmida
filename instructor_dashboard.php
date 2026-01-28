<?php
session_start();
require_once 'config.php';

// Check if logged in as instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

$instructor_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];

// Fetch Stats
try {
    // 1. Total Courses
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
    $stmt->execute([$instructor_id]);
    $total_courses = $stmt->fetchColumn();

    // 2. Total Students
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.student_id) 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    $stmt->execute([$instructor_id]);
    $total_students = $stmt->fetchColumn();

    // 3. Total Revenue (Simplified: All enrollment amounts from students for his courses)
    // Assuming a 'payments' table or similar logic. For now, let's just sum based on course price * enrollments
    $stmt = $pdo->prepare("
        SELECT SUM(c.price) 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    $stmt->execute([$instructor_id]);
    $total_revenue = $stmt->fetchColumn() ?: 0;

    // Fetch Courses
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ? ORDER BY created_at DESC");
    $stmt->execute([$instructor_id]);
    $my_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Recent Enrollments
    $stmt = $pdo->prepare("
        SELECT e.enrolled_at, u.first_name, u.last_name, c.title 
        FROM enrollments e 
        JOIN users u ON e.student_id = u.id 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ? 
        ORDER BY e.enrolled_at DESC LIMIT 5
    ");
    $stmt->execute([$instructor_id]);
    $recent_enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Instructeur | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .instructor-header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 0 0 30px 30px;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: -3rem;
            padding: 0 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border-bottom: 5px solid var(--primary);
            text-align: center;
        }

        .stat-value { font-size: 2.5rem; font-weight: 800; color: #1e293b; margin-bottom: 0.5rem; }
        .stat-label { color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; }

        .dashboard-container { max-width: 1200px; margin: 4rem auto; padding: 0 2rem; }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-left: 5px solid var(--primary);
            padding-left: 1rem;
        }

        .course-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .course-table th, .course-table td { padding: 1.25rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
        .course-table th { background: #f8fafc; font-weight: 700; color: #475569; }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-published { background: #dcfce7; color: #166534; }
        .status-draft { background: #fef3c7; color: #92400e; }

        .action-btns { display: flex; gap: 0.5rem; }
        .btn-icon { padding: 0.5rem; border-radius: 8px; border: 1px solid #e2e8f0; transition: all 0.2s; }
        .btn-icon:hover { background: #f1f5f9; border-color: var(--primary); }

        .recent-list { background: white; border-radius: 15px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .recent-item { display: flex; align-items: center; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid #f1f5f9; }
        .recent-item:last-child { border-bottom: none; }
        .student-avatar { width: 40px; height: 40px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #475569; }
    </style>
</head>
<body class="no-sidebar">

    <header>
        <a href="index.php" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
        </a>
        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="formation.php">Formations</a></li>
                <li><a href="instructor_dashboard.php" class="active"><b>Mon Tableau de Bord</b></a></li>
                <li id="logout"><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="instructor-header">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Bienvenue, <?php echo htmlspecialchars($first_name); ?>! 👨‍🏫</h1>
            <p style="opacity: 0.8; font-size: 1.1rem;">Gérez vos cours, suivez vos revenus et interagissez avec vos étudiants.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_courses; ?></div>
                <div class="stat-label">Cours Créés</div>
            </div>
            <div class="stat-card" style="border-color: #10b981;">
                <div class="stat-value"><?php echo $total_students; ?></div>
                <div class="stat-label">Étudiants Inscrits</div>
            </div>
            <div class="stat-card" style="border-color: #f59e0b;">
                <div class="stat-value"><?php echo number_format($total_revenue, 0, '.', ','); ?> DA</div>
                <div class="stat-label">Revenus Estimés</div>
            </div>
        </div>

        <div class="dashboard-container">
            <!-- Course Management -->
            <section style="margin-bottom: 4rem;">
                <div class="section-header">
                    <h2 class="section-title">Mes Formations</h2>
                    <a href="create_course.php" class="btn btn-primary">+ Ajouter un cours</a>
                </div>

                <?php if (empty($my_courses)): ?>
                    <div style="text-align: center; padding: 4rem; background: white; border-radius: 20px;">
                        <span style="font-size: 4rem;">📑</span>
                        <h3>Vous n'avez pas encore créé de formation.</h3>
                        <p style="color: #64748b; margin-bottom: 2rem;">Partagez votre expertise dès aujourd'hui !</p>
                        <a href="create_course.php" class="btn btn-primary">Créer ma première formation</a>
                    </div>
                <?php else: ?>
                    <table class="course-table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Prix</th>
                                <th>Étudiants</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_courses as $course): ?>
                                <?php 
                                    // Fetch student count for this specific course
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
                                    $stmt->execute([$course['id']]);
                                    $count = $stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo number_format($course['price'], 0, '.', ','); ?> DA</td>
                                    <td><?php echo $count; ?> inscrits</td>
                                    <td>
                                        <span class="status-badge <?php echo $course['is_published'] ? 'status-published' : 'status-draft'; ?>">
                                            <?php echo $course['is_published'] ? 'Publié' : 'Brouillon'; ?>
                                        </span>
                                    </td>
                                    <td class="action-btns">
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn-icon" title="Modifier">✏️</a>
                                        <a href="course_content.php?id=<?php echo $course['id']; ?>" class="btn-icon" title="Contenu">📚</a>
                                        <a href="delete_course.php?id=<?php echo $course['id']; ?>" class="btn-icon" title="Supprimer" onclick="return confirm('Supprimer ce cours ?');">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 3rem;">
                <!-- Recent Enrollments -->
                <section>
                    <div class="section-header">
                        <h2 class="section-title">Dernières Inscriptions</h2>
                    </div>
                    <?php if (empty($recent_enrollments)): ?>
                        <div class="recent-list" style="text-align:center; color: #64748b;">
                            Aucune inscription récente.
                        </div>
                    <?php else: ?>
                        <div class="recent-list">
                            <?php foreach ($recent_enrollments as $enroll): ?>
                                <div class="recent-item">
                                    <div class="student-avatar"><?php echo strtoupper($enroll['first_name'][0] . $enroll['last_name'][0]); ?></div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($enroll['first_name'] . ' ' . $enroll['last_name']); ?></div>
                                        <div style="font-size: 0.85rem; color: #64748b;">Inscrit à : <?php echo htmlspecialchars($enroll['title']); ?></div>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #94a3b8;"><?php echo date('d M', strtotime($enroll['enrolled_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Quick Actions -->
                <section>
                    <div class="section-header">
                        <h2 class="section-title">Ressources</h2>
                    </div>
                    <div class="recent-list">
                        <a href="#" style="display:block; padding: 1rem; border-radius: 10px; background: #f8fafc; margin-bottom:1rem; text-decoration:none; color: #1e293b; font-weight: 600;">
                            📂 Guide du Formateur
                        </a>
                        <a href="#" style="display:block; padding: 1rem; border-radius: 10px; background: #f8fafc; margin-bottom:1rem; text-decoration:none; color: #1e293b; font-weight: 600;">
                            🎥 Tutoriels Upload Vidéo
                        </a>
                        <a href="#" style="display:block; padding: 1rem; border-radius: 10px; background: #f8fafc; text-decoration:none; color: #1e293b; font-weight: 600;">
                            💬 Forum des Professeurs
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <footer style="margin-top: 4rem;">
        <p>&copy; 2025 Centre de Formation Professionnelle - Espace Instructeur</p>
    </footer>

</body>
</html>
