<?php
session_start();
require_once 'config.php';

// Check if logged in as instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $level = $_POST['difficulty_level'];
    $max_students = $_POST['max_students'] ?: 20; // Default 20
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    if (empty($title) || empty($description) || !isset($price)) {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO courses (instructor_id, title, description, price, difficulty_level, max_students, is_published) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$instructor_id, $title, $description, $price, $level, $max_students, $is_published]);

            $success_msg = "Formation créée avec succès !";
            header("Location: instructor_dashboard.php?msg=course_created");
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
    <title>Créer une Formation | Espace Instructeur</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container { max-width: 800px; margin: 4rem auto; background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 2rem; }
        .form-group label { display: block; margin-bottom: 0.75rem; font-weight: 700; color: #1e293b; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 1rem; }
        .form-group textarea { height: 150px; resize: vertical; }
        .checkbox-group { display: flex; align-items: center; gap: 0.75rem; }
        .checkbox-group input { width: auto; }
        .btn-submit { width: 100%; padding: 1.25rem; font-size: 1.1rem; font-weight: 700; }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; }
    </style>
</head>
<body class="no-sidebar">

    <header>
        <a href="index.php" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
        </a>
        <nav>
            <ul>
                <li><a href="instructor_dashboard.php">Retour au Dashboard</a></li>
                <li id="logout"><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <h1 style="margin-bottom: 2rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem;">Nouvelle Formation</h1>

            <?php if ($error_msg): ?>
                <div class="error-msg"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="title">Titre de la formation *</label>
                    <input type="text" id="title" name="title" placeholder="Ex: Masterclass Communication Digitale" required>
                </div>

                <div class="form-group">
                    <label for="description">Description complète *</label>
                    <textarea id="description" name="description" placeholder="Détaillez le programme, les prérequis et les objectifs..." required></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div class="form-group">
                        <label for="price">Prix (en DA) *</label>
                        <input type="number" id="price" name="price" placeholder="5000" required>
                    </div>
                    <div class="form-group">
                        <label for="max_students">Nombre de places max</label>
                        <input type="number" id="max_students" name="max_students" placeholder="20" value="20">
                    </div>
                </div>

                <div class="form-group">
                    <label for="difficulty_level">Niveau de difficulté</label>
                    <select id="difficulty_level" name="difficulty_level">
                        <option value="beginner">Débutant</option>
                        <option value="intermediate">Intermédiaire</option>
                        <option value="advanced">Avancé</option>
                    </select>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_published" name="is_published" checked>
                    <label for="is_published" style="margin-bottom: 0;">Publier immédiatement (visible par les étudiants)</label>
                </div>

                <button type="submit" class="btn btn-primary btn-submit">Enregistrer la formation</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Centre de Formation Professionnelle</p>
    </footer>

</body>
</html>
