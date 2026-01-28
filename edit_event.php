<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get event ID
if (!isset($_GET['id'])) {
    header("Location: admin_events.php");
    exit();
}

$event_id = $_GET['id'];

// Fetch event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: admin_events.php");
    exit();
}

$error_msg = "";

// Handle form submission
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
            $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, image_url = ?, max_participants = ?, is_published = ? WHERE id = ?");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $image_url, $max_participants, $is_published, $event_id]);
            
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
    <title>Modifier l'Événement | Centre De Formation</title>
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
            cursor: pointer;
        }

        .btn-submit:hover {
            background: var(--secondary);
        }

        .btn-cancel {
            padding: 1rem 2rem;
            background: #f1f5f9;
            color: var(--secondary);
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="no-sidebar">

    <header>
        <a href="index.php" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
        </a>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard Admin</a></li>
                <li><a href="admin_events.php">📅 Événements</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <div class="form-header">
                <h1>Modifier l'Événement</h1>
                <p>Mettez à jour les informations de l'événement</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-error">
                    ❌ <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Titre de l'événement <span class="required">*</span></label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($event['title']); ?>">
                </div>

                <div class="form-group">
                    <label>Description <span class="required">*</span></label>
                    <textarea name="description" required><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="event_date" required value="<?php echo $event['event_date']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Heure</label>
                        <input type="time" name="event_time" value="<?php echo $event['event_time']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Lieu <span class="required">*</span></label>
                    <input type="text" name="location" required value="<?php echo htmlspecialchars($event['location']); ?>">
                </div>

                <div class="form-group">
                    <label>URL de l'image de couverture</label>
                    <input type="text" name="image_url" value="<?php echo htmlspecialchars($event['image_url']); ?>">
                </div>

                <div class="form-group">
                    <label>Nombre maximum de participants</label>
                    <input type="number" name="max_participants" value="<?php echo $event['max_participants']; ?>">
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="is_published" id="is_published" <?php echo $event['is_published'] ? 'checked' : ''; ?>>
                    <label for="is_published">Publier sur le site</label>
                </div>

                <div class="form-actions">
                    <a href="admin_events.php" class="btn-cancel">Annuler</a>
                    <button type="submit" class="btn-submit">💾 Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </main>

</body>
</html>
