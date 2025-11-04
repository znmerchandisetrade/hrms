-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 03:50 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `leave_management_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `brand_name`, `company_id`, `created_at`) VALUES
(1, 'Arrow', 1, '2025-10-11 13:50:17'),
(2, 'Wallstreet', 2, '2025-10-11 13:50:17'),
(3, 'Sahara', 2, '2025-10-11 13:50:17'),
(4, 'Criterion', 2, '2025-10-18 05:01:27'),
(5, 'Ultimo', 2, '2025-10-18 05:01:27'),
(6, 'Van Heusen', 1, '2025-10-18 05:02:58'),
(7, 'Izod', 1, '2025-10-18 05:02:58'),
(8, 'Support Department', 4, '2025-10-18 05:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `change_dayoff_applications`
--

CREATE TABLE `change_dayoff_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_dayoff` varchar(20) NOT NULL,
  `requested_dayoff` varchar(20) NOT NULL,
  `effective_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `manager_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `change_dayoff_applications`
--

INSERT INTO `change_dayoff_applications` (`id`, `user_id`, `current_dayoff`, `requested_dayoff`, `effective_date`, `reason`, `status`, `approved_by`, `approved_at`, `manager_notes`, `created_at`, `updated_at`) VALUES
(1, 3, 'Sunday', 'Monday', '2025-11-01', 'Need to attend family events on Sundays', 'approved', 2, '2025-10-23 04:00:00', 'Approved for family reasons', '2025-10-23 18:35:34', '2025-10-23 18:35:34'),
(2, 5, 'Monday', 'Friday', '2025-11-15', 'Medical appointments scheduled on Mondays', 'pending', NULL, NULL, NULL, '2025-10-23 18:35:34', '2025-10-23 18:35:34'),
(3, 19, 'Tuesday', 'Thursday', '2025-11-10', 'Personal commitments', 'rejected', 6, '2025-10-23 04:30:00', 'Not enough staff coverage on Thursdays', '2025-10-23 18:35:34', '2025-10-23 18:35:34'),
(4, 5, 'Sunday', 'Monday', '2025-10-25', 'ksnjkvjg', 'pending', NULL, NULL, NULL, '2025-10-23 18:43:43', '2025-10-23 18:43:43'),
(5, 3, 'Sunday', 'Monday', '2025-10-24', '.amv', 'pending', NULL, NULL, NULL, '2025-10-23 18:48:22', '2025-10-23 18:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `company_name`, `created_at`) VALUES
(1, 'Corporate Apparel Inc.', '2025-10-11 13:50:17'),
(2, 'Concept Clothing Inc.', '2025-10-11 13:50:17'),
(3, 'Third Party', '2025-10-18 04:59:15'),
(4, 'CEO Group Inc.', '2025-10-18 05:00:16');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `created_at`) VALUES
(1, 'Sales & Operations', '2025-10-11 13:50:18'),
(2, 'Marketing', '2025-10-11 13:50:18'),
(3, 'IT', '2025-10-11 13:50:18'),
(4, 'HR', '2025-10-11 13:50:18'),
(5, 'Finance', '2025-10-11 13:50:18'),
(6, 'Merchandising', '2025-10-23 13:49:08');

-- --------------------------------------------------------

--
-- Table structure for table `id_renewal_applications`
--

CREATE TABLE `id_renewal_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_valid_from` date NOT NULL,
  `current_valid_to` date NOT NULL,
  `requested_valid_from` date NOT NULL,
  `requested_valid_to` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `manager_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `late_letter_applications`
--

CREATE TABLE `late_letter_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `late_date` date NOT NULL,
  `arrival_time` time NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `manager_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `late_letter_applications`
--

