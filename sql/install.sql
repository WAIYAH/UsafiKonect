-- =====================================================
-- UsafiKonect Database Schema & Seed Data
-- Version: 2.0.0
-- Database: usafikonect
-- Charset: utf8mb4
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+03:00"; -- East Africa Time

-- Create database
CREATE DATABASE IF NOT EXISTS `usafikonect` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `usafikonect`;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(15) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('customer','provider','admin') NOT NULL DEFAULT 'customer',
  `provider_type` ENUM('individual','shop') DEFAULT NULL,
  `estate` VARCHAR(100) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT 'avatar.png',
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verified_at` DATETIME DEFAULT NULL,
  `remember_token` VARCHAR(255) DEFAULT NULL,
  `reset_token` VARCHAR(255) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_estate` (`estate`),
  INDEX `idx_users_active` (`is_active`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: provider_details
-- =====================================================
CREATE TABLE IF NOT EXISTS `provider_details` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `business_name` VARCHAR(150) NOT NULL,
  `business_type` ENUM('individual','shop') NOT NULL DEFAULT 'individual',
  `price_per_kg` DECIMAL(10,2) NOT NULL DEFAULT 150.00,
  `description` TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `approved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_provider_approved` (`is_approved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: bookings
-- =====================================================
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_number` VARCHAR(20) NOT NULL UNIQUE,
  `customer_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NOT NULL,
  `service_type` ENUM('wash_fold','wash_iron','dry_clean','iron_only') NOT NULL DEFAULT 'wash_fold',
  `weight_kg` DECIMAL(5,1) NOT NULL DEFAULT 1.0,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `pickup_date` DATE NOT NULL,
  `pickup_time` TIME NOT NULL,
  `delivery_date` DATE DEFAULT NULL,
  `pickup_address` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','confirmed','processing','ready','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('mpesa','cash','wallet') NOT NULL DEFAULT 'mpesa',
  `payment_status` ENUM('pending','unpaid','paid','refunded','failed') NOT NULL DEFAULT 'pending',
  `mpesa_receipt` VARCHAR(50) DEFAULT NULL,
  `mpesa_checkout_id` VARCHAR(100) DEFAULT NULL,
  `special_instructions` TEXT DEFAULT NULL,
  `is_loyalty_redeem` TINYINT(1) NOT NULL DEFAULT 0,
  `cancelled_by` ENUM('customer','provider','admin') DEFAULT NULL,
  `cancelled_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`provider_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_bookings_customer` (`customer_id`),
  INDEX `idx_bookings_provider` (`provider_id`),
  INDEX `idx_bookings_status` (`status`),
  INDEX `idx_bookings_payment` (`payment_status`),
  INDEX `idx_bookings_date` (`pickup_date`),
  INDEX `idx_bookings_number` (`booking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: ratings
-- =====================================================
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `provider_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `review` TEXT DEFAULT NULL,
  `provider_reply` TEXT DEFAULT NULL,
  `provider_reply_at` DATETIME DEFAULT NULL,
  `is_verified_purchase` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`provider_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_ratings_provider` (`provider_id`),
  INDEX `idx_ratings_customer` (`customer_id`),
  INDEX `idx_ratings_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: subscriptions
-- =====================================================
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `plan_type` ENUM('weekly','monthly','yearly') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `payment_status` ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `mpesa_transaction_id` VARCHAR(50) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_sub_user` (`user_id`),
  INDEX `idx_sub_status` (`status`),
  INDEX `idx_sub_end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: wallet_transactions
-- =====================================================
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('top_up','payment','refund','withdrawal') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `balance_after` DECIMAL(10,2) NOT NULL,
  `reference` VARCHAR(50) DEFAULT NULL,
  `mpesa_transaction_id` VARCHAR(50) DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_wallet_user` (`user_id`),
  INDEX `idx_wallet_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: loyalty_points
-- =====================================================
CREATE TABLE IF NOT EXISTS `loyalty_points` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `points` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_bookings` INT UNSIGNED NOT NULL DEFAULT 0,
  `free_bookings_earned` INT UNSIGNED NOT NULL DEFAULT 0,
  `free_bookings_used` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_loyalty_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('booking','payment','rating','security','system') NOT NULL DEFAULT 'system',
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_read` (`is_read`),
  INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: user_sessions
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `session_token` VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_session_token` (`session_token`),
  INDEX `idx_session_user` (`user_id`),
  INDEX `idx_session_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: support_tickets
-- =====================================================
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('open','replied','closed') NOT NULL DEFAULT 'open',
  `priority` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `admin_reply` TEXT DEFAULT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `replied_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_ticket_user` (`user_id`),
  INDEX `idx_ticket_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: site_settings
-- =====================================================
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: login_attempts (for rate limiting)
-- =====================================================
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_attempts_ip` (`ip_address`),
  INDEX `idx_attempts_email` (`email`),
  INDEX `idx_attempts_time` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Site Settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'UsafiKonect'),
('site_email', 'info@usafikonect.co.ke'),
('site_phone', '+254 700 123 456'),
('platform_fee_percent', '10'),
('mpesa_env', 'sandbox'),
('mpesa_consumer_key', 'YOUR_CONSUMER_KEY'),
('mpesa_consumer_secret', 'YOUR_CONSUMER_SECRET'),
('mpesa_shortcode', '174379'),
('mpesa_passkey', 'YOUR_PASSKEY'),
('mpesa_callback_url', 'http://localhost/usafikonect/api/mpesa-callback.php'),
('mpesa_test_mode', '1'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', 'your-email@gmail.com'),
('smtp_password', 'your-app-password'),
('maintenance_mode', '0'),
('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.');

-- =====================================================
-- Users: Admin (password: Admin@123)
-- =====================================================
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `estate`, `profile_image`, `is_verified`, `is_active`, `email_verified_at`) VALUES
(1, 'System Admin', 'admin@usafikonect.co.ke', '0700000000', '$2y$12$LJ3m4yS4Ey2p6K8vqHkZxOqFQe.g/4ZbQ0ueVGhY1xFkxFTn3Ky5e', 'admin', 'Westlands', 'avatar.png', 1, 1, NOW());

-- =====================================================
-- Users: Providers (password: Password@123)
-- Hash: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- =====================================================
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `provider_type`, `estate`, `profile_image`, `is_verified`, `is_active`, `email_verified_at`) VALUES
(2, 'Mama Fua Cleaning', 'mama.fua@example.com', '0712345678', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'individual', 'Roysambu', 'avatar.png', 1, 1, NOW()),
(3, 'Sparkle Laundry Services', 'sparkle@example.com', '0723456789', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'shop', 'Umoja', 'avatar.png', 1, 1, NOW()),
(4, 'Fresh & Clean Laundry', 'freshclean@example.com', '0734567890', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'shop', 'Donholm', 'avatar.png', 1, 1, NOW()),
(5, 'Wanjiku Laundry', 'wanjiku@example.com', '0745678901', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'individual', 'Kilimani', 'avatar.png', 1, 1, NOW()),
(6, 'Nguo Safi Express', 'nguosafi@example.com', '0756789012', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'provider', 'shop', 'Langata', 'avatar.png', 1, 1, NOW());

-- =====================================================
-- Users: Customers (password: Password@123)
-- =====================================================
INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `estate`, `profile_image`, `is_verified`, `is_active`, `email_verified_at`) VALUES
(7, 'John Kamau', 'john@example.com', '0767890123', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Roysambu', 'avatar.png', 1, 1, NOW()),
(8, 'Mary Wanjiru', 'mary@example.com', '0778901234', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Umoja', 'avatar.png', 1, 1, NOW()),
(9, 'Peter Odhiambo', 'peter@example.com', '0789012345', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Donholm', 'avatar.png', 1, 1, NOW()),
(10, 'Grace Muthoni', 'grace@example.com', '0790123456', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Kilimani', 'avatar.png', 1, 1, NOW()),
(11, 'David Kipchoge', 'david@example.com', '0701234567', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Langata', 'avatar.png', 1, 1, NOW()),
(12, 'Amina Hassan', 'amina@example.com', '0711234567', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Roysambu', 'avatar.png', 1, 1, NOW()),
(13, 'Brian Otieno', 'brian@example.com', '0722345678', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Westlands', 'avatar.png', 1, 1, NOW()),
(14, 'Elizabeth Njeri', 'elizabeth@example.com', '0733456789', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Umoja', 'avatar.png', 1, 1, NOW()),
(15, 'Samuel Mwangi', 'samuel@example.com', '0744567890', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Donholm', 'avatar.png', 1, 1, NOW()),
(16, 'Faith Chebet', 'faith@example.com', '0755678901', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Kilimani', 'avatar.png', 1, 1, NOW());

-- =====================================================
-- Provider Details
-- =====================================================
INSERT INTO `provider_details` (`user_id`, `business_name`, `business_type`, `price_per_kg`, `description`, `is_approved`, `approved_at`) VALUES
(2, 'Mama Fua Cleaning Services', 'individual', 120.00, 'Experienced laundry professional in Roysambu. Tunaosha nguo kwa upole na uangalifu. Over 5 years of trusted service. We handle your clothes with care and return them fresh and clean.', 1, NOW()),
(3, 'Sparkle Laundry Services', 'shop', 150.00, 'Professional laundry shop in Umoja. We offer premium washing, ironing, and dry cleaning services. Fast turnaround and excellent customer service guaranteed.', 1, NOW()),
(4, 'Fresh & Clean Laundry', 'shop', 130.00, 'Quality laundry services in Donholm. We specialize in delicate fabrics and ensure your clothes are well taken care of. Pickup available within Donholm estate.', 1, NOW()),
(5, 'Wanjiku Premium Laundry', 'individual', 180.00, 'Premium laundry services in Kilimani. Specializing in high-end garments, suits, and designer wear. We treat every piece like our own.', 1, NOW()),
(6, 'Nguo Safi Express', 'shop', 100.00, 'Affordable and reliable laundry services in Langata. Same-day washing available. Asante kwa kutuamini na nguo zako!', 1, NOW());

-- =====================================================
-- Bookings (25 bookings across various statuses)
-- =====================================================
INSERT INTO `bookings` (`id`, `booking_number`, `customer_id`, `provider_id`, `service_type`, `weight_kg`, `total_amount`, `pickup_date`, `pickup_time`, `delivery_date`, `pickup_address`, `status`, `payment_method`, `payment_status`, `mpesa_receipt`, `special_instructions`, `created_at`) VALUES
-- Delivered bookings
(1, 'USK-20260301-0001', 7, 2, 'wash_fold', 3.0, 360.00, '2026-03-01', '09:00:00', '2026-03-02', 'Roysambu, Block A', 'delivered', 'mpesa', 'paid', 'QKJ3B7HXLM', 'Please use gentle detergent', '2026-03-01 08:30:00'),
(2, 'USK-20260302-0002', 8, 3, 'wash_iron', 4.0, 600.00, '2026-03-02', '10:00:00', '2026-03-03', 'Umoja Phase 2', 'delivered', 'mpesa', 'paid', 'RFT4C8IYNM', NULL, '2026-03-02 09:15:00'),
(3, 'USK-20260303-0003', 9, 4, 'wash_fold', 5.0, 650.00, '2026-03-03', '08:00:00', '2026-03-04', 'Donholm Estate', 'delivered', 'cash', 'paid', NULL, 'Separate whites from colors', '2026-03-03 07:45:00'),
(4, 'USK-20260305-0004', 10, 5, 'dry_clean', 2.0, 550.00, '2026-03-05', '11:00:00', '2026-03-06', 'Kilimani, Argwings Kodhek', 'delivered', 'wallet', 'paid', NULL, NULL, '2026-03-05 10:30:00'),
(5, 'USK-20260306-0005', 11, 6, 'wash_fold', 4.0, 400.00, '2026-03-06', '09:30:00', '2026-03-07', 'Langata South', 'delivered', 'mpesa', 'paid', 'PQL5D9JZOK', 'Need by tomorrow evening', '2026-03-06 09:00:00'),
(6, 'USK-20260308-0006', 12, 2, 'iron_only', 2.0, 240.00, '2026-03-08', '08:30:00', '2026-03-09', 'Roysambu, TRM area', 'delivered', 'mpesa', 'paid', 'SNG6E0KAPL', NULL, '2026-03-08 08:00:00'),
(7, 'USK-20260310-0007', 7, 3, 'wash_iron', 6.0, 900.00, '2026-03-10', '10:00:00', '2026-03-11', 'Roysambu, Marurui', 'delivered', 'cash', 'paid', NULL, 'Extra rinse please', '2026-03-10 09:30:00'),
(8, 'USK-20260312-0008', 13, 2, 'wash_fold', 2.0, 240.00, '2026-03-12', '07:30:00', '2026-03-13', 'Westlands, Sarit Centre area', 'delivered', 'mpesa', 'paid', 'TUH7F1LBQM', NULL, '2026-03-12 07:00:00'),
(9, 'USK-20260313-0009', 14, 3, 'wash_fold', 6.0, 900.00, '2026-03-13', '09:00:00', '2026-03-14', 'Umoja Innercore', 'delivered', 'wallet', 'paid', NULL, NULL, '2026-03-13 08:45:00'),
(10, 'USK-20260315-0010', 15, 4, 'dry_clean', 3.0, 583.00, '2026-03-15', '08:00:00', '2026-03-16', 'Donholm Phase 5', 'delivered', 'mpesa', 'paid', 'VWJ8G2MCRN', 'Handle with care - silk blankets', '2026-03-15 07:30:00'),
-- Ready for delivery
(11, 'USK-20260320-0011', 7, 2, 'wash_fold', 4.0, 480.00, '2026-03-20', '09:00:00', NULL, 'Roysambu, Zimmerman', 'ready', 'mpesa', 'paid', 'XYL9H3NDSO', NULL, '2026-03-20 08:30:00'),
(12, 'USK-20260321-0012', 16, 5, 'wash_iron', 3.0, 540.00, '2026-03-21', '11:00:00', NULL, 'Kilimani, Valley Arcade', 'ready', 'cash', 'pending', NULL, NULL, '2026-03-21 10:45:00'),
-- Processing
(13, 'USK-20260325-0013', 8, 2, 'wash_fold', 3.0, 360.00, '2026-03-25', '08:00:00', NULL, 'Umoja Phase 1', 'processing', 'mpesa', 'paid', 'ZAN0I4OETP', 'Starch the shirts please', '2026-03-25 07:30:00'),
(14, 'USK-20260325-0014', 9, 3, 'wash_iron', 4.0, 600.00, '2026-03-25', '10:00:00', NULL, 'Donholm Phase 8', 'processing', 'wallet', 'paid', NULL, NULL, '2026-03-25 09:40:00'),
(15, 'USK-20260326-0015', 10, 6, 'wash_fold', 5.0, 500.00, '2026-03-26', '09:00:00', NULL, 'Kilimani, Lenana Rd', 'processing', 'mpesa', 'paid', 'BCO1J5PFUQ', NULL, '2026-03-26 08:30:00'),
-- Confirmed
(16, 'USK-20260327-0016', 11, 2, 'wash_iron', 3.5, 420.00, '2026-03-27', '08:30:00', NULL, 'Langata, Karen Rd', 'confirmed', 'mpesa', 'paid', 'DEP2K6QGVR', NULL, '2026-03-27 08:00:00'),
(17, 'USK-20260328-0017', 12, 4, 'wash_fold', 3.0, 390.00, '2026-03-28', '09:00:00', NULL, 'Roysambu, TRM', 'confirmed', 'cash', 'pending', NULL, 'Use fabric softener', '2026-03-28 08:30:00'),
(18, 'USK-20260328-0018', 13, 5, 'dry_clean', 2.0, 560.00, '2026-03-28', '10:30:00', NULL, 'Westlands, Parklands', 'confirmed', 'wallet', 'paid', NULL, NULL, '2026-03-28 10:00:00'),
-- Pending
(19, 'USK-20260329-0019', 7, 3, 'wash_fold', 4.0, 600.00, '2026-03-29', '09:00:00', NULL, 'Roysambu, Kasarani area', 'pending', 'mpesa', 'pending', NULL, NULL, '2026-03-29 08:30:00'),
(20, 'USK-20260329-0020', 14, 2, 'wash_iron', 2.5, 300.00, '2026-03-29', '11:00:00', NULL, 'Umoja, Mowlem', 'pending', 'mpesa', 'pending', NULL, 'Duvet - king size', '2026-03-29 10:30:00'),
(21, 'USK-20260330-0021', 15, 6, 'wash_fold', 3.0, 300.00, '2026-03-30', '08:00:00', NULL, 'Donholm, Savannah', 'pending', 'cash', 'pending', NULL, NULL, '2026-03-30 07:30:00'),
(22, 'USK-20260330-0022', 16, 3, 'iron_only', 4.0, 600.00, '2026-03-30', '10:00:00', NULL, 'Kilimani, Hurlingham', 'pending', 'wallet', 'pending', NULL, NULL, '2026-03-30 09:30:00'),
-- Cancelled
(23, 'USK-20260310-0023', 8, 4, 'wash_fold', 2.0, 260.00, '2026-03-10', '09:00:00', NULL, 'Umoja Phase 3', 'cancelled', 'mpesa', 'refunded', 'FGQ3L7RHWS', NULL, '2026-03-10 08:30:00'),
(24, 'USK-20260315-0024', 10, 2, 'wash_fold', 3.0, 360.00, '2026-03-15', '08:00:00', NULL, 'Kilimani, Dennis Pritt', 'cancelled', 'wallet', 'refunded', NULL, 'Changed plans', '2026-03-15 07:30:00'),
-- Loyalty free booking
(25, 'USK-20260328-0025', 7, 2, 'wash_fold', 2.0, 0.00, '2026-03-28', '09:00:00', NULL, 'Roysambu, Block C', 'confirmed', 'wallet', 'paid', NULL, 'Free loyalty wash!', '2026-03-28 08:45:00');

-- =====================================================
-- Ratings (15 reviews)
-- =====================================================
INSERT INTO `ratings` (`booking_id`, `customer_id`, `provider_id`, `rating`, `review`, `is_verified_purchase`, `created_at`) VALUES
(1, 7, 2, 5, 'Mama Fua did an amazing job! Clothes came back spotless and smelling fresh. Highly recommend! Asante sana!', 1, '2026-03-02 18:00:00'),
(2, 8, 3, 4, 'Good service, blankets were very clean. Delivery took a bit longer than expected but overall satisfied.', 1, '2026-03-03 20:00:00'),
(3, 9, 4, 4, 'Decent service. Whites were separated well. Would use again.', 1, '2026-03-04 16:00:00'),
(4, 10, 5, 5, 'Premium quality service! My silk items were handled with utmost care. Worth every shilling.', 1, '2026-03-06 19:00:00'),
(5, 11, 6, 5, 'Fast and affordable! Same-day service was a lifesaver. Nguo zangu zilikuwa safi kabisa!', 1, '2026-03-07 17:00:00'),
(6, 12, 2, 4, 'Good cleaning, reasonable prices. The pickup service is very convenient.', 1, '2026-03-09 15:00:00'),
(7, 7, 3, 5, 'Sparkle really lives up to their name! Blankets came back fluffy and fresh.', 1, '2026-03-11 18:30:00'),
(8, 13, 2, 4, 'Reliable service. Clothes were clean and neatly folded. Communication was good.', 1, '2026-03-13 16:00:00'),
(9, 14, 3, 5, 'Excellent service! They handled a large load quickly and everything was perfect.', 1, '2026-03-14 19:00:00'),
(10, 15, 4, 4, 'Good service for blankets. Handled the silk ones carefully as requested.', 1, '2026-03-16 14:00:00');

-- =====================================================
-- Subscriptions (customer subscriptions for discounts + provider subs)
-- =====================================================
INSERT INTO `subscriptions` (`user_id`, `plan_type`, `amount`, `status`, `start_date`, `end_date`, `payment_status`, `mpesa_transaction_id`) VALUES
(7, 'monthly', 300.00, 'active', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'paid', 'SUB_MPQ3R8SIXT'),
(8, 'yearly', 2880.00, 'active', DATE_SUB(CURDATE(), INTERVAL 185 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 'paid', 'SUB_NRS4T9TJYU'),
(10, 'weekly', 100.00, 'active', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'paid', 'SUB_OTU5U0UKZV');

-- =====================================================
-- Wallet Transactions & Balances
-- =====================================================
INSERT INTO `wallet_transactions` (`user_id`, `type`, `amount`, `balance_after`, `reference`, `description`, `created_at`) VALUES
-- Customer 7 (John) - Balance: 2434
(7, 'top_up', 2000.00, 2000.00, 'WLT-0001', 'M-Pesa top up', '2026-03-01 08:00:00'),
(7, 'payment', -550.00, 1450.00, 'USK-20260305-0004', 'Booking payment', '2026-03-05 10:30:00'),
(7, 'top_up', 500.00, 1950.00, 'WLT-0002', 'M-Pesa top up', '2026-03-15 12:00:00'),
(7, 'refund', 484.00, 2434.00, 'USK-20260315-0024', 'Booking cancellation refund', '2026-03-15 13:00:00'),
(7, 'payment', 0.00, 2434.00, 'USK-20260328-0025', 'Loyalty free booking', '2026-03-28 08:45:00'),
-- Customer 8 (Mary) - Balance: 800
(8, 'top_up', 1000.00, 1000.00, 'WLT-0003', 'M-Pesa top up', '2026-03-02 09:00:00'),
(8, 'payment', -200.00, 800.00, 'USK-20260313-0009', 'Partial wallet payment', '2026-03-13 08:45:00'),
-- Customer 10 (Grace) - Balance: 2200
(10, 'top_up', 3000.00, 3000.00, 'WLT-0004', 'M-Pesa top up', '2026-03-04 10:00:00'),
(10, 'payment', -550.00, 2450.00, 'USK-20260305-0004', 'Booking payment', '2026-03-05 10:30:00'),
(10, 'refund', 484.00, 2934.00, 'USK-20260315-0024', 'Cancelled booking refund', '2026-03-15 14:00:00'),
(10, 'payment', -734.00, 2200.00, 'USK-20260328-0018', 'Booking payment', '2026-03-28 10:00:00');

-- =====================================================
-- Loyalty Points
-- =====================================================
INSERT INTO `loyalty_points` (`user_id`, `points`, `total_bookings`, `free_bookings_earned`, `free_bookings_used`) VALUES
(7, 50, 5, 1, 1),
(8, 30, 3, 0, 0),
(9, 20, 2, 0, 0),
(10, 20, 2, 0, 0),
(11, 10, 1, 0, 0),
(12, 10, 1, 0, 0),
(13, 20, 2, 0, 0),
(14, 20, 2, 0, 0),
(15, 20, 2, 0, 0),
(16, 20, 2, 0, 0);

-- =====================================================
-- Notifications
-- =====================================================
INSERT INTO `notifications` (`user_id`, `type`, `message`, `link`, `is_read`, `created_at`) VALUES
(7, 'booking', 'Your booking USK-20260328-0025 has been confirmed by Mama Fua Cleaning.', 'customer/booking-detail.php?id=25', 0, '2026-03-28 09:00:00'),
(7, 'booking', 'Your laundry for booking USK-20260320-0011 is ready for delivery!', 'customer/booking-detail.php?id=11', 0, '2026-03-27 16:00:00'),
(7, 'system', 'Hongera! You have earned a free wash! Redeem on your next booking.', 'customer/wallet.php', 1, '2026-03-25 12:00:00'),
(2, 'booking', 'You have a new booking request USK-20260329-0020 from Elizabeth Njeri.', 'provider/booking-detail.php?id=20', 0, '2026-03-29 10:30:00'),
(2, 'booking', 'You have a new booking request USK-20260327-0016 from David Kipchoge.', 'provider/booking-detail.php?id=16', 0, '2026-03-27 08:00:00'),
(2, 'system', 'Your monthly subscription expires in 5 days. Renew to keep receiving bookings.', NULL, 0, '2026-03-25 08:00:00'),
(3, 'booking', 'You have a new booking request USK-20260330-0022.', 'provider/booking-detail.php?id=22', 0, '2026-03-30 09:30:00'),
(8, 'booking', 'Your booking USK-20260325-0013 is now being processed by Mama Fua.', 'customer/booking-detail.php?id=13', 0, '2026-03-25 10:00:00'),
(9, 'booking', 'Your booking USK-20260325-0014 is now being processed.', 'customer/booking-detail.php?id=14', 1, '2026-03-25 12:00:00'),
(1, 'system', 'A new provider has registered and is awaiting approval.', 'admin/providers.php', 0, '2026-03-29 14:00:00'),
(1, 'system', 'Platform revenue this month: KES 1,200. View detailed reports.', 'admin/reports.php', 1, '2026-03-28 08:00:00');

COMMIT;
