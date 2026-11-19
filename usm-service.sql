-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2026 at 04:56 AM
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
(1, 'Student', 1),
(2, 'Pejabat Pengarah', 1),
(3, 'Pusat Media dan Perhubungan Awam', 1),
(4, 'Unit Hal Ehwal Islam', 1),
(5, 'Pejabat Perniagaan Korporat (CBO)', 1),
(6, 'Unit Keselamatan, Kesihatan Dan Persekitaran Pekerjaan (OSHE)', 1),
(7, 'Jabatan Sains Bioperubatan', 1),
(8, 'Jabatan Toksikologi', 1),
(9, 'Jabatan Perubatan Klinikal', 1),
(10, 'Jabatan Kesihatan Komuniti', 1),
(11, 'Jabatan Sains Pergigian', 1),
(12, 'Jabatan Pengimejan Bioperubatan', 1),
(13, 'Pejabat Pentadbiran Jabatan', 1),
(14, 'Bahagian Akademik Dan Antarabangsa', 1),
(15, 'Seksyen Sumber Manusia', 1),
(16, 'Seksyen Kewangan', 1),
(17, 'Pejabat Pembangunan Projek Khas', 1),
(18, 'Seksyen Kejuruteraan', 1),
(19, 'Seksyen Transformasi Digital', 1),
(20, 'Seksyen Keselamatan', 1),
(21, 'Bahagian Penyelidikan, Inovasi Dan Jaringan', 1),
(22, 'Cancer Teaching Complex (CTC), PKTAAB', 1),
(23, 'Bahagian Perkhidmatan Perubatan Dan Pembedahan', 1),
(24, 'Bahagian Perkhidmatan Sokongan Klinikal', 1),
(25, 'Perkhidmatan Kejururawatan', 1);

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
  `condition_notes` varchar(1000) DEFAULT NULL,
  `accessories` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `uid`, `requester_name`, `department_id`, `service_type_id`, `service_details`, `estimated_cost`, `requested_at`, `handler_id`, `status`, `ready_at`, `picked_up_at`, `payment_date`, `reference_no`, `completed_at`, `created_by`, `completed_by`, `created_at`, `updated_at`, `requester_phone`, `requester_email`, `device_ownership`, `device_type`, `device_type_other`, `device_brand`, `device_model`, `device_serial`, `asset_number`, `device_condition`, `condition_notes`, `accessories`) VALUES
