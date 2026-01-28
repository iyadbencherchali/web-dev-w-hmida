-- ========================================
-- BASE DE DONNÉES COMPLÈTE - CENTRE DE FORMATION
-- ========================================
-- Version: 1.0
-- Date: 2026-01-28
-- Description: Script SQL complet incluant toutes les tables du système

DROP DATABASE IF EXISTS cours_db;
CREATE DATABASE cours_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cours_db;

-- ========================================
-- TABLE: users
-- Description: Table principale des utilisateurs (étudiants, formateurs, admins)
-- ========================================
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL DEFAULT 'student',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: instructors
-- Description: Informations supplémentaires pour les formateurs
-- ========================================
CREATE TABLE instructors (
    user_id BIGINT PRIMARY KEY,
    bio TEXT,
    expertise VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- TABLE: students
-- Description: Informations supplémentaires pour les étudiants
-- ========================================
CREATE TABLE students (
    user_id BIGINT PRIMARY KEY,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- TABLE: admins
-- Description: Informations supplémentaires pour les administrateurs
-- ========================================
CREATE TABLE admins (
    user_id BIGINT PRIMARY KEY,
    role_description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- TABLE: courses
-- Description: Catalogue des formations disponibles
-- ========================================
CREATE TABLE courses (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    instructor_id BIGINT NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'beginner',
    max_students INTEGER,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (instructor_id) REFERENCES instructors(user_id) ON DELETE RESTRICT,
    INDEX idx_courses_instructor (instructor_id),
    INDEX idx_courses_published (is_published),
    INDEX idx_courses_price (price)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: lessons
-- Description: Leçons/modules de chaque formation
-- ========================================
CREATE TABLE lessons (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT NOT NULL,
    title VARCHAR(500) NOT NULL,
    content TEXT NOT NULL,
    video_url VARCHAR(500),
    duration_minutes INTEGER,
    file_path VARCHAR(500) NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_lessons_course (course_id),
    INDEX idx_lessons_order (display_order)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: enrollments
-- Description: Inscriptions des étudiants aux formations
-- ========================================
CREATE TABLE enrollments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT NOT NULL,
    course_id BIGINT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    progress_percentage INTEGER NOT NULL DEFAULT 0,
    UNIQUE KEY uk_enrollment_student_course (student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT chk_progress CHECK (progress_percentage BETWEEN 0 AND 100),
    INDEX idx_enrollments_student (student_id),
    INDEX idx_enrollments_course (course_id)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: assignments
-- Description: Devoirs/exercices associés aux leçons
-- ========================================
CREATE TABLE assignments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    lesson_id BIGINT NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT NOT NULL,
    due_date TIMESTAMP NULL,
    max_points INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_assignments_lesson (lesson_id)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: submissions
-- Description: Soumissions des étudiants pour les devoirs
-- ========================================
CREATE TABLE submissions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    assignment_id BIGINT NOT NULL,
    student_id BIGINT NOT NULL,
    content TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    points_earned DECIMAL(5,2) NULL,
    feedback TEXT NULL,
    graded_by BIGINT,
    graded_at TIMESTAMP NULL,
    UNIQUE KEY uk_submission_assignment_student (assignment_id, student_id),
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(user_id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES instructors(user_id) ON DELETE SET NULL,
    INDEX idx_submissions_assignment (assignment_id),
    INDEX idx_submissions_student (student_id)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: comments
-- Description: Commentaires sur les cours et leçons
-- ========================================
CREATE TABLE comments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    course_id BIGINT,
    lesson_id BIGINT,
    parent_comment_id BIGINT,
    content TEXT NOT NULL,
    is_instructor_reply BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_comments_course (course_id),
    INDEX idx_comments_lesson (lesson_id),
    INDEX idx_comments_user (user_id)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: payments
-- Description: Historique des paiements
-- ========================================
CREATE TABLE payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_payments_user (user_id),
    INDEX idx_payments_date (created_at),
    INDEX idx_payments_status (status)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: events
-- Description: Événements et workshops
-- ========================================
CREATE TABLE events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    location VARCHAR(255),
    image_url VARCHAR(500),
    is_published BOOLEAN DEFAULT TRUE,
    max_participants INTEGER,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_events_date (event_date),
    INDEX idx_events_published (is_published)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: questions
-- Description: Questions de support des étudiants
-- ========================================
CREATE TABLE questions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    question TEXT NOT NULL,
    status ENUM('new', 'in_progress', 'answered') DEFAULT 'new',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    answered_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_questions_user (user_id),
    INDEX idx_questions_status (status)
) ENGINE=InnoDB;

-- ========================================
-- TABLE: reviews
-- Description: Avis et témoignages des étudiants
-- ========================================
CREATE TABLE reviews (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_reviews_user (user_id),
    INDEX idx_reviews_approved (is_approved),
    INDEX idx_reviews_rating (rating)
) ENGINE=InnoDB;

-- ========================================
-- DONNÉES DE DÉMONSTRATION
-- ========================================

-- Insertion d'événements de démonstration
INSERT INTO events (title, description, event_date, event_time, location, image_url, is_published) VALUES
('Workshop Intelligence Artificielle', 'Découvrez les fondamentaux du Machine Learning et des réseaux de neurones. Atelier pratique avec exercices en Python.', '2026-02-15', '14:00:00', 'USDB Pavillon 1, Blida', 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800', TRUE),
('Conférence Cybersécurité 2026', 'Les dernières tendances en sécurité informatique : protection des données, ethical hacking, et conformité RGPD.', '2026-03-10', '09:00:00', 'Centre de Formation, Alger', 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800', TRUE),
('Bootcamp Développement Web', 'Formation intensive de 5 jours : HTML, CSS, JavaScript, PHP, MySQL. Du débutant à la réalisation d''un projet complet.', '2026-02-28', '10:00:00', 'En ligne (Zoom)', 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800', TRUE);

-- ========================================
-- FIN DU SCRIPT
-- ========================================
