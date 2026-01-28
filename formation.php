<?php
session_start();
require_once 'config.php';

// Fetch courses from database
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.first_name, u.last_name 
        FROM courses c 
        JOIN instructors i ON c.instructor_id = i.user_id 
        JOIN users u ON i.user_id = u.id 
        WHERE c.is_published = 1
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching courses: " . $e->getMessage());
}

// Image Mapping (Temporary until column added to DB)
$course_images = [
    'Formation Python Complète' => '4375050_logo_python_icon.png',
    'Certification Cisco CCNA 200-301' => '294687_cisco_icon.png',
    'Expert en Cybersécurité' => '12983448_virus_malware_trojan_cybersecurity_icon.png',
    'Développeur Web Fullstack' => '317756_badge_css_css3_achievement_award_icon.png',
    'Microsoft Azure Fundamentals' => '4202105_microsoft_logo_social_social media_icon.png',
    'Google Analytics & Marketing Digital' => '2993685_brand_brands_google_logo_logos_icon.png'
];

?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formations | Centre De Formation</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Styles for promotional pricing */
    .price-wrapper {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 0.25rem;
    }

    .original-price-crossed {
      font-size: 0.9rem;
      color: #94a3b8;
      text-decoration: line-through;
      font-weight: 500;
    }

    .discounted-price {
      font-size: 1.2rem;
      color: #10b981;
      font-weight: 800;
    }

    .discount-badge {
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      color: white;
      padding: 0.2rem 0.6rem;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 700;
      display: inline-block;
      box-shadow: 0 2px 4px rgba(251, 191, 36, 0.3);
    }

    .price-container {
      display: flex;
      align-items: center;
    }
  </style>
</head>

