<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

if (!isset($_GET['id'])) {
    header("Location: instructor_dashboard.php");
    exit();
}

$course_id = $_GET['id'];

// Check ownership and fetch data
try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$course_id, $instructor_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        die("Formation non trouvée ou accès refusé.");
    }
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $level = $_POST['difficulty_level'];
    $max_students = $_POST['max_students'];
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    if (empty($title) || empty($description) || !isset($price)) {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE courses 
                SET title = ?, description = ?, price = ?, difficulty_level = ?, max_students = ?, is_published = ? 
                WHERE id = ? AND instructor_id = ?
            ");
            $stmt->execute([$title, $description, $price, $level, $max_students, $is_published, $course_id, $instructor_id]);

            header("Location: instructor_dashboard.php?msg=course_updated");
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
    <title>Modifier la Formation | Espace Instructeur</title>
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
            <h1 style="margin-bottom: 2rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem;">Modifier la Formation</h1>

            <?php if ($error_msg): ?>
                <div class="error-msg"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="title">Titre de la formation *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description complète *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div class="form-group">
                        <label for="price">Prix (en DA) *</label>
                        <input type="number" id="price" name="price" value="<?php echo $course['price']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="max_students">Nombre de places max</label>
                        <input type="number" id="max_students" name="max_students" value="<?php echo $course['max_students']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="difficulty_level">Niveau de difficulté</label>
                    <select id="difficulty_level" name="difficulty_level">
                        <option value="beginner" <?php echo $course['difficulty_level'] == 'beginner' ? 'selected' : ''; ?>>Débutant</option>
                        <option value="intermediate" <?php echo $course['difficulty_level'] == 'intermediate' ? 'selected' : ''; ?>>Intermédiaire</option>
                        <option value="advanced" <?php echo $course['difficulty_level'] == 'advanced' ? 'selected' : ''; ?>>Avancé</option>
                    </select>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_published" name="is_published" <?php echo $course['is_published'] ? 'checked' : ''; ?>>
                    <label for="is_published" style="margin-bottom: 0;">Publier immédiatement (visible par les étudiants)</label>
                </div>

                <button type="submit" class="btn btn-primary btn-submit">Mettre à jour la formation</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Centre de Formation Professionnelle</p>
    </footer>

</body>
</html>
