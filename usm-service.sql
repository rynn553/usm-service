-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 02:41 AM
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
(1, 'USM-2026-DEMO001', 'Pemohon Demo 1', 1, 1, 'Contoh servis format PC.', 50.00, '2026-10-12 09:00:00', 1, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-19 08:53:53', '2026-10-19 08:53:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'USM-2026-DEMO002', 'Pemohon Demo 2', 2, 2, 'Contoh pemasangan aplikasi.', 20.00, '2026-10-12 10:00:00', 2, 'ready', '2026-10-13 14:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-19 08:53:53', '2026-10-19 08:53:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'USM-2026-DEMO003', 'Pemohon Demo 3', 1, 3, 'Contoh pemeriksaan masalah PC.', 30.00, '2026-10-10 11:00:00', 1, 'completed', '2026-10-11 14:00:00', '2026-10-12 15:00:00', '2026-10-12', 'DEMO-REF-003', '2026-10-12 15:00:00', 1, 1, '2026-10-19 08:53:53', '2026-10-19 08:53:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'USM-2026-DEMO004', 'Pemohon Demo 4', 2, 1, 'Contoh permintaan September, selesai Oktober.', 50.00, '2026-09-30 09:00:00', 2, 'completed', '2026-10-01 12:00:00', '2026-10-02 10:00:00', '2026-10-02', 'DEMO-REF-004', '2026-10-02 10:00:00', 1, 1, '2026-10-19 08:53:53', '2026-10-19 08:53:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'USM-2026-81C3619DE3', 'Arina', 1, 3, 'Install SPSS error', 10.00, '2026-10-20 12:15:00', 1, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-20 12:17:07', '2026-10-20 12:17:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'USM-2026-A7C5BE7F1D', 'Ammar', 1, 1, 'tukar device', 11.00, '2026-10-21 12:58:00', 1, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-21 12:59:01', '2026-10-21 12:59:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'USM-2026-E5276B4FAC', 'abu', 2, 7, 'sss', 11.00, '2026-10-21 13:00:00', 1, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-21 13:01:12', '2026-10-21 13:01:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'USM-2026-BC5DD23CE0', 'wina', 2, 7, '', 12.00, '2026-10-21 14:20:00', 8, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-21 14:20:40', '2026-10-21 14:20:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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

--
-- Dumping data for table `service_request_hardware`
--

INSERT INTO `service_request_hardware` (`id`, `request_id`, `work_type`, `hardware_type`, `old_model`, `new_model`, `old_serial`, `new_serial`, `notes`) VALUES
(1, 7, 'replacement', 'pc', 'aa', 'cc', '11', '22', NULL),
(2, 8, 'installation', 'pc', NULL, 'qq', NULL, '33', 'aa'),
(3, 9, 'replacement', 'SSD', 'HDD', 'SDD', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `service_request_types`
--

CREATE TABLE `service_request_types` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `service_type_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_request_types`
--

INSERT INTO `service_request_types` (`request_id`, `service_type_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 1),
(6, 3),
(7, 1),
(7, 7),
(8, 7),
(9, 7);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `service_request_accessories`
--
ALTER TABLE `service_request_accessories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_request_hardware`
--
ALTER TABLE `service_request_hardware`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
