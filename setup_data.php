<?php
require_once 'db_connect.php';

echo "<h1>Seeding Database...</h1>";

// 1. Create Instructors (Users + Instructors table)
$instructors = [
    [
        'first_name' => 'Mohamed',
        'last_name' => 'Chettat',
        'email' => 'chettat@formationpro.dz',
        'bio' => 'Expert Python Developer with 10 years experience',
        'expertise' => 'Python, Data Science'
    ],
    [
        'first_name' => 'Fouaad',
        'last_name' => 'Hmida',
        'email' => 'hmida@formationpro.dz',
        'bio' => 'Cisco Certified Instructor (CCIE)',
        'expertise' => 'Networking, Cisco'
    ],
    [
        'first_name' => 'Dr.',
        'last_name' => 'Security',
        'email' => 'security@formationpro.dz',
        'bio' => 'Cybersecurity Analyst and Researcher',
        'expertise' => 'Cybersecurity, Ethical Hacking'
    ],
    [
        'first_name' => 'Sarah',
        'last_name' => 'Dev',
        'email' => 'sarah@formationpro.dz',
        'bio' => 'Fullstack Web Developer',
        'expertise' => 'HTML, CSS, JS, React'
    ],
    [
        'first_name' => 'M.',
        'last_name' => 'Cloud Expert',
        'email' => 'cloud@formationpro.dz',
        'bio' => 'Microsoft Azure MVP',
        'expertise' => 'Cloud Computing, Azure'
    ],
    [
        'first_name' => 'Mme.',
        'last_name' => 'Marketing Pro',
        'email' => 'marketing@formationpro.dz',
        'bio' => 'Digital Marketing Specialist',
        'expertise' => 'SEO, Google Analytics'
    ]
];

$instructor_ids = [];

foreach ($instructors as $inst) {
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$inst['email']]);
        $user = $stmt->fetch();

        if ($user) {
            $user_id = $user['id'];
            echo "User {$inst['email']} exists (ID: $user_id)<br>";
        } else {
            // Create user
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$inst['first_name'], $inst['last_name'], $inst['email'], $password]);
            $user_id = $pdo->lastInsertId();
            echo "Created user {$inst['email']} (ID: $user_id)<br>";
        }

        // Check if instructor entry exists
        $stmt = $pdo->prepare("SELECT user_id FROM instructors WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO instructors (user_id, bio, expertise) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $inst['bio'], $inst['expertise']]);
            echo "Added instructor profile for ID $user_id<br>";
        }

        $instructor_ids[$inst['last_name']] = $user_id;

    } catch (PDOException $e) {
        echo "Error creating instructor {$inst['email']}: " . $e->getMessage() . "<br>";
    }
}

// 2. Insert Courses
$courses = [
    [
        'title' => 'Formation Python Complète',
        'instructor_key' => 'Chettat',
        'description' => 'Apprenez les bases du langage Python et développez vos premières applications.',
        'difficulty_level' => 'beginner',
        'price' => 5000.00,
        'image' => '4375050_logo_python_icon.png',
        'duration' => '1 semestre',
        'rating' => 4.8
    ],
    [
        'title' => 'Certification Cisco CCNA 200-301',
        'instructor_key' => 'Hmida',
        'description' => 'Préparez-vous à la certification CCNA et maîtrisez les réseaux Cisco.',
        'difficulty_level' => 'intermediate',
        'price' => 60000.00,
        'image' => '294687_cisco_icon.png',
        'duration' => '2 ans',
        'rating' => 4.9
    ],
    [
        'title' => 'Expert en Cybersécurité',
        'instructor_key' => 'Security',
        'description' => 'Devenez un expert en sécurité informatique et protection des systèmes.',
        'difficulty_level' => 'advanced',
        'price' => 45000.00,
        'image' => '12983448_virus_malware_trojan_cybersecurity_icon.png',
        'duration' => '1 an',
        'rating' => 4.7
    ],
    [
        'title' => 'Développeur Web Fullstack',
        'instructor_key' => 'Dev',
        'description' => 'Maîtrisez HTML, CSS, JavaScript et créez des applications web modernes.',
        'difficulty_level' => 'beginner',
        'price' => 40000.00,
        'image' => '317756_badge_css_css3_achievement_award_icon.png',
        'duration' => '6 mois',
        'rating' => 4.8
    ],
    [
        'title' => 'Microsoft Azure Fundamentals',
        'instructor_key' => 'Cloud Expert',
        'description' => 'Découvrez le cloud computing avec Microsoft Azure et obtenez votre certification.',
        'difficulty_level' => 'intermediate',
        'price' => 35000.00,
        'image' => '4202105_microsoft_logo_social_social media_icon.png',
        'duration' => '4 mois',
        'rating' => 4.6
    ],
    [
        'title' => 'Google Analytics & Marketing Digital',
        'instructor_key' => 'Marketing Pro',
        'description' => 'Analysez vos données web et optimisez vos campagnes marketing digitales.',
        'difficulty_level' => 'beginner',
        'price' => 25000.00,
        'image' => '2993685_brand_brands_google_logo_logos_icon.png',
        'duration' => '3 mois',
        'rating' => 4.7
    ]
];

echo "<h3>Inserting Courses...</h3>";

foreach ($courses as $c) {
    try {
        $inst_id = $instructor_ids[$c['instructor_key']];
        
        // Check if course exists
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE title = ?");
        $stmt->execute([$c['title']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO courses (instructor_id, title, description, difficulty_level, price, is_published) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$inst_id, $c['title'], $c['description'], $c['difficulty_level'], $c['price']]);
            echo "Inserted course: {$c['title']}<br>";
        } else {
            echo "Course already exists: {$c['title']}<br>";
        }
    } catch (PDOException $e) {
        echo "Error inserting {$c['title']}: " . $e->getMessage() . "<br>";
    }
}

echo "<h2>Database Seeding Completed!</h2>";
?>
