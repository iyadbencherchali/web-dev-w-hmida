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

$lesson_id = $_GET['id'];
$instructor_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

try {
    // Fetch lesson and verify ownership through course table
    $stmt = $pdo->prepare("
        SELECT l.*, c.instructor_id 
        FROM lessons l 
        JOIN courses c ON l.course_id = c.id 
        WHERE l.id = ?
    ");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch();

    if (!$lesson || $lesson['instructor_id'] != $instructor_id) {
        die("Accès refusé.");
    }

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_lesson'])) {
    $title = trim($_POST['lesson_title']);
    $content = trim($_POST['lesson_content']);
    $video_url = trim($_POST['video_url']);
    
    if (empty($title) || empty($content)) {
        $error_msg = "Le titre et le contenu sont obligatoires.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE lessons SET title = ?, content = ?, video_url = ? WHERE id = ?");
            $stmt->execute([$title, $content, $video_url, $lesson_id]);
            header("Location: course_content.php?id=" . $lesson['course_id'] . "&msg=lesson_updated");
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
    <title>Modifier la leçon | Espace Instructeur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="no-sidebar">

    <header>
        <a href="index.php" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
        </a>
        <nav>
            <ul>
                <li><a href="course_content.php?id=<?php echo $lesson['course_id']; ?>">Annuler</a></li>
            </ul>
        </nav>
    </header>

    <main style="max-width: 800px; margin: 4rem auto; padding: 0 2rem;">
        <div style="background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
            <h1 style="margin-bottom: 2rem;">Modifier la leçon</h1>
            
            <?php if ($error_msg): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 10px; margin-bottom: 2rem;"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>Titre de la leçon</label>
                    <input type="text" name="lesson_title" value="<?php echo htmlspecialchars($lesson['title']); ?>" required style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>Contenu textuel / Description</label>
                    <textarea name="lesson_content" required style="width:100%; height:250px; padding:0.8rem; border:1px solid #e2e8f0; border-radius:8px;"><?php echo htmlspecialchars($lesson['content']); ?></textarea>
                </div>
                <div class="form-group" style="margin-bottom:2rem;">
                    <label>URL Vidéo (Youtube / Vimeo)</label>
                    <input type="url" name="video_url" value="<?php echo htmlspecialchars($lesson['video_url']); ?>" style="width:100%; padding:0.8rem; border:1px solid #e2e8f0; border-radius:8px;">
                </div>
                <button type="submit" name="update_lesson" class="btn btn-primary" style="width:100%; padding:1rem;">Enregistrer les modifications</button>
            </form>
        </div>
    </main>

</body>
</html>
