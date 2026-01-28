<?php
session_start();
require_once 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$course_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify enrollment
try {
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    $enrollment = $stmt->fetch();
    
    if (!$enrollment && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'instructor') {
        die("Accès refusé. Vous devez être inscrit à ce cours.");
    }

    // Fetch Course Details
    $stmt = $pdo->prepare("SELECT c.*, u.first_name as instructor_fname, u.last_name as instructor_lname FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    // Fetch Lessons
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY display_order ASC");
    $stmt->execute([$course_id]);
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Current Lesson
    $lesson_id = $_GET['lesson'] ?? ($lessons[0]['id'] ?? null);
    $current_lesson = null;
    if ($lesson_id) {
        foreach ($lessons as $l) {
            if ($l['id'] == $lesson_id) {
                $current_lesson = $l;
                break;
            }
        }
    }

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> | Apprentissage</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --sidebar-width: 350px;
        }
        body { background: #f8fafc; display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* Sidebar */
        .course-sidebar {
            width: var(--sidebar-width);
            background: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }
        .sidebar-header { padding: 2rem; border-bottom: 1px solid #e2e8f0; }
        .lesson-list { flex: 1; overflow-y: auto; padding: 1rem 0; }
        .lesson-item { 
            display: flex; 
            padding: 1rem 2rem; 
            text-decoration: none; 
            color: #475569; 
            transition: all 0.2s;
            border-left: 4px solid transparent;
            align-items: center;
            gap: 1rem;
        }
        .lesson-item:hover { background: #f1f5f9; color: var(--primary); }
        .lesson-item.active { background: #eff6ff; color: var(--primary); border-left-color: var(--primary); font-weight: 600; }
        
        /* Main Content */
        .course-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 4rem;
            max-width: 1000px;
        }

        .video-container {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        
        .lesson-content {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            line-height: 1.8;
            color: #334155;
        }
        
        .lesson-content h1 { margin-bottom: 1.5rem; color: #1e293b; }

        @media (max-width: 1024px) {
            .course-sidebar { width: 100%; height: auto; position: static; }
            body { flex-direction: column; }
            .course-main { margin-left: 0; padding: 2rem; }
        }
    </style>
</head>
<body>

    <aside class="course-sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" style="text-decoration: none; color: #64748b; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                ⬅️ Retour au tableau de bord
            </a>
            <h2 style="font-size: 1.1rem; line-height: 1.4;"><?php echo htmlspecialchars($course['title']); ?></h2>
            <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.5rem;">Instructeur : <?php echo htmlspecialchars($course['instructor_fname']); ?></p>
        </div>
        
        <div class="lesson-list">
            <?php foreach ($lessons as $index => $lesson): ?>
                <a href="view_course.php?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id']; ?>" 
                   class="lesson-item <?php echo ($lesson['id'] == $lesson_id) ? 'active' : ''; ?>">
                    <span style="opacity: 0.5; font-size: 0.8rem;">#<?php echo $index + 1; ?></span>
                    <?php echo htmlspecialchars($lesson['title']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <main class="course-main">
        <?php if ($current_lesson): ?>
            <?php if (!empty($current_lesson['video_url'])): ?>
                <div class="video-container">
                    <?php 
                        // Basic YouTube embed conversion
                        $url = $current_lesson['video_url'];
                        if (strpos($url, 'youtube.com/watch?v=') !== false) {
                            $video_id = explode('v=', $url)[1];
                            if (strpos($video_id, '&') !== false) $video_id = explode('&', $video_id)[0];
                            $embed_url = "https://www.youtube.com/embed/" . $video_id;
                        } elseif (strpos($url, 'youtu.be/') !== false) {
                            $video_id = explode('youtu.be/', $url)[1];
                            $embed_url = "https://www.youtube.com/embed/" . $video_id;
                        } else {
                            $embed_url = $url;
                        }
                    ?>
                    <iframe width="100%" height="100%" src="<?php echo $embed_url; ?>" frameborder="0" allowfullscreen></iframe>
                </div>
            <?php endif; ?>

            <div class="lesson-content">
                <h1><?php echo htmlspecialchars($current_lesson['title']); ?></h1>
                <div class="content-text">
                    <?php echo nl2br(htmlspecialchars($current_lesson['content'])); ?>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem;">
                <h2>Bienvenue dans votre formation !</h2>
                <p style="color: #64748b; margin-top: 1rem;">Sélectionnez une leçon dans le menu à gauche pour commencer votre apprentissage.</p>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>
