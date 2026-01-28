<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width , initial-scale=1.0">
    <title>Accueil | Centre De Formation</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <a href="index.php" class="logo-link" style="margin-left: 20px;">
            <img src="logo/Desktop - 3.png" alt="Centre de Formation" style="height: 70px;">
        </a>

        <nav>
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="formation.php">Formations</a></li>
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
        <h2>Accès rapide</h2>
        <ol>
            <li><a href="#hero">Accueil</a></li>
            <li><a href="#categories">Catégories</a></li>
            <li><a href="#featured-courses">Formations</a></li>
            <li><a href="#why-us">Pourquoi Nous</a></li>
            <li><a href="#testimonials">Témoignages</a></li>
            <li><a href="#instructor">Enseigner</a></li>
        </ol>
    </aside>


    <!-- MAIN CONTENT -->
    <main>
        <!-- 1. HERO SECTION -->
        <section id="hero" class="hero-section">
            <div class="hero-content">
                <h1>Learn New Skills Anytime, Anywhere</h1>
                <p class="hero-subtitle">Courses from top instructors. Flexible, affordable, and job-ready.</p>

                <div class="hero-search">
                    <input type="text" placeholder="Search for a course, skill, or instructor...">
                    <button class="btn-search">Search</button>
                </div>

                <div class="hero-ctas">
                    <a href="formation.php" class="btn btn-primary">Browse Courses</a>
                    <a href="signup.php" class="btn btn-secondary">Sign Up Free</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="Design sans titre.png" alt="Students learning online">
            </div>
        </section>

        <!-- 6. PARTNER LOGOS (TRUSTED BY) -->
        <section id="trusted-by" class="section-padding">
            <h2 class="section-title-center">Trusted by top companies</h2>
            <div class="marquee-container">
                <ul class="marquee-content">
                    <li><img src="4202105_microsoft_logo_social_social media_icon.png" width="30"> Microsoft</li>
                    <li><img src="2993685_brand_brands_google_logo_logos_icon.png" width="30"> Google</li>
                    <li><b>Bencherchali Corp</b></li>
                    <li><b>Hmida Corp</b></li>
                    <li><img src="4375050_logo_python_icon.png" width="30"> Python Foundation</li>
                    <li><img src="294687_cisco_icon.png" width="30"> Cisco</li>
                    <!-- Duplicates -->
                    <li><img src="4202105_microsoft_logo_social_social media_icon.png" width="30"> Microsoft</li>
                    <li><img src="2993685_brand_brands_google_logo_logos_icon.png" width="30"> Google</li>
                    <li><b>Bencherchali Corp</b></li>
                    <li>Udemy</li>
                    <li><img src="4375050_logo_python_icon.png" width="30"> Python Foundation</li>
                    <li><img src="294687_cisco_icon.png" width="30"> Cisco</li>
                </ul>
            </div>
        </section>

        <!-- 2. COURSE CATEGORIES -->
        <section id="categories" class="section-padding">
            <h2 class="section-title">Explore Categories</h2>
            <div class="categories-grid">
                <div class="category-card">
                    <span class="cat-icon">💻</span>
                    <h3>Programming</h3>
                </div>
                <div class="category-card">
                    <span class="cat-icon">📊</span>
                    <h3>Business</h3>
                </div>
                <div class="category-card">
                    <span class="cat-icon">🤖</span>
                    <h3>AI & Data</h3>
                </div>
                <div class="category-card">
                    <span class="cat-icon">🎨</span>
                    <h3>Design</h3>
                </div>
                <div class="category-card">
                    <span class="cat-icon">📢</span>
                    <h3>Marketing</h3>
                </div>
                <div class="category-card">
                    <span class="cat-icon">🗣️</span>
                    <h3>Languages</h3>
                </div>
                <div class="category-card">
                    <span class="cat-icon">🌱</span>
                    <h3>Personal Dev</h3>
                </div>
                <div class="category-card">
                    <span class="cat-icon">➕</span>
                    <h3>More...</h3>
                </div>
            </div>
        </section>

        <!-- 3. FEATURED COURSES -->
        <section id="featured-courses" class="section-padding bg-light">
            <h2 class="section-title">Featured Courses</h2>
            <div class="courses-grid">
                <!-- Course 1 -->
                <article class="course-card">
                    <div class="course-thumb">
                        <img src="4375050_logo_python_icon.png" alt="Python">
                        <span class="badge">Beginner</span>
                    </div>
                    <div class="course-info">
                        <h3>Complete Python Bootcamp</h3>
                        <p class="instructor">M. Chettat</p>
                        <div class="rating">⭐⭐⭐⭐⭐ (4.8)</div>
                        <div class="course-meta">
                            <span>👥 1,200 students</span>
                            <span class="price">30,000 DA</span>
                        </div>
                    </div>
                </article>
                <!-- Course 2 -->
                <article class="course-card">
                    <div class="course-thumb">
                        <img src="294687_cisco_icon.png" alt="Cisco">
                        <span class="badge">Intermediate</span>
                    </div>
                    <div class="course-info">
                        <h3>Cisco CCNA 200-301</h3>
                        <p class="instructor">M. Fouaad Hmida</p>
                        <div class="rating">⭐⭐⭐⭐⭐ (4.9)</div>
                        <div class="course-meta">
                            <span>👥 850 students</span>
                            <span class="price">60,000 DA</span>
                        </div>
                    </div>
                </article>
                <!-- Course 3 -->
                <article class="course-card">
                    <div class="course-thumb">
                        <img src="12983448_virus_malware_trojan_cybersecurity_icon.png" alt="Security">
                        <span class="badge">Advanced</span>
                    </div>
                    <div class="course-info">
                        <h3>Cybersecurity Expert</h3>
                        <p class="instructor">Dr. Security</p>
                        <div class="rating">⭐⭐⭐⭐☆ (4.7)</div>
                        <div class="course-meta">
                            <span>👥 500 students</span>
                            <span class="price">45,000 DA</span>
                        </div>
                    </div>
                </article>
                <!-- Course 4 -->
                <article class="course-card">
                    <div class="course-thumb">
                        <img src="317756_badge_css_css3_achievement_award_icon.png" alt="Web Dev">
                        <span class="badge">Beginner</span>
                    </div>
                    <div class="course-info">
                        <h3>Fullstack Web Developer</h3>
                        <p class="instructor">Sarah Dev</p>
                        <div class="rating">⭐⭐⭐⭐⭐ (4.8)</div>
                        <div class="course-meta">
                            <span>👥 2,000 students</span>
                            <span class="price">40,000 DA</span>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <!-- 4. WHY CHOOSE US -->
        <section id="why-us" class="section-padding">
            <h2 class="section-title-center">Why Choose Us</h2>
            <div class="features-grid">
                <div class="feature-block">
                    <div class="feature-icon">📚</div>
                    <h3>Learn from Experts</h3>
                    <p>Top instructors from around the world.</p>
                </div>
                <div class="feature-block">
                    <div class="feature-icon">💻</div>
                    <h3>100% Online</h3>
                    <p>Learn at your own pace, anywhere.</p>
                </div>
                <div class="feature-block">
                    <div class="feature-icon">📄</div>
                    <h3>Get Certified</h3>
                    <p>Earn certificates to boost your career.</p>
                </div>
                <div class="feature-block">
                    <div class="feature-icon">🔍</div>
                    <h3>Personalized</h3>
                    <p>Recommendations just for you.</p>
                </div>
            </div>
        </section>

        <!-- 7. HOW IT WORKS -->
        <section id="how-it-works" class="section-padding bg-light">
            <h2 class="section-title-center">How It Works</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Browse Courses</h3>
                    <p>Explore our wide range of topics.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Learn</h3>
                    <p>Watch videos and complete assignments.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Earn Certificate</h3>
                    <p>Showcase your new skills.</p>
                </div>
            </div>
        </section>

        <!-- 5. TESTIMONIALS WITH CAROUSEL -->
        <section id="testimonials" class="section-padding">
            <h2 class="section-title-center">What Students Say</h2>

            <div class="testimonials-carousel">
                <button class="carousel-btn prev-btn" onclick="changeSlide(-1)">‹</button>

                <div class="testimonials-wrapper">
                    <div class="testimonials-slide active">
                        <div class="testimonials-grid">
                            <div class="testimonial-card">
                                <p>"Udemy en 2ème position. This platform changed my career!"</p>
                                <div class="user-info">
                                    <strong>Iyad Bencherchali</strong>
                                    <span>⭐⭐⭐⭐⭐</span>
                                </div>
                            </div>
                            <div class="testimonial-card">
                                <p>"Te9ra lyoum tefhem lbareh. Excellent instructors."</p>
                                <div class="user-info">
                                    <strong>Fouaad Hmida</strong>
                                    <span>⭐⭐⭐⭐⭐</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="testimonials-slide">
                        <div class="testimonials-grid">
                            <div class="testimonial-card">
                                <p>"The courses are well-structured and easy to follow. I learned so much!"</p>
                                <div class="user-info">
                                    <strong>Sarah Martinez</strong>
                                    <span>⭐⭐⭐⭐⭐</span>
                                </div>
                            </div>
                            <div class="testimonial-card">
                                <p>"Best investment in my education. The instructors are top-notch professionals."</p>
                                <div class="user-info">
                                    <strong>Ahmed El Mansouri</strong>
                                    <span>⭐⭐⭐⭐⭐</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="carousel-btn next-btn" onclick="changeSlide(1)">›</button>
            </div>

            <div class="carousel-dots">
                <span class="dot active" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
            </div>
        </section>

        <script>
            let currentSlideIndex = 1;
            showSlide(currentSlideIndex);

            function changeSlide(n) {
                showSlide(currentSlideIndex += n);
            }

            function currentSlide(n) {
                showSlide(currentSlideIndex = n);
            }

            function showSlide(n) {
                let slides = document.getElementsByClassName("testimonials-slide");
                let dots = document.getElementsByClassName("dot");

                if (n > slides.length) { currentSlideIndex = 1 }
                if (n < 1) { currentSlideIndex = slides.length }

                for (let i = 0; i < slides.length; i++) {
                    slides[i].classList.remove("active");
                }
                for (let i = 0; i < dots.length; i++) {
                    dots[i].classList.remove("active");
                }

                slides[currentSlideIndex - 1].classList.add("active");
                dots[currentSlideIndex - 1].classList.add("active");
            }
        </script>

        <!-- 8. INSTRUCTOR SECTION -->
        <section id="instructor" class="section-padding bg-dark text-white">
            <div class="instructor-content">
                <h2>Become an Instructor</h2>
                <p>Teach what you love and inspire students around the world.</p>
                <a href="#" class="btn btn-primary">Start Teaching</a>
            </div>
        </section>

        <!-- 10. NEWSLETTER -->
        <section id="newsletter" class="section-padding">
            <div class="newsletter-box">
                <h2>Subscribe to our newsletter</h2>
                <p>Get free courses, updates, and discounts.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Enter your email">
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
        </section>
    </main>


    <!-- FOOTER -->
    <footer>
        <p><strong>Contact :</strong> 0667 81 23 51 | contact@formationpro.dz</p>
        <p>Adresse : USDB Pavillon 1, Blida</p>
        <p>&copy; 2025 Centre de Formation Professionnelle</p>
        <img src="images.jpg" alt="Image du panier" width="100">
    </footer>

</body>

</html>
