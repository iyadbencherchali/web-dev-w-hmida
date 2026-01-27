<?php
// config.php
require_once 'db_connect.php';

// Global Configuration
define('SITE_NAME', 'Centre de Formation Professionnelle');
define('CURRENCY', 'DA');

// Image Mapping (Centralized)
// This allows us to use the same images across all pages
$course_images = [
    'Formation Python Complète' => '4375050_logo_python_icon.png',
    'Certification Cisco CCNA 200-301' => '294687_cisco_icon.png',
    'Expert en Cybersécurité' => '12983448_virus_malware_trojan_cybersecurity_icon.png',
    'Développeur Web Fullstack' => '317756_badge_css_css3_achievement_award_icon.png',
    'Microsoft Azure Fundamentals' => '4202105_microsoft_logo_social_social media_icon.png',
    'Google Analytics & Marketing Digital' => '2993685_brand_brands_google_logo_logos_icon.png'
];

// Helper Function: Get Course Image
function get_course_image($title) {
    global $course_images;
    return $course_images[$title] ?? 'default_course.png';
}
?>
