<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$first_name = $_SESSION['first_name'];

// Handle role update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
        
        // Ensure they exist in instructor/student tables
        if ($new_role == 'instructor') {
            $pdo->exec("INSERT IGNORE INTO instructors (user_id) VALUES ($user_id)");
        } elseif ($new_role == 'student') {
            $pdo->exec("INSERT IGNORE INTO students (user_id) VALUES ($user_id)");
        }
        
        header("Location: admin_users.php?msg=role_updated");
        exit();
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
}

// Handle Account Status (Ban/Activate)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $uid = $_GET['id'];
    $current_status = $_GET['current'];
    $new_status = ($current_status == 1) ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$new_status, $uid]);
    header("Location: admin_users.php?msg=status_updated");
    exit();
}

// Fetch Users (excluding self)
$my_id = $_SESSION['user_id'];
$users = $pdo->query("SELECT * FROM users WHERE id != $my_id ORDER BY created_at DESC")->fetchAll();

// Stats
$total_users = count($users);
$students = count(array_filter($users, fn($u) => $u['role'] == 'student'));
$instructors = count(array_filter($users, fn($u) => $u['role'] == 'instructor'));
$active_users = count(array_filter($users, fn($u) => $u['is_active'] == 1));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs | Centre De Formation</title>
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
        .users-stats-row {
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

        /* --- USERS CARD --- */
        .users-card {
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

        /* --- USERS TABLE --- */
        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .users-table thead th {
            text-align: left;
            padding: 0.75rem 1rem;
            color: var(--text-light);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .users-table tbody tr {
            background: #f8fafc;
            transition: all 0.3s ease;
        }

        .users-table tbody tr:hover {
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: scale(1.01);
        }

        .users-table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .users-table tbody td:first-child { border-radius: 10px 0 0 10px; }
        .users-table tbody td:last-child { border-radius: 0 10px 10px 0; }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .role-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .role-form select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.85rem;
            font-weight: 600;
            outline: none;
        }

        .role-form button {
            padding: 6px 12px;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .role-form button:hover {
            background: var(--primary);
            transform: scale(1.05);
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-active {
            color: #15803d;
        }

        .status-active .status-dot {
            background: #22c55e;
        }

        .status-inactive {
            color: #b91c1c;
        }

        .status-inactive .status-dot {
            background: #ef4444;
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

        .btn-ban {
            background: #fef2f2;
            color: #ef4444;
        }

        .btn-ban:hover {
            background: #fee2e2;
            transform: scale(1.05);
        }

        .btn-activate {
            background: #dcfce7;
            color: #15803d;
        }

        .btn-activate:hover {
            background: #bbf7d0;
            transform: scale(1.05);
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
            .users-stats-row { grid-template-columns: 1fr; }
            .role-form { flex-direction: column; }
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
                <li><a href="admin_users.php" class="active"><b>👥 Utilisateurs</b></a></li>
                <li><a href="admin_courses.php">📚 Modération</a></li>
                <li><a href="admin_events.php">📅 Événements</a></li>
                <li><a href="admin_reviews.php">⭐ Avis</a></li>
                <li><a href="admin_sales.php">💳 Finances</a></li>
                <li><a href="admin_questions.php">💬 Support</a></li>
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
                    Gestion des Utilisateurs 👥
                </h1>
                <p style="color:#e2e8f0; font-size:1.2rem; margin-bottom:3rem; font-weight:300;">
                    Contrôlez les accès et les rôles de votre communauté.
                </p>

                <div class="stats-container" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                    <!-- Stat Card 1 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: var(--primary);"><?php echo $total_users; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Total Utilisateurs</div>
                    </div>
                
                    <!-- Stat Card 2 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #10b981;"><?php echo $students; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Étudiants</div>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="glass-panel" style="padding: 2rem; min-width: 220px; text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #f59e0b;"><?php echo $instructors; ?></div>
                        <div style="color: #cbd5e1; font-weight: 600;">Formateurs</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- MAIN CONTENT -->
        <section class="section-padding">
            <div class="container" style="max-width: 1400px; margin: 0 auto;">
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'role_updated'): ?>
                    <div class="success-alert">
                        ✅ Rôle mis à jour avec succès !
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'status_updated'): ?>
                    <div class="success-alert">
                        ✅ Statut du compte modifié !
                    </div>
                <?php endif; ?>

                <!-- DETAILED STATS -->
                <div class="users-stats-row">
                    <div class="stat-box">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number"><?php echo $total_users; ?></div>
                        <div class="stat-label">Comptes enregistrés</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">👨‍🎓</div>
                        <div class="stat-number"><?php echo $students; ?></div>
                        <div class="stat-label">Étudiants inscrits</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">👨‍🏫</div>
                        <div class="stat-number"><?php echo $instructors; ?></div>
                        <div class="stat-label">Formateurs actifs</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number"><?php echo $active_users; ?></div>
                        <div class="stat-label">Comptes actifs</div>
                    </div>
                </div>

                <!-- USERS TABLE -->
                <div class="users-card">
                    <div class="card-header">
                        <h3>Liste des Utilisateurs</h3>
                        <div style="font-size: 0.85rem; color: var(--text-light); font-weight: 600;">
                            <?php echo count($users); ?> utilisateurs
                        </div>
                    </div>

                    <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <div style="font-size: 4rem;">👥</div>
                            <p>Aucun utilisateur dans le système.</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>Identité</th>
                                        <th>Email</th>
                                        <th>Rôle</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar"><?php echo strtoupper($user['first_name'][0].$user['last_name'][0]); ?></div>
                                                <div>
                                                    <div style="font-weight: 700; color: var(--secondary);"><?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></div>
                                                    <div style="font-size: 0.75rem; color: var(--text-light);">ID: #<?php echo $user['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="color: var(--text-light);"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <form action="" method="POST" class="role-form">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="new_role">
                                                    <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Étudiant</option>
                                                    <option value="instructor" <?php echo $user['role'] == 'instructor' ? 'selected' : ''; ?>>Formateur</option>
                                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                                <button type="submit" name="update_role">OK</button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <div class="status-dot"></div>
                                                <?php echo $user['is_active'] ? 'Actif' : 'Bloqué'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="?toggle_status=1&id=<?php echo $user['id']; ?>&current=<?php echo $user['is_active']; ?>" 
                                               class="btn-action <?php echo $user['is_active'] ? 'btn-ban' : 'btn-activate'; ?>">
                                                <?php echo $user['is_active'] ? '🚫 Suspendre' : '✅ Activer'; ?>
                                            </a>
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
