<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: instructor_dashboard.php");
    exit();
}

$course_id = $_GET['id'];
$instructor_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// Verify ownership
try {
    $stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$course_id, $instructor_id]);
    $course = $stmt->fetch();
    if (!$course) die("Accès refusé.");

    // Fetch existing lessons
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY display_order ASC");
    $stmt->execute([$course_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Handle Add Lesson
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_lesson'])) {
    $title = trim($_POST['lesson_title']);
    $content = trim($_POST['lesson_content']);
    $video_url = trim($_POST['video_url']);
    
    if (empty($title) || empty($content)) {
        $error_msg = "Le titre et le contenu sont obligatoires.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO lessons (course_id, title, content, video_url, file_path) VALUES (?, ?, ?, ?, '')");
            $stmt->execute([$course_id, $title, $content, $video_url]);
            header("Location: course_content.php?id=$course_id&msg=lesson_added");
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
    <title>Contenu du cours | Espace Instructeur</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .content-container { max-width: 1000px; margin: 4rem auto; padding: 0 2rem; }
        .lesson-card { background: white; padding: 1.5rem; border-radius: 15px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .lesson-info h3 { margin-bottom: 0.25rem; font-size: 1.1rem; }
        .add-lesson-box { background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); margin-top: 3rem; }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.85rem; }
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

    <main class="content-container">
        <h1 style="margin-bottom: 1rem;">Gestion du contenu : <?php echo htmlspecialchars($course['title']); ?></h1>
        <p style="color: #64748b; margin-bottom: 3rem;">Ajoutez, modifiez ou réorganisez les leçons de votre formation.</p>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'lesson_added'): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 10px; margin-bottom: 2rem;">Leçon ajoutée avec succès !</div>
        <?php endif; ?>

        <div class="lessons-list">
            <?php if (empty($lessons)): ?>
                <div style="text-align: center; padding: 3rem; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 20px;">
                    Aucune leçon pour le moment. Utilisez le formulaire ci-dessous pour commencer.
                </div>
            <?php else: ?>
                <?php foreach ($lessons as $index => $lesson): ?>
                    <div class="lesson-card">
                        <div class="lesson-info">
                            <span style="font-weight: 700; color: var(--primary); margin-right: 1rem;">#<?php echo $index + 1; ?></span>
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($lesson['title']); ?></span>
                        </div>
                        <div class="action-btns">
                            <a href="edit_lesson.php?id=<?php echo $lesson['id']; ?>" class="btn-icon">✏️</a>
                            <a href="delete_lesson.php?id=<?php echo $lesson['id']; ?>" class="btn-icon" onclick="return confirm('Supprimer cette leçon ?');">🗑️</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="add-lesson-box">
            <h2 style="margin-bottom: 1.5rem;">Ajouter une nouvelle leçon</h2>
            <form action="" method="POST">
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>Titre de la leçon</label>
                    <input type="text" name="lesson_title" placeholder="Ex: Introduction aux variables" required style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>Contenu textuel / Description</label>
                    <textarea name="lesson_content" placeholder="Le texte de votre leçon ici..." required style="width:100%; height:150px; padding:0.8rem; border:1px solid #e2e8f0; border-radius:8px;"></textarea>
                </div>
                <div class="form-group" style="margin-bottom:2rem;">
                    <label>URL Vidéo (Youtube / Vimeo)</label>
                    <input type="url" name="video_url" placeholder="https://..." style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <button type="submit" name="add_lesson" class="btn btn-primary" style="width:100%; padding:1rem;">Publier la leçon</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Centre de Formation Professionnelle</p>
    </footer>

</body>
</html>
