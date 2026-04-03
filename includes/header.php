<?php
/**
 * UsafiKonect - Common Header
 * Included at the top of every page
 * 
 * Variables to set before including:
 * $page_title - Page title (default: 'UsafiKonect')
 * $page_description - Meta description
 * $page_keywords - Meta keywords
 * $body_class - Additional body classes
 * $og_image - Open Graph image URL
 * $hide_navbar - Set true to hide navbar
 */

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/functions.php';
}

$page_title = isset($page_title) ? $page_title . ' | ' . APP_NAME : APP_NAME . ' - Your Trusted Laundry Partner in Nairobi';
$page_description = $page_description ?? 'UsafiKonect connects you with trusted laundry service providers in Nairobi, Kenya. Book wash & fold, dry cleaning, ironing, and more. Pay via M-Pesa with doorstep pickup & delivery.';
$page_keywords = $page_keywords ?? 'laundry service Nairobi, laundry app Kenya, wash clothes Nairobi, M-Pesa laundry, UsafiKonect';
$body_class = $body_class ?? '';
$og_image = $og_image ?? APP_URL . '/assets/images/og-image.jpg';
$canonical_url = APP_URL . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta Tags -->
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($page_description) ?>">
    <meta name="keywords" content="<?= e($page_keywords) ?>">
    <meta name="author" content="UsafiKonect">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e($canonical_url) ?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($page_title) ?>">
    <meta property="og:description" content="<?= e($page_description) ?>">
    <meta property="og:image" content="<?= e($og_image) ?>">
    <meta property="og:url" content="<?= e($canonical_url) ?>">
    <meta property="og:site_name" content="UsafiKonect">
    <meta property="og:locale" content="en_KE">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($page_title) ?>">
    <meta name="twitter:description" content="<?= e($page_description) ?>">
    <meta name="twitter:image" content="<?= e($og_image) ?>">
    
    <!-- CSRF Meta Tag -->
    <meta name="csrf-token" content="<?= generate_csrf_token() ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/favicon-180.png">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        orange: {
                            50: '#FFF7ED', 100: '#FFEDD5', 200: '#FED7AA', 300: '#FDBA74',
                            400: '#FB923C', 500: '#F97316', 600: '#EA580C', 700: '#C2410C',
                            800: '#9A3412', 900: '#7C2D12',
                        },
                        deepblue: {
                            50: '#EFF6FF', 100: '#DBEAFE', 200: '#BFDBFE', 300: '#93C5FD',
                            400: '#60A5FA', 500: '#3B82F6', 600: '#2563EB', 700: '#1D4ED8',
                            800: '#1E3A8A', 900: '#1E3A5C',
                        },
                        teal: {
                            50: '#F0FDFA', 100: '#CCFBF1', 200: '#99F6E4', 300: '#5EEAD4',
                            400: '#2DD4BF', 500: '#14B8A6', 600: '#0D9488', 700: '#0F766E',
                            800: '#115E59', 900: '#134E4A',
                        },
                        cream: '#FEF3C7',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- GSAP + ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.4/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.4/ScrollTrigger.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "UsafiKonect",
        "description": "Nairobi's trusted laundry service platform connecting customers with professional laundry providers.",
        "url": "<?= APP_URL ?>",
        "telephone": "+254700123456",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Nairobi",
            "addressRegion": "Nairobi County",
            "addressCountry": "KE"
        },
        "areaServed": {
            "@type": "City",
            "name": "Nairobi"
        },
        "priceRange": "KES 100 - KES 500",
        "serviceType": "Laundry Service"
    }
    </script>
</head>
<body class="font-sans antialiased bg-cream text-gray-800 dark:bg-gray-900 dark:text-gray-100 <?= e($body_class) ?>">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:bg-orange-500 focus:text-white focus:p-3 focus:z-50">Skip to main content</a>