(1, '1', 'Ali Hakim', 1, 1, '[DATA DUMMY] Pemasangan sistem operasi', 0.00, '2026-10-20 09:00:00', 1, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-20 09:00:00', '2026-10-20 09:00:00', NULL, 'demo01@example.com', 'usm', 'Laptop', NULL, 'HP', 'Demo Model 1', 'DEMO-SN-001', 'DEMO-ASSET-001', 'good', NULL, 'Charger'),
(2, '2', 'Nur Aina', 2, 2, '[DATA DUMMY] Pemasangan Microsoft Office', 15.00, '2026-10-21 09:00:00', 2, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-21 09:00:00', '2026-10-21 09:00:00', NULL, 'demo02@example.com', 'personal', 'Laptop', NULL, 'Lenovo', 'Demo Model 2', 'DEMO-SN-002', NULL, 'good', NULL, 'Tiada'),
(3, '3', 'Muhammad Faiz', 3, 3, '[DATA DUMMY] Pemasangan antivirus dan SPSS', 25.00, '2026-10-22 09:00:00', 3, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-22 09:00:00', '2026-10-22 09:00:00', NULL, 'demo03@example.com', 'usm', 'Laptop', NULL, 'Acer', 'Demo Model 3', 'DEMO-SN-003', 'DEMO-ASSET-003', 'good', NULL, 'Beg Laptop'),
(4, '4', 'Siti Sarah', 4, 4, '[DATA DUMMY] Komputer tidak dapat dihidupkan', 0.00, '2026-10-23 09:00:00', 4, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-23 09:00:00', '2026-10-23 09:00:00', NULL, 'demo04@example.com', 'personal', 'Laptop', NULL, 'Dell', 'Demo Model 4', 'DEMO-SN-004', NULL, 'good', NULL, 'Mouse, Charger'),
(5, '5', 'Amir Hakimi', 5, 5, '[DATA DUMMY] Imbasan dan pembersihan virus', 0.00, '2026-10-24 09:00:00', 5, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-24 09:00:00', '2026-10-24 09:00:00', NULL, 'demo05@example.com', 'usm', 'Laptop', NULL, 'HP', 'Demo Model 5', 'DEMO-SN-005', 'DEMO-ASSET-005', 'good', NULL, 'Beg Laptop, Mouse, Charger'),
(6, '6', 'Nurul Iman', 1, 6, '[DATA DUMMY] Konfigurasi Wi-Fi', 0.00, '2026-10-25 09:00:00', 6, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-25 09:00:00', '2026-10-25 09:00:00', NULL, 'demo06@example.com', 'personal', 'Laptop', NULL, 'Lenovo', 'Demo Model 6', 'DEMO-SN-006', NULL, 'good', NULL, 'Charger'),
(7, '7', 'Adam Faris', 2, 7, '[DATA DUMMY] Penggantian SSD dan RAM', 150.00, '2026-10-26 09:00:00', 7, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-26 09:00:00', '2026-10-26 09:00:00', NULL, 'demo07@example.com', 'usm', 'Laptop', NULL, 'Acer', 'Demo Model 7', 'DEMO-SN-007', 'DEMO-ASSET-007', 'good', NULL, 'Tiada'),
(8, '8', 'Hana Sofea', 3, 1, '[DATA DUMMY] Pemasangan sistem operasi', 0.00, '2026-10-27 09:00:00', 8, 'in_progress', NULL, NULL, NULL, NULL, NULL, 1, NULL, '2026-10-27 09:00:00', '2026-10-27 09:00:00', NULL, 'demo08@example.com', 'personal', 'Laptop', NULL, 'Dell', 'Demo Model 8', 'DEMO-SN-008', NULL, 'good', NULL, 'Beg Laptop'),
(9, '9', 'Irfan Zikri', 4, 2, '[DATA DUMMY] Pemasangan Microsoft Office', 15.00, '2026-10-28 09:00:00', 1, 'ready', '2026-10-30 14:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-28 09:00:00', '2026-10-28 09:00:00', NULL, 'demo09@example.com', 'usm', 'Laptop', NULL, 'HP', 'Demo Model 9', 'DEMO-SN-009', 'DEMO-ASSET-009', 'good', NULL, 'Mouse, Charger'),
(10, '10', 'Aisyah Nabila', 5, 3, '[DATA DUMMY] Pemasangan antivirus dan SPSS', 25.00, '2026-10-29 09:00:00', 2, 'ready', '2026-10-31 14:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-29 09:00:00', '2026-10-29 09:00:00', NULL, 'demo10@example.com', 'personal', 'Laptop', NULL, 'Lenovo', 'Demo Model 10', 'DEMO-SN-010', NULL, 'good', NULL, 'Beg Laptop, Mouse, Charger'),
(11, '11', 'Hakim Danish', 1, 4, '[DATA DUMMY] Komputer tidak dapat dihidupkan', 0.00, '2026-10-30 09:00:00', 3, 'ready', '2026-11-01 14:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-30 09:00:00', '2026-10-30 09:00:00', NULL, 'demo11@example.com', 'usm', 'Laptop', NULL, 'Acer', 'Demo Model 11', 'DEMO-SN-011', 'DEMO-ASSET-011', 'good', NULL, 'Charger'),
(12, '12', 'Siti Balqis', 2, 5, '[DATA DUMMY] Imbasan dan pembersihan virus', 0.00, '2026-10-31 09:00:00', 4, 'ready', '2026-11-02 14:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-10-31 09:00:00', '2026-10-31 09:00:00', NULL, 'demo12@example.com', 'personal', 'Laptop', NULL, 'Dell', 'Demo Model 12', 'DEMO-SN-012', NULL, 'good', NULL, 'Tiada'),
(13, '13', 'Farhan Azmi', 3, 6, '[DATA DUMMY] Konfigurasi Wi-Fi', 0.00, '2026-11-01 09:00:00', 5, 'ready', '2026-11-03 14:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-11-01 09:00:00', '2026-11-01 09:00:00', NULL, 'demo13@example.com', 'usm', 'Laptop', NULL, 'HP', 'Demo Model 13', 'DEMO-SN-013', 'DEMO-ASSET-013', 'good', NULL, 'Beg Laptop'),
(14, '14', 'Nur Syuhada', 4, 7, '[DATA DUMMY] Penggantian SSD dan RAM', 150.00, '2026-11-02 09:00:00', 6, 'ready', '2026-11-04 14:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-11-02 09:00:00', '2026-11-02 09:00:00', NULL, 'demo14@example.com', 'personal', 'Laptop', NULL, 'Lenovo', 'Demo Model 14', 'DEMO-SN-014', NULL, 'good', NULL, 'Mouse, Charger'),
(15, '15', 'Amirul Haziq', 5, 1, '[DATA DUMMY] Pemasangan sistem operasi', 0.00, '2026-11-03 09:00:00', 7, 'ready', '2026-11-05 14:00:00', NULL, NULL, NULL, NULL, 1, NULL, '2026-11-03 09:00:00', '2026-11-03 09:00:00', NULL, 'demo15@example.com', 'usm', 'Laptop', NULL, 'Acer', 'Demo Model 15', 'DEMO-SN-015', 'DEMO-ASSET-015', 'good', NULL, 'Beg Laptop, Mouse, Charger'),
(16, '16', 'Maisarah Ali', 1, 2, '[DATA DUMMY] Pemasangan Microsoft Office', 15.00, '2026-11-04 09:00:00', 8, 'completed', '2026-11-06 14:00:00', '2026-11-07 15:00:00', '2026-11-07', 'DEMO-REF-016', '2026-11-07 15:00:00', 1, 1, '2026-11-04 09:00:00', '2026-11-04 09:00:00', NULL, 'demo16@example.com', 'personal', 'Laptop', NULL, 'Dell', 'Demo Model 16', 'DEMO-SN-016', NULL, 'good', NULL, 'Charger'),
(17, '17', 'Muhammad Aiman', 2, 3, '[DATA DUMMY] Pemasangan antivirus dan SPSS', 25.00, '2026-11-05 09:00:00', 1, 'completed', '2026-11-07 14:00:00', '2026-11-08 15:00:00', '2026-11-08', 'DEMO-REF-017', '2026-11-08 15:00:00', 1, 1, '2026-11-05 09:00:00', '2026-11-05 09:00:00', NULL, 'demo17@example.com', 'usm', 'Laptop', NULL, 'HP', 'Demo Model 17', 'DEMO-SN-017', 'DEMO-ASSET-017', 'good', NULL, 'Tiada'),
(18, '18', 'Nur Izzati', 3, 4, '[DATA DUMMY] Komputer tidak dapat dihidupkan', 0.00, '2026-11-06 09:00:00', 2, 'completed', '2026-11-08 14:00:00', '2026-11-09 15:00:00', '2026-11-09', 'DEMO-REF-018', '2026-11-09 15:00:00', 1, 1, '2026-11-06 09:00:00', '2026-11-06 09:00:00', NULL, 'demo18@example.com', 'personal', 'Laptop', NULL, 'Lenovo', 'Demo Model 18', 'DEMO-SN-018', NULL, 'good', NULL, 'Beg Laptop'),
(19, '19', 'Haziq Firdaus', 4, 5, '[DATA DUMMY] Imbasan dan pembersihan virus', 0.00, '2026-11-07 09:00:00', 3, 'completed', '2026-11-09 14:00:00', '2026-11-10 15:00:00', '2026-11-10', 'DEMO-REF-019', '2026-11-10 15:00:00', 1, 1, '2026-11-07 09:00:00', '2026-11-07 09:00:00', NULL, 'demo19@example.com', 'usm', 'Laptop', NULL, 'Acer', 'Demo Model 19', 'DEMO-SN-019', 'DEMO-ASSET-019', 'good', NULL, 'Mouse, Charger'),
(20, '20', 'Nadia Sofea', 5, 6, '[DATA DUMMY] Konfigurasi Wi-Fi', 0.00, '2026-11-08 09:00:00', 4, 'completed', '2026-11-10 14:00:00', '2026-11-11 15:00:00', '2026-11-11', 'DEMO-REF-020', '2026-11-11 15:00:00', 1, 1, '2026-11-08 09:00:00', '2026-11-08 09:00:00', NULL, 'demo20@example.com', 'personal', 'Laptop', NULL, 'Dell', 'Demo Model 20', 'DEMO-SN-020', NULL, 'good', NULL, 'Beg Laptop, Mouse, Charger'),
(21, '21', 'Syafiq Rahman', 1, 7, '[DATA DUMMY] Penggantian SSD dan RAM', 150.00, '2026-11-09 09:00:00', 5, 'completed', '2026-11-11 14:00:00', '2026-11-12 15:00:00', '2026-11-12', 'DEMO-REF-021', '2026-11-12 15:00:00', 1, 1, '2026-11-09 09:00:00', '2026-11-09 09:00:00', NULL, 'demo21@example.com', 'usm', 'Laptop', NULL, 'HP', 'Demo Model 21', 'DEMO-SN-021', 'DEMO-ASSET-021', 'good', NULL, 'Charger'),
(22, '22', 'Aina Balqis', 2, 1, '[DATA DUMMY] Pemasangan sistem operasi', 0.00, '2026-11-10 09:00:00', 6, 'completed', '2026-11-12 14:00:00', '2026-11-13 15:00:00', '2026-11-13', 'DEMO-REF-022', '2026-11-13 15:00:00', 1, 1, '2026-11-10 09:00:00', '2026-11-10 09:00:00', NULL, 'demo22@example.com', 'personal', 'Laptop', NULL, 'Lenovo', 'Demo Model 22', 'DEMO-SN-022', NULL, 'good', NULL, 'Tiada'),
(23, '23', 'Adam Harith', 3, 2, '[DATA DUMMY] Pemasangan Microsoft Office', 15.00, '2026-11-11 09:00:00', 7, 'completed', '2026-11-13 14:00:00', '2026-11-14 15:00:00', '2026-11-14', 'DEMO-REF-023', '2026-11-14 15:00:00', 1, 1, '2026-11-11 09:00:00', '2026-11-11 09:00:00', NULL, 'demo23@example.com', 'usm', 'Laptop', NULL, 'Acer', 'Demo Model 23', 'DEMO-SN-023', 'DEMO-ASSET-023', 'good', NULL, 'Beg Laptop'),
(24, '24', 'Siti Hajar', 4, 3, '[DATA DUMMY] Pemasangan antivirus dan SPSS', 25.00, '2026-11-12 09:00:00', 8, 'completed', '2026-11-14 14:00:00', '2026-11-15 15:00:00', '2026-11-15', 'DEMO-REF-024', '2026-11-15 15:00:00', 1, 1, '2026-11-12 09:00:00', '2026-11-12 09:00:00', NULL, 'demo24@example.com', 'personal', 'Laptop', NULL, 'Dell', 'Demo Model 24', 'DEMO-SN-024', NULL, 'good', NULL, 'Mouse, Charger');

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
(1, 7, 'replacement', 'SSD', 'SSD 128GB', 'SSD 512GB', 'DEMO-OLD-7', 'DEMO-NEW-7', '[DATA DUMMY] Penggantian storan'),
(2, 14, 'replacement', 'SSD', 'SSD 128GB', 'SSD 512GB', 'DEMO-OLD-14', 'DEMO-NEW-14', '[DATA DUMMY] Penggantian storan'),
(3, 21, 'replacement', 'SSD', 'SSD 128GB', 'SSD 512GB', 'DEMO-OLD-21', 'DEMO-NEW-21', '[DATA DUMMY] Penggantian storan');

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
(4, 4),
(4, 5),
(5, 5),
(6, 6),
(7, 7),
(8, 1),
(8, 2),
(9, 2),
(10, 3),
(11, 4),
(12, 5),
(12, 6),
(13, 6),
(14, 7),
(15, 1),
(16, 2),
(16, 3),
(17, 3),
(18, 4),
(19, 5),
(20, 6),
(20, 7),
(21, 7),
(22, 1),
(23, 2),
(24, 3),
(24, 4);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `handlers`
--
ALTER TABLE `handlers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