INSERT INTO `late_letter_applications` (`id`, `user_id`, `late_date`, `arrival_time`, `reason`, `status`, `approved_by`, `approved_at`, `manager_notes`, `created_at`, `updated_at`) VALUES
(1, 3, '2025-10-20', '09:30:00', 'Heavy traffic due to road construction', 'approved', 2, '2025-10-23 05:00:00', 'Traffic verified', '2025-10-23 18:35:34', '2025-10-23 18:35:34'),
(2, 5, '2025-10-21', '10:15:00', 'Family emergency this morning', 'pending', NULL, NULL, NULL, '2025-10-23 18:35:34', '2025-10-23 18:35:34'),
(3, 19, '2025-10-19', '09:45:00', 'Public transportation delay', 'approved', 6, '2025-10-23 05:30:00', 'Transportation issue confirmed', '2025-10-23 18:35:34', '2025-10-23 18:35:34');

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company` varchar(100) NOT NULL,
  `store_brand` varchar(100) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `day_off_count` int(11) NOT NULL,
  `reliever_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `manager_notes` text DEFAULT NULL,
  `processed` tinyint(1) DEFAULT 0,
  `user_deleted` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `user_id`, `company`, `store_brand`, `start_date`, `end_date`, `reason`, `day_off_count`, `reliever_name`, `status`, `approved_by_user_id`, `approved_at`, `manager_notes`, `processed`, `user_deleted`, `approved_by`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 3, 'Concept Clothing Inc.', 'Arrow', '2025-10-10', '2025-10-10', 'jalfjljAS', 1, 'LJALFJ', 'rejected', 2, '2025-10-11 17:04:54', 'no reliever', 1, 0, 2, '', '2025-10-11 05:52:15', '2025-10-11 17:16:18'),
