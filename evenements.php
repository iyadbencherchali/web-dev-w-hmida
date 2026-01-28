<?php 
session_start(); 
require_once 'config.php';

$success_msg = "";
$error_msg = "";

// Handle event proposal submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_event'])) {
    if (!isset($_SESSION['user_id'])) {
        $error_msg = "Vous devez être connecté pour proposer un événement.";
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $event_date = trim($_POST['event_date']);
        $event_time = trim($_POST['event_time']) ?: NULL;
        $location = trim($_POST['location']);
        $image_url = trim($_POST['image_url']) ?: NULL;
        $max_participants = !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : NULL;
        
        if (empty($title) || empty($description) || empty($event_date) || empty($location)) {
            $error_msg = "Veuillez remplir tous les champs obligatoires.";
        } else {
            try {
                // Insert with is_published = 0 for moderation
                $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, image_url, max_participants, is_published, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
                $stmt->execute([$title, $description, $event_date, $event_time, $location, $image_url, $max_participants, $_SESSION['user_id']]);
                
                $success_msg = "Votre événement a été soumis avec succès ! Il sera publié après validation par l'équipe.";
            } catch (PDOException $e) {
                $error_msg = "Erreur lors de la soumission : " . $e->getMessage();
            }
        }
    }
}

// Fetch published events
$stmt = $pdo->query("
    SELECT * FROM events 
    WHERE is_published = 1 
    ORDER BY event_date ASC
");
$events = $stmt->fetchAll();

// Separate upcoming and past events
$upcoming_events = array_filter($events, fn($e) => $e['event_date'] >= date('Y-m-d'));
$past_events = array_filter($events, fn($e) => $e['event_date'] < date('Y-m-d'));

// Fetch published courses for sidebar
$stmt_courses = $pdo->query("
    SELECT c.*, u.first_name, u.last_name 
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id 
    WHERE c.is_published = 1 
    ORDER BY c.created_at DESC 
    LIMIT 5
");
$courses = $stmt_courses->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- HEADER -->
    <header>
        <a href="index.php" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
        </a>

        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="formation.php">Formations</a></li>
                <li><a href="evenements.php" class="active">Évènements</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li><a href="panier.php">Panier</a></li>
                <li><a href="paiement.php">Paiement</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php $dash = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : (($_SESSION['role'] == 'instructor') ? 'instructor_dashboard.php' : 'dashboard.php'); ?>
                    <li><a href="<?php echo $dash; ?>">Mon Espace</a></li>
                    <li><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
                <?php else: ?>
                    <li><a href="login.php">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- SIDEBAR TOGGLE -->
    <input type="checkbox" id="menu-toggle">
    <label for="menu-toggle" class="menu-btn">☰ Menu</label>

    <!-- ASIDE (SIDEBAR) -->
    <aside>
        <h2>📅 Navigation</h2>
        <ol>
            <li><a href="#all-events">Tous les événements</a></li>
            <li><a href="#upcoming">À venir</a></li>
            <li><a href="#past">Événements passés</a></li>
            <li><a href="#add-event">Proposer un événement</a></li>
        </ol>
    </aside>

    <!-- MAIN CONTENT -->
    <main>
        <!-- HERO SECTION -->
        <section class="search-section">
            <div class="search-container">
                <h1>Événements & Workshops</h1>
                <p class="search-subtitle">Participez à nos conférences, ateliers et formations spéciales</p>
            </div>
        </section>

        <!-- EVENTS SECTION -->
        <section class="section-padding" id="all-events">
            <div class="container">
                <h2 class="section-title" id="upcoming">📅 Événements à Venir</h2>

                <?php if (empty($upcoming_events)): ?>
                    <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: var(--radius); border: 1px solid var(--border);">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                        <h3 style="margin-bottom: 1rem; color: var(--secondary);">Aucun événement prévu pour le moment</h3>
                        <p style="color: var(--text-light);">Revenez bientôt pour découvrir nos prochains workshops !</p>
                    </div>
                <?php else: ?>
                    <div class="formations-grid">
                        <?php foreach($upcoming_events as $event): ?>
                            <article class="formation">
                                <?php if (!empty($event['image_url'])): ?>
                                    <div class="formation-thumb">
                                        <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                        <span class="badge" style="background: var(--success);">À VENIR</span>
                                    </div>
                                <?php else: ?>
                                    <div class="formation-thumb">
                                        <div style="width: 100%; height: 200px; background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                            📅
                                        </div>
                                        <span class="badge" style="background: var(--success);">À VENIR</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="formation-content">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    
                                    <div class="formation-meta">
                                        <span class="meta-item">📅 <?php echo date('d F Y', strtotime($event['event_date'])); ?></span>
                                        <?php if ($event['event_time']): ?>
                                            <span class="meta-item">🕐 <?php echo date('H:i', strtotime($event['event_time'])); ?></span>
                                        <?php endif; ?>
                                        <span class="meta-item">📍 <?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                    
                                    <p><?php echo htmlspecialchars(substr($event['description'], 0, 120)); ?>...</p>
                                    
                                    <?php if ($event['max_participants']): ?>
                                        <div style="margin-top: 1rem; color: var(--text-light); font-size: 0.9rem;">
                                            <strong>👥 Places limitées :</strong> <?php echo $event['max_participants']; ?> participants max
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($past_events)): ?>
                    <h2 class="section-title" id="past" style="margin-top: 4rem; opacity: 0.7;">🕒 Événements Passés</h2>
                    <div class="formations-grid" style="opacity: 0.6;">
                        <?php foreach($past_events as $event): ?>
                            <article class="formation">
                                <?php if (!empty($event['image_url'])): ?>
                                    <div class="formation-thumb">
                                        <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                        <span class="badge" style="background: var(--text-light);">TERMINÉ</span>
                                    </div>
                                <?php else: ?>
                                    <div class="formation-thumb">
                                        <div style="width: 100%; height: 200px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 3rem;">
                                            📅
                                        </div>
                                        <span class="badge" style="background: var(--text-light);">TERMINÉ</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="formation-content">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    
                                    <div class="formation-meta">
                                        <span class="meta-item">📅 <?php echo date('d F Y', strtotime($event['event_date'])); ?></span>
                                        <span class="meta-item">📍 <?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                    
                                    <p><?php echo htmlspecialchars(substr($event['description'], 0, 120)); ?>...</p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- PROPOSE EVENT SECTION -->
        <section class="section-padding" id="add-event" style="background: #f8fafc;">
            <div class="container" style="max-width: 800px;">
                <h2 class="section-title">📝 Proposer un Événement</h2>
                
                <?php if (!empty($success_msg)): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #bbf7d0;">
                        ✅ <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca;">
                        ❌ <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div style="background: white; padding: 2.5rem; border-radius: var(--radius); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div style="text-align: center; padding: 2rem;">
                            <p style="margin-bottom: 1.5rem; color: var(--text-light);">Vous devez être connecté pour proposer un événement.</p>
                            <a href="login.php" class="btn btn-primary">Se connecter</a>
                        </div>
                    <?php else: ?>
                        <p style="margin-bottom: 2rem; color: var(--text-light);">
                            Vous souhaitez organiser un workshop, une conférence ou un atelier ? Proposez-le ici ! 
                            Votre événement sera examiné par notre équipe avant publication.
                        </p>

                        <form method="POST" action="">
                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Titre de l'événement <span style="color: red;">*</span></label>
                                <input type="text" name="title" required maxlength="255" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem;">
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Description <span style="color: red;">*</span></label>
                                <textarea name="description" required rows="5" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem; resize: vertical;"></textarea>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date <span style="color: red;">*</span></label>
                                    <input type="date" name="event_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Heure (optionnel)</label>
                                    <input type="time" name="event_time" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem;">
                                </div>
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Lieu <span style="color: red;">*</span></label>
                                <input type="text" name="location" required maxlength="255" placeholder="Ex: USDB Pavillon 1, Blida" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem;">
                            </div>

                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">URL de l'image (optionnel)</label>
                                <input type="url" name="image_url" maxlength="500" placeholder="https://exemple.com/image.jpg" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem;">
                            </div>

                            <div style="margin-bottom: 2rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Nombre maximum de participants (optionnel)</label>
                                <input type="number" name="max_participants" min="1" placeholder="Ex: 50" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem;">
                            </div>

                            <button type="submit" name="submit_event" class="btn btn-primary" style="width: 100%;">
                                Soumettre l'événement
                            </button>
                        </form>
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
