-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2026 at 03:10 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `usm-service`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `is_active`) VALUES
(1, 'Department of Computer Science', 1),
(2, 'Department of Engineering', 1);

-- --------------------------------------------------------

--
-- Table structure for table `handlers`
--

CREATE TABLE `handlers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `handlers`
--

INSERT INTO `handlers` (`id`, `name`, `is_active`) VALUES
(1, 'Shafiza', 1),
(2, 'Zairil', 1),
(3, 'Salleh', 1),
(4, 'Nazir', 1),
(5, 'Abdillah', 1),
(6, 'Fauzi', 1),
(7, 'Hisham', 1),
(8, 'Amin', 1);

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `uid` varchar(40) NOT NULL,
  `requester_name` varchar(150) NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `service_type_id` int(10) UNSIGNED NOT NULL,
  `service_details` text NOT NULL,
  `estimated_cost` decimal(10,2) NOT NULL,
  `requested_at` datetime NOT NULL,
  `handler_id` int(10) UNSIGNED NOT NULL,
  `status` enum('in_progress','ready','completed') NOT NULL DEFAULT 'in_progress',
  `ready_at` datetime DEFAULT NULL,
  `picked_up_at` datetime DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `completed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `requester_phone` varchar(30) DEFAULT NULL,
  `requester_email` varchar(190) DEFAULT NULL,
  `device_ownership` enum('usm','personal') DEFAULT NULL,
  `device_type` varchar(30) DEFAULT NULL,
  `device_type_other` varchar(100) DEFAULT NULL,
  `device_brand` varchar(120) DEFAULT NULL,
  `device_model` varchar(150) DEFAULT NULL,
  `device_serial` varchar(150) DEFAULT NULL,
  `asset_number` varchar(150) DEFAULT NULL,
  `device_condition` enum('unchecked','good','damaged','off') DEFAULT NULL,
  `condition_notes` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `uid`, `requester_name`, `department_id`, `service_type_id`, `service_details`, `estimated_cost`, `requested_at`, `handler_id`, `status`, `ready_at`, `picked_up_at`, `payment_date`, `reference_no`, `completed_at`, `created_by`, `completed_by`, `created_at`, `updated_at`, `requester_phone`, `requester_email`, `device_ownership`, `device_type`, `device_type_other`, `device_brand`, `device_model`, `device_serial`, `asset_number`, `device_condition`, `condition_notes`) VALUES