<body>

  <!-- HEADER -->
  <header>
    <a href="formation.php" class="logo-link" style="margin-left: 20px;">
      <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
    </a>

    <nav>
      <ul>
        <li><a href="index.php">Accueil</a></li>
        <li><a href="formation.php" class="active">Formations</a></li>
        <li><a href="evenements.php">Évènements</a></li>
        <li><a href="blog.php">Blog</a></li>
        <li><a href="panier.php">Panier</a></li>
        <li><a href="paiement.php">Paiement</a></li>
        <?php if (isset($_SESSION['user_id'])): 
            $dash = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : (($_SESSION['role'] == 'instructor') ? 'instructor_dashboard.php' : 'dashboard.php');
        ?>
            <li><a href="<?php echo $dash; ?>">Mon Espace</a></li>
            <li><a href="logout.php" style="color: var(--danger)">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="login.php">Connexion</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- SIDEBAR TOGGLE (CSS ONLY) -->
  <input type="checkbox" id="menu-toggle">

  <label for="menu-toggle" class="menu-btn">☰ Menu</label>

  <!-- ASIDE (SIDEBAR) -->
  <aside>
    <h2>Catégories</h2>
    <ol>
      <li><a href="#all">Toutes les formations</a></li>
      <li><a href="#programming"> Programmation</a></li>
      <li><a href="#business"> Business</a></li>
      <li><a href="#data"> IA & Data</a></li>
      <li><a href="#design"> Design</a></li>
      <li><a href="#marketing"> Marketing</a></li>
      <li><a href="#languages"> Langues</a></li>
    </ol>
  </aside>

  <!-- MAIN CONTENT -->
  <main>
    <!-- SEARCH BAR SECTION -->
    <section class="search-section">
      <div class="search-container">
        <h1>Trouvez la formation parfaite</h1>
        <p class="search-subtitle">Explorez nos formations certifiantes avec des experts reconnus</p>
        <div class="course-search-bar">
          <input type="text" placeholder="Rechercher une formation, compétence ou instructeur...">
          <button class="btn-search">🔍 Rechercher</button>
        </div>
      </div>
    </section>

    <!-- FORMATIONS LAYOUT -->
    <div class="formations-layout">
      <!-- FILTERS SIDEBAR -->
      <section id="filtres">
        <h3>🎯 Filtrer les résultats</h3>

        <form>
          <div class="filter-group">
            <label for="partenaire">Partenaire</label>
            <select id="partenaire">
              <option>Tous</option>
              <option>Microsoft</option>
              <option>Google</option>
              <option>Cisco</option>
              <option>Bencherchali Corp</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="niveau">Niveau</label>
            <select id="niveau">
              <option>Tous les niveaux</option>
              <option>Débutant</option>
              <option>Intermédiaire</option>
              <option>Avancé</option>
            </select>
          </div>

          <div class="filter-group">
            <label for="budget-min">Budget minimum</label>
            <input type="number" id="budget-min" placeholder="0 DA">
          </div>

          <div class="filter-group">
            <label for="budget-max">Budget maximum</label>
            <input type="number" id="budget-max" placeholder="100,000 DA">
          </div>

          <div class="filter-group">
            <label for="evaluation">Évaluation minimum</label>
            <select id="evaluation">
              <option>Toutes</option>
              <option>⭐⭐⭐⭐⭐ 5 étoiles</option>
              <option>⭐⭐⭐⭐ 4+ étoiles</option>
              <option>⭐⭐⭐ 3+ étoiles</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary filter-btn">Appliquer les filtres</button>
          <button type="reset" class="btn btn-secondary filter-btn">Réinitialiser</button>
        </form>
      </section>

      <!-- COURSES GRID -->
      <section id="formations">
        <div class="formations-header">
          <h2>Toutes les formations</h2>
          <p class="results-count">Affichage de <?php echo count($courses); ?> formations</p>
        </div>

        <div class="formations-grid">
          <?php if (empty($courses)): ?>
            <p>Aucune formation trouvée.</p>
          <?php else: ?>
            <?php foreach ($courses as $course): ?>
                <?php 
                    // Determine badge class
                    $badgeClass = 'badge-beginner';
                    if ($course['difficulty_level'] == 'intermediate') $badgeClass = 'badge-intermediate';
                    if ($course['difficulty_level'] == 'advanced') $badgeClass = 'badge-advanced';
                    
                    // Format output
                    $levelLabel = ucfirst($course['difficulty_level']);
                    if($levelLabel == 'Beginner') $levelLabel = 'Débutant';
                    if($levelLabel == 'Intermediate') $levelLabel = 'Intermédiaire';
                    if($levelLabel == 'Advanced') $levelLabel = 'Avancé';

                    $image = $course_images[$course['title']] ?? 'default_course.png';
                ?>
              <article class="formation">
                <div class="formation-thumb">
                  <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                  <span class="badge <?php echo $badgeClass; ?>"><?php echo $levelLabel; ?></span>
                </div>
                <div class="formation-content">
                  <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                  <p class="formation-instructor">👨‍🏫 <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></p>
                  <p class="formation-description"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>

                  <div class="formation-meta">
                    <span class="rating">⭐ 4.8 (1,200 étudiants)</span> <!-- Static for now per schema limitations -->
                    <span class="duration">📅 6 mois</span> <!-- Static for now -->
                  </div>


                  <div class="formation-details">
                    <?php if (($course['max_students'] ?? 20) <= 0): ?>
                        <span class="places" style="color: var(--danger); font-weight: 700;">🚫 Complet (0 places)</span>
                    <?php else: ?>
                        <span class="places">📍 <?php echo $course['max_students'] ?? 20; ?> places disponibles</span>
                    <?php endif; ?>
                    <?php 
                      $is_on_promo = isset($course['is_on_promotion']) && $course['is_on_promotion'] == 1;
                      $original_price = $course['price'];
                      $discounted_price = $course['discounted_price'] ?? $original_price;
                      $discount_percentage = $course['discount_percentage'] ?? 0;
                    ?>
                    <span class="price-container">
                      <?php if ($is_on_promo && $discount_percentage > 0): ?>
                        <span class="price-wrapper">
                          <span class="original-price-crossed"><?php echo number_format($original_price, 0, '.', ','); ?> DA</span>
                          <span class="discounted-price"><?php echo number_format($discounted_price, 0, '.', ','); ?> DA</span>
                          <span class="discount-badge">-<?php echo number_format($discount_percentage, 0); ?>%</span>
                        </span>
                      <?php else: ?>
                        <span class="price"><?php echo number_format($original_price, 0, '.', ','); ?> DA</span>
                      <?php endif; ?>
                    </span>
                  </div>

                  <div class="formation-buttons">
                    <?php if (($course['max_students'] ?? 20) <= 0): ?>
                        <button class="btn btn-secondary" style="width:100%; cursor: not-allowed; opacity: 0.7;" disabled>Victime de son succès</button>
                    <?php else: ?>
                        <form action="enroll.php" method="POST" style="flex:1;">
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                            <input type="hidden" name="action" value="enroll">
                            <button type="submit" class="btn btn-primary" style="width:100%;">Inscription immédiate</button>
                        </form>
                        <form action="cart.php" method="POST" style="flex:1;">
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                            <input type="hidden" name="action" value="add">
                            <button class="btn btn-secondary" style="width:100%;">🛒 Ajouter au panier</button>
                        </form>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Load More Button -->
        <div class="load-more-container">
          <button class="btn btn-secondary load-more-btn">Charger plus de formations</button>
        </div>
      </section>
    </div>
  </main>

  <!-- FOOTER -->
  <footer>
    <p><strong>Contact :</strong> 0667 81 23 51 | contact@formationpro.dz</p>
    <p>Adresse : USDB Pavillon 1, Blida</p>
    <p>&copy; 2025 Centre de Formation Professionnelle</p>
    <img src="images.jpg" alt="Image du panier" width="100">
  </footer>

  <!-- JAVASCRIPT FOR FILTERS -->
  <script>
    // Get all filter elements
    const niveauFilter = document.getElementById('niveau');
    const budgetMinFilter = document.getElementById('budget-min');
    const budgetMaxFilter = document.getElementById('budget-max');
    const searchInput = document.querySelector('.course-search-bar input');
    const searchButton = document.querySelector('.course-search-bar .btn-search');
    const filterForm = document.querySelector('#filtres form');
    const resetButton = document.querySelector('#filtres form button[type="reset"]');
    const resultsCount = document.querySelector('.results-count');

    // Get all course cards
    const allCourses = document.querySelectorAll('.formation');

    // Filter function
    function filterCourses() {
      const selectedNiveau = niveauFilter.value.toLowerCase();
      const minBudget = parseInt(budgetMinFilter.value) || 0;
      const maxBudget = parseInt(budgetMaxFilter.value) || Infinity;
      const searchTerm = searchInput.value.toLowerCase();

      let visibleCount = 0;

      allCourses.forEach(course => {
        // Get course data
        const badge = course.querySelector('.badge');
        const niveau = badge ? badge.textContent.toLowerCase() : '';
        const priceText = course.querySelector('.price').textContent;
        const price = parseInt(priceText.replace(/[^0-9]/g, ''));
        const title = course.querySelector('h3').textContent.toLowerCase();
        const instructor = course.querySelector('.formation-instructor').textContent.toLowerCase();
        const description = course.querySelector('.formation-description').textContent.toLowerCase();

        // Check niveau filter
        let niveauMatch = true;
        if (selectedNiveau !== 'tous les niveaux' && selectedNiveau !== '') {
          niveauMatch = niveau.includes(selectedNiveau) || 
                       (selectedNiveau === 'débutant' && niveau.includes('beginner')) ||
                       (selectedNiveau === 'intermédiaire' && niveau.includes('intermediate')) ||
                       (selectedNiveau === 'avancé' && niveau.includes('advanced'));
        }

        // Check price filter
        const priceMatch = price >= minBudget && price <= maxBudget;

        // Check search filter
        const searchMatch = searchTerm === '' || 
                          title.includes(searchTerm) || 
                          instructor.includes(searchTerm) || 
                          description.includes(searchTerm);

        // Show or hide course
        if (niveauMatch && priceMatch && searchMatch) {
          course.style.display = 'block';
          visibleCount++;
        } else {
          course.style.display = 'none';
        }
      });

      // Update results count
      resultsCount.textContent = `Affichage de ${visibleCount} formation${visibleCount > 1 ? 's' : ''}`;
    }

    // Add event listeners
    niveauFilter.addEventListener('change', filterCourses);
    budgetMinFilter.addEventListener('input', filterCourses);
    budgetMaxFilter.addEventListener('input', filterCourses);
    searchInput.addEventListener('input', filterCourses);

    // Search button
    searchButton.addEventListener('click', (e) => {
      e.preventDefault();
      filterCourses();
    });

    // Filter form submit
    filterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      filterCourses();
    });

    // Reset button
    resetButton.addEventListener('click', () => {
      setTimeout(() => {
        searchInput.value = '';
        filterCourses();
      }, 10);
    });

    // Initialize - show all courses
    filterCourses();
  </script>

</body>
</html>