(2, 3, 'Concept Clothing Inc.', 'Arrow', '2025-10-11', '2025-10-13', 'vacation', 3, 'janice', 'rejected', 2, '2025-10-11 14:55:25', 'no reliever', 1, 0, 2, '', '2025-10-11 13:19:51', '2025-10-11 17:16:18'),
(3, 4, 'Concept Clothing Inc.', 'wallstreet', '2025-10-11', '2025-10-12', 'hajkhfj', 2, 'jllj', 'approved', 2, '2025-10-11 14:51:28', 'done', 1, 1, 2, '', '2025-10-11 13:34:03', '2025-10-11 17:16:18'),
(4, 3, 'Global Retail Corp', 'Downtown Flagship - Main Retail', '2025-10-01', '2025-10-11', 'ahfshjkahf', 11, 'janice', 'rejected', 2, '2025-10-11 14:50:48', 'no reliever', 1, 0, NULL, NULL, '2025-10-11 14:04:32', '2025-10-11 17:16:18'),
(5, 5, 'Global Retail Corp', 'Mall Branch - Main Retail', '2025-10-22', '2025-10-22', 'birthday', 1, 'janice', 'rejected', 2, '2025-10-11 17:10:00', 'not allowed', 1, 0, NULL, NULL, '2025-10-11 15:17:18', '2025-10-11 17:16:18'),
(6, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-12', '2025-10-12', 'hkafkhakfs', 1, 'janice', 'approved', 2, '2025-10-11 16:47:58', 'approved', 1, 0, NULL, NULL, '2025-10-11 16:18:37', '2025-10-11 17:16:18'),
(7, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-24', '2025-10-25', 'birthday', 2, 'janice', 'pending', NULL, NULL, NULL, 1, 0, NULL, NULL, '2025-10-11 17:11:39', '2025-10-11 17:32:49'),
(8, 3, 'Corporate Apparel Inc.', 'Head Office - Arrow', '2025-10-03', '2025-10-03', 'undertime', 1, 'janice', 'pending', NULL, NULL, NULL, 1, 0, NULL, NULL, '2025-10-11 17:52:34', '2025-10-11 17:54:38'),
(9, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-12', '2025-10-12', 'bitrthday', 1, 'janice', 'pending', NULL, NULL, NULL, 1, 0, NULL, NULL, '2025-10-12 05:08:18', '2025-10-12 05:09:40'),
(10, 3, 'Corporate Apparel Inc.', 'Head Office - Arrow', '2025-10-12', '2025-10-12', 'late', 1, 'janice', 'rejected', NULL, '2025-10-18 15:08:23', '', 1, 0, NULL, NULL, '2025-10-12 05:42:41', '2025-10-18 15:08:23'),
(11, 3, 'Corporate Apparel Inc.', 'Head Office - Arrow', '2025-10-18', '2025-10-18', 'ljljlj', 1, 'jlljloj', 'rejected', NULL, NULL, NULL, 1, 0, NULL, NULL, '2025-10-12 05:48:49', '2025-10-18 14:21:33'),
(12, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-21', '2025-10-17', 'birthday', 5, 'janice', 'approved', NULL, '2025-10-18 15:10:03', 'bad employee', 1, 0, NULL, NULL, '2025-10-14 10:32:12', '2025-10-18 15:10:03'),
(13, 18, 'Third Party', 'N/A - N/A', '2025-10-24', '2025-10-24', 'vacation leave', 1, 'janice', 'rejected', NULL, '2025-10-18 15:09:36', '', 1, 0, NULL, NULL, '2025-10-18 06:05:00', '2025-10-18 15:09:36'),
(14, 3, 'Corporate Apparel Inc.', 'Head Office - Arrow', '2025-10-24', '2025-10-25', 'holiday ----', 2, 'janice', 'approved', 6, '2025-10-18 14:44:17', 'good', 1, 0, NULL, NULL, '2025-10-18 14:39:11', '2025-10-18 14:44:17'),
(15, 19, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-24', '2025-10-21', ',njafkjksf', 4, 'janice', 'rejected', 6, '2025-10-18 15:16:23', 'very good with growth', 1, 0, NULL, NULL, '2025-10-18 15:01:46', '2025-10-18 15:16:23'),
(16, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-31', '2025-10-31', 'hospitalize', 1, 'janice', 'approved', 6, '2025-10-23 12:09:07', 'proceed\r\n', 1, 0, NULL, NULL, '2025-10-23 12:08:47', '2025-10-23 12:09:07'),
(17, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-25', '2025-10-26', 'travel abroad', 2, 'janice', 'approved', 6, '2025-10-23 15:42:41', '', 1, 0, NULL, NULL, '2025-10-23 15:42:01', '2025-10-23 15:42:41'),
(18, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-11-01', '2025-11-01', 'go to cemetery', 1, 'janice', 'pending', NULL, NULL, NULL, 0, 0, NULL, NULL, '2025-10-23 18:22:46', '2025-10-23 18:22:46');

-- --------------------------------------------------------

--
-- Table structure for table `leave_history`
--

CREATE TABLE `leave_history` (
  `id` int(11) NOT NULL,
  `leave_application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company` varchar(100) NOT NULL,
  `store_brand` varchar(100) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `day_off_count` int(11) NOT NULL,
  `reliever_name` varchar(100) DEFAULT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `approved_by` int(11) NOT NULL,
  `manager_notes` text DEFAULT NULL,
  `user_deleted` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_history`
--

INSERT INTO `leave_history` (`id`, `leave_application_id`, `user_id`, `company`, `store_brand`, `start_date`, `end_date`, `reason`, `day_off_count`, `reliever_name`, `status`, `approved_by`, `manager_notes`, `user_deleted`, `created_at`) VALUES
(2, 3, 4, 'Concept Clothing Inc.', 'wallstreet', '2025-10-11', '2025-10-12', 'hajkhfj', 2, 'jllj', 'approved', 2, 'done', 1, '2025-10-11 14:51:28'),
(3, 2, 3, 'Concept Clothing Inc.', 'Arrow', '2025-10-11', '2025-10-13', 'vacation', 3, 'janice', 'rejected', 2, 'no reliever', 0, '2025-10-11 14:55:25'),
(4, 6, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-12', '2025-10-12', 'hkafkhakfs', 1, 'janice', 'approved', 2, 'approved', 0, '2025-10-11 16:47:58'),
(5, 1, 3, 'Concept Clothing Inc.', 'Arrow', '2025-10-10', '2025-10-10', 'jalfjljAS', 1, 'LJALFJ', 'rejected', 2, 'no reliever', 0, '2025-10-11 17:04:54'),
(7, 7, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-24', '2025-10-25', 'birthday', 2, 'janice', 'approved', 2, 'approved', 0, '2025-10-11 17:32:49'),
(8, 8, 3, 'Corporate Apparel Inc.', 'Head Office - Arrow', '2025-10-03', '2025-10-03', 'undertime', 1, 'janice', 'approved', 2, 'approved', 0, '2025-10-11 17:54:38'),
(9, 9, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-12', '2025-10-12', 'bitrthday', 1, 'janice', 'approved', 2, 'good employee', 0, '2025-10-12 05:09:40'),
(10, 12, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-21', '2025-10-17', 'birthday', 5, 'janice', 'approved', 6, 'bad employee', 0, '2025-10-18 15:10:03'),
(11, 11, 3, 'Corporate Apparel Inc.', 'Head Office - Arrow', '2025-10-18', '2025-10-18', 'ljljlj', 1, 'jlljloj', 'rejected', 6, 'bad', 0, '2025-10-18 04:26:39'),
(12, 13, 18, 'Third Party', 'N/A - N/A', '2025-10-24', '2025-10-24', 'vacation leave', 1, 'janice', 'rejected', 6, '', 0, '2025-10-18 15:09:36'),
(13, 10, 3, 'Corporate Apparel Inc.', 'Head Office - Arrow', '2025-10-12', '2025-10-12', 'late', 1, 'janice', 'rejected', 6, '', 0, '2025-10-18 15:08:23'),
(14, 14, 3, 'Corporate Apparel Inc.', 'Head Office - Arrow', '2025-10-24', '2025-10-25', 'holiday ----', 2, 'janice', 'approved', 6, 'good', 0, '2025-10-18 14:44:17'),
(15, 15, 19, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-24', '2025-10-21', ',njafkjksf', 4, 'janice', 'rejected', 6, 'very good with growth', 0, '2025-10-18 15:16:23'),
(16, 16, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-31', '2025-10-31', 'hospitalize', 1, 'janice', 'approved', 6, 'proceed\r\n', 0, '2025-10-23 12:09:07'),
(17, 17, 5, 'Corporate Apparel Inc.', 'Megamall - Arrow', '2025-10-25', '2025-10-26', 'travel abroad', 2, 'janice', 'approved', 6, '', 0, '2025-10-23 15:42:41');

-- --------------------------------------------------------

--
-- Table structure for table `recipients`
--

CREATE TABLE `recipients` (
  `id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipients`
--

INSERT INTO `recipients` (`id`, `store_id`, `name`, `position`, `created_at`, `updated_at`) VALUES
(1, 1, 'Ms. Peachy Garcia', 'Store Consignor Manager', '2025-10-23 12:28:00', '2025-10-23 12:28:00'),
(2, 2, 'Mr. John Smith', 'Mall Operations Manager', '2025-10-23 12:28:00', '2025-10-23 12:28:00'),
(3, 3, 'Ms. Maria Santos', 'Store Administrator', '2025-10-23 12:28:00', '2025-10-23 12:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `created_at`) VALUES
(1, 'Employee', '2025-10-11 13:50:18'),
(2, 'Team Lead', '2025-10-11 13:50:18'),
(3, 'Operations Manager', '2025-10-11 13:50:18'),
(4, 'Area Manager', '2025-10-11 13:50:18'),
(5, 'HR Manager', '2025-10-11 13:50:18'),
(6, 'AVP', '2025-10-11 16:45:53'),
(7, 'Admin', '2025-10-23 13:35:57');

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `id` int(11) NOT NULL,
  `store_name` varchar(100) NOT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stores`
--

INSERT INTO `stores` (`id`, `store_name`, `brand_id`, `created_at`) VALUES
(1, 'The Landmark Alabang', 1, '2025-10-11 13:50:18'),
(2, 'Megamall', 1, '2025-10-11 13:50:18'),
(3, 'RDS Ermita', 2, '2025-10-11 13:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `store_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `date_hired` date DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('employee','manager','admin') DEFAULT 'employee',
  `store_brand` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `company_id`, `brand_id`, `department_id`, `store_id`, `role_id`, `date_hired`, `full_name`, `profile_image`, `role`, `store_brand`, `department`, `created_at`, `deleted_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 4, NULL, 5, '2020-03-01', 'System Administrator', NULL, 'admin', 'Head Office', 'Management', '2025-10-11 05:40:35', NULL),
(2, 'manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 3, 1, 3, '2022-06-01', 'Jeff D', NULL, 'manager', 'Main Store', 'Management', '2025-10-11 05:40:35', NULL),
(3, 'employee1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1, 1, 1, '2023-01-15', 'John Doe', NULL, 'employee', 'Main Store', 'Sales', '2025-10-11 05:40:35', NULL),
(4, 'jose', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, NULL, 'jose protacio', NULL, 'employee', 'wallstreet', 'operation', '2025-10-11 13:32:04', '2025-10-11 17:08:03'),
(5, 'jeff_criterion', '$2y$10$cmKOycDN6FHIQY64wC6ImOe9ZLs.sXH1081wwh/U1P2.bNDrudjB2', 1, 1, 3, 2, 1, '2025-10-11', 'Jeffrey Diana', '68ea717914655.jpg', 'employee', NULL, NULL, '2025-10-11 15:02:17', NULL),
(6, 'chat', '$2y$10$QyYOdR1FMfLUzOXMYPzQb.zyJqXQSbkETPxm67zFccDEvltEY0Jtq', 2, 1, 4, 1, 6, '2025-10-22', 'chat del remedio', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-11 16:20:38', NULL),
(7, 'tracee', '$2y$10$TAXlR9o1qAx9DnMUsG2pJ.8dUTCzK/LMlitOfk3v5uonag961t.WS', 2, 2, 1, 1, 3, '2025-10-22', 'tracee s', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-11 17:06:50', NULL),
(8, 'marionne', '$2y$10$V.nYVDSQvy7tlR5oAIXyV.PH7mcu.9jVEhui3298LMqEcDq.tDwgu', 1, 3, 1, 1, 3, '2025-10-12', 'marione i', 'user_1760246187_68eb39abaf57c.jpg', 'employee', NULL, NULL, '2025-10-12 05:16:27', NULL),
(9, 'LYNNLD', '$2y$10$AOyUh35PCzyTiX.Kx.qvQ.A0K2X7wwxNoAJfwpxbb0iUXzXZ5zlVG', 1, 1, 4, 1, 6, '2025-10-10', 'lynn diana', 'user_1760758841_68f30c39a0165.png', 'employee', NULL, NULL, '2025-10-18 03:40:41', NULL),
(10, 'employee4', '$2y$10$d9a8HFVLsTCazlSdC0Wg0Okz3P7Mrzh4JkdiMxJP6eM77Ll03MrMi', 2, 3, 5, 1, 2, '2025-10-25', 'andres boni', 'user_1760759059_68f30d13a39e4.png', 'employee', NULL, NULL, '2025-10-18 03:44:19', '2025-10-18 04:27:27'),
(11, 'zoed', '$2y$10$LfaBrCdyC6vkj9Fc7b0r4enfhFLKJ749I.JYt79ZKSSj1r.kn6zZu', 2, 3, 4, NULL, 4, '2025-10-18', 'zoe diana', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-18 04:00:15', NULL),
(12, 'niquid', '$2y$10$Ua03Qa9ZzioOAiIRNblOEOGwol/XvrFVo2vKCPEGgPim/ppxuOV/S', 2, 3, 3, NULL, 4, '2025-10-17', 'niqui d', 'user_1760761471_68f3167f16b49.jpg', 'employee', NULL, NULL, '2025-10-18 04:24:31', NULL),
(13, 'andresb', '$2y$10$sMCQNPYn9tS8QMaIabsizONjQYN/UNmIta3uzeZ33fwBzGzVT1EVO', 4, 8, 4, NULL, 6, NULL, 'andres bonifacio', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-18 05:28:07', '2025-10-18 05:28:36'),
(14, 'purec', '$2y$10$nLfgzGdC/p6e9cedgUA/OuPXUJjpcVxrDNbicyHtI8.yx1jK5nyyq', 2, 4, 4, NULL, 4, NULL, 'pure caro', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-18 05:33:33', NULL),
(15, 'antonioluna', '$2y$10$MoA0BYCSG/00Iuk.eJXMbOuy8KZ2N4MiVQAMr8ZA/7jH.fTrdzyYm', 2, 5, 5, NULL, 4, NULL, 'antonio luna', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-18 05:38:27', NULL),
(16, 'rody', '$2y$10$orLpdLZB9FEYb1DHk9l5LOxCQyGssiik/qsOvPcs0pXnPidLnoll2', 4, 8, 5, NULL, 4, NULL, 'rodrigo duterte', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-18 05:52:15', NULL),
(17, 'jvflavier', '$2y$10$6KcU0G0MuavmI2Q6ZXzrOeSTkU16WCHak1a6cW1uxiy8NQ77bvrk.', NULL, NULL, NULL, NULL, 1, NULL, 'juan flavier', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-18 06:01:16', NULL),
(18, 'sarahduterte', '$2y$10$RE9DGFfjb78DHVd5XUpG0.lUEUEyDyz7Pob1jUH00tfhYAyDMDfPy', 3, NULL, 1, NULL, 1, NULL, 'sarah duterte', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-18 06:04:09', NULL),
(19, 'Gloria_A', '$2y$10$cjRCtfaasayPbFt6cv1lF.EsUFCz6uRAKhD9e/wxvvzuoHDQupm02', 1, 1, 1, 2, 1, '2025-10-18', 'Gloria Arroyo', 'default-avatar.png', 'employee', NULL, NULL, '2025-10-18 14:49:49', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brand_name` (`brand_name`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `change_dayoff_applications`
--
ALTER TABLE `change_dayoff_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_name` (`company_name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `id_renewal_applications`
--
ALTER TABLE `id_renewal_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `late_letter_applications`
--
ALTER TABLE `late_letter_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `approved_by_user_id` (`approved_by_user_id`),
  ADD KEY `idx_leave_applications_user_id` (`user_id`),
  ADD KEY `idx_leave_applications_status` (`status`),
  ADD KEY `idx_leave_applications_processed` (`processed`),
  ADD KEY `idx_leave_applications_created_at` (`created_at`),
  ADD KEY `idx_leave_apps_user_status` (`user_id`,`status`),
  ADD KEY `idx_leave_apps_user_processed` (`user_id`,`processed`);

--
-- Indexes for table `leave_history`
--
ALTER TABLE `leave_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_application_id` (`leave_application_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_leave_history_user_id` (`user_id`),
  ADD KEY `idx_leave_history_status` (`status`),
  ADD KEY `idx_leave_history_created_at` (`created_at`),
  ADD KEY `idx_leave_history_user_status` (`user_id`,`status`);

--
-- Indexes for table `recipients`
--
ALTER TABLE `recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipients_store_id` (`store_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `store_name` (`store_name`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `store_id` (`store_id`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_role_id` (`role_id`),
  ADD KEY `idx_users_deleted_at` (`deleted_at`),
  ADD KEY `idx_users_role_deleted` (`role_id`,`deleted_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `change_dayoff_applications`
--
ALTER TABLE `change_dayoff_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `id_renewal_applications`
--
ALTER TABLE `id_renewal_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `late_letter_applications`
--
ALTER TABLE `late_letter_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `leave_history`
--
ALTER TABLE `leave_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `recipients`
--
ALTER TABLE `recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stores`
--
ALTER TABLE `stores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `change_dayoff_applications`
--
ALTER TABLE `change_dayoff_applications`
  ADD CONSTRAINT `change_dayoff_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `change_dayoff_applications_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `id_renewal_applications`
--
ALTER TABLE `id_renewal_applications`
  ADD CONSTRAINT `id_renewal_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `id_renewal_applications_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `late_letter_applications`
--
ALTER TABLE `late_letter_applications`
  ADD CONSTRAINT `late_letter_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `late_letter_applications_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `fk_leave_apps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leave_applications_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leave_applications_ibfk_3` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `leave_history`
--
ALTER TABLE `leave_history`
  ADD CONSTRAINT `leave_history_ibfk_1` FOREIGN KEY (`leave_application_id`) REFERENCES `leave_applications` (`id`),
  ADD CONSTRAINT `leave_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `leave_history_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `recipients`
--
ALTER TABLE `recipients`
  ADD CONSTRAINT `recipients_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stores`
--
ALTER TABLE `stores`
  ADD CONSTRAINT `stores_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `users_ibfk_4` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  ADD CONSTRAINT `users_ibfk_5` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