(2, 'AMDI-DEMO-2026-001', 'Ahmad Hakimi', 1, 1, '[DATA DUMMY] Pemasangan Windows pada komputer pejabat', 0.00, '2026-10-08 10:30:00', 2, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo1@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-001', 'DEMO-ASSET-001', 'unchecked', NULL),
(3, 'AMDI-DEMO-2026-002', 'Nur Aisyah', 2, 2, '[DATA DUMMY] Pemasangan Microsoft Office', 15.00, '2026-10-09 11:30:00', 3, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo2@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-002', 'DEMO-ASSET-002', 'unchecked', NULL),
(4, 'AMDI-DEMO-2026-003', 'Muhammad Danish', 1, 3, '[DATA DUMMY] Pemasangan dan konfigurasi SPSS', 20.00, '2026-10-10 12:30:00', 1, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo3@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-003', 'DEMO-ASSET-003', 'unchecked', NULL),
(5, 'AMDI-DEMO-2026-004', 'Siti Hajar', 2, 4, '[DATA DUMMY] Komputer tidak dapat dihidupkan', 0.00, '2026-10-11 13:30:00', 5, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo4@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-004', 'DEMO-ASSET-004', 'unchecked', NULL),
(6, 'AMDI-DEMO-2026-005', 'Farhan Azmi', 1, 5, '[DATA DUMMY] Imbasan dan pembersihan virus', 0.00, '2026-10-12 14:30:00', 6, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo5@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-005', 'DEMO-ASSET-005', 'unchecked', NULL),
(7, 'AMDI-DEMO-2026-006', 'Nur Izzati', 2, 6, '[DATA DUMMY] Masalah sambungan Wi-Fi', 0.00, '2026-10-13 15:30:00', 7, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo6@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-006', 'DEMO-ASSET-006', 'unchecked', NULL),
(8, 'AMDI-DEMO-2026-007', 'Amirul Hakim', 1, 7, '[DATA DUMMY] Pemasangan SSD baharu', 180.00, '2026-10-14 09:30:00', 8, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo7@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-007', 'DEMO-ASSET-007', 'unchecked', NULL),
(9, 'AMDI-DEMO-2026-008', 'Nadia Sofea', 2, 6, '[DATA DUMMY] Konfigurasi Wi-Fi komputer riba', 0.00, '2026-10-15 10:30:00', 2, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo8@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-008', 'DEMO-ASSET-008', 'unchecked', NULL),
(10, 'AMDI-DEMO-2026-009', 'Syafiq Rahman', 1, 4, '[DATA DUMMY] Pemeriksaan komputer perlahan', 0.00, '2026-10-16 11:30:00', 3, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo9@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-009', 'DEMO-ASSET-009', 'unchecked', NULL),
(11, 'AMDI-DEMO-2026-010', 'Aina Balqis', 2, 2, '[DATA DUMMY] Pemasangan Microsoft Office', 15.00, '2026-10-17 12:30:00', 1, 'ready', '2026-10-18 15:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo10@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-010', 'DEMO-ASSET-010', 'unchecked', NULL),
(12, 'AMDI-DEMO-2026-011', 'Haziq Firdaus', 1, 4, '[DATA DUMMY] Pemeriksaan masalah paparan monitor', 0.00, '2026-10-18 13:30:00', 5, 'ready', '2026-10-19 15:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo11@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-011', 'DEMO-ASSET-011', 'unchecked', NULL),
(13, 'AMDI-DEMO-2026-012', 'Nurul Syuhada', 2, 3, '[DATA DUMMY] Pemasangan antivirus', 25.00, '2026-10-19 14:30:00', 6, 'ready', '2026-10-20 15:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo12@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-012', 'DEMO-ASSET-012', 'unchecked', NULL),
(14, 'AMDI-DEMO-2026-013', 'Irfan Zikri', 1, 7, '[DATA DUMMY] Penukaran RAM komputer', 95.00, '2026-10-20 15:30:00', 7, 'ready', '2026-10-21 15:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo13@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-013', 'DEMO-ASSET-013', 'unchecked', NULL),
(15, 'AMDI-DEMO-2026-014', 'Maisarah Ali', 2, 6, '[DATA DUMMY] Konfigurasi rangkaian Wi-Fi', 0.00, '2026-10-21 09:30:00', 8, 'ready', '2026-10-22 15:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo14@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-014', 'DEMO-ASSET-014', 'unchecked', NULL),
(16, 'AMDI-DEMO-2026-015', 'Adam Harith', 1, 1, '[DATA DUMMY] Pemasangan sistem pengoperasian', 30.00, '2026-10-22 10:30:00', 2, 'ready', '2026-10-23 15:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 10:09:00', '2026-10-23 10:09:00', NULL, 'demo15@example.com', 'usm', 'Laptop', NULL, NULL, NULL, 'DEMO-SN-015', 'DEMO-ASSET-015', 'unchecked', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_request_accessories`
--

CREATE TABLE `service_request_accessories` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `accessory_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_request_hardware`
--

CREATE TABLE `service_request_hardware` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_id` int(10) UNSIGNED NOT NULL,
  `work_type` enum('replacement','installation') NOT NULL,
  `hardware_type` varchar(120) NOT NULL,
  `old_model` varchar(255) DEFAULT NULL,
  `new_model` varchar(255) NOT NULL,
  `old_serial` varchar(150) DEFAULT NULL,
  `new_serial` varchar(150) DEFAULT NULL,
  `notes` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_request_types`
--

CREATE TABLE `service_request_types` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `service_type_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_types`
--

CREATE TABLE `service_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_types`
--

INSERT INTO `service_types` (`id`, `name`, `is_active`) VALUES
(1, 'Pemasangan Sistem Pengoperasian Komputer', 1),
(2, 'Pemasangan Perisian Microsoft Office', 1),
(3, 'Pemasangan Perisian Antivirus/SPSS', 1),
(4, 'Perkhidmatan Merungkai Isu Komputer', 1),
(5, 'Perkhidmatan Imbasan dan Pembersihan Virus Komputer', 1),
(6, 'Perkhidmatan Konfigurasi Wi-Fi', 1),
(7, 'Perkhidmatan Penukaran/ Pemasangan Perkakasan Komputer', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password_hash`, `is_active`, `created_at`) VALUES
(1, 'Admin', 'admin', '$2y$10$g26qCczv4nAT/q3TsokG5ejAqJLfhkOF5ONJ1MdRdh2xa5j85eXki', 1, '2026-10-19 08:53:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `handlers`
--
ALTER TABLE `handlers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD KEY `fk_request_department` (`department_id`),
  ADD KEY `fk_request_handler_directory` (`handler_id`),
  ADD KEY `fk_request_creator` (`created_by`),
  ADD KEY `fk_request_completer` (`completed_by`),
  ADD KEY `idx_request_date` (`requested_at`),
  ADD KEY `idx_type_date` (`service_type_id`,`requested_at`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_completion_date` (`completed_at`);

--
-- Indexes for table `service_request_accessories`
--
ALTER TABLE `service_request_accessories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sra_request` (`request_id`);

--
-- Indexes for table `service_request_hardware`
--
ALTER TABLE `service_request_hardware`
  ADD PRIMARY KEY (`id`),
  ADD KEY `srh_request` (`request_id`);

--
-- Indexes for table `service_request_types`
--
ALTER TABLE `service_request_types`
  ADD PRIMARY KEY (`request_id`,`service_type_id`),
  ADD KEY `srt_type` (`service_type_id`);

--
-- Indexes for table `service_types`
--
ALTER TABLE `service_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `handlers`
--
ALTER TABLE `handlers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `service_request_accessories`
--
ALTER TABLE `service_request_accessories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `service_request_hardware`
--
ALTER TABLE `service_request_hardware`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `service_types`
--
ALTER TABLE `service_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `fk_request_completer` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_request_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_request_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_request_handler_directory` FOREIGN KEY (`handler_id`) REFERENCES `handlers` (`id`),
  ADD CONSTRAINT `fk_request_type` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`);

--
-- Constraints for table `service_request_accessories`
--
ALTER TABLE `service_request_accessories`
  ADD CONSTRAINT `sra_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_request_hardware`
--
ALTER TABLE `service_request_hardware`
  ADD CONSTRAINT `srh_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_request_types`
--
ALTER TABLE `service_request_types`
  ADD CONSTRAINT `srt_request` FOREIGN KEY (`request_id`) REFERENCES `service_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `srt_type` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
