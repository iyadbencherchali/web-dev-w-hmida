<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = trim($_POST['event_date']);
    $event_time = trim($_POST['event_time']) ?: NULL;
    $location = trim($_POST['location']);
    $image_url = trim($_POST['image_url']);
    $max_participants = !empty($_POST['max_participants']) ? intval($_POST['max_participants']) : NULL;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($title) || empty($description) || empty($event_date) || empty($location)) {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, image_url, max_participants, is_published, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $image_url, $max_participants, $is_published, $_SESSION['user_id']]);
            
            header("Location: admin_events.php?msg=success");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Événement | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body.no-sidebar { margin: 0; background: #f8fafc; }
        
        .form-container {
            max-width: 900px;
            margin: 3rem auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 3rem;
        }

        .form-header {
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .form-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            color: var(--secondary);
        }

        .form-header p {
            margin: 0;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .required {
            color: #ef4444;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f1f5f9;
        }

        .btn-submit {
            flex: 1;
            padding: 1rem 2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-cancel {
            padding: 1rem 2rem;
            background: #f1f5f9;
            color: var(--secondary);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .help-text {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
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
                <li><a href="admin_events.php">📅 Événements</a></li>
                <li id="logout"><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <div class="form-header">
                <h1>Créer un Nouvel Événement</h1>
                <p>Ajoutez un événement, workshop ou conférence à votre calendrier</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Titre de l'événement <span class="required">*</span></label>
                    <input type="text" name="title" required placeholder="Ex: Workshop Intelligence Artificielle">
                </div>

                <div class="form-group">
                    <label>Description <span class="required">*</span></label>
                    <textarea name="description" required placeholder="Décrivez votre événement en détail..."></textarea>
                    <div class="help-text">Expliquez le contenu, les objectifs et ce que les participants apprendront</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="event_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label>Heure (optionnel)</label>
                        <input type="time" name="event_time">
                    </div>
                </div>

                <div class="form-group">
                    <label>Lieu <span class="required">*</span></label>
                    <input type="text" name="location" required placeholder="Ex: USDB Pavillon 1, Blida ou En ligne (Zoom)">
                </div>

                <div class="form-group">
                    <label>URL de l'image de couverture</label>
                    <input type="text" name="image_url" placeholder="https://exemple.com/image.jpg">
                    <div class="help-text">Lien vers une image (JPG, PNG) - Recommandé: 1200x600px</div>
                </div>

                <div class="form-group">
                    <label>Nombre maximum de participants (optionnel)</label>
                    <input type="number" name="max_participants" min="1" placeholder="Ex: 50">
                    <div class="help-text">Laissez vide si pas de limite</div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="is_published" id="is_published" checked>
                    <label for="is_published">Publier immédiatement sur le site</label>
                </div>

                <div class="form-actions">
                    <a href="admin_events.php" class="btn-cancel">Annuler</a>
                    <button type="submit" class="btn-submit">✅ Créer l'événement</button>
                </div>
            </form>
        </div>
    </main>

    <footer style="margin-top: 3rem;">
        <p><strong>Contact :</strong> 0667 81 23 51 | contact@formationpro.dz</p>
        <p>&copy; 2025 Centre de Formation Professionnelle</p>
    </footer>

</body>
</html>
