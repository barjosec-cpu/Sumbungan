-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 04, 2026 at 11:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sumbungan_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `barangay_settings`
--

CREATE TABLE `barangay_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `barangay_name` varchar(191) NOT NULL DEFAULT 'Barangay Sanroque',
  `contact_number` varchar(64) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barangay_settings`
--

INSERT INTO `barangay_settings` (`id`, `barangay_name`, `contact_number`, `address`, `logo`) VALUES
(1, 'Barangay Sanroque', '0912-345-6789', 'Sanroque, Philippines', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(32) NOT NULL,
  `complainant_id` int(10) UNSIGNED NOT NULL,
  `assigned_officer_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('Noise','Sanitation','Security','Traffic','Other') NOT NULL DEFAULT 'Other',
  `location` varchar(500) NOT NULL,
  `description` text NOT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','in-progress','resolved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `code`, `complainant_id`, `assigned_officer_id`, `type`, `location`, `description`, `photo_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 'BRY-4021', 2, NULL, 'Noise', 'Purok 2, Phase 1', 'Extremely loud karaoke at 2:00 AM on a Tuesday.', NULL, 'resolved', '2023-10-11 02:15:00', '2026-05-05 04:17:10'),
(2, 'BRY-4025', 2, NULL, 'Sanitation', 'Sitio Uno Entrance', 'Uncollected garbage piling up near the main gate.', NULL, 'in-progress', '2023-10-12 10:45:00', '2026-05-05 04:17:10'),
(3, 'BRY-4030', 2, NULL, 'Traffic', 'Intersection Blvd', 'Illegal parking blocking the fire hydrant.', NULL, 'rejected', '2023-10-13 08:00:00', '2026-05-05 05:26:01'),
(4, 'BRY-0004', 2, NULL, 'Sanitation', 'Purok 1', 'HOW?', 'uploads/c_0bd320c59e050f1b.png', 'rejected', '2026-05-05 04:47:25', '2026-05-05 05:25:17');

-- --------------------------------------------------------

--
-- Table structure for table `complaint_timeline`
--

CREATE TABLE `complaint_timeline` (
  `id` int(10) UNSIGNED NOT NULL,
  `complaint_id` int(10) UNSIGNED NOT NULL,
  `status_label` varchar(191) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `complaint_timeline`
--

INSERT INTO `complaint_timeline` (`id`, `complaint_id`, `status_label`, `details`, `created_at`) VALUES
(1, 1, 'Report Received', 'Resident filed online.', '2023-10-11 02:15:00'),
(2, 1, 'Investigation', 'Barangay Tanod visited site.', '2023-10-11 09:00:00'),
(3, 1, 'Resolved', 'Owner agreed to observe quiet hours.', '2023-10-11 14:00:00'),
(4, 2, 'Report Received', 'Photos attached by resident.', '2023-10-12 10:45:00'),
(5, 2, 'Assigned to Officer', 'Officer Santos assigned.', '2023-10-12 11:30:00'),
(6, 3, 'Report Received', 'Waiting for validation.', '2023-10-13 08:00:00'),
(7, 3, 'Status: Resolved', 'Updated by administrator.', '2026-05-05 04:44:51'),
(8, 4, 'Report Received', 'Complaint filed online.', '2026-05-05 04:47:25'),
(9, 4, 'Status: Rejected', 'Updated by administrator.', '2026-05-05 05:25:17'),
(10, 3, 'Status: Pending', 'Updated by administrator.', '2026-05-05 05:25:43'),
(11, 3, 'Cancelled by complainant', 'Resident requested cancellation while case was still pending.', '2026-05-05 05:26:01');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'Case BRY-4025 has been updated to In Progress.', 1, '2026-05-05 04:07:10'),
(2, 2, 'New community guidelines published for October.', 1, '2026-05-05 03:17:10'),
(3, 2, 'hi', 1, '2026-05-05 04:44:12'),
(4, 2, 'Case #BRY-4030 status changed to Resolved.', 1, '2026-05-05 04:44:51'),
(5, 2, 'Case #BRY-4030: dfb', 1, '2026-05-05 04:44:56'),
(6, 2, 'Your complaint #BRY-0004 was received and is pending review.', 1, '2026-05-05 04:47:25'),
(7, 2, 'hi', 0, '2026-05-05 05:00:17'),
(8, 3, 'Welcome to Sumbungan! File your first complaint anytime.', 1, '2026-05-05 05:12:58'),
(9, 2, 'Case #BRY-0004 status changed to Rejected.', 0, '2026-05-05 05:25:17'),
(10, 2, 'Case #BRY-4030 status changed to Pending.', 0, '2026-05-05 05:25:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('complainant','admin','officer') NOT NULL DEFAULT 'complainant',
  `address` varchar(500) DEFAULT NULL,
  `profile_pic` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `address`, `profile_pic`, `created_at`) VALUES
(1, 'Kap', 'kap@sanroque.gov.ph', '$2y$10$PPYJqRuZRRaP2lJ3C/s.f.AZ/g98kpKWyPgfYIzPp1q.Od8jLLRIi', 'admin', 'Barangay Hall, Sanroque', 'uploads/p_1_77147c7e.png', '2026-05-05 04:17:10'),
(2, 'Juana Dela Cruz', 'juana@example.com', '$2y$10$NMXUnD2yFy4mNOpYRL9/COyX.tPt8yAtAdVWZBH6dzuzhKTjzEpH.', 'complainant', 'Sitio 1, Brgy. Sanroque', NULL, '2026-05-05 04:17:10'),
(3, 'wew', 'wew@gmail.com', '$2y$10$MDTvh4lSUlfhvoyapo3FRuFbPWqq8LoqsTStXJyk7PaIJlIjCtFX2', 'complainant', 'San Roque , Zamboanga City', NULL, '2026-05-05 05:12:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barangay_settings`
--
ALTER TABLE `barangay_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `complaints_code_unique` (`code`),
  ADD KEY `complaints_complainant_id` (`complainant_id`),
  ADD KEY `complaints_status` (`status`),
  ADD KEY `complaints_type` (`type`),
  ADD KEY `complaints_officer_fk` (`assigned_officer_id`);

--
-- Indexes for table `complaint_timeline`
--
ALTER TABLE `complaint_timeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaint_timeline_complaint_id` (`complaint_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `complaint_timeline`
--
ALTER TABLE `complaint_timeline`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_complainant_fk` FOREIGN KEY (`complainant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaints_officer_fk` FOREIGN KEY (`assigned_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `complaint_timeline`
--
ALTER TABLE `complaint_timeline`
  ADD CONSTRAINT `complaint_timeline_fk` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
