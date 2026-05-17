-- Sumbungan barangay complaint system — import in phpMyAdmin
CREATE DATABASE IF NOT EXISTS `sumbungan_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sumbungan_db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `complaint_timeline`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `barangay_settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('complainant','admin','officer') NOT NULL DEFAULT 'complainant',
  `address` varchar(500) DEFAULT NULL,
  `profile_pic` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `barangay_settings` (
  `id` tinyint unsigned NOT NULL DEFAULT 1,
  `barangay_name` varchar(191) NOT NULL DEFAULT 'Barangay Sanroque',
  `contact_number` varchar(64) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `complaints` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `complainant_id` int unsigned NOT NULL,
  `assigned_officer_id` int unsigned DEFAULT NULL,
  `type` enum('Noise','Sanitation','Security','Traffic','Other') NOT NULL DEFAULT 'Other',
  `location` varchar(500) NOT NULL,
  `description` text NOT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','in-progress','resolved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `complaints_code_unique` (`code`),
  KEY `complaints_complainant_id` (`complainant_id`),
  KEY `complaints_status` (`status`),
  KEY `complaints_type` (`type`),
  CONSTRAINT `complaints_complainant_fk` FOREIGN KEY (`complainant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `complaints_officer_fk` FOREIGN KEY (`assigned_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `complaint_timeline` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `complaint_id` int unsigned NOT NULL,
  `status_label` varchar(191) NOT NULL,
  `details` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `complaint_timeline_complaint_id` (`complaint_id`),
  CONSTRAINT `complaint_timeline_fk` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id` (`user_id`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `address`, `profile_pic`) VALUES
(1, 'Kap', 'kap@sanroque.gov.ph', '$2y$10$PPYJqRuZRRaP2lJ3C/s.f.AZ/g98kpKWyPgfYIzPp1q.Od8jLLRIi', 'admin', 'Barangay Hall, Sanroque', NULL),
(2, 'Juana Dela Cruz', 'juana@example.com', '$2y$10$NMXUnD2yFy4mNOpYRL9/COyX.tPt8yAtAdVWZBH6dzuzhKTjzEpH.', 'complainant', 'Sitio 1, Brgy. Sanroque', NULL);

INSERT INTO `barangay_settings` (`id`, `barangay_name`, `contact_number`, `address`, `logo`) VALUES
(1, 'Barangay Sanroque', '0912-345-6789', 'Sanroque, Philippines', NULL);

INSERT INTO `complaints` (`id`, `code`, `complainant_id`, `assigned_officer_id`, `type`, `location`, `description`, `photo_path`, `status`, `created_at`) VALUES
(1, 'BRY-4021', 2, NULL, 'Noise', 'Purok 2, Phase 1', 'Extremely loud karaoke at 2:00 AM on a Tuesday.', NULL, 'resolved', '2023-10-11 02:15:00'),
(2, 'BRY-4025', 2, NULL, 'Sanitation', 'Sitio Uno Entrance', 'Uncollected garbage piling up near the main gate.', NULL, 'in-progress', '2023-10-12 10:45:00'),
(3, 'BRY-4030', 2, NULL, 'Traffic', 'Intersection Blvd', 'Illegal parking blocking the fire hydrant.', NULL, 'pending', '2023-10-13 08:00:00');

INSERT INTO `complaint_timeline` (`complaint_id`, `status_label`, `details`, `created_at`) VALUES
(1, 'Report Received', 'Resident filed online.', '2023-10-11 02:15:00'),
(1, 'Investigation', 'Barangay Tanod visited site.', '2023-10-11 09:00:00'),
(1, 'Resolved', 'Owner agreed to observe quiet hours.', '2023-10-11 14:00:00'),
(2, 'Report Received', 'Photos attached by resident.', '2023-10-12 10:45:00'),
(2, 'Assigned to Officer', 'Officer Santos assigned.', '2023-10-12 11:30:00'),
(3, 'Report Received', 'Waiting for validation.', '2023-10-13 08:00:00');

INSERT INTO `notifications` (`user_id`, `message`, `is_read`, `created_at`) VALUES
(2, 'Case BRY-4025 has been updated to In Progress.', 0, NOW() - INTERVAL 10 MINUTE),
(2, 'New community guidelines published for October.', 0, NOW() - INTERVAL 1 HOUR);
