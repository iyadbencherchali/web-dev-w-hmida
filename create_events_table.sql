-- Script SQL pour créer la table events
-- À exécuter dans phpMyAdmin ou via un script PHP

CREATE TABLE IF NOT EXISTS events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME,
    location VARCHAR(255),
    image_url VARCHAR(500),
    is_published BOOLEAN DEFAULT 1,
    max_participants INTEGER,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques événements de démonstration
INSERT INTO events (title, description, event_date, event_time, location, image_url, is_published, created_by) VALUES
('Workshop Intelligence Artificielle', 'Découvrez les fondamentaux du Machine Learning et des réseaux de neurones. Atelier pratique avec exercices en Python.', '2026-02-15', '14:00:00', 'USDB Pavillon 1, Blida', 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800', 1, NULL),
('Conférence Cybersécurité 2026', 'Les dernières tendances en sécurité informatique : protection des données, ethical hacking, et conformité RGPD.', '2026-03-10', '09:00:00', 'Centre de Formation, Alger', 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800', 1, NULL),
('Bootcamp Développement Web', 'Formation intensive de 5 jours : HTML, CSS, JavaScript, PHP, MySQL. Du débutant à la réalisation d''un projet complet.', '2026-02-28', '10:00:00', 'En ligne (Zoom)', 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800', 1, NULL);
