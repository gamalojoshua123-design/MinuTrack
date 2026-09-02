-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 06:26 PM
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
-- Database: `pos_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` varchar(50) DEFAULT NULL,
  `result` varchar(20) DEFAULT 'success',
  `detail` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `username`, `action`, `category`, `target_type`, `target_id`, `result`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:34:54'),
(2, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 03:06:09'),
(3, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 03:06:24'),
(4, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 03:14:57'),
(5, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 03:15:43'),
(6, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 03:16:48'),
(7, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 15:35:04'),
(8, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 15:39:14'),
(9, 13, '0001', 'login', 'auth', 'user', '13', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 15:48:08'),
(10, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-17 15:49:17'),
(11, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 04:34:19'),
(12, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Mobile/15E148 Safari/604.1', '2026-08-19 04:36:33'),
(13, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Mobile/15E148 Safari/604.1', '2026-08-19 04:37:42'),
(14, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 04:41:18'),
(15, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 04:54:48'),
(16, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Mobile/15E148 Safari/604.1', '2026-08-19 04:58:47'),
(17, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-19 05:15:28'),
(18, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-19 05:19:41'),
(19, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.68.107', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1', '2026-08-19 05:20:58'),
(20, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 05:24:19'),
(21, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 05:27:41'),
(22, 14, 'owner', 'login', 'auth', 'user', '14', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 05:28:20'),
(23, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.68.116', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6 Mobile/15E148 Safari/604.1 Brave', '2026-08-19 05:29:56'),
(24, 14, 'owner', 'login', 'auth', 'user', '14', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-19 05:30:40'),
(25, 14, 'owner', 'login', 'auth', 'user', '14', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 05:36:49'),
(26, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-19 05:38:18'),
(27, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-19 05:40:13'),
(28, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 05:43:25'),
(29, 14, 'owner', 'login', 'auth', 'user', '14', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 05:44:54'),
(30, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 05:57:48'),
(31, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 06:02:29'),
(32, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.68.109', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-19 06:18:03'),
(33, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 08:03:00'),
(34, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 09:11:24'),
(35, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 12:50:03'),
(36, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 13:11:02'),
(37, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 13:18:53'),
(38, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 13:19:44'),
(39, 14, 'owner', 'login', 'auth', 'user', '14', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 13:22:11'),
(40, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 13:22:49'),
(41, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:01:03'),
(42, 14, 'owner', 'login', 'auth', 'user', '14', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:39:03'),
(43, 14, 'owner', 'deleted', 'branches', 'branch', '3', 'success', 'Permanently deleted branch \'Minute Burger Jasaan\' (27 inventory items, 0 orders, 0 user accounts deleted)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:47:39'),
(44, 14, 'owner', 'deleted', 'branches', 'branch', '4', 'success', 'Permanently deleted branch \'Minute Burger Balingasag\' (0 inventory items, 0 orders, 0 user accounts deleted)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:47:58'),
(45, 14, 'owner', 'user_update', 'users', 'user', '14', 'success', 'Updated user, role = admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:50:09'),
(46, 14, 'owner', 'user_update', 'users', 'user', '1', 'success', 'Updated user, role = cashier', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:50:23'),
(47, 14, 'owner', 'user_update', 'users', 'user', '3', 'success', 'Updated user, role = manager', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:50:39'),
(48, 14, 'owner', 'user_update', 'users', 'user', '15', 'success', 'Updated user, role = manager', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:51:21'),
(49, 14, 'owner', 'user_update', 'users', 'user', '15', 'success', 'Updated user, role = manager', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:51:39'),
(50, 14, 'owner', 'user_update', 'users', 'user', '3', 'success', 'Updated user, role = cashier', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:52:04'),
(51, 14, 'owner', 'user_update', 'users', 'user', '1', 'success', 'Updated user, role = cashier', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 14:52:17'),
(52, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:04:33'),
(53, 2, 'Allan', 'user_update', 'users', 'user', '15', 'success', 'Updated user, role = manager', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:04:56'),
(54, 2, 'Allan', 'user_update', 'users', 'user', '14', 'success', 'Updated user, role = admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:05:55'),
(55, 2, 'Allan', 'user_update', 'users', 'user', '2', 'success', 'Updated user, role = admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:06:02'),
(56, 2, 'Allan', 'user_update', 'users', 'user', '5', 'success', 'Updated user, role = manager', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:06:13'),
(57, 2, 'Allan', 'user_update', 'users', 'user', '1', 'success', 'Updated user, role = cashier', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:06:22'),
(58, 2, 'Allan', 'user_update', 'users', 'user', '3', 'success', 'Updated user, role = cashier', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:06:34'),
(59, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 15:19:07'),
(60, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 15:23:03'),
(61, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:23:25'),
(62, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:27:15'),
(63, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:29:55'),
(64, 15, 'Barbe', 'unauthorized_access', 'auth', 'page', '/minute1/tools/archive.php', 'denied', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:31:38'),
(65, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 15:33:23'),
(66, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 15:35:15'),
(67, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 15:40:06'),
(68, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 16:19:40'),
(69, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:23:29'),
(70, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:37:04'),
(71, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:42:15'),
(72, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:42:58'),
(73, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:51:35'),
(74, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:52:04'),
(75, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:54:52'),
(76, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:59:08'),
(77, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 16:59:50'),
(78, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 17:17:56'),
(79, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 17:18:57'),
(80, 5, 'Joshua', 'unauthorized_access', 'auth', 'page', '/minute1/tools/archive.php', 'denied', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:24:32'),
(81, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:24:51'),
(82, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 17:35:00'),
(83, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 17:36:10'),
(84, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.1.15', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 18:06:43'),
(85, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.1.8', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 18:08:54'),
(86, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 23:46:07'),
(87, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 23:47:00'),
(88, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 23:47:42'),
(89, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 23:56:23'),
(90, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-21 23:56:48'),
(91, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 00:11:58'),
(92, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 00:12:14'),
(93, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 00:14:15'),
(94, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 00:16:34'),
(95, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 00:18:22'),
(96, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 00:18:49'),
(97, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 00:22:37'),
(98, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 00:22:52'),
(99, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 00:24:20'),
(100, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 00:26:16'),
(101, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 00:34:19'),
(102, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 00:35:01'),
(103, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-22 00:35:20'),
(104, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-22 00:37:05'),
(105, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-22 00:37:45'),
(106, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 00:39:55'),
(107, 3, 'wenggams', 'login', 'auth', 'user', '3', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-22 00:41:36'),
(108, 14, 'Merfern', 'login', 'auth', 'user', '14', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 00:51:02'),
(109, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 01:02:53'),
(110, 2, 'Allan', 'branch_delete', 'branches', 'branch', '17', 'success', 'Permanently deleted inactive branch \"ZZ Delete Test\"', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 01:02:54'),
(111, 2, 'Allan', 'branch_delete', 'branches', 'branch', '16', 'success', 'Permanently deleted inactive branch \"Minute Burger CDO\"', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 01:02:54'),
(112, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-PH) WindowsPowerShell/5.1.26100.9168', '2026-08-22 01:03:33'),
(113, 14, 'Merfern', 'branch_delete', 'branches', 'branch', '18', 'success', 'Permanently deleted inactive branch \"Minute Burger CDO\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:09:08'),
(114, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.68.101', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1', '2026-08-22 01:14:25'),
(115, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.68.101', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1', '2026-08-22 01:15:16'),
(116, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:25:27'),
(117, 14, 'Merfern', 'login', 'auth', 'user', '14', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-22 01:26:07'),
(118, 2, 'Allan', 'branch_delete', 'branches', 'branch', '19', 'success', 'Permanently deleted inactive branch \"Minute Burger CDO\"', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:31:01'),
(119, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-22 01:44:06'),
(120, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 01:50:13'),
(121, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-22 02:01:11'),
(122, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '192.168.68.103', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-22 02:02:52'),
(123, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 02:55:32'),
(124, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 03:32:14'),
(125, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 03:33:59'),
(126, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 14:05:20'),
(127, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 14:05:50'),
(128, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 15:16:43'),
(129, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 15:18:17'),
(130, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 15:20:13'),
(131, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 09:17:45'),
(132, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 09:42:12'),
(133, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 10:28:58'),
(134, 15, 'Barbe', 'unauthorized_access', 'auth', 'page', '/minute1/tools/archive.php', 'denied', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 10:40:59'),
(135, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 11:51:59'),
(136, 5, 'Joshua', 'login', 'auth', 'user', '5', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 11:52:22'),
(137, 2, 'Allan', 'login', 'auth', 'user', '2', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 12:38:44'),
(138, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 12:39:31'),
(139, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '192.168.1.4', 'Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/92.0.4515.90 Mobile/15E148 Safari/604.1', '2026-08-31 12:53:58'),
(140, 15, 'Barbe', 'login', 'auth', 'user', '15', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 13:07:34'),
(141, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 13:09:04'),
(142, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 13:21:32'),
(143, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 14:18:16'),
(144, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 14:41:35'),
(145, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 15:03:41'),
(146, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 15:09:24'),
(147, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 15:32:46'),
(148, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 16:02:54'),
(149, 1, 'Brix', 'login', 'auth', 'user', '1', 'success', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 16:23:39');

-- --------------------------------------------------------

--
-- Table structure for table `backup_download_tokens`
--

CREATE TABLE `backup_download_tokens` (
  `id` int(11) NOT NULL,
  `token` char(64) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_download_tokens`
--

INSERT INTO `backup_download_tokens` (`id`, `token`, `filename`, `created_at`, `expires_at`) VALUES
(66, '9cc2e478d18bd91a6f238e511e55d95065ef3b91f8592c4e9b0943b93ae715dc', 'auto_shiftend_2026-08-31_21-07-22.sql', '2026-08-31 21:07:22', '2026-08-31 21:22:22'),
(67, 'a0ad23b5ce46c55dea38f29e57bdd258051988e87f7051cba447a59789e9aaf7', 'auto_shiftend_2026-08-31_21-21-51.sql', '2026-08-31 21:21:51', '2026-08-31 21:36:51');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT '',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Minute Burger Jasaan', 'Jasaan', 'active', '2026-07-15 12:16:59', '2026-08-31 09:43:44'),
(2, 'Minute Burger Balingasag', 'Cagayan de Oro', 'active', '2026-07-15 12:16:59', '2026-08-21 14:47:13');

-- --------------------------------------------------------

--
-- Table structure for table `cashier_inventory_counts`
--

CREATE TABLE `cashier_inventory_counts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL DEFAULT 0,
  `counted_stock` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `counted_by` int(11) NOT NULL,
  `counted_by_name` varchar(255) NOT NULL,
  `counted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashier_shifts`
--

CREATE TABLE `cashier_shifts` (
  `id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_type` enum('AM','PM') NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `opening_cash` decimal(10,2) DEFAULT 0.00,
  `closing_cash` decimal(10,2) DEFAULT 0.00,
  `cash_drop_total` decimal(10,2) DEFAULT 0.00,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `total_transactions` int(11) DEFAULT 0,
  `status` enum('active','closed') DEFAULT 'active',
  `started_by` int(11) NOT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shift_quota` decimal(10,2) DEFAULT 10000.00,
  `late_start` tinyint(1) DEFAULT 0,
  `late_minutes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashier_shifts`
--

INSERT INTO `cashier_shifts` (`id`, `cashier_id`, `shift_date`, `shift_type`, `start_time`, `end_time`, `opening_cash`, `closing_cash`, `cash_drop_total`, `total_sales`, `total_transactions`, `status`, `started_by`, `closed_by`, `created_at`, `shift_quota`, `late_start`, `late_minutes`) VALUES
(22, 3, '2026-03-23', 'AM', '2026-03-23 12:38:35', '2026-05-24 07:21:19', 894.00, 894.00, 0.00, 0.00, 0, 'closed', 3, 3, '2026-03-23 04:38:35', 4472.00, 1, 398),
(25, 3, '2026-06-25', 'AM', '2026-06-25 07:56:06', NULL, 2000.00, 0.00, 0.00, 0.00, 0, 'active', 3, NULL, '2026-06-24 23:56:06', 10000.00, 0, 0),
(26, 1, '2026-06-30', 'PM', '2026-06-30 23:35:12', '2026-06-30 23:36:41', 21211.00, 21211.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-06-30 15:35:12', 10000.00, 0, 0),
(27, 1, '2026-07-01', 'AM', '2026-07-01 12:46:19', '2026-07-01 13:11:40', 6666.00, 6666.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-07-01 04:46:19', 10000.00, 0, 0),
(28, 1, '2026-07-03', 'AM', '2026-07-03 09:34:43', '2026-07-03 09:35:04', 1406.00, 1406.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-07-03 01:34:43', 7028.00, 1, 214),
(29, 1, '2026-07-03', 'AM', '2026-07-03 10:10:50', '2026-07-03 10:14:33', 1306.00, 1364.00, 0.00, 58.00, 1, 'closed', 1, 1, '2026-07-03 02:10:50', 6528.00, 1, 250),
(30, 1, '2026-07-03', 'AM', '2026-07-03 10:22:00', '2026-07-04 09:06:46', 1275.00, 1275.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-07-03 02:22:00', 6375.00, 1, 262),
(31, 1, '2026-07-04', 'AM', '2026-07-04 09:07:34', '2026-07-04 09:07:58', 1481.00, 1751.00, 0.00, 270.00, 1, 'closed', 1, 1, '2026-07-04 01:07:34', 7403.00, 1, 187),
(32, 1, '2026-07-04', 'AM', '2026-07-04 09:08:21', '2026-07-18 11:51:17', 1478.00, 1667.00, 0.00, 189.00, 1, 'closed', 1, 1, '2026-07-04 01:08:21', 7389.00, 1, 188),
(36, 15, '2026-07-15', 'AM', '2026-07-15 23:38:19', '2026-08-22 11:34:48', 0.00, 1258.00, 0.00, 1258.00, 5, 'closed', 15, 15, '2026-07-15 15:38:19', 10000.00, 0, 0),
(44, 1, '2026-07-18', 'AM', '2026-07-18 11:51:40', '2026-07-18 12:05:53', 1025.00, 1025.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-07-18 03:51:40', 5125.00, 1, 351),
(45, 1, '2026-07-24', 'AM', '2026-07-24 15:35:58', '2026-08-08 09:06:57', 500.00, 500.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-07-24 07:35:58', 2014.00, 1, 575),
(47, 1, '2026-08-19', 'AM', '2026-08-19 12:36:43', '2026-08-21 23:24:09', 900.00, 1224.00, 0.00, 324.00, 1, 'closed', 1, 1, '2026-08-19 04:36:43', 4500.00, 1, 396),
(48, 5, '2026-08-19', 'AM', '2026-08-19 13:15:34', '2026-08-19 16:06:23', 0.00, 184.00, 0.00, 184.00, 1, 'closed', 5, 5, '2026-08-19 05:15:34', 10000.00, 0, 0),
(49, 5, '2026-08-21', 'PM', '2026-08-21 21:17:25', '2026-08-22 00:42:37', 0.00, 150.00, 0.00, 150.00, 1, 'closed', 5, 5, '2026-08-21 13:17:25', 10000.00, 0, 0),
(52, 5, '2026-08-21', 'PM', '2026-08-22 00:52:07', '2026-08-22 01:22:56', 0.00, 184.00, 0.00, 184.00, 1, 'closed', 5, 5, '2026-08-21 16:52:07', 10000.00, 0, 0),
(53, 5, '2026-08-21', 'PM', '2026-08-22 02:23:16', '2026-08-22 02:23:21', 0.00, 0.00, 0.00, 0.00, 0, 'closed', 5, 5, '2026-08-21 18:23:16', 10000.00, 0, 0),
(54, 1, '2026-08-22', 'AM', '2026-08-22 07:56:56', '2026-08-31 21:07:22', 1678.00, 2776.00, 0.00, 1098.00, 7, 'closed', 1, 1, '2026-08-21 23:56:56', 8389.00, 1, 116),
(55, 15, '2026-08-29', 'PM', '2026-08-29 22:06:37', '2026-08-29 22:08:50', 0.00, 0.00, 0.00, 0.00, 0, 'closed', 15, 15, '2026-08-29 14:06:37', 10000.00, 0, 0),
(56, 15, '2026-08-31', 'PM', '2026-08-31 19:41:10', NULL, 0.00, 0.00, 0.00, 0.00, 0, 'active', 15, NULL, '2026-08-31 11:41:10', 10000.00, 0, 0),
(57, 5, '2026-08-31', 'PM', '2026-08-31 19:52:27', '2026-08-31 19:52:54', 0.00, 182.00, 0.00, 182.00, 1, 'closed', 5, 5, '2026-08-31 11:52:27', 10000.00, 0, 0),
(58, 1, '2026-08-31', 'PM', '2026-08-31 21:09:10', '2026-08-31 21:21:51', 2213.00, 2213.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-08-31 13:09:10', 7375.00, 1, 189),
(59, 1, '2026-08-31', 'PM', '2026-08-31 22:18:32', '2026-08-31 22:20:14', 1925.00, 1925.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-08-31 14:18:32', 6417.00, 1, 258),
(60, 1, '2026-08-31', 'PM', '2026-08-31 22:41:46', '2026-08-31 22:42:08', 1829.00, 1829.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-08-31 14:41:46', 6097.00, 1, 281),
(61, 1, '2026-08-31', 'PM', '2026-08-31 23:03:45', '2026-08-31 23:03:50', 1738.00, 1738.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-08-31 15:03:45', 5792.00, 1, 303),
(62, 1, '2026-08-31', 'PM', '2026-08-31 23:09:28', '2026-08-31 23:09:34', 1713.00, 1713.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-08-31 15:09:28', 5708.00, 1, 309),
(63, 1, '2026-08-31', 'PM', '2026-08-31 23:34:16', '2026-08-31 23:34:31', 1617.00, 1617.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-08-31 15:34:16', 5389.00, 1, 334),
(64, 1, '2026-08-31', 'PM', '2026-09-01 00:23:44', '2026-09-01 00:24:02', 3000.00, 3000.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-08-31 16:23:44', 10000.00, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `cash_drop_log`
--

CREATE TABLE `cash_drop_log` (
  `id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `drop_amount` decimal(10,2) NOT NULL,
  `drop_reason` varchar(255) DEFAULT NULL,
  `drop_time` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `discount_type` enum('none','senior','pwd') DEFAULT 'none',
  `discount_id` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_templates`
--

CREATE TABLE `ingredient_templates` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit` varchar(20) DEFAULT 'piece',
  `category` varchar(50) DEFAULT 'Uncategorized',
  `default_min_stock` int(11) DEFAULT 10,
  `default_cost_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredient_templates`
--

INSERT INTO `ingredient_templates` (`id`, `item_name`, `unit`, `category`, `default_min_stock`, `default_cost_price`, `created_at`) VALUES
(1, 'Bacon', 'piece', 'Proteins', 10, 0.00, '2026-07-16 12:53:49'),
(2, 'Beef Patties', 'piece', 'Proteins', 10, 0.00, '2026-07-16 12:53:49'),
(3, 'Black Pepper Sauce', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(4, 'Bottled Water', 'bottle', 'General', 20, 0.00, '2026-07-16 12:53:49'),
(5, 'Burger Buns', 'piece', 'Bread & Buns', 50, 0.00, '2026-07-16 12:53:49'),
(6, 'Burger Patty', 'piece', 'Uncategorized', 10, 0.00, '2026-07-16 12:53:49'),
(7, 'Calamansi Syrup', 'ml', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(8, 'Cheese Slices', 'piece', 'Dairy', 100, 0.00, '2026-07-16 12:53:49'),
(9, 'Chicken Fillet', 'piece', 'Proteins', 10, 0.00, '2026-07-16 12:53:49'),
(10, 'Chimichurri Sauce', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(11, 'Chocolate Powder', 'scoop', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(12, 'Coffee Powder', 'scoop', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(13, 'Coleslaw Mix', 'portion', 'General', 20, 0.00, '2026-07-16 12:53:49'),
(14, 'Cup', 'piece', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(15, 'Egg', 'piece', 'General', 20, 0.00, '2026-07-16 12:53:49'),
(16, 'Fruit Syrup', 'ml', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(17, 'Ice', 'cup', 'General', 100, 0.00, '2026-07-16 12:53:49'),
(18, 'Ketchup', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(19, 'Lettuce', 'piece', 'Vegetables', 20, 0.00, '2026-07-16 12:53:49'),
(20, 'Mayo', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(21, 'Onions', 'piece', 'Vegetables', 25, 0.00, '2026-07-16 12:53:49'),
(22, 'Roasted Sesame Sauce', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(23, 'Shawarma Sauce', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(24, 'Steak Patty', 'piece', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(25, 'Supreme Cheese', 'piece', 'Cheese', 1000, 0.00, '2026-07-16 12:53:49'),
(26, 'Veggie Patty', 'piece', 'Proteins', 10, 0.00, '2026-07-16 12:53:49'),
(27, 'Water', 'ml', 'General', 100, 0.00, '2026-07-16 12:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'Uncategorized',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock` int(11) NOT NULL DEFAULT 10,
  `unit` varchar(20) DEFAULT 'piece',
  `selling_unit` varchar(50) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `is_perishable` tinyint(1) DEFAULT 0,
  `shelf_life_days` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `reorder_point` int(11) DEFAULT NULL,
  `location_rack` varchar(50) DEFAULT NULL,
  `location_shelf` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `template_id`, `branch_id`, `item_name`, `category`, `quantity`, `min_stock`, `unit`, `selling_unit`, `cost_price`, `last_updated`, `status`, `deleted_at`, `supplier`, `is_perishable`, `shelf_life_days`, `category_id`, `supplier_id`, `reorder_point`, `location_rack`, `location_shelf`, `expiry_date`) VALUES
(1, 5, 1, 'Burger Buns', 'Bread & Buns', 100, 50, 'piece', NULL, 0.00, '2026-08-31 10:40:34', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 6, 1, 'Burger Patty', 'Uncategorized', 100, 10, 'piece', NULL, 0.00, '2026-08-31 10:44:30', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 8, 1, 'Cheese Slices', 'Dairy', 2166, 100, 'piece', NULL, 0.00, '2026-08-31 11:50:14', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 19, 1, 'Lettuce', 'Vegetables', 227, 20, 'piece', NULL, 0.00, '2026-08-22 03:34:32', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 21, 1, 'Onions', 'Vegetables', 326, 25, 'piece', NULL, 0.00, '2026-08-22 03:34:32', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 2, 1, 'Beef Patties', 'Proteins', 45, 10, 'piece', NULL, 0.00, '2026-08-22 03:34:32', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 9, 1, 'Chicken Fillet', 'Proteins', 274, 10, 'piece', NULL, 0.00, '2026-08-22 03:34:32', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 26, 1, 'Veggie Patty', 'Proteins', 116, 10, 'piece', NULL, 0.00, '2026-08-22 02:03:08', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 24, 1, 'Steak Patty', 'General', 122, 10, 'piece', NULL, 0.00, '2026-08-21 15:33:39', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 3, 1, 'Black Pepper Sauce', 'General', 224, 10, 'portion', NULL, 0.00, '2026-08-22 02:03:08', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 10, 1, 'Chimichurri Sauce', 'General', 309, 10, 'portion', NULL, 0.00, '2026-08-22 03:34:32', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 22, 1, 'Roasted Sesame Sauce', 'General', 344, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 23, 1, 'Shawarma Sauce', 'General', 252, 10, 'portion', NULL, 0.00, '2026-08-22 03:34:32', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 15, 1, 'Egg', 'General', 62, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 13, 1, 'Coleslaw Mix', 'General', 180, 20, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 13, 1, 'Coleslaw Mix', 'General', 50, 20, 'portion', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 27, 1, 'Water', 'General', 798, 100, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 12, 1, 'Coffee Powder', 'General', 320, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 11, 1, 'Chocolate Powder', 'General', 320, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 7, 1, 'Calamansi Syrup', 'General', 318, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 16, 1, 'Fruit Syrup', 'General', 320, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(34, 14, 1, 'Cup', 'General', 318, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 4, 1, 'Bottled Water', 'General', 157, 20, 'bottle', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 3, 1, 'Black Pepper Sauce', 'General', 25, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 3, 1, 'Black Pepper Sauce', 'General', 78, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 10, 1, 'Chimichurri Sauce', 'General', 100, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 22, 1, 'Roasted Sesame Sauce', 'General', 100, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 23, 1, 'Shawarma Sauce', 'General', 62, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 20, 1, 'Mayo', 'General', 186, 10, 'portion', NULL, 0.00, '2026-08-22 03:34:32', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 18, 1, 'Ketchup', 'General', 256, 10, 'portion', NULL, 0.00, '2026-08-22 00:17:32', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 3, 1, 'Black Pepper Sauce', 'General', 78, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 10, 1, 'Chimichurri Sauce', 'General', 76, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 22, 1, 'Roasted Sesame Sauce', 'General', 100, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 23, 1, 'Shawarma Sauce', 'General', 88, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 20, 1, 'Mayo', 'General', 94, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 18, 1, 'Ketchup', 'General', 100, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 9, 1, 'Chicken Fillet', 'Proteins', 123, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 17, 1, 'Ice', 'General', 300, 100, 'cup', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 5, 1, 'Burger Buns', 'Bread & Buns', 60, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 8, 1, 'Cheese Slices', 'Dairy', 2070, 100, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(54, 19, 1, 'Lettuce', 'Vegetables', 129, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(55, 21, 1, 'Onions', 'Vegetables', 166, 25, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(56, 2, 1, 'Beef Patties', 'Proteins', 51, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(57, 1, 1, 'Bacon', 'Proteins', 33, 10, 'piece', NULL, 0.00, '2026-08-21 15:34:30', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(58, 26, 1, 'Veggie Patty', 'Proteins', 46, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(59, 24, 1, 'Steak Patty', 'General', 52, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(60, 22, 1, 'Roasted Sesame Sauce', 'General', 54, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(61, 15, 1, 'Egg', 'General', 23, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(62, 13, 1, 'Coleslaw Mix', 'General', 30, 20, 'portion', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(63, 27, 1, 'Water', 'General', 299, 100, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(64, 12, 1, 'Coffee Powder', 'General', 120, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(65, 11, 1, 'Chocolate Powder', 'General', 120, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, 7, 1, 'Calamansi Syrup', 'General', 119, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(67, 16, 1, 'Fruit Syrup', 'General', 120, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, 14, 1, 'Cup', 'General', 119, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, 4, 1, 'Bottled Water', 'General', 59, 20, 'bottle', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(70, 3, 1, 'Black Pepper Sauce', 'General', 29, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(71, 23, 1, 'Shawarma Sauce', 'General', 44, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(72, 20, 1, 'Mayo', 'General', 42, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(73, 18, 1, 'Ketchup', 'General', 60, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(74, 10, 1, 'Chimichurri Sauce', 'General', 45, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(75, 9, 1, 'Chicken Fillet', 'Proteins', 73, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(82, 25, 1, 'Supreme Cheese', 'Cheese', 2100, 1000, 'piece', NULL, 0.00, '2026-08-31 10:42:23', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(83, 1, 2, 'Bacon', 'Proteins', 181, 10, 'piece', NULL, 0.00, '2026-08-21 16:05:58', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(85, 2, 2, 'Beef Patties', 'Proteins', 176, 10, 'piece', NULL, 0.00, '2026-08-31 11:52:41', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(87, 3, 2, 'Black Pepper Sauce', 'General', 21, 10, 'portion', NULL, 0.00, '2026-08-31 11:52:41', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(89, 4, 2, 'Bottled Water', 'General', 96, 20, 'bottle', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(91, 5, 2, 'Burger Buns', 'Bread & Buns', 288, 50, 'piece', NULL, 0.00, '2026-08-31 11:52:41', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(93, 6, 2, 'Burger Patty', 'Uncategorized', 325, 10, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(95, 7, 2, 'Calamansi Syrup', 'General', 128, 50, 'ml', NULL, 0.00, '2026-07-18 04:36:08', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(97, 8, 2, 'Cheese Slices', 'Dairy', 154, 100, 'piece', NULL, 0.00, '2026-08-31 11:52:41', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(99, 9, 2, 'Chicken Fillet', 'Proteins', 95, 10, 'piece', NULL, 0.00, '2026-08-22 00:42:08', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(101, 10, 2, 'Chimichurri Sauce', 'General', 30, 10, 'portion', NULL, 0.00, '2026-08-22 00:42:08', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(103, 11, 2, 'Chocolate Powder', 'General', 118, 50, 'scoop', NULL, 0.00, '2026-07-18 04:36:16', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(105, 12, 2, 'Coffee Powder', 'General', 122, 50, 'scoop', NULL, 0.00, '2026-07-18 04:36:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(107, 13, 2, 'Coleslaw Mix', 'General', 30, 20, 'portion', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(109, 14, 2, 'Cup', 'General', 250, 50, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(111, 15, 2, 'Egg', 'General', 60, 20, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(113, 16, 2, 'Fruit Syrup', 'General', 124, 50, 'ml', NULL, 0.00, '2026-07-18 04:36:45', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(115, 17, 2, 'Ice', 'General', 400, 100, 'cup', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(117, 18, 2, 'Ketchup', 'General', 42, 10, 'portion', NULL, 0.00, '2026-08-22 00:43:09', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(119, 19, 2, 'Lettuce', 'Vegetables', 37, 20, 'piece', NULL, 0.00, '2026-08-31 11:52:41', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(121, 20, 2, 'Mayo', 'General', 26, 10, 'portion', NULL, 0.00, '2026-08-22 00:42:08', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(123, 21, 2, 'Onions', 'Vegetables', 84, 25, 'piece', NULL, 0.00, '2026-08-31 11:52:41', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(125, 22, 2, 'Roasted Sesame Sauce', 'General', 34, 10, 'portion', NULL, 0.00, '2026-08-22 00:42:08', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(127, 23, 2, 'Shawarma Sauce', 'General', 494, 10, 'portion', NULL, 0.00, '2026-08-31 11:52:41', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(129, 24, 2, 'Steak Patty', 'General', 91, 10, 'piece', NULL, 0.00, '2026-08-22 00:43:09', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(131, 25, 2, 'Supreme Cheese', 'Cheese', 1075, 1000, 'piece', NULL, 0.00, '2026-07-18 04:36:40', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(133, 26, 2, 'Veggie Patty', 'Proteins', 46, 10, 'piece', NULL, 0.00, '2026-08-22 00:42:08', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(135, 27, 2, 'Water', 'General', 200, 100, 'ml', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(137, 17, 1, 'Ice', 'General', 500, 100, 'cup', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(166, NULL, 2, 'Beef Patties', 'Uncategorized', 100, 10, 'piece', NULL, 0.00, '2026-08-21 15:11:32', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(167, NULL, NULL, 'Cheese', 'Cheese', 100, 10, 'piece', NULL, 0.00, '2026-08-31 10:43:48', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(168, NULL, NULL, 'Cheese', 'Cheese', 99, 48, 'piece', NULL, 0.00, '2026-08-31 10:51:23', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(169, NULL, 1, 'Cheese', 'Cheese', 98, 49, 'piece', NULL, 0.00, '2026-08-31 11:06:06', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_alerts`
--

CREATE TABLE `inventory_alerts` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `alert_type` enum('low_stock','expiring','expired','reorder') NOT NULL,
  `threshold_value` int(11) DEFAULT NULL,
  `current_value` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','read','resolved') DEFAULT 'new',
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_batches`
--

CREATE TABLE `inventory_batches` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `batch_quantity` int(11) NOT NULL,
  `remaining_quantity` int(11) NOT NULL,
  `received_at` datetime NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_batches`
--

INSERT INTO `inventory_batches` (`id`, `inventory_id`, `batch_number`, `batch_quantity`, `remaining_quantity`, `received_at`, `expiry_date`, `manufacture_date`, `purchase_order_id`, `cost_per_unit`, `notes`, `created_at`) VALUES
(1, 82, NULL, 1000, 0, '2026-07-04 03:11:55', '2026-08-04', NULL, NULL, NULL, NULL, '2026-07-04 09:11:55'),
(2, 82, NULL, 1000, 0, '2026-07-04 03:12:21', NULL, NULL, NULL, NULL, NULL, '2026-07-04 09:12:21'),
(3, 3, NULL, 100, 100, '2026-07-08 16:39:50', '2026-07-15', NULL, NULL, NULL, NULL, '2026-07-08 22:39:50'),
(4, 1, NULL, 100, 0, '2026-07-08 17:24:56', '2026-08-08', NULL, NULL, NULL, NULL, '2026-07-08 23:24:56'),
(5, 1, NULL, 100, 100, '2026-08-31 18:40:34', NULL, NULL, NULL, NULL, NULL, '2026-08-31 18:40:34'),
(6, 82, NULL, 100, 100, '2026-08-31 18:41:54', NULL, NULL, NULL, NULL, NULL, '2026-08-31 18:41:54'),
(7, 82, NULL, 2000, 2000, '2026-08-31 18:42:23', NULL, NULL, NULL, NULL, NULL, '2026-08-31 18:42:23'),
(8, 167, NULL, 100, 100, '2026-08-31 18:43:48', NULL, NULL, NULL, NULL, NULL, '2026-08-31 18:43:48'),
(9, 168, NULL, 99, 99, '2026-08-31 18:51:23', NULL, NULL, NULL, NULL, NULL, '2026-08-31 18:51:23'),
(10, 169, NULL, 98, 98, '2026-08-31 19:06:06', NULL, NULL, NULL, NULL, NULL, '2026-08-31 19:06:06');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_counts`
--

CREATE TABLE `inventory_counts` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `system_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `actual_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `difference` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `counted_by` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `counted_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_deliveries`
--

CREATE TABLE `inventory_deliveries` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `expected_date` date NOT NULL,
  `order_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('upcoming','completed','cancelled') DEFAULT 'upcoming',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_deliveries`
--

INSERT INTO `inventory_deliveries` (`id`, `inventory_id`, `supplier`, `expected_date`, `order_date`, `received_date`, `quantity`, `status`, `created_at`) VALUES
(1, 51, 'Minute Burger Commissary', '2026-03-27', '2026-03-24', NULL, 50, 'upcoming', '2026-03-24 01:54:59'),
(2, 52, 'Minute Burger Commissary', '2026-03-27', '2026-03-24', NULL, 50, 'upcoming', '2026-03-24 01:54:59'),
(3, 53, 'Minute Burger Commissary', '2026-03-27', '2026-03-24', NULL, 50, 'upcoming', '2026-03-24 01:54:59'),
(4, 54, 'Local Supplier', '2026-03-19', '2026-03-17', '2026-03-19', 100, 'completed', '2026-03-17 01:54:59'),
(5, 55, 'Local Supplier', '2026-03-19', '2026-03-17', '2026-03-19', 100, 'completed', '2026-03-17 01:54:59');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history`
--

CREATE TABLE `inventory_history` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `change_type` enum('sale','restock','adjustment','waste') NOT NULL,
  `change_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_history`
--

INSERT INTO `inventory_history` (`id`, `inventory_id`, `item_name`, `previous_quantity`, `new_quantity`, `quantity_change`, `change_type`, `change_date`, `notes`, `created_at`) VALUES
(1, 1, 'Burger Buns', 138, 136, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(2, 16, 'Beef Patties', 38, 36, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(3, 3, 'Cheese Slices', 156, 154, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(4, 4, 'Lettuce', 154, 152, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(5, 6, 'Onions', 216, 214, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(6, 21, 'Black Pepper Sauce', 78, 76, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(7, 36, 'Black Pepper Sauce', 25, 23, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(8, 37, 'Black Pepper Sauce', 78, 76, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(9, 43, 'Black Pepper Sauce', 78, 76, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(10, 85, 'Beef Patties', 216, 212, -4, 'sale', '2026-08-19', 'Order #78 - sale', '2026-08-19 05:30:26'),
(11, 91, 'Burger Buns', 336, 332, -4, 'sale', '2026-08-19', 'Order #78 - sale', '2026-08-19 05:30:26'),
(12, 97, 'Cheese Slices', 206, 202, -4, 'sale', '2026-08-19', 'Order #78 - sale', '2026-08-19 05:30:26'),
(13, 119, 'Lettuce', 81, 77, -4, 'sale', '2026-08-19', 'Order #78 - sale', '2026-08-19 05:30:26'),
(14, 123, 'Onions', 126, 122, -4, 'sale', '2026-08-19', 'Order #78 - sale', '2026-08-19 05:30:26'),
(15, 127, 'Shawarma Sauce', 31, 27, -4, 'sale', '2026-08-19', 'Order #78 - sale', '2026-08-19 05:30:26'),
(16, 127, 'Shawarma Sauce', 27, 23, -4, 'sale', '2026-08-19', 'Order #78 - sale', '2026-08-19 05:30:26'),
(17, 127, 'Shawarma Sauce', 23, 19, -4, 'sale', '2026-08-19', 'Order #78 - sale', '2026-08-19 05:30:26'),
(18, 85, 'Beef Patties', 216, 214, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(19, 91, 'Burger Buns', 336, 334, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(20, 97, 'Cheese Slices', 206, 204, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(21, 119, 'Lettuce', 81, 79, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(22, 123, 'Onions', 126, 124, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(23, 85, 'Beef Patties', 214, 212, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(24, 91, 'Burger Buns', 334, 332, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(25, 97, 'Cheese Slices', 204, 202, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(26, 119, 'Lettuce', 79, 77, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(27, 123, 'Onions', 124, 122, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(28, 127, 'Shawarma Sauce', 23, 21, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(29, 127, 'Shawarma Sauce', 21, 19, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(30, 127, 'Shawarma Sauce', 19, 17, -2, 'sale', '2026-08-21', 'Order #82 - sale', '2026-08-21 13:23:18'),
(31, 16, 'Beef Patties', 89, 87, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(32, 1, 'Burger Buns', 198, 196, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(33, 3, 'Cheese Slices', 2226, 2224, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(34, 4, 'Lettuce', 283, 281, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(35, 6, 'Onions', 382, 380, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(36, 24, 'Shawarma Sauce', 282, 280, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(37, 24, 'Shawarma Sauce', 280, 278, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(38, 24, 'Shawarma Sauce', 278, 276, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(39, 16, 'Beef Patties', 87, 85, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(40, 21, 'Black Pepper Sauce', 288, 286, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(41, 21, 'Black Pepper Sauce', 286, 284, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(42, 21, 'Black Pepper Sauce', 284, 282, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(43, 21, 'Black Pepper Sauce', 282, 280, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(44, 1, 'Burger Buns', 196, 194, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(45, 3, 'Cheese Slices', 2224, 2222, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(46, 4, 'Lettuce', 281, 279, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(47, 6, 'Onions', 380, 378, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(48, 1, 'Burger Buns', 194, 192, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(49, 3, 'Cheese Slices', 2222, 2220, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(50, 4, 'Lettuce', 279, 277, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(51, 6, 'Onions', 378, 376, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(52, 20, 'Steak Patty', 126, 124, -2, 'sale', '2026-08-21', 'Order #89 - sale', '2026-08-21 15:23:44'),
(53, 16, 'Beef Patties', 85, 83, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(54, 1, 'Burger Buns', 192, 190, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(55, 3, 'Cheese Slices', 2220, 2218, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(56, 4, 'Lettuce', 277, 275, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(57, 6, 'Onions', 376, 374, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(58, 24, 'Shawarma Sauce', 276, 274, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(59, 24, 'Shawarma Sauce', 274, 272, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(60, 24, 'Shawarma Sauce', 272, 270, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(61, 1, 'Burger Buns', 190, 188, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(62, 3, 'Cheese Slices', 2218, 2216, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(63, 4, 'Lettuce', 275, 273, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(64, 6, 'Onions', 374, 372, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(65, 20, 'Steak Patty', 124, 122, -2, 'sale', '2026-08-21', 'Order #90 - sale', '2026-08-21 15:33:39'),
(66, 1, 'Burger Buns', 188, 186, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(67, 3, 'Cheese Slices', 2216, 2214, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(68, 18, 'Chicken Fillet', 290, 288, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(69, 4, 'Lettuce', 273, 271, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(70, 6, 'Onions', 372, 370, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(71, 19, 'Veggie Patty', 124, 122, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(72, 16, 'Beef Patties', 83, 81, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(73, 1, 'Burger Buns', 186, 184, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(74, 3, 'Cheese Slices', 2214, 2212, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(75, 4, 'Lettuce', 271, 269, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(76, 41, 'Mayo', 206, 204, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(77, 41, 'Mayo', 204, 202, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(78, 6, 'Onions', 370, 368, -2, 'sale', '2026-08-22', 'Order #94 - sale', '2026-08-21 16:59:21'),
(79, 91, 'Burger Buns', 332, 330, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(80, 97, 'Cheese Slices', 202, 200, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(81, 99, 'Chicken Fillet', 115, 113, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(82, 119, 'Lettuce', 77, 75, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(83, 123, 'Onions', 122, 120, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(84, 133, 'Veggie Patty', 50, 48, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(85, 85, 'Beef Patties', 212, 210, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(86, 91, 'Burger Buns', 330, 328, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(87, 97, 'Cheese Slices', 200, 198, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(88, 119, 'Lettuce', 75, 73, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(89, 121, 'Mayo', 38, 36, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(90, 121, 'Mayo', 36, 34, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(91, 123, 'Onions', 120, 118, -2, 'sale', '2026-08-22', 'Order #95 - sale', '2026-08-21 17:00:04'),
(92, 16, 'Beef Patties', 81, 79, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(93, 21, 'Black Pepper Sauce', 280, 278, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(94, 21, 'Black Pepper Sauce', 278, 276, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(95, 21, 'Black Pepper Sauce', 276, 274, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(96, 21, 'Black Pepper Sauce', 274, 272, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(97, 1, 'Burger Buns', 184, 182, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(98, 3, 'Cheese Slices', 2212, 2210, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(99, 4, 'Lettuce', 269, 267, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(100, 6, 'Onions', 368, 366, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(101, 16, 'Beef Patties', 79, 77, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(102, 1, 'Burger Buns', 182, 180, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(103, 3, 'Cheese Slices', 2210, 2208, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(104, 4, 'Lettuce', 267, 265, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(105, 6, 'Onions', 366, 364, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(106, 24, 'Shawarma Sauce', 270, 268, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(107, 24, 'Shawarma Sauce', 268, 266, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(108, 24, 'Shawarma Sauce', 266, 264, -2, 'sale', '2026-08-22', 'Order #96 - sale', '2026-08-21 23:57:07'),
(109, 1, 'Burger Buns', 180, 178, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(110, 3, 'Cheese Slices', 2208, 2206, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(111, 18, 'Chicken Fillet', 288, 286, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(112, 18, 'Chicken Fillet', 286, 284, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(113, 22, 'Chimichurri Sauce', 321, 319, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(114, 22, 'Chimichurri Sauce', 319, 317, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(115, 22, 'Chimichurri Sauce', 317, 315, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(116, 4, 'Lettuce', 265, 263, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(117, 6, 'Onions', 364, 362, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(118, 16, 'Beef Patties', 77, 75, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(119, 21, 'Black Pepper Sauce', 272, 270, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(120, 21, 'Black Pepper Sauce', 270, 268, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(121, 21, 'Black Pepper Sauce', 268, 266, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(122, 21, 'Black Pepper Sauce', 266, 264, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(123, 1, 'Burger Buns', 178, 176, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(124, 3, 'Cheese Slices', 2206, 2204, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(125, 4, 'Lettuce', 263, 261, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(126, 6, 'Onions', 362, 360, -2, 'sale', '2026-08-22', 'Order #97 - sale', '2026-08-22 00:14:26'),
(127, 16, 'Beef Patties', 75, 71, -4, 'sale', '2026-08-22', 'Order #98 - sale', '2026-08-22 00:17:32'),
(128, 1, 'Burger Buns', 176, 174, -2, 'sale', '2026-08-22', 'Order #98 - sale', '2026-08-22 00:17:32'),
(129, 3, 'Cheese Slices', 2204, 2200, -4, 'sale', '2026-08-22', 'Order #98 - sale', '2026-08-22 00:17:32'),
(130, 42, 'Ketchup', 260, 258, -2, 'sale', '2026-08-22', 'Order #98 - sale', '2026-08-22 00:17:32'),
(131, 42, 'Ketchup', 258, 256, -2, 'sale', '2026-08-22', 'Order #98 - sale', '2026-08-22 00:17:32'),
(132, 16, 'Beef Patties', 71, 67, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(133, 21, 'Black Pepper Sauce', 264, 260, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(134, 21, 'Black Pepper Sauce', 260, 256, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(135, 21, 'Black Pepper Sauce', 256, 252, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(136, 21, 'Black Pepper Sauce', 252, 248, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(137, 1, 'Burger Buns', 174, 170, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(138, 3, 'Cheese Slices', 2200, 2196, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(139, 4, 'Lettuce', 261, 257, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(140, 6, 'Onions', 360, 356, -4, 'sale', '2026-08-22', 'Order #99 - sale', '2026-08-22 00:24:31'),
(141, 16, 'Beef Patties', 67, 63, -4, 'sale', '2026-08-22', 'Order #100 - sale', '2026-08-22 00:24:53'),
(142, 1, 'Burger Buns', 170, 166, -4, 'sale', '2026-08-22', 'Order #100 - sale', '2026-08-22 00:24:53'),
(143, 3, 'Cheese Slices', 2196, 2192, -4, 'sale', '2026-08-22', 'Order #100 - sale', '2026-08-22 00:24:53'),
(144, 4, 'Lettuce', 257, 253, -4, 'sale', '2026-08-22', 'Order #100 - sale', '2026-08-22 00:24:53'),
(145, 6, 'Onions', 356, 352, -4, 'sale', '2026-08-22', 'Order #100 - sale', '2026-08-22 00:24:53'),
(146, 1, 'Burger Buns', 166, 164, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(147, 3, 'Cheese Slices', 2192, 2190, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(148, 18, 'Chicken Fillet', 284, 282, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(149, 4, 'Lettuce', 253, 251, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(150, 6, 'Onions', 352, 350, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(151, 19, 'Veggie Patty', 122, 120, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(152, 16, 'Beef Patties', 63, 61, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(153, 1, 'Burger Buns', 164, 162, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(154, 3, 'Cheese Slices', 2190, 2188, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(155, 4, 'Lettuce', 251, 249, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(156, 41, 'Mayo', 202, 200, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(157, 41, 'Mayo', 200, 198, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(158, 6, 'Onions', 350, 348, -2, 'sale', '2026-08-22', 'Order #101 - sale', '2026-08-22 00:26:56'),
(159, 1, 'Burger Buns', 162, 160, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(160, 3, 'Cheese Slices', 2188, 2186, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(161, 18, 'Chicken Fillet', 282, 280, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(162, 4, 'Lettuce', 249, 247, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(163, 6, 'Onions', 348, 346, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(164, 19, 'Veggie Patty', 120, 118, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(165, 16, 'Beef Patties', 61, 59, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(166, 1, 'Burger Buns', 160, 158, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(167, 3, 'Cheese Slices', 2186, 2184, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(168, 4, 'Lettuce', 247, 245, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(169, 41, 'Mayo', 198, 196, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(170, 41, 'Mayo', 196, 194, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(171, 6, 'Onions', 346, 344, -2, 'sale', '2026-08-22', 'Order #102 - sale', '2026-08-22 00:35:33'),
(172, 16, 'Beef Patties', 59, 55, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(173, 21, 'Black Pepper Sauce', 248, 244, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(174, 21, 'Black Pepper Sauce', 244, 240, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(175, 21, 'Black Pepper Sauce', 240, 236, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(176, 21, 'Black Pepper Sauce', 236, 232, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(177, 1, 'Burger Buns', 158, 154, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(178, 3, 'Cheese Slices', 2184, 2180, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(179, 4, 'Lettuce', 245, 241, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(180, 6, 'Onions', 344, 340, -4, 'sale', '2026-08-22', 'Order #103 - sale', '2026-08-22 00:38:06'),
(181, 91, 'Burger Buns', 328, 326, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(182, 97, 'Cheese Slices', 198, 196, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(183, 99, 'Chicken Fillet', 113, 111, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(184, 119, 'Lettuce', 73, 71, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(185, 123, 'Onions', 118, 116, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(186, 133, 'Veggie Patty', 48, 46, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(187, 85, 'Beef Patties', 210, 208, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(188, 91, 'Burger Buns', 326, 324, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(189, 97, 'Cheese Slices', 196, 194, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(190, 119, 'Lettuce', 71, 69, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(191, 121, 'Mayo', 34, 32, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(192, 121, 'Mayo', 32, 30, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(193, 123, 'Onions', 116, 114, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(194, 85, 'Beef Patties', 208, 206, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(195, 91, 'Burger Buns', 324, 322, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(196, 97, 'Cheese Slices', 194, 192, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(197, 119, 'Lettuce', 69, 67, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(198, 123, 'Onions', 114, 112, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(199, 127, 'Shawarma Sauce', 17, 15, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(200, 127, 'Shawarma Sauce', 15, 13, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(201, 127, 'Shawarma Sauce', 13, 11, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(202, 85, 'Beef Patties', 206, 204, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(203, 87, 'Black Pepper Sauce', 45, 43, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(204, 87, 'Black Pepper Sauce', 43, 41, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(205, 87, 'Black Pepper Sauce', 41, 39, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(206, 87, 'Black Pepper Sauce', 39, 37, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(207, 91, 'Burger Buns', 322, 320, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(208, 97, 'Cheese Slices', 192, 190, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(209, 119, 'Lettuce', 67, 65, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(210, 123, 'Onions', 112, 110, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(211, 91, 'Burger Buns', 320, 318, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(212, 97, 'Cheese Slices', 190, 188, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(213, 99, 'Chicken Fillet', 111, 109, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(214, 99, 'Chicken Fillet', 109, 107, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(215, 101, 'Chimichurri Sauce', 36, 34, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(216, 101, 'Chimichurri Sauce', 34, 32, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(217, 101, 'Chimichurri Sauce', 32, 30, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(218, 119, 'Lettuce', 65, 63, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(219, 123, 'Onions', 110, 108, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(220, 91, 'Burger Buns', 318, 316, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(221, 97, 'Cheese Slices', 188, 186, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(222, 99, 'Chicken Fillet', 107, 105, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(223, 99, 'Chicken Fillet', 105, 103, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(224, 119, 'Lettuce', 63, 61, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(225, 123, 'Onions', 108, 106, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(226, 125, 'Roasted Sesame Sauce', 40, 38, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(227, 125, 'Roasted Sesame Sauce', 38, 36, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(228, 125, 'Roasted Sesame Sauce', 36, 34, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(229, 91, 'Burger Buns', 316, 314, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(230, 97, 'Cheese Slices', 186, 184, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(231, 119, 'Lettuce', 61, 59, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(232, 123, 'Onions', 106, 104, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(233, 129, 'Steak Patty', 95, 93, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(234, 85, 'Beef Patties', 204, 200, -4, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(235, 91, 'Burger Buns', 314, 312, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(236, 97, 'Cheese Slices', 184, 180, -4, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(237, 117, 'Ketchup', 50, 48, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(238, 117, 'Ketchup', 48, 46, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(239, 91, 'Burger Buns', 312, 310, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(240, 99, 'Chicken Fillet', 103, 99, -4, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(241, 99, 'Chicken Fillet', 99, 95, -4, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(242, 119, 'Lettuce', 59, 57, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(243, 121, 'Mayo', 30, 28, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(244, 121, 'Mayo', 28, 26, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(245, 85, 'Beef Patties', 200, 196, -4, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(246, 91, 'Burger Buns', 310, 308, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(247, 97, 'Cheese Slices', 180, 176, -4, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(248, 119, 'Lettuce', 57, 55, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(249, 123, 'Onions', 104, 102, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(250, 85, 'Beef Patties', 196, 194, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(251, 91, 'Burger Buns', 308, 306, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(252, 97, 'Cheese Slices', 176, 174, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(253, 119, 'Lettuce', 55, 53, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(254, 123, 'Onions', 102, 100, -2, 'sale', '2026-08-22', 'Order #104 - sale', '2026-08-22 00:42:08'),
(273, 85, 'Beef Patties', 194, 192, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(274, 91, 'Burger Buns', 306, 304, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(275, 97, 'Cheese Slices', 174, 172, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(276, 119, 'Lettuce', 53, 51, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(277, 123, 'Onions', 100, 98, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(278, 127, 'Shawarma Sauce', 11, 9, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(279, 127, 'Shawarma Sauce', 9, 7, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(280, 127, 'Shawarma Sauce', 7, 5, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(281, 85, 'Beef Patties', 192, 190, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(282, 87, 'Black Pepper Sauce', 37, 35, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(283, 87, 'Black Pepper Sauce', 35, 33, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(284, 87, 'Black Pepper Sauce', 33, 31, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(285, 87, 'Black Pepper Sauce', 31, 29, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(286, 91, 'Burger Buns', 304, 302, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(287, 97, 'Cheese Slices', 172, 170, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(288, 119, 'Lettuce', 51, 49, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(289, 123, 'Onions', 98, 96, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(290, 85, 'Beef Patties', 190, 186, -4, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(291, 91, 'Burger Buns', 302, 300, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(292, 97, 'Cheese Slices', 170, 166, -4, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(293, 117, 'Ketchup', 46, 44, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(294, 117, 'Ketchup', 44, 42, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(295, 91, 'Burger Buns', 300, 298, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(296, 97, 'Cheese Slices', 166, 164, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(297, 119, 'Lettuce', 49, 47, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(298, 123, 'Onions', 96, 94, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(299, 129, 'Steak Patty', 93, 91, -2, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(300, 85, 'Beef Patties', 186, 180, -6, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(301, 91, 'Burger Buns', 298, 292, -6, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(302, 97, 'Cheese Slices', 164, 158, -6, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(303, 119, 'Lettuce', 47, 41, -6, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(304, 123, 'Onions', 94, 88, -6, 'sale', '2026-08-22', 'Order #106 - sale', '2026-08-22 00:43:09'),
(305, 1, 'Burger Buns', 154, 152, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(306, 3, 'Cheese Slices', 2180, 2178, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(307, 18, 'Chicken Fillet', 280, 278, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(308, 4, 'Lettuce', 241, 239, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(309, 6, 'Onions', 340, 338, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(310, 19, 'Veggie Patty', 118, 116, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(311, 16, 'Beef Patties', 55, 53, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(312, 1, 'Burger Buns', 152, 150, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(313, 3, 'Cheese Slices', 2178, 2176, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(314, 4, 'Lettuce', 239, 237, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(315, 41, 'Mayo', 194, 192, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(316, 41, 'Mayo', 192, 190, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(317, 6, 'Onions', 338, 336, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(318, 16, 'Beef Patties', 53, 51, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(319, 1, 'Burger Buns', 150, 148, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(320, 3, 'Cheese Slices', 2176, 2174, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(321, 4, 'Lettuce', 237, 235, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(322, 6, 'Onions', 336, 334, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(323, 24, 'Shawarma Sauce', 264, 262, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(324, 24, 'Shawarma Sauce', 262, 260, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(325, 24, 'Shawarma Sauce', 260, 258, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(326, 16, 'Beef Patties', 51, 49, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(327, 21, 'Black Pepper Sauce', 232, 230, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(328, 21, 'Black Pepper Sauce', 230, 228, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(329, 21, 'Black Pepper Sauce', 228, 226, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(330, 21, 'Black Pepper Sauce', 226, 224, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(331, 1, 'Burger Buns', 148, 146, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(332, 3, 'Cheese Slices', 2174, 2172, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(333, 4, 'Lettuce', 235, 233, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(334, 6, 'Onions', 334, 332, -2, 'sale', '2026-08-22', 'Order #108 - sale', '2026-08-22 02:03:08'),
(335, 16, 'Beef Patties', 49, 47, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(336, 1, 'Burger Buns', 146, 144, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(337, 3, 'Cheese Slices', 2172, 2170, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(338, 4, 'Lettuce', 233, 231, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(339, 41, 'Mayo', 190, 188, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(340, 41, 'Mayo', 188, 186, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(341, 6, 'Onions', 332, 330, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(342, 16, 'Beef Patties', 47, 45, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(343, 1, 'Burger Buns', 144, 142, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(344, 3, 'Cheese Slices', 2170, 2168, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(345, 4, 'Lettuce', 231, 229, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(346, 6, 'Onions', 330, 328, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(347, 24, 'Shawarma Sauce', 258, 256, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(348, 24, 'Shawarma Sauce', 256, 254, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(349, 24, 'Shawarma Sauce', 254, 252, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(350, 1, 'Burger Buns', 142, 140, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(351, 3, 'Cheese Slices', 2168, 2166, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(352, 18, 'Chicken Fillet', 278, 276, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(353, 18, 'Chicken Fillet', 276, 274, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(354, 22, 'Chimichurri Sauce', 315, 313, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(355, 22, 'Chimichurri Sauce', 313, 311, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(356, 22, 'Chimichurri Sauce', 311, 309, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(357, 4, 'Lettuce', 229, 227, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(358, 6, 'Onions', 328, 326, -2, 'sale', '2026-08-22', 'Order #110 - sale', '2026-08-22 03:34:32'),
(359, 85, 'Beef Patties', 180, 178, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(360, 87, 'Black Pepper Sauce', 29, 27, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(361, 87, 'Black Pepper Sauce', 27, 25, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(362, 87, 'Black Pepper Sauce', 25, 23, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(363, 87, 'Black Pepper Sauce', 23, 21, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(364, 91, 'Burger Buns', 292, 290, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(365, 97, 'Cheese Slices', 158, 156, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(366, 119, 'Lettuce', 41, 39, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(367, 123, 'Onions', 88, 86, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(368, 85, 'Beef Patties', 178, 176, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(369, 91, 'Burger Buns', 290, 288, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(370, 97, 'Cheese Slices', 156, 154, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(371, 119, 'Lettuce', 39, 37, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(372, 123, 'Onions', 86, 84, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(373, 127, 'Shawarma Sauce', 500, 498, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(374, 127, 'Shawarma Sauce', 498, 496, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41'),
(375, 127, 'Shawarma Sauce', 496, 494, -2, 'sale', '2026-08-31', 'Order #111 - sale', '2026-08-31 11:52:41');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `quantity_added` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `update_date` datetime NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`id`, `item_id`, `item_name`, `previous_quantity`, `quantity_added`, `new_quantity`, `user_id`, `user_name`, `update_date`, `batch_id`, `action`, `performed_by`) VALUES
(1, 6, 'Onions', 0, 100, 100, 10, 'Brix Hallazgo Lico', '2026-03-22 16:00:34', NULL, NULL, NULL),
(2, 6, 'Onions', 100, 100, 200, 10, 'Brix Hallazgo Lico', '2026-03-22 16:05:18', NULL, NULL, NULL),
(3, 6, 'Onions', 200, 100, 300, 10, 'Brix Hallazgo Lico', '2026-03-22 16:05:21', NULL, NULL, NULL),
(4, 6, 'Onions', 300, 100, 400, 10, 'Brix Hallazgo Lico', '2026-03-22 16:05:38', NULL, NULL, NULL),
(5, 17, 'Bacon', 48, 2, 50, 10, 'Brix Hallazgo Lico', '2026-03-22 16:13:31', NULL, NULL, NULL),
(6, 17, 'Bacon', 50, 2, 52, 10, 'Brix Hallazgo Lico', '2026-03-22 16:17:52', NULL, NULL, NULL),
(7, 17, 'Bacon', 52, 2, 54, 10, 'Brix Hallazgo Lico', '2026-03-22 16:19:46', NULL, NULL, NULL),
(8, 17, 'Bacon', 54, 2, 56, 10, 'Brix Hallazgo Lico', '2026-03-22 16:22:48', NULL, NULL, NULL),
(9, 17, 'Bacon', 56, 2, 58, 10, 'Brix Hallazgo Lico', '2026-03-22 16:22:50', NULL, NULL, NULL),
(10, 17, 'Bacon', 58, 123, 181, 10, 'Brix Hallazgo Lico', '2026-03-22 16:23:01', NULL, NULL, NULL),
(11, 17, 'Bacon', 181, 123, 304, 10, 'Brix Hallazgo Lico', '2026-03-22 16:27:27', NULL, NULL, NULL),
(12, 4, 'Lettuce', 2, 300, 302, 10, 'Brix Hallazgo Lico', '2026-03-22 22:40:26', NULL, NULL, NULL),
(13, 16, 'Beef Patties', 24, 200, 224, 10, 'Brix Hallazgo Lico', '2026-03-22 22:42:55', NULL, NULL, NULL),
(14, 53, 'Cheese Slices', 70, 2000, 2070, 10, 'Brix Hallazgo Lico', '2026-03-24 08:57:45', NULL, NULL, NULL),
(42, 95, 'Calamansi Syrup', 28, 100, 128, 17, 'Jasaan Manager', '2026-07-18 12:36:08', NULL, NULL, NULL),
(44, 103, 'Chocolate Powder', 18, 100, 118, 17, 'Jasaan Manager', '2026-07-18 12:36:16', NULL, NULL, NULL),
(48, 131, 'Supreme Cheese', 75, 1000, 1075, 17, 'Jasaan Manager', '2026-07-18 12:36:40', NULL, NULL, NULL),
(49, 113, 'Fruit Syrup', 24, 100, 124, 17, 'Jasaan Manager', '2026-07-18 12:36:45', NULL, NULL, NULL),
(50, 105, 'Coffee Powder', 22, 100, 122, 17, 'Jasaan Manager', '2026-07-18 12:36:49', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `movement_type` enum('stock_in','stock_out','waste','adjustment','return') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_quantity` int(11) DEFAULT NULL,
  `new_quantity` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `inventory_id`, `batch_id`, `movement_type`, `quantity`, `previous_quantity`, `new_quantity`, `reference_type`, `reference_id`, `notes`, `reason`, `performed_by`, `branch_id`, `created_at`) VALUES
(2, 16, NULL, 'stock_in', 30, 56, 86, NULL, NULL, 'Beef patties delivery', NULL, NULL, 1, '2026-03-22 02:11:28'),
(3, 1, NULL, 'stock_out', 15, 115, 100, NULL, NULL, 'Used for morning shift', NULL, NULL, 1, '2026-03-23 02:11:28'),
(4, 3, NULL, 'stock_out', 12, 130, 118, NULL, NULL, 'Used for burger production', NULL, NULL, 1, '2026-03-23 02:11:28'),
(5, 25, NULL, 'waste', 5, 44, 39, NULL, NULL, 'Expired eggs disposed', NULL, NULL, 1, '2026-03-24 02:11:28'),
(6, 4, NULL, 'stock_in', 50, 166, 216, NULL, NULL, 'Fresh lettuce delivery', NULL, NULL, 1, '2026-03-21 02:11:28'),
(7, 6, NULL, 'stock_in', 100, 178, 278, NULL, NULL, 'Onion bulk order received', NULL, NULL, 1, '2026-03-20 02:11:28'),
(8, 35, NULL, 'stock_out', 20, 119, 99, NULL, NULL, 'Bottled water sold', NULL, NULL, 1, '2026-03-23 02:11:28'),
(9, 57, NULL, 'stock_in', 15, 18, 33, NULL, NULL, 'Bacon restock', NULL, NULL, 1, '2026-03-23 02:11:28'),
(10, 52, NULL, 'stock_in', 30, 30, 60, NULL, NULL, 'Buns delivery', NULL, NULL, 1, '2026-03-22 02:11:28'),
(11, 53, NULL, 'stock_out', 10, 80, 70, NULL, NULL, 'Cheese used for orders', NULL, NULL, 1, '2026-03-24 02:11:28'),
(12, 54, NULL, 'stock_in', 50, 79, 129, NULL, NULL, 'Lettuce from local supplier', NULL, NULL, 1, '2026-03-21 02:11:28'),
(13, 82, 1, 'stock_in', 1000, NULL, NULL, NULL, NULL, 'Opening stock', NULL, 2, 1, '2026-07-04 09:11:55'),
(14, 82, 2, 'stock_in', 1000, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2026-07-04 09:12:21'),
(15, 3, 3, 'stock_in', 100, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2026-07-08 22:39:50'),
(16, 1, 4, 'stock_in', 100, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2026-07-08 23:24:56'),
(17, 1, NULL, 'stock_out', 2, 138, 136, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(18, 16, NULL, 'stock_out', 2, 38, 36, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(19, 3, NULL, 'stock_out', 2, 156, 154, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(20, 4, NULL, 'stock_out', 2, 154, 152, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(21, 6, NULL, 'stock_out', 2, 216, 214, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(22, 21, NULL, 'stock_out', 2, 78, 76, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(23, 36, NULL, 'stock_out', 2, 25, 23, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(24, 37, NULL, 'stock_out', 2, 78, 76, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(25, 43, NULL, 'stock_out', 2, 78, 76, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(26, 85, NULL, 'stock_out', 4, 216, 212, 'order', 78, 'Order #78 - sale', 'sale', 5, 2, '2026-08-19 13:30:26'),
(27, 91, NULL, 'stock_out', 4, 336, 332, 'order', 78, 'Order #78 - sale', 'sale', 5, 2, '2026-08-19 13:30:26'),
(28, 97, NULL, 'stock_out', 4, 206, 202, 'order', 78, 'Order #78 - sale', 'sale', 5, 2, '2026-08-19 13:30:26'),
(29, 119, NULL, 'stock_out', 4, 81, 77, 'order', 78, 'Order #78 - sale', 'sale', 5, 2, '2026-08-19 13:30:26'),
(30, 123, NULL, 'stock_out', 4, 126, 122, 'order', 78, 'Order #78 - sale', 'sale', 5, 2, '2026-08-19 13:30:26'),
(31, 127, NULL, 'stock_out', 4, 31, 27, 'order', 78, 'Order #78 - sale', 'sale', 5, 2, '2026-08-19 13:30:26'),
(32, 127, NULL, 'stock_out', 4, 27, 23, 'order', 78, 'Order #78 - sale', 'sale', 5, 2, '2026-08-19 13:30:26'),
(33, 127, NULL, 'stock_out', 4, 23, 19, 'order', 78, 'Order #78 - sale', 'sale', 5, 2, '2026-08-19 13:30:26'),
(34, 85, NULL, 'stock_out', 2, 216, 214, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(35, 91, NULL, 'stock_out', 2, 336, 334, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(36, 97, NULL, 'stock_out', 2, 206, 204, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(37, 119, NULL, 'stock_out', 2, 81, 79, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(38, 123, NULL, 'stock_out', 2, 126, 124, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(39, 85, NULL, 'stock_out', 2, 214, 212, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(40, 91, NULL, 'stock_out', 2, 334, 332, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(41, 97, NULL, 'stock_out', 2, 204, 202, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(42, 119, NULL, 'stock_out', 2, 79, 77, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(43, 123, NULL, 'stock_out', 2, 124, 122, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(44, 127, NULL, 'stock_out', 2, 23, 21, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(45, 127, NULL, 'stock_out', 2, 21, 19, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(46, 127, NULL, 'stock_out', 2, 19, 17, 'order', 82, 'Order #82 - sale', 'sale', 5, 2, '2026-08-21 21:23:18'),
(52, 16, NULL, 'stock_out', 2, 89, 87, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(53, 1, NULL, 'stock_out', 2, 198, 196, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(54, 3, NULL, 'stock_out', 2, 2226, 2224, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(55, 4, NULL, 'stock_out', 2, 283, 281, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(56, 6, NULL, 'stock_out', 2, 382, 380, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(57, 24, NULL, 'stock_out', 2, 282, 280, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(58, 24, NULL, 'stock_out', 2, 280, 278, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(59, 24, NULL, 'stock_out', 2, 278, 276, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(60, 16, NULL, 'stock_out', 2, 87, 85, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(61, 21, NULL, 'stock_out', 2, 288, 286, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(62, 21, NULL, 'stock_out', 2, 286, 284, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(63, 21, NULL, 'stock_out', 2, 284, 282, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(64, 21, NULL, 'stock_out', 2, 282, 280, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(65, 1, NULL, 'stock_out', 2, 196, 194, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(66, 3, NULL, 'stock_out', 2, 2224, 2222, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(67, 4, NULL, 'stock_out', 2, 281, 279, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(68, 6, NULL, 'stock_out', 2, 380, 378, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(69, 1, NULL, 'stock_out', 2, 194, 192, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(70, 3, NULL, 'stock_out', 2, 2222, 2220, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(71, 4, NULL, 'stock_out', 2, 279, 277, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(72, 6, NULL, 'stock_out', 2, 378, 376, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(73, 20, NULL, 'stock_out', 2, 126, 124, 'order', 89, 'Order #89 - sale', 'sale', 1, 1, '2026-08-21 23:23:44'),
(74, 16, NULL, 'stock_out', 2, 85, 83, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(75, 1, NULL, 'stock_out', 2, 192, 190, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(76, 3, NULL, 'stock_out', 2, 2220, 2218, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(77, 4, NULL, 'stock_out', 2, 277, 275, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(78, 6, NULL, 'stock_out', 2, 376, 374, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(79, 24, NULL, 'stock_out', 2, 276, 274, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(80, 24, NULL, 'stock_out', 2, 274, 272, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(81, 24, NULL, 'stock_out', 2, 272, 270, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(82, 1, NULL, 'stock_out', 2, 190, 188, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(83, 3, NULL, 'stock_out', 2, 2218, 2216, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(84, 4, NULL, 'stock_out', 2, 275, 273, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(85, 6, NULL, 'stock_out', 2, 374, 372, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(86, 20, NULL, 'stock_out', 2, 124, 122, 'order', 90, 'Order #90 - sale', 'sale', 15, 1, '2026-08-21 23:33:39'),
(87, 1, NULL, 'stock_out', 2, 188, 186, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(88, 3, NULL, 'stock_out', 2, 2216, 2214, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(89, 18, NULL, 'stock_out', 2, 290, 288, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(90, 4, NULL, 'stock_out', 2, 273, 271, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(91, 6, NULL, 'stock_out', 2, 372, 370, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(92, 19, NULL, 'stock_out', 2, 124, 122, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(93, 16, NULL, 'stock_out', 2, 83, 81, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(94, 1, NULL, 'stock_out', 2, 186, 184, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(95, 3, NULL, 'stock_out', 2, 2214, 2212, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(96, 4, NULL, 'stock_out', 2, 271, 269, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(97, 41, NULL, 'stock_out', 2, 206, 204, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(98, 41, NULL, 'stock_out', 2, 204, 202, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(99, 6, NULL, 'stock_out', 2, 370, 368, 'order', 94, 'Order #94 - sale', 'sale', 15, 1, '2026-08-22 00:59:21'),
(100, 91, NULL, 'stock_out', 2, 332, 330, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(101, 97, NULL, 'stock_out', 2, 202, 200, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(102, 99, NULL, 'stock_out', 2, 115, 113, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(103, 119, NULL, 'stock_out', 2, 77, 75, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(104, 123, NULL, 'stock_out', 2, 122, 120, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(105, 133, NULL, 'stock_out', 2, 50, 48, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(106, 85, NULL, 'stock_out', 2, 212, 210, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(107, 91, NULL, 'stock_out', 2, 330, 328, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(108, 97, NULL, 'stock_out', 2, 200, 198, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(109, 119, NULL, 'stock_out', 2, 75, 73, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(110, 121, NULL, 'stock_out', 2, 38, 36, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(111, 121, NULL, 'stock_out', 2, 36, 34, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(112, 123, NULL, 'stock_out', 2, 120, 118, 'order', 95, 'Order #95 - sale', 'sale', 5, 2, '2026-08-22 01:00:04'),
(113, 16, NULL, 'stock_out', 2, 81, 79, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(114, 21, NULL, 'stock_out', 2, 280, 278, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(115, 21, NULL, 'stock_out', 2, 278, 276, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(116, 21, NULL, 'stock_out', 2, 276, 274, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(117, 21, NULL, 'stock_out', 2, 274, 272, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(118, 1, NULL, 'stock_out', 2, 184, 182, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(119, 3, NULL, 'stock_out', 2, 2212, 2210, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(120, 4, NULL, 'stock_out', 2, 269, 267, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(121, 6, NULL, 'stock_out', 2, 368, 366, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(122, 16, NULL, 'stock_out', 2, 79, 77, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(123, 1, NULL, 'stock_out', 2, 182, 180, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(124, 3, NULL, 'stock_out', 2, 2210, 2208, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(125, 4, NULL, 'stock_out', 2, 267, 265, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(126, 6, NULL, 'stock_out', 2, 366, 364, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(127, 24, NULL, 'stock_out', 2, 270, 268, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(128, 24, NULL, 'stock_out', 2, 268, 266, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(129, 24, NULL, 'stock_out', 2, 266, 264, 'order', 96, 'Order #96 - sale', 'sale', 1, 1, '2026-08-22 07:57:07'),
(130, 1, NULL, 'stock_out', 2, 180, 178, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(131, 3, NULL, 'stock_out', 2, 2208, 2206, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(132, 18, NULL, 'stock_out', 2, 288, 286, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(133, 18, NULL, 'stock_out', 2, 286, 284, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(134, 22, NULL, 'stock_out', 2, 321, 319, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(135, 22, NULL, 'stock_out', 2, 319, 317, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(136, 22, NULL, 'stock_out', 2, 317, 315, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(137, 4, NULL, 'stock_out', 2, 265, 263, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(138, 6, NULL, 'stock_out', 2, 364, 362, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(139, 16, NULL, 'stock_out', 2, 77, 75, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(140, 21, NULL, 'stock_out', 2, 272, 270, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(141, 21, NULL, 'stock_out', 2, 270, 268, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(142, 21, NULL, 'stock_out', 2, 268, 266, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(143, 21, NULL, 'stock_out', 2, 266, 264, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(144, 1, NULL, 'stock_out', 2, 178, 176, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(145, 3, NULL, 'stock_out', 2, 2206, 2204, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(146, 4, NULL, 'stock_out', 2, 263, 261, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(147, 6, NULL, 'stock_out', 2, 362, 360, 'order', 97, 'Order #97 - sale', 'sale', 1, 1, '2026-08-22 08:14:26'),
(148, 16, NULL, 'stock_out', 4, 75, 71, 'order', 98, 'Order #98 - sale', 'sale', 1, 1, '2026-08-22 08:17:32'),
(149, 1, NULL, 'stock_out', 2, 176, 174, 'order', 98, 'Order #98 - sale', 'sale', 1, 1, '2026-08-22 08:17:32'),
(150, 3, NULL, 'stock_out', 4, 2204, 2200, 'order', 98, 'Order #98 - sale', 'sale', 1, 1, '2026-08-22 08:17:32'),
(151, 42, NULL, 'stock_out', 2, 260, 258, 'order', 98, 'Order #98 - sale', 'sale', 1, 1, '2026-08-22 08:17:32'),
(152, 42, NULL, 'stock_out', 2, 258, 256, 'order', 98, 'Order #98 - sale', 'sale', 1, 1, '2026-08-22 08:17:32'),
(153, 16, NULL, 'stock_out', 4, 71, 67, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(154, 21, NULL, 'stock_out', 4, 264, 260, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(155, 21, NULL, 'stock_out', 4, 260, 256, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(156, 21, NULL, 'stock_out', 4, 256, 252, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(157, 21, NULL, 'stock_out', 4, 252, 248, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(158, 1, NULL, 'stock_out', 4, 174, 170, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(159, 3, NULL, 'stock_out', 4, 2200, 2196, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(160, 4, NULL, 'stock_out', 4, 261, 257, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(161, 6, NULL, 'stock_out', 4, 360, 356, 'order', 99, 'Order #99 - sale', 'sale', 1, 1, '2026-08-22 08:24:31'),
(162, 16, NULL, 'stock_out', 4, 67, 63, 'order', 100, 'Order #100 - sale', 'sale', 1, 1, '2026-08-22 08:24:53'),
(163, 1, NULL, 'stock_out', 4, 170, 166, 'order', 100, 'Order #100 - sale', 'sale', 1, 1, '2026-08-22 08:24:53'),
(164, 3, NULL, 'stock_out', 4, 2196, 2192, 'order', 100, 'Order #100 - sale', 'sale', 1, 1, '2026-08-22 08:24:53'),
(165, 4, NULL, 'stock_out', 4, 257, 253, 'order', 100, 'Order #100 - sale', 'sale', 1, 1, '2026-08-22 08:24:53'),
(166, 6, NULL, 'stock_out', 4, 356, 352, 'order', 100, 'Order #100 - sale', 'sale', 1, 1, '2026-08-22 08:24:53'),
(167, 1, NULL, 'stock_out', 2, 166, 164, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(168, 3, NULL, 'stock_out', 2, 2192, 2190, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(169, 18, NULL, 'stock_out', 2, 284, 282, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(170, 4, NULL, 'stock_out', 2, 253, 251, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(171, 6, NULL, 'stock_out', 2, 352, 350, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(172, 19, NULL, 'stock_out', 2, 122, 120, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(173, 16, NULL, 'stock_out', 2, 63, 61, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(174, 1, NULL, 'stock_out', 2, 164, 162, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(175, 3, NULL, 'stock_out', 2, 2190, 2188, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(176, 4, NULL, 'stock_out', 2, 251, 249, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(177, 41, NULL, 'stock_out', 2, 202, 200, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(178, 41, NULL, 'stock_out', 2, 200, 198, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(179, 6, NULL, 'stock_out', 2, 350, 348, 'order', 101, 'Order #101 - sale', 'sale', 15, 1, '2026-08-22 08:26:56'),
(180, 1, NULL, 'stock_out', 2, 162, 160, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(181, 3, NULL, 'stock_out', 2, 2188, 2186, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(182, 18, NULL, 'stock_out', 2, 282, 280, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(183, 4, NULL, 'stock_out', 2, 249, 247, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(184, 6, NULL, 'stock_out', 2, 348, 346, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(185, 19, NULL, 'stock_out', 2, 120, 118, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(186, 16, NULL, 'stock_out', 2, 61, 59, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(187, 1, NULL, 'stock_out', 2, 160, 158, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(188, 3, NULL, 'stock_out', 2, 2186, 2184, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(189, 4, NULL, 'stock_out', 2, 247, 245, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(190, 41, NULL, 'stock_out', 2, 198, 196, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(191, 41, NULL, 'stock_out', 2, 196, 194, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(192, 6, NULL, 'stock_out', 2, 346, 344, 'order', 102, 'Order #102 - sale', 'sale', 1, 1, '2026-08-22 08:35:33'),
(193, 16, NULL, 'stock_out', 4, 59, 55, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(194, 21, NULL, 'stock_out', 4, 248, 244, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(195, 21, NULL, 'stock_out', 4, 244, 240, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(196, 21, NULL, 'stock_out', 4, 240, 236, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(197, 21, NULL, 'stock_out', 4, 236, 232, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(198, 1, NULL, 'stock_out', 4, 158, 154, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(199, 3, NULL, 'stock_out', 4, 2184, 2180, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(200, 4, NULL, 'stock_out', 4, 245, 241, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(201, 6, NULL, 'stock_out', 4, 344, 340, 'order', 103, 'Order #103 - sale', 'sale', 1, 1, '2026-08-22 08:38:06'),
(202, 91, NULL, 'stock_out', 2, 328, 326, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(203, 97, NULL, 'stock_out', 2, 198, 196, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(204, 99, NULL, 'stock_out', 2, 113, 111, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(205, 119, NULL, 'stock_out', 2, 73, 71, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(206, 123, NULL, 'stock_out', 2, 118, 116, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(207, 133, NULL, 'stock_out', 2, 48, 46, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(208, 85, NULL, 'stock_out', 2, 210, 208, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(209, 91, NULL, 'stock_out', 2, 326, 324, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(210, 97, NULL, 'stock_out', 2, 196, 194, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(211, 119, NULL, 'stock_out', 2, 71, 69, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(212, 121, NULL, 'stock_out', 2, 34, 32, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(213, 121, NULL, 'stock_out', 2, 32, 30, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(214, 123, NULL, 'stock_out', 2, 116, 114, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(215, 85, NULL, 'stock_out', 2, 208, 206, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(216, 91, NULL, 'stock_out', 2, 324, 322, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(217, 97, NULL, 'stock_out', 2, 194, 192, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(218, 119, NULL, 'stock_out', 2, 69, 67, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(219, 123, NULL, 'stock_out', 2, 114, 112, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(220, 127, NULL, 'stock_out', 2, 17, 15, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(221, 127, NULL, 'stock_out', 2, 15, 13, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(222, 127, NULL, 'stock_out', 2, 13, 11, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(223, 85, NULL, 'stock_out', 2, 206, 204, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(224, 87, NULL, 'stock_out', 2, 45, 43, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(225, 87, NULL, 'stock_out', 2, 43, 41, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(226, 87, NULL, 'stock_out', 2, 41, 39, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(227, 87, NULL, 'stock_out', 2, 39, 37, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(228, 91, NULL, 'stock_out', 2, 322, 320, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(229, 97, NULL, 'stock_out', 2, 192, 190, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(230, 119, NULL, 'stock_out', 2, 67, 65, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(231, 123, NULL, 'stock_out', 2, 112, 110, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(232, 91, NULL, 'stock_out', 2, 320, 318, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(233, 97, NULL, 'stock_out', 2, 190, 188, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(234, 99, NULL, 'stock_out', 2, 111, 109, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(235, 99, NULL, 'stock_out', 2, 109, 107, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(236, 101, NULL, 'stock_out', 2, 36, 34, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(237, 101, NULL, 'stock_out', 2, 34, 32, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(238, 101, NULL, 'stock_out', 2, 32, 30, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(239, 119, NULL, 'stock_out', 2, 65, 63, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(240, 123, NULL, 'stock_out', 2, 110, 108, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(241, 91, NULL, 'stock_out', 2, 318, 316, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(242, 97, NULL, 'stock_out', 2, 188, 186, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(243, 99, NULL, 'stock_out', 2, 107, 105, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(244, 99, NULL, 'stock_out', 2, 105, 103, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(245, 119, NULL, 'stock_out', 2, 63, 61, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(246, 123, NULL, 'stock_out', 2, 108, 106, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(247, 125, NULL, 'stock_out', 2, 40, 38, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(248, 125, NULL, 'stock_out', 2, 38, 36, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(249, 125, NULL, 'stock_out', 2, 36, 34, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(250, 91, NULL, 'stock_out', 2, 316, 314, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(251, 97, NULL, 'stock_out', 2, 186, 184, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(252, 119, NULL, 'stock_out', 2, 61, 59, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(253, 123, NULL, 'stock_out', 2, 106, 104, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(254, 129, NULL, 'stock_out', 2, 95, 93, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(255, 85, NULL, 'stock_out', 4, 204, 200, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(256, 91, NULL, 'stock_out', 2, 314, 312, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(257, 97, NULL, 'stock_out', 4, 184, 180, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(258, 117, NULL, 'stock_out', 2, 50, 48, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(259, 117, NULL, 'stock_out', 2, 48, 46, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(260, 91, NULL, 'stock_out', 2, 312, 310, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(261, 99, NULL, 'stock_out', 4, 103, 99, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(262, 99, NULL, 'stock_out', 4, 99, 95, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(263, 119, NULL, 'stock_out', 2, 59, 57, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(264, 121, NULL, 'stock_out', 2, 30, 28, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(265, 121, NULL, 'stock_out', 2, 28, 26, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(266, 85, NULL, 'stock_out', 4, 200, 196, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(267, 91, NULL, 'stock_out', 2, 310, 308, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(268, 97, NULL, 'stock_out', 4, 180, 176, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(269, 119, NULL, 'stock_out', 2, 57, 55, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(270, 123, NULL, 'stock_out', 2, 104, 102, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(271, 85, NULL, 'stock_out', 2, 196, 194, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(272, 91, NULL, 'stock_out', 2, 308, 306, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(273, 97, NULL, 'stock_out', 2, 176, 174, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(274, 119, NULL, 'stock_out', 2, 55, 53, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(275, 123, NULL, 'stock_out', 2, 102, 100, 'order', 104, 'Order #104 - sale', 'sale', 3, 2, '2026-08-22 08:42:08'),
(294, 85, NULL, 'stock_out', 2, 194, 192, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(295, 91, NULL, 'stock_out', 2, 306, 304, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(296, 97, NULL, 'stock_out', 2, 174, 172, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(297, 119, NULL, 'stock_out', 2, 53, 51, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(298, 123, NULL, 'stock_out', 2, 100, 98, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(299, 127, NULL, 'stock_out', 2, 11, 9, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(300, 127, NULL, 'stock_out', 2, 9, 7, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(301, 127, NULL, 'stock_out', 2, 7, 5, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(302, 85, NULL, 'stock_out', 2, 192, 190, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(303, 87, NULL, 'stock_out', 2, 37, 35, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(304, 87, NULL, 'stock_out', 2, 35, 33, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(305, 87, NULL, 'stock_out', 2, 33, 31, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(306, 87, NULL, 'stock_out', 2, 31, 29, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(307, 91, NULL, 'stock_out', 2, 304, 302, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(308, 97, NULL, 'stock_out', 2, 172, 170, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(309, 119, NULL, 'stock_out', 2, 51, 49, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(310, 123, NULL, 'stock_out', 2, 98, 96, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(311, 85, NULL, 'stock_out', 4, 190, 186, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(312, 91, NULL, 'stock_out', 2, 302, 300, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(313, 97, NULL, 'stock_out', 4, 170, 166, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(314, 117, NULL, 'stock_out', 2, 46, 44, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(315, 117, NULL, 'stock_out', 2, 44, 42, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(316, 91, NULL, 'stock_out', 2, 300, 298, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(317, 97, NULL, 'stock_out', 2, 166, 164, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(318, 119, NULL, 'stock_out', 2, 49, 47, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(319, 123, NULL, 'stock_out', 2, 96, 94, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(320, 129, NULL, 'stock_out', 2, 93, 91, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(321, 85, NULL, 'stock_out', 6, 186, 180, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(322, 91, NULL, 'stock_out', 6, 298, 292, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(323, 97, NULL, 'stock_out', 6, 164, 158, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(324, 119, NULL, 'stock_out', 6, 47, 41, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(325, 123, NULL, 'stock_out', 6, 94, 88, 'order', 106, 'Order #106 - sale', 'sale', 3, 2, '2026-08-22 08:43:09'),
(326, 1, NULL, 'stock_out', 2, 154, 152, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(327, 3, NULL, 'stock_out', 2, 2180, 2178, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(328, 18, NULL, 'stock_out', 2, 280, 278, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(329, 4, NULL, 'stock_out', 2, 241, 239, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(330, 6, NULL, 'stock_out', 2, 340, 338, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(331, 19, NULL, 'stock_out', 2, 118, 116, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(332, 16, NULL, 'stock_out', 2, 55, 53, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(333, 1, NULL, 'stock_out', 2, 152, 150, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(334, 3, NULL, 'stock_out', 2, 2178, 2176, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(335, 4, NULL, 'stock_out', 2, 239, 237, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(336, 41, NULL, 'stock_out', 2, 194, 192, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(337, 41, NULL, 'stock_out', 2, 192, 190, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(338, 6, NULL, 'stock_out', 2, 338, 336, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(339, 16, NULL, 'stock_out', 2, 53, 51, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(340, 1, NULL, 'stock_out', 2, 150, 148, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(341, 3, NULL, 'stock_out', 2, 2176, 2174, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(342, 4, NULL, 'stock_out', 2, 237, 235, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(343, 6, NULL, 'stock_out', 2, 336, 334, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(344, 24, NULL, 'stock_out', 2, 264, 262, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(345, 24, NULL, 'stock_out', 2, 262, 260, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(346, 24, NULL, 'stock_out', 2, 260, 258, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(347, 16, NULL, 'stock_out', 2, 51, 49, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(348, 21, NULL, 'stock_out', 2, 232, 230, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(349, 21, NULL, 'stock_out', 2, 230, 228, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(350, 21, NULL, 'stock_out', 2, 228, 226, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(351, 21, NULL, 'stock_out', 2, 226, 224, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(352, 1, NULL, 'stock_out', 2, 148, 146, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(353, 3, NULL, 'stock_out', 2, 2174, 2172, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(354, 4, NULL, 'stock_out', 2, 235, 233, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(355, 6, NULL, 'stock_out', 2, 334, 332, 'order', 108, 'Order #108 - sale', 'sale', 15, 1, '2026-08-22 10:03:08'),
(356, 16, NULL, 'stock_out', 2, 49, 47, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(357, 1, NULL, 'stock_out', 2, 146, 144, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(358, 3, NULL, 'stock_out', 2, 2172, 2170, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(359, 4, NULL, 'stock_out', 2, 233, 231, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(360, 41, NULL, 'stock_out', 2, 190, 188, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(361, 41, NULL, 'stock_out', 2, 188, 186, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(362, 6, NULL, 'stock_out', 2, 332, 330, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(363, 16, NULL, 'stock_out', 2, 47, 45, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(364, 1, NULL, 'stock_out', 2, 144, 142, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(365, 3, NULL, 'stock_out', 2, 2170, 2168, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(366, 4, NULL, 'stock_out', 2, 231, 229, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(367, 6, NULL, 'stock_out', 2, 330, 328, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(368, 24, NULL, 'stock_out', 2, 258, 256, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(369, 24, NULL, 'stock_out', 2, 256, 254, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(370, 24, NULL, 'stock_out', 2, 254, 252, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(371, 1, NULL, 'stock_out', 2, 142, 140, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(372, 3, NULL, 'stock_out', 2, 2168, 2166, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(373, 18, NULL, 'stock_out', 2, 278, 276, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(374, 18, NULL, 'stock_out', 2, 276, 274, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(375, 22, NULL, 'stock_out', 2, 315, 313, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(376, 22, NULL, 'stock_out', 2, 313, 311, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(377, 22, NULL, 'stock_out', 2, 311, 309, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(378, 4, NULL, 'stock_out', 2, 229, 227, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(379, 6, NULL, 'stock_out', 2, 328, 326, 'order', 110, 'Order #110 - sale', 'sale', 15, 1, '2026-08-22 11:34:32'),
(382, 1, 4, 'waste', 50, NULL, NULL, NULL, NULL, 'Expired', NULL, 15, NULL, '2026-08-31 18:33:26'),
(383, 1, 4, 'waste', 50, NULL, NULL, NULL, NULL, 'Expired', NULL, 15, NULL, '2026-08-31 18:33:39'),
(384, 1, 5, 'stock_in', 100, NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, '2026-08-31 18:40:34'),
(385, 82, 1, 'waste', 1000, NULL, NULL, NULL, NULL, 'Expired', NULL, 15, NULL, '2026-08-31 18:41:44'),
(386, 82, 2, 'waste', 1000, NULL, NULL, NULL, NULL, 'Expired', NULL, 15, NULL, '2026-08-31 18:41:44'),
(387, 82, 6, 'stock_in', 100, NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, '2026-08-31 18:41:54'),
(388, 82, 7, 'stock_in', 2000, NULL, NULL, NULL, NULL, NULL, NULL, 15, NULL, '2026-08-31 18:42:23'),
(389, 167, 8, 'stock_in', 100, NULL, NULL, NULL, NULL, 'Opening stock', NULL, 15, NULL, '2026-08-31 18:43:48'),
(390, 168, 9, 'stock_in', 99, NULL, NULL, NULL, NULL, 'Opening stock', NULL, 15, NULL, '2026-08-31 18:51:23'),
(391, 169, 10, 'stock_in', 98, NULL, NULL, NULL, NULL, 'Opening stock', NULL, 15, NULL, '2026-08-31 19:06:06'),
(392, 85, NULL, 'stock_out', 2, 180, 178, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(393, 87, NULL, 'stock_out', 2, 29, 27, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(394, 87, NULL, 'stock_out', 2, 27, 25, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(395, 87, NULL, 'stock_out', 2, 25, 23, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(396, 87, NULL, 'stock_out', 2, 23, 21, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(397, 91, NULL, 'stock_out', 2, 292, 290, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(398, 97, NULL, 'stock_out', 2, 158, 156, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(399, 119, NULL, 'stock_out', 2, 41, 39, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(400, 123, NULL, 'stock_out', 2, 88, 86, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(401, 85, NULL, 'stock_out', 2, 178, 176, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(402, 91, NULL, 'stock_out', 2, 290, 288, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(403, 97, NULL, 'stock_out', 2, 156, 154, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(404, 119, NULL, 'stock_out', 2, 39, 37, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(405, 123, NULL, 'stock_out', 2, 86, 84, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(406, 127, NULL, 'stock_out', 2, 500, 498, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(407, 127, NULL, 'stock_out', 2, 498, 496, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41'),
(408, 127, NULL, 'stock_out', 2, 496, 494, 'order', 111, 'Order #111 - sale', 'sale', 5, 2, '2026-08-31 19:52:41');

-- --------------------------------------------------------

--
-- Table structure for table `login_rate_limits`
--

CREATE TABLE `login_rate_limits` (
  `ip_address` varchar(45) NOT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `first_attempt_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment` decimal(10,2) NOT NULL,
  `change` decimal(10,2) NOT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `date_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `total_amount`, `payment`, `change`, `cashier_id`, `shift_id`, `branch_id`, `date_time`) VALUES
(1, 'ORD-20251111-4765', 0.00, 40.00, 0.00, 2, NULL, NULL, '2025-11-12 02:43:17'),
(2, 'ORD-20251111-4195', 0.00, 40.00, 0.00, 2, NULL, NULL, '2025-11-12 02:44:42'),
(3, 'ORD-20260218-3595', 195.00, 400.00, 205.00, 2, NULL, NULL, '2026-02-18 21:38:13'),
(4, 'ORD-20260218-8423', 75.00, 100.00, 25.00, 2, NULL, NULL, '2026-02-18 21:39:12'),
(5, 'ORD-20260218-8681', 35.00, 100.00, 65.00, 2, NULL, NULL, '2026-02-18 21:40:35'),
(6, 'ORD-20260218-2072', 65.00, 1000.00, 935.00, 2, NULL, NULL, '2026-02-18 21:41:32'),
(7, 'ORD-20260307-9147', 191.00, 192.00, 1.00, 2, NULL, NULL, '2026-03-07 09:05:48'),
(8, 'ORD-20260314-1350', 308.00, 500.00, 192.00, 1, NULL, NULL, '2026-03-14 13:46:33'),
(9, 'ORD-20260316-9029', 92.00, 100.00, 8.00, 2, NULL, NULL, '2026-03-16 15:53:41'),
(10, 'ORD-20260317-0709', 189.00, 200.00, 11.00, 2, NULL, NULL, '2026-03-17 15:04:02'),
(11, 'ORD-20260317-9407', 189.00, 200.00, 11.00, 2, NULL, NULL, '2026-03-17 15:15:31'),
(12, 'ORD-20260318-9474', 184.00, 190.00, 6.00, 2, NULL, NULL, '2026-03-18 14:12:59'),
(13, 'ORD-20260318-0409', 651.00, 1000.00, 349.00, 1, NULL, NULL, '2026-03-18 14:47:01'),
(14, 'ORD-20260318-8738', 270.00, 1000.00, 730.00, 1, NULL, NULL, '2026-03-18 14:47:13'),
(15, 'ORD-20260319-9097', 97.00, 100.00, 3.00, 2, NULL, NULL, '2026-03-19 12:45:31'),
(16, 'ORD-20260319-3329', 184.00, 200.00, 16.00, 2, NULL, NULL, '2026-03-19 17:09:53'),
(17, 'ORD-20260319-4077', 176.00, 500.00, 324.00, 5, NULL, NULL, '2026-03-19 20:41:33'),
(18, 'ORD-20260319-6454', 174.00, 200.00, 26.00, 5, NULL, NULL, '2026-03-19 20:52:12'),
(19, 'ORD-20260319-4587', 366.00, 1000.00, 634.00, 5, NULL, NULL, '2026-03-19 21:16:20'),
(20, 'ORD-20260320-6913', 276.00, 1000.00, 724.00, 5, NULL, NULL, '2026-03-20 11:50:33'),
(21, 'ORD-20260320-0827', 87.00, 100.00, 13.00, 5, NULL, NULL, '2026-03-20 12:01:11'),
(22, 'ORD-20260320-1117', 179.00, 200.00, 21.00, 5, NULL, NULL, '2026-03-20 12:32:04'),
(23, 'ORD-20260320-1560', 97.00, 100.00, 3.00, 5, NULL, NULL, '2026-03-20 12:32:44'),
(24, 'ORD-20260320-5497', 291.00, 500.00, 209.00, 5, NULL, NULL, '2026-03-20 13:13:23'),
(25, 'ORD-20260320-3870', 174.00, 200.00, 26.00, 5, NULL, NULL, '2026-03-20 13:45:07'),
(26, 'ORD-20260320-6942', 485.00, 500.00, 15.00, 5, NULL, NULL, '2026-03-20 13:45:38'),
(27, 'ORD-20260320-6338', 194.00, 400.00, 206.00, 5, NULL, NULL, '2026-03-20 14:08:05'),
(28, 'ORD-20260320-0194', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-03-20 16:41:31'),
(29, 'ORD-20260320-5448', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-03-20 16:47:05'),
(30, 'ORD-20260320-3689', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-03-20 16:48:26'),
(31, 'ORD-20260320-8440', 97.00, 100.00, 3.00, 2, NULL, NULL, '2026-03-20 18:41:27'),
(32, 'ORD-20260320-5506', 184.00, 200.00, 16.00, 2, NULL, NULL, '2026-03-21 02:29:53'),
(33, 'ORD-20260320-2894', 87.00, 90.00, 3.00, 2, NULL, NULL, '2026-03-21 04:32:21'),
(34, 'ORD-20260321-1361', 317.00, 500.00, 183.00, 1, NULL, NULL, '2026-03-21 09:52:07'),
(35, 'ORD-20260321-8241', 187.00, 200.00, 13.00, NULL, NULL, NULL, '2026-03-21 15:45:14'),
(36, 'ORD-20260322-9175', 283.00, 300.00, 17.00, NULL, NULL, NULL, '2026-03-22 15:07:35'),
(37, 'ORD-20260322-2619', 283.00, 300.00, 17.00, NULL, NULL, NULL, '2026-03-22 16:09:41'),
(38, 'ORD-20260322-4079', 380.00, 400.00, 20.00, NULL, NULL, NULL, '2026-03-22 16:28:00'),
(39, 'ORD-20260322-0853', 451.00, 500.00, 49.00, NULL, NULL, NULL, '2026-03-22 17:38:39'),
(40, 'ORD-20260322-7751', 371.00, 400.00, 29.00, NULL, NULL, NULL, '2026-03-22 17:39:14'),
(41, 'ORD-20260322-3491', 279.00, 280.00, 1.00, NULL, NULL, NULL, '2026-03-22 18:22:21'),
(43, 'ORD-20260322-0310', 191.00, 200.00, 9.00, NULL, NULL, NULL, '2026-03-22 22:41:39'),
(44, 'ORD-20260322-9207', 182.00, 200.00, 18.00, NULL, NULL, NULL, '2026-03-22 23:31:31'),
(45, 'ORD-20260322-8871', 193.00, 1000.00, 807.00, NULL, NULL, NULL, '2026-03-23 00:05:47'),
(46, 'ORD-20260322-4848', 2254.00, 3000.00, 746.00, NULL, NULL, NULL, '2026-03-23 00:49:02'),
(47, 'ORD-20260322-3756', 1408.00, 2000.00, 592.00, NULL, NULL, NULL, '2026-03-23 00:49:36'),
(48, 'ORD-20260322-8532', 90.00, 1000000.00, 999910.00, NULL, NULL, NULL, '2026-03-23 01:00:42'),
(49, 'ORD-CGY-001', 284.00, 334.00, 50.00, 1, NULL, NULL, '2026-03-18 01:54:13'),
(50, 'ORD-CGY-002', 126.00, 146.00, 20.00, 1, NULL, NULL, '2026-03-19 01:54:13'),
(51, 'ORD-CGY-003', 333.00, 433.00, 100.00, 1, NULL, NULL, '2026-03-20 01:54:13'),
(52, 'ORD-CGY-004', 306.00, 336.00, 30.00, 1, NULL, NULL, '2026-03-21 01:54:13'),
(53, 'ORD-CGY-005', 330.00, 430.00, 100.00, 1, NULL, NULL, '2026-03-22 01:54:13'),
(54, 'ORD-CGY-006', 438.00, 638.00, 200.00, 1, NULL, NULL, '2026-03-23 01:54:13'),
(55, 'ORD-CGY-007', 258.00, 308.00, 50.00, 1, NULL, NULL, '2026-03-24 01:54:13'),
(56, 'ORD-CGY-008', 374.00, 474.00, 100.00, 1, NULL, NULL, '2026-03-18 04:54:13'),
(57, 'ORD-CGY-009', 303.00, 353.00, 50.00, 1, NULL, NULL, '2026-03-20 06:54:13'),
(58, 'ORD-CGY-010', 164.00, 194.00, 30.00, 1, NULL, NULL, '2026-03-22 03:54:13'),
(59, 'ORD-CGY-011', 295.00, 355.00, 60.00, 1, NULL, NULL, '2026-03-23 05:54:13'),
(60, 'ORD-CGY-012', 237.00, 277.00, 40.00, 1, NULL, NULL, '2026-03-23 23:54:13'),
(61, 'ORD-20260703-7316', 90.00, 100.00, 10.00, 2, NULL, NULL, '2026-07-03 09:13:44'),
(62, 'ORD-20260703-3927', 58.00, 100.00, 42.00, 1, 29, NULL, '2026-07-03 10:13:46'),
(63, 'ORD-20260704-6145', 90.00, 1000.00, 910.00, 2, NULL, NULL, '2026-07-04 08:54:07'),
(64, 'ORD-20260704-4156', 189.00, 1000.00, 811.00, 2, NULL, NULL, '2026-07-04 09:05:24'),
(65, 'ORD-20260704-1496', 1307.00, 1500.00, 193.00, 2, NULL, NULL, '2026-07-04 09:06:07'),
(66, 'ORD-20260704-5502', 270.00, 300.00, 30.00, 1, 31, NULL, '2026-07-04 09:07:42'),
(67, 'ORD-20260704-7173', 16.00, 100.00, 84.00, 2, NULL, NULL, '2026-07-04 09:14:38'),
(68, 'ORD-20260704-3941', 1339.00, 1500.00, 161.00, 2, NULL, NULL, '2026-07-04 09:19:47'),
(69, 'ORD-20260704-3893', 1339.00, 1500.00, 161.00, 2, NULL, NULL, '2026-07-04 09:20:26'),
(70, 'ORD-20260704-8006', 1801.00, 1801.00, 0.00, 2, NULL, NULL, '2026-07-04 09:21:54'),
(71, 'ORD-20260708-7265', 189.00, 200.00, 11.00, 1, 32, NULL, '2026-07-08 11:49:28'),
(72, 'ORD-20260708-1258', 283.00, 500.00, 217.00, 2, NULL, NULL, '2026-07-08 12:51:57'),
(73, 'ORD-20260715-4410', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-07-16 00:38:55'),
(74, 'ORD-20260716-3772', 90.00, 100.00, 10.00, NULL, NULL, NULL, '2026-07-16 00:43:56'),
(75, 'ORD-20260716-6982', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-07-16 20:42:02'),
(76, 'ORD-20260716-7464', 92.00, 100.00, 8.00, NULL, NULL, 2, '2026-07-16 21:50:29'),
(77, 'ORD-20260718-3816', 196.00, 1000000.00, 999804.00, 2, NULL, NULL, '2026-07-18 11:50:10'),
(78, 'ORD-20260819-1972', 184.00, 200.00, 16.00, 5, 48, 2, '2026-08-19 13:30:26'),
(82, 'ORD-20260821-8262', 150.00, 200.00, 50.00, 5, 49, 2, '2026-08-21 21:23:18'),
(89, 'ORD-20260821-9182', 324.00, 500.00, 176.00, 1, 47, 1, '2026-08-21 23:23:44'),
(90, 'ORD-20260821-2517', 234.00, 300.00, 66.00, 15, 36, 1, '2026-08-21 23:33:39'),
(93, 'ORD-20260822-1227', 184.00, 200.00, 16.00, 2, NULL, NULL, '2026-08-22 00:51:46'),
(94, 'ORD-20260822-9567', 184.00, 200.00, 16.00, 15, 36, 1, '2026-08-22 00:59:21'),
(95, 'ORD-20260822-6170', 184.00, 200.00, 16.00, 5, 52, 2, '2026-08-22 01:00:04'),
(96, 'ORD-20260822-6789', 182.00, 200.00, 18.00, 1, 54, 1, '2026-08-22 07:57:07'),
(97, 'ORD-20260822-8160', 191.00, 200.00, 9.00, 1, 54, 1, '2026-08-22 08:14:26'),
(98, 'ORD-20260822-0032', 81.00, 90.00, 9.00, 1, 54, 1, '2026-08-22 08:17:32'),
(99, 'ORD-20260822-7190', 180.00, 180.00, 0.00, 1, 54, 1, '2026-08-22 08:24:31'),
(100, 'ORD-20260822-9491', 84.00, 100.00, 16.00, 1, 54, 1, '2026-08-22 08:24:53'),
(101, 'ORD-20260822-0815', 184.00, 190.00, 6.00, 15, 36, 1, '2026-08-22 08:26:56'),
(102, 'ORD-20260822-5774', 184.00, 200.00, 16.00, 1, 54, 1, '2026-08-22 08:35:33'),
(103, 'ORD-20260822-9201', 196.00, 200.00, 4.00, 1, 54, 1, '2026-08-22 08:38:06'),
(104, 'ORD-20260822-0100', 1282.00, 1500.00, 218.00, 3, 25, 2, '2026-08-22 08:42:08'),
(106, 'ORD-20260822-1680', 1235.00, 1300.00, 65.00, 3, 25, 2, '2026-08-22 08:43:09'),
(108, 'ORD-20260822-9482', 366.00, 1000.00, 634.00, 15, 36, 1, '2026-08-22 10:03:08'),
(110, 'ORD-20260822-3519', 290.00, 290.00, 0.00, 15, 36, 1, '2026-08-22 11:34:32'),
(111, 'ORD-20260831-8040', 182.00, 200.00, 18.00, 5, 57, 2, '2026-08-31 19:52:41');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 3, NULL, 1, 65.00, 65.00),
(2, 3, NULL, 1, 75.00, 75.00),
(3, 3, NULL, 1, 55.00, 55.00),
(4, 4, NULL, 3, 25.00, 75.00),
(5, 5, NULL, 1, 35.00, 35.00),
(6, 6, NULL, 1, 65.00, 65.00),
(7, 7, 46, 1, 101.00, 101.00),
(8, 7, 45, 1, 90.00, 90.00),
(9, 8, 49, 1, 87.00, 87.00),
(10, 8, 44, 1, 97.00, 97.00),
(11, 8, 48, 1, 92.00, 92.00),
(12, 8, 37, 1, 16.00, 16.00),
(13, 8, 36, 1, 16.00, 16.00),
(14, 9, 48, 1, 92.00, 92.00),
(15, 10, 48, 1, 92.00, 92.00),
(16, 10, 44, 1, 97.00, 97.00),
(17, 11, 48, 1, 92.00, 92.00),
(18, 11, 44, 1, 97.00, 97.00),
(19, 12, 44, 1, 97.00, 97.00),
(20, 12, 49, 1, 87.00, 87.00),
(21, 13, 48, 5, 92.00, 460.00),
(22, 13, 45, 1, 90.00, 90.00),
(23, 13, 46, 1, 101.00, 101.00),
(24, 14, 45, 3, 90.00, 270.00),
(25, 15, 44, 1, 97.00, 97.00),
(26, 16, 44, 1, 97.00, 97.00),
(27, 16, 49, 1, 87.00, 87.00),
(28, 17, 47, 1, 95.00, 95.00),
(29, 17, 53, 1, 81.00, 81.00),
(30, 18, 49, 2, 87.00, 174.00),
(31, 19, 49, 1, 87.00, 87.00),
(32, 19, 44, 1, 97.00, 97.00),
(33, 19, 48, 1, 92.00, 92.00),
(34, 19, 45, 1, 90.00, 90.00),
(35, 20, 49, 1, 87.00, 87.00),
(36, 20, 44, 1, 97.00, 97.00),
(37, 20, 48, 1, 92.00, 92.00),
(38, 21, 49, 1, 87.00, 87.00),
(39, 22, 49, 1, 87.00, 87.00),
(40, 22, 48, 1, 92.00, 92.00),
(41, 23, 44, 1, 97.00, 97.00),
(42, 24, 44, 3, 97.00, 291.00),
(43, 25, 49, 2, 87.00, 174.00),
(44, 26, 44, 5, 97.00, 485.00),
(45, 27, 44, 2, 97.00, 194.00),
(46, 28, 45, 1, 90.00, 90.00),
(47, 29, 45, 1, 90.00, 90.00),
(48, 30, 45, 1, 90.00, 90.00),
(49, 31, 44, 1, 97.00, 97.00),
(50, 32, 44, 1, 97.00, 97.00),
(51, 32, 49, 1, 87.00, 87.00),
(52, 33, 49, 1, 87.00, 87.00),
(53, 34, 49, 1, 87.00, 87.00),
(54, 34, 44, 1, 97.00, 97.00),
(55, 34, 48, 1, 92.00, 92.00),
(56, 34, 33, 1, 16.00, 16.00),
(57, 34, 27, 1, 25.00, 25.00),
(58, 35, 44, 1, 97.00, 97.00),
(59, 35, 45, 1, 90.00, 90.00),
(60, 36, 45, 1, 90.00, 90.00),
(61, 36, 48, 1, 92.00, 92.00),
(62, 36, 46, 1, 101.00, 101.00),
(63, 37, 45, 1, 90.00, 90.00),
(64, 37, 48, 1, 92.00, 92.00),
(65, 37, 46, 1, 101.00, 101.00),
(66, 38, 45, 1, 90.00, 90.00),
(67, 38, 48, 1, 92.00, 92.00),
(68, 38, 44, 1, 97.00, 97.00),
(69, 38, 46, 1, 101.00, 101.00),
(70, 39, 44, 1, 97.00, 97.00),
(71, 39, 48, 1, 92.00, 92.00),
(72, 39, 45, 1, 90.00, 90.00),
(73, 39, 46, 1, 101.00, 101.00),
(74, 39, 54, 1, 71.00, 71.00),
(75, 40, 45, 3, 90.00, 270.00),
(76, 40, 46, 1, 101.00, 101.00),
(77, 41, 45, 1, 90.00, 90.00),
(78, 41, 48, 1, 92.00, 92.00),
(79, 41, 44, 1, 97.00, 97.00),
(82, 43, 46, 1, 101.00, 101.00),
(83, 43, 45, 1, 90.00, 90.00),
(84, 44, 45, 1, 90.00, 90.00),
(85, 44, 48, 1, 92.00, 92.00),
(86, 45, 48, 1, 92.00, 92.00),
(87, 45, 46, 1, 101.00, 101.00),
(88, 46, 49, 3, 87.00, 261.00),
(89, 46, 44, 3, 97.00, 291.00),
(90, 46, 48, 3, 92.00, 276.00),
(91, 46, 45, 3, 90.00, 270.00),
(92, 46, 46, 3, 101.00, 303.00),
(93, 46, 47, 3, 95.00, 285.00),
(94, 46, 50, 4, 142.00, 568.00),
(95, 47, 49, 2, 87.00, 174.00),
(96, 47, 44, 2, 97.00, 194.00),
(97, 47, 48, 2, 92.00, 184.00),
(98, 47, 45, 2, 90.00, 180.00),
(99, 47, 46, 2, 101.00, 202.00),
(100, 47, 47, 2, 95.00, 190.00),
(101, 47, 50, 2, 142.00, 284.00),
(102, 48, 45, 1, 90.00, 90.00),
(103, 49, 70, 1, 97.00, 291.00),
(104, 50, 71, 3, 90.00, 270.00),
(105, 51, 72, 2, 101.00, 202.00),
(106, 52, 77, 2, 42.00, 84.00),
(107, 53, 57, 2, 25.00, 25.00),
(108, 54, 70, 3, 97.00, 194.00),
(109, 55, 71, 2, 90.00, 90.00),
(110, 56, 72, 1, 101.00, 101.00),
(111, 57, 77, 3, 42.00, 126.00),
(112, 58, 57, 2, 25.00, 75.00),
(113, 59, 70, 3, 97.00, 291.00),
(114, 60, 71, 3, 90.00, 90.00),
(118, 49, 72, 1, 101.00, 202.00),
(119, 50, 77, 2, 42.00, 42.00),
(120, 51, 57, 2, 25.00, 50.00),
(121, 52, 70, 1, 97.00, 97.00),
(122, 53, 71, 1, 90.00, 180.00),
(123, 54, 72, 2, 101.00, 202.00),
(124, 55, 77, 1, 42.00, 42.00),
(125, 56, 57, 1, 25.00, 50.00),
(126, 57, 70, 1, 97.00, 194.00),
(127, 58, 71, 1, 90.00, 180.00),
(128, 59, 72, 1, 101.00, 101.00),
(129, 60, 77, 1, 42.00, 84.00),
(133, 61, 45, 1, 90.00, 90.00),
(134, 62, 51, 1, 42.00, 42.00),
(135, 62, 33, 1, 16.00, 16.00),
(136, 63, 45, 1, 90.00, 90.00),
(137, 64, 70, 1, 97.00, 97.00),
(138, 64, 48, 1, 92.00, 92.00),
(139, 65, 75, 1, 87.00, 87.00),
(140, 65, 70, 1, 97.00, 97.00),
(141, 65, 48, 1, 92.00, 92.00),
(142, 65, 45, 1, 90.00, 90.00),
(143, 65, 72, 1, 101.00, 101.00),
(144, 65, 73, 1, 95.00, 95.00),
(145, 65, 50, 1, 142.00, 142.00),
(146, 65, 79, 1, 81.00, 81.00),
(147, 65, 80, 1, 71.00, 71.00),
(148, 65, 78, 1, 65.00, 65.00),
(149, 65, 51, 1, 42.00, 42.00),
(150, 65, 64, 1, 97.00, 97.00),
(151, 65, 65, 1, 95.00, 95.00),
(152, 65, 57, 1, 25.00, 25.00),
(153, 65, 58, 1, 25.00, 25.00),
(154, 65, 32, 1, 20.00, 20.00),
(155, 65, 59, 1, 18.00, 18.00),
(156, 65, 61, 1, 23.00, 23.00),
(157, 65, 69, 1, 12.00, 12.00),
(158, 65, 68, 1, 13.00, 13.00),
(159, 65, 37, 1, 16.00, 16.00),
(160, 66, 45, 3, 90.00, 270.00),
(161, 67, 37, 1, 16.00, 16.00),
(162, 68, 75, 1, 87.00, 87.00),
(163, 68, 70, 1, 97.00, 97.00),
(164, 68, 48, 1, 92.00, 92.00),
(165, 68, 45, 1, 90.00, 90.00),
(166, 68, 72, 1, 101.00, 101.00),
(167, 68, 73, 1, 95.00, 95.00),
(168, 68, 50, 1, 142.00, 142.00),
(169, 68, 79, 1, 81.00, 81.00),
(170, 68, 80, 1, 71.00, 71.00),
(171, 68, 78, 1, 65.00, 65.00),
(172, 68, 51, 1, 42.00, 42.00),
(173, 68, 64, 1, 97.00, 97.00),
(174, 68, 65, 1, 95.00, 95.00),
(175, 68, 33, 1, 16.00, 16.00),
(176, 68, 57, 1, 25.00, 25.00),
(177, 68, 58, 1, 25.00, 25.00),
(178, 68, 32, 1, 20.00, 20.00),
(179, 68, 59, 1, 18.00, 18.00),
(180, 68, 61, 1, 23.00, 23.00),
(181, 68, 69, 1, 12.00, 12.00),
(182, 68, 68, 1, 13.00, 13.00),
(183, 68, 66, 1, 16.00, 16.00),
(184, 68, 37, 1, 16.00, 16.00),
(185, 69, 75, 1, 87.00, 87.00),
(186, 69, 70, 1, 97.00, 97.00),
(187, 69, 48, 1, 92.00, 92.00),
(188, 69, 45, 1, 90.00, 90.00),
(189, 69, 72, 1, 101.00, 101.00),
(190, 69, 73, 1, 95.00, 95.00),
(191, 69, 50, 1, 142.00, 142.00),
(192, 69, 79, 1, 81.00, 81.00),
(193, 69, 80, 1, 71.00, 71.00),
(194, 69, 78, 1, 65.00, 65.00),
(195, 69, 51, 1, 42.00, 42.00),
(196, 69, 64, 1, 97.00, 97.00),
(197, 69, 65, 1, 95.00, 95.00),
(198, 69, 33, 1, 16.00, 16.00),
(199, 69, 57, 1, 25.00, 25.00),
(200, 69, 58, 1, 25.00, 25.00),
(201, 69, 32, 1, 20.00, 20.00),
(202, 69, 59, 1, 18.00, 18.00),
(203, 69, 61, 1, 23.00, 23.00),
(204, 69, 69, 1, 12.00, 12.00),
(205, 69, 68, 1, 13.00, 13.00),
(206, 69, 66, 1, 16.00, 16.00),
(207, 69, 37, 1, 16.00, 16.00),
(208, 70, 64, 3, 97.00, 291.00),
(209, 70, 79, 3, 81.00, 243.00),
(210, 70, 50, 4, 142.00, 568.00),
(211, 70, 78, 4, 65.00, 260.00),
(212, 70, 51, 2, 42.00, 84.00),
(213, 70, 80, 5, 71.00, 355.00),
(214, 71, 48, 1, 92.00, 92.00),
(215, 71, 70, 1, 97.00, 97.00),
(216, 72, 45, 1, 90.00, 90.00),
(217, 72, 72, 1, 101.00, 101.00),
(218, 72, 48, 1, 92.00, 92.00),
(219, 73, 45, 1, 90.00, 90.00),
(220, 74, 45, 1, 90.00, 90.00),
(221, 75, 45, 1, 90.00, 90.00),
(222, 76, 74, 1, 92.00, 92.00),
(223, 77, 73, 1, 95.00, 95.00),
(224, 77, 72, 1, 101.00, 101.00),
(225, 78, 48, 2, 92.00, 184.00),
(232, 82, 51, 1, 42.00, 42.00),
(233, 82, 33, 1, 16.00, 16.00),
(234, 82, 48, 1, 92.00, 92.00),
(240, 89, 48, 1, 92.00, 92.00),
(241, 89, 45, 1, 90.00, 90.00),
(242, 89, 50, 1, 142.00, 142.00),
(243, 90, 48, 1, 92.00, 92.00),
(244, 90, 50, 1, 142.00, 142.00),
(249, 93, 75, 1, 87.00, 87.00),
(250, 93, 70, 1, 97.00, 97.00),
(251, 94, 75, 1, 87.00, 87.00),
(252, 94, 70, 1, 97.00, 97.00),
(253, 95, 75, 1, 87.00, 87.00),
(254, 95, 70, 1, 97.00, 97.00),
(255, 96, 45, 1, 90.00, 90.00),
(256, 96, 48, 1, 92.00, 92.00),
(257, 97, 72, 1, 101.00, 101.00),
(258, 97, 45, 1, 90.00, 90.00),
(259, 98, 79, 1, 81.00, 81.00),
(260, 99, 45, 2, 90.00, 180.00),
(261, 100, 51, 2, 42.00, 84.00),
(262, 101, 75, 1, 87.00, 87.00),
(263, 101, 70, 1, 97.00, 97.00),
(264, 102, 75, 1, 87.00, 87.00),
(265, 102, 70, 1, 97.00, 97.00),
(266, 103, 45, 2, 90.00, 180.00),
(267, 103, 33, 1, 16.00, 16.00),
(268, 104, 75, 1, 87.00, 87.00),
(269, 104, 70, 1, 97.00, 97.00),
(270, 104, 48, 1, 92.00, 92.00),
(271, 104, 45, 1, 90.00, 90.00),
(272, 104, 72, 1, 101.00, 101.00),
(273, 104, 73, 1, 95.00, 95.00),
(274, 104, 50, 1, 142.00, 142.00),
(275, 104, 79, 1, 81.00, 81.00),
(276, 104, 80, 1, 71.00, 71.00),
(277, 104, 78, 1, 65.00, 65.00),
(278, 104, 51, 1, 42.00, 42.00),
(279, 104, 64, 1, 97.00, 97.00),
(280, 104, 65, 1, 95.00, 95.00),
(281, 104, 33, 1, 16.00, 16.00),
(282, 104, 57, 1, 25.00, 25.00),
(283, 104, 58, 1, 25.00, 25.00),
(284, 104, 32, 1, 20.00, 20.00),
(285, 104, 59, 1, 18.00, 18.00),
(286, 104, 61, 1, 23.00, 23.00),
(290, 106, 48, 1, 92.00, 92.00),
(291, 106, 45, 1, 90.00, 90.00),
(292, 106, 79, 1, 81.00, 81.00),
(293, 106, 50, 1, 142.00, 142.00),
(294, 106, 64, 7, 97.00, 679.00),
(295, 106, 51, 3, 42.00, 126.00),
(296, 106, 58, 1, 25.00, 25.00),
(298, 108, 75, 1, 87.00, 87.00),
(299, 108, 70, 1, 97.00, 97.00),
(300, 108, 48, 1, 92.00, 92.00),
(301, 108, 45, 1, 90.00, 90.00),
(303, 110, 70, 1, 97.00, 97.00),
(304, 110, 48, 1, 92.00, 92.00),
(305, 110, 72, 1, 101.00, 101.00),
(306, 111, 45, 1, 90.00, 90.00),
(307, 111, 48, 1, 92.00, 92.00);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `label`, `description`, `category`, `is_system`, `created_at`) VALUES
(1, 'dashboard_view', 'Dashboard', 'View dashboard and statistics', 'Dashboard', 1, '2026-08-08 01:20:30'),
(2, 'pos_access', 'POS Access', 'Access the Point of Sale system', 'POS', 1, '2026-08-08 01:20:30'),
(3, 'products_view', 'View Products', 'View product list and details', 'Products', 1, '2026-08-08 01:20:30'),
(4, 'products_manage', 'Manage Products', 'Add, edit, delete products and recipes', 'Products', 1, '2026-08-08 01:20:30'),
(5, 'inventory_view', 'View Inventory', 'View inventory items and stock levels', 'Inventory', 1, '2026-08-08 01:20:30'),
(6, 'inventory_manage', 'Manage Inventory', 'Edit inventory items and post adjustments', 'Inventory', 1, '2026-08-08 01:20:30'),
(7, 'inventory_receive', 'Stock Receiving', 'Receive stock deliveries', 'Inventory', 1, '2026-08-08 01:20:30'),
(8, 'inventory_count', 'Physical Count', 'Perform physical inventory counts', 'Inventory', 1, '2026-08-08 01:20:30'),
(9, 'inventory_reports', 'Inventory Reports', 'View inventory reports', 'Inventory', 1, '2026-08-08 01:20:30'),
(10, 'inventory_stock_movements', 'Stock Movement History', 'View stock movement history', 'Inventory', 1, '2026-08-08 01:20:30'),
(11, 'transactions_view', 'View Transactions', 'View transaction history', 'Transactions', 1, '2026-08-08 01:20:30'),
(12, 'reports_view', 'View Reports', 'View sales and financial reports', 'Reports', 1, '2026-08-08 01:20:30'),
(13, 'users_view', 'View Users', 'View user list and details', 'Users', 1, '2026-08-08 01:20:30'),
(14, 'users_manage', 'Manage Users', 'Add, edit, delete users', 'Users', 1, '2026-08-08 01:20:30'),
(15, 'users_roles_manage', 'Manage Roles & Permissions', 'Edit roles and their permissions', 'Users', 1, '2026-08-08 01:20:30'),
(16, 'cashiers_view', 'View Cashiers', 'View cashier list and details', 'Cashiers', 1, '2026-08-08 01:20:30'),
(17, 'cashiers_manage', 'Manage Cashiers', 'Add, edit, delete cashiers', 'Cashiers', 1, '2026-08-08 01:20:30'),
(18, 'branches_view', 'View Branches', 'View branch list and details', 'Branches', 1, '2026-08-08 01:20:30'),
(19, 'branches_manage', 'Manage Branches', 'Add, edit, delete branches', 'Branches', 1, '2026-08-08 01:20:30'),
(20, 'branch_comparison_view', 'Branch Comparison', 'View branch-to-branch comparison', 'Branch Comparison', 1, '2026-08-08 01:20:30'),
(21, 'archive_view', 'View Archive', 'View and manage archived items', 'Archive', 1, '2026-08-08 01:20:30'),
(22, 'ai_use', 'AI Assistant', 'Use the AI assistant and analytics', 'AI', 1, '2026-08-08 01:20:30'),
(23, 'backup_create', 'Create Backup', 'Create database backups', 'Backup', 1, '2026-08-08 01:20:30'),
(24, 'backup_restore', 'Restore Backup', 'Restore database from backup', 'Backup', 1, '2026-08-08 01:20:30'),
(25, 'backup_delete', 'Delete Backup', 'Delete backup files', 'Backup', 1, '2026-08-08 01:20:30'),
(26, 'backup_download', 'Download Backup', 'Download backup files', 'Backup', 1, '2026-08-08 01:20:30'),
(27, 'system_settings', 'System Settings', 'Manage system settings', 'System', 1, '2026-08-08 01:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `permission_logs`
--

CREATE TABLE `permission_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `granted` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category` varchar(50) DEFAULT NULL,
  `product_type` enum('burger','patty','bun','hotdog','drink','addon') DEFAULT 'burger',
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_bogo` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `min_stock` int(11) NOT NULL DEFAULT 10,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `stock`, `category`, `product_type`, `image`, `status`, `is_bogo`, `created_at`, `deleted_at`, `min_stock`, `updated_at`) VALUES
(27, 'Calamantea', 25.00, 99, 'Drinks', 'drink', '69a6df159ee18.jpg', 'inactive', 0, '2026-03-03 13:16:05', '2026-03-24 01:00:07', 10, '2026-03-24 09:00:07'),
(28, 'Fruit Twist', 25.00, 100, 'Drinks', 'drink', '69a6df5c6f81c.jpg', 'inactive', 0, '2026-03-03 13:17:16', '2026-03-24 01:00:16', 10, '2026-03-24 09:00:16'),
(29, 'Hot Coffee', 18.00, 100, 'Drinks', 'drink', '69a6df77bc93c.jpg', 'inactive', 0, '2026-03-03 13:17:43', '2026-03-24 01:01:11', 10, '2026-03-24 09:01:11'),
(30, 'Iced Choco', 24.00, 100, 'Drinks', 'drink', '69a6df9539b42.jpg', 'inactive', 0, '2026-03-03 13:18:13', '2026-03-24 01:01:16', 10, '2026-03-24 09:01:16'),
(31, 'Iced Coffee', 23.00, 100, 'Drinks', 'drink', '69a6dfc2a1cb3.jpg', 'inactive', 0, '2026-03-03 13:18:58', '2026-03-24 01:01:25', 10, '2026-03-24 09:01:25'),
(32, 'Hot Choco', 20.00, 97, 'Drinks', 'drink', '69a6dfd9d9adf.jpg', 'active', 0, '2026-03-03 13:19:21', NULL, 10, '2026-07-04 09:20:26'),
(33, 'Bottled Water', 16.00, 96, 'Drinks', 'drink', '69a6dff279406.jpg', 'active', 0, '2026-03-03 13:19:46', NULL, 10, '2026-07-04 09:20:26'),
(34, 'Chili Con Cheese Franks', 97.00, 100, 'Hotdogs', 'hotdog', '69a6e0185a60d.jpg', 'inactive', 0, '2026-03-03 13:20:24', '2026-03-24 01:02:22', 10, '2026-03-24 09:02:22'),
(35, 'French Onion Franks', 95.00, 100, 'Hotdogs', 'hotdog', '69a6e03431734.jpg', 'inactive', 0, '2026-03-03 13:20:52', '2026-03-24 01:02:13', 10, '2026-03-24 09:02:13'),
(36, 'Eggs', 16.00, 99, 'Add-ons', 'addon', '69a6e06888287.jpg', 'inactive', 0, '2026-03-03 13:21:44', '2026-03-24 00:58:41', 10, '2026-03-24 08:58:41'),
(37, 'Supreme Cheese', 16.00, 95, 'Add-ons', 'addon', '69a6e086e061b.jpg', 'active', 0, '2026-03-03 13:22:14', NULL, 10, '2026-07-04 09:20:26'),
(38, 'Coleslaw', 13.00, 100, 'Add-ons', 'addon', '69a6e0b473463.jpg', 'inactive', 0, '2026-03-03 13:23:00', '2026-03-24 00:58:36', 10, '2026-03-24 08:58:36'),
(39, 'Clover Chips', 12.00, 50, 'Add-ons', 'addon', '69a6e0d2a3017.jpg', 'inactive', 0, '2026-03-03 13:23:30', '2026-03-24 00:58:31', 10, '2026-03-24 08:58:31'),
(44, 'Bacon Cheese Burger', 97.00, 72, 'BIG TIME Burgers', 'burger', '69a7dd83b8d37.jpg', 'inactive', 1, '2026-03-04 07:21:39', '2026-03-24 00:59:03', 10, '2026-07-15 23:26:52'),
(45, 'Black Pepper Burger', 90.00, 61, 'BIG TIME Burgers', 'burger', '69a7dda2dfb70.jpg', 'active', 1, '2026-03-04 07:22:10', NULL, 10, '2026-07-16 20:42:02'),
(46, 'Crispy Chicken Chimichurri Burger', 101.00, 86, 'BIG TIME Burgers', 'burger', '69a7ddd426464.jpg', 'inactive', 1, '2026-03-04 07:23:00', '2026-03-24 00:59:28', 10, '2026-03-24 08:59:28'),
(47, 'Crispy Chicken Roasted Sesame Burger', 95.00, 94, 'BIG TIME Burgers', 'burger', '69a7de16c1fff.jpg', 'inactive', 1, '2026-03-04 07:24:06', '2026-03-24 00:59:48', 10, '2026-03-24 08:59:48'),
(48, 'Beef Shawarma Burger', 92.00, 68, 'BIG TIME Burgers', 'burger', '69a7de2d65b50.jpg', 'active', 1, '2026-03-04 07:24:29', NULL, 10, '2026-07-24 15:33:46'),
(49, '50/50 Veggie Chicken Burger', 87.00, 78, 'BIG TIME Burgers', 'burger', '69a7de6476080.jpg', 'inactive', 1, '2026-03-04 07:25:24', '2026-07-03 01:08:10', 10, '2026-07-15 23:26:57'),
(50, 'Premium Steak Burger', 142.00, 37, 'BIG TIME Burgers', 'burger', '69a7de7f4ebe4.jpg', 'active', 1, '2026-03-04 07:25:51', NULL, 10, '2026-07-04 09:21:54'),
(51, 'Minute Burger', 42.00, 94, 'MinuteBurgers', 'burger', '69a7deb841772.jpg', 'active', 1, '2026-03-04 07:26:48', NULL, 10, '2026-07-04 09:21:54'),
(52, 'Double Minute Burger', 65.00, 100, 'MinuteBurgers', 'burger', '69a7dee13ca2b.jpg', 'inactive', 1, '2026-03-04 07:27:29', '2026-03-24 01:01:55', 10, '2026-03-24 09:01:55'),
(53, 'Double Cheesy Burger', 81.00, 99, 'MinuteBurgers', 'burger', '69bb9a246811e.jpg', 'inactive', 1, '2026-03-04 07:28:04', '2026-03-24 01:02:08', 10, '2026-03-24 09:02:08'),
(54, 'Double Chicken Burger', 71.00, 99, 'MinuteBurgers', 'burger', '69a7df21c688d.jpg', 'inactive', 1, '2026-03-04 07:28:33', '2026-03-24 01:02:02', 10, '2026-03-24 09:02:02'),
(57, 'Calamantea', 25.00, 66, 'Drinks', 'drink', '69a6df159ee18.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(58, 'Fruit Twist', 25.00, 67, 'Drinks', 'drink', '69a6df5c6f81c.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(59, 'Hot Coffee', 18.00, 67, 'Drinks', 'drink', '69a6df77bc93c.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(60, 'Iced Choco', 24.00, 70, 'Drinks', 'drink', '69a6df9539b42.jpg', 'inactive', 0, '2026-03-23 17:53:39', '2026-03-24 01:01:29', 10, '2026-07-09 13:50:58'),
(61, 'Iced Coffee', 23.00, 67, 'Drinks', 'drink', '69a6dfc2a1cb3.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(62, 'Hot Choco', 20.00, 70, 'Drinks', 'drink', '69a6dfd9d9adf.jpg', 'inactive', 0, '2026-03-23 17:53:39', '2026-03-24 01:00:32', 10, '2026-07-09 13:50:58'),
(63, 'Bottled Water', 16.00, 69, 'Drinks', 'drink', '69a6dff279406.jpg', 'inactive', 0, '2026-03-23 17:53:39', '2026-03-24 01:00:01', 10, '2026-07-09 13:50:58'),
(64, 'Chili Con Cheese Franks', 97.00, 64, 'Hotdogs', 'hotdog', '69a6e0185a60d.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(65, 'French Onion Franks', 95.00, 67, 'Hotdogs', 'hotdog', '69a6e03431734.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(66, 'Eggs', 16.00, 67, 'Add-ons', 'addon', '69a6e06888287.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(67, 'Supreme Cheese', 16.00, 69, 'Add-ons', 'addon', '69a6e086e061b.jpg', 'inactive', 0, '2026-03-23 17:53:39', '2026-03-24 00:58:49', 10, '2026-07-09 13:50:58'),
(68, 'Coleslaw', 13.00, 67, 'Add-ons', 'addon', '69a6e0b473463.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(69, 'Clover Chips', 12.00, 32, 'Add-ons', 'addon', '69a6e0d2a3017.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(70, 'Bacon Cheese Burger', 97.00, 45, 'BIG TIME Burgers', 'burger', '69a7dd83b8d37.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(71, 'Black Pepper Burger', 90.00, 51, 'BIG TIME Burgers', 'burger', '69a7dda2dfb70.jpg', 'inactive', 1, '2026-03-23 17:53:39', '2026-03-24 00:59:19', 10, '2026-07-09 13:50:58'),
(72, 'Crispy Chicken Chimichurri Burger', 101.00, 56, 'BIG TIME Burgers', 'burger', '69a7ddd426464.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(73, 'Crispy Chicken Roasted Sesame Burger', 95.00, 62, 'BIG TIME Burgers', 'burger', '69a7de16c1fff.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(74, 'Beef Shawarma Burger', 92.00, 51, 'BIG TIME Burgers', 'burger', '69a7de2d65b50.jpg', 'inactive', 1, '2026-03-23 17:53:39', '2026-03-24 00:59:12', 10, '2026-08-22 00:56:23'),
(75, '50/50 Veggie Chicken Burger', 87.00, 51, 'BIG TIME Burgers', 'burger', '6a854714b2105.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-08-19 14:03:00'),
(76, 'Premium Steak Burger', 142.00, 30, 'BIG TIME Burgers', 'burger', '69a7de7f4ebe4.jpg', 'inactive', 1, '2026-03-23 17:53:39', '2026-03-24 00:59:41', 10, '2026-07-09 13:50:58'),
(77, 'Minute Burger', 42.00, 70, 'MinuteBurgers', 'burger', '69a7deb841772.jpg', 'inactive', 1, '2026-03-23 17:53:39', '2026-03-24 01:01:39', 10, '2026-07-09 13:50:58'),
(78, 'Double Minute Burger', 65.00, 63, 'MinuteBurgers', 'burger', '69a7dee13ca2b.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(79, 'Double Cheesy Burger', 81.00, 63, 'MinuteBurgers', 'burger', '69bb9a246811e.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(80, 'Double Chicken Burger', 71.00, 61, 'MinuteBurgers', 'burger', '69a7df21c688d.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

CREATE TABLE `product_ingredients` (
  `id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `qty_required` decimal(10,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`id`, `template_id`, `product_id`, `inventory_id`, `qty_required`) VALUES
(1, 5, 1, 1, 1.00),
(2, 6, 1, 2, 1.00),
(3, 8, 1, 3, 1.00),
(28, 5, 51, 1, 1.00),
(29, 2, 51, 16, 1.00),
(30, 8, 51, 3, 1.00),
(31, 19, 51, 4, 1.00),
(32, 21, 51, 6, 1.00),
(33, 5, 52, 1, 1.00),
(34, 2, 52, 16, 2.00),
(35, 8, 52, 3, 2.00),
(36, 19, 52, 4, 1.00),
(37, 21, 52, 6, 1.00),
(57, 5, 49, 1, 1.00),
(58, 26, 49, 19, 1.00),
(59, 9, 49, 18, 1.00),
(60, 8, 49, 3, 1.00),
(61, 19, 49, 4, 1.00),
(62, 21, 49, 6, 1.00),
(64, 5, 50, 1, 1.00),
(65, 24, 50, 20, 1.00),
(66, 8, 50, 3, 1.00),
(67, 19, 50, 4, 1.00),
(68, 21, 50, 6, 1.00),
(78, 15, 36, 25, 1.00),
(79, 8, 37, 3, 1.00),
(80, 7, 27, 32, 1.00),
(81, 27, 27, 28, 1.00),
(82, 14, 27, 34, 1.00),
(83, 16, 28, 33, 1.00),
(84, 27, 28, 28, 1.00),
(85, 14, 28, 34, 1.00),
(86, 16, 28, 33, 1.00),
(87, 27, 28, 28, 1.00),
(88, 14, 28, 34, 1.00),
(89, 12, 29, 30, 1.00),
(90, 27, 29, 28, 1.00),
(91, 14, 29, 34, 1.00),
(92, 12, 29, 30, 1.00),
(93, 27, 29, 28, 1.00),
(94, 14, 29, 34, 1.00),
(95, 11, 30, 31, 1.00),
(97, 14, 30, 34, 1.00),
(98, 11, 32, 31, 1.00),
(99, 27, 32, 28, 1.00),
(100, 14, 32, 34, 1.00),
(101, 4, 33, 35, 1.00),
(109, 5, 45, 1, 1.00),
(110, 2, 45, 16, 1.00),
(111, 8, 45, 3, 1.00),
(112, 19, 45, 4, 1.00),
(113, 21, 45, 6, 1.00),
(114, 3, 45, 21, 1.00),
(115, 3, 45, 36, 1.00),
(116, 3, 45, 37, 1.00),
(117, 3, 45, 43, 1.00),
(124, 5, 48, 1, 1.00),
(125, 2, 48, 16, 1.00),
(126, 8, 48, 3, 1.00),
(127, 19, 48, 4, 1.00),
(128, 21, 48, 6, 1.00),
(129, 23, 48, 24, 1.00),
(130, 23, 48, 40, 1.00),
(131, 23, 48, 46, 1.00),
(139, 5, 46, 1, 1.00),
(140, 9, 46, 18, 1.00),
(141, 9, 46, 49, 1.00),
(142, 8, 46, 3, 1.00),
(143, 19, 46, 4, 1.00),
(144, 21, 46, 6, 1.00),
(145, 10, 46, 22, 1.00),
(146, 10, 46, 38, 1.00),
(147, 10, 46, 44, 1.00),
(154, 5, 47, 1, 1.00),
(155, 9, 47, 18, 1.00),
(156, 9, 47, 49, 1.00),
(157, 8, 47, 3, 1.00),
(158, 19, 47, 4, 1.00),
(159, 21, 47, 6, 1.00),
(160, 22, 47, 23, 1.00),
(161, 22, 47, 39, 1.00),
(162, 22, 47, 45, 1.00),
(169, 5, 44, 1, 1.00),
(170, 2, 44, 16, 1.00),
(171, 8, 44, 3, 1.00),
(174, 19, 44, 4, 1.00),
(175, 21, 44, 6, 1.00),
(176, 20, 44, 41, 1.00),
(177, 20, 44, 47, 1.00),
(184, 5, 53, 1, 1.00),
(185, 2, 53, 16, 2.00),
(186, 8, 53, 3, 2.00),
(187, 18, 53, 42, 1.00),
(188, 18, 53, 48, 1.00),
(198, 5, 54, 1, 1.00),
(199, 9, 54, 18, 2.00),
(200, 9, 54, 49, 2.00),
(201, 19, 54, 4, 1.00),
(202, 20, 54, 41, 1.00),
(203, 20, 54, 47, 1.00),
(205, 5, 49, 1, 1.00),
(206, 26, 49, 19, 1.00),
(207, 9, 49, 18, 1.00),
(208, 8, 49, 3, 1.00),
(209, 19, 49, 4, 1.00),
(210, 21, 49, 6, 1.00),
(211, 5, 78, 1, 1.00),
(212, 2, 78, 16, 2.00),
(213, 8, 78, 3, 2.00),
(214, 19, 78, 4, 1.00),
(215, 21, 78, 6, 1.00),
(216, 5, 75, 1, 1.00),
(217, 26, 75, 19, 1.00),
(218, 9, 75, 18, 1.00),
(219, 8, 75, 3, 1.00),
(220, 19, 75, 4, 1.00),
(221, 21, 75, 6, 1.00),
(222, 15, 66, 25, 1.00),
(223, 7, 57, 32, 1.00),
(224, 27, 57, 28, 1.00),
(225, 14, 57, 34, 1.00),
(226, 16, 58, 33, 1.00),
(227, 27, 58, 28, 1.00),
(228, 14, 58, 34, 1.00),
(229, 12, 59, 30, 1.00),
(230, 27, 59, 28, 1.00),
(231, 14, 59, 34, 1.00),
(232, 5, 72, 1, 1.00),
(233, 9, 72, 18, 1.00),
(234, 9, 72, 49, 1.00),
(235, 8, 72, 3, 1.00),
(236, 19, 72, 4, 1.00),
(237, 21, 72, 6, 1.00),
(238, 10, 72, 22, 1.00),
(239, 10, 72, 38, 1.00),
(240, 10, 72, 44, 1.00),
(241, 5, 73, 1, 1.00),
(242, 9, 73, 18, 1.00),
(243, 9, 73, 49, 1.00),
(244, 8, 73, 3, 1.00),
(245, 19, 73, 4, 1.00),
(246, 21, 73, 6, 1.00),
(247, 22, 73, 23, 1.00),
(248, 22, 73, 39, 1.00),
(249, 22, 73, 45, 1.00),
(250, 5, 70, 1, 1.00),
(251, 2, 70, 16, 1.00),
(252, 8, 70, 3, 1.00),
(253, 19, 70, 4, 1.00),
(254, 21, 70, 6, 1.00),
(255, 20, 70, 41, 1.00),
(256, 20, 70, 47, 1.00),
(257, 5, 79, 1, 1.00),
(258, 2, 79, 16, 2.00),
(259, 8, 79, 3, 2.00),
(260, 18, 79, 42, 1.00),
(261, 18, 79, 48, 1.00),
(262, 5, 80, 1, 1.00),
(263, 9, 80, 18, 2.00),
(264, 9, 80, 49, 2.00),
(265, 19, 80, 4, 1.00),
(266, 20, 80, 41, 1.00),
(267, 20, 80, 47, 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_inventory_usage`
--

CREATE TABLE `product_inventory_usage` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_used` decimal(10,2) NOT NULL COMMENT 'Amount of inventory used per product',
  `unit` varchar(20) DEFAULT 'piece',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `batch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_inventory_usage`
--

INSERT INTO `product_inventory_usage` (`id`, `product_id`, `inventory_id`, `quantity_used`, `unit`, `created_at`, `updated_at`, `batch_id`) VALUES
(12, 49, 1, 2.00, 'piece', '2026-03-19 04:46:15', '2026-03-19 04:46:15', NULL),
(13, 44, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(14, 45, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(15, 46, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(16, 47, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(17, 48, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(18, 50, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(19, 51, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(20, 52, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(21, 53, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(22, 54, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `restock_requests`
--

CREATE TABLE `restock_requests` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('product','inventory') NOT NULL DEFAULT 'inventory',
  `item_name` varchar(255) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `requested_quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_by_name` varchar(255) NOT NULL,
  `request_date` datetime NOT NULL,
  `status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restock_requests`
--

INSERT INTO `restock_requests` (`id`, `item_id`, `item_type`, `item_name`, `current_quantity`, `requested_quantity`, `notes`, `requested_by`, `requested_by_name`, `request_date`, `status`) VALUES
(1, 4, 'inventory', 'Lettuce', 12, 40, 'add more lettuce\r\n', 1, 'Brix Cyrel H. Lico', '2026-03-20 19:44:01', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin', 'System Administrator - Full access', 1, '2026-07-09 14:12:34', '2026-08-08 01:20:30'),
(2, 'manager', 'manager', 'Store Manager - Limited administrative access', 1, '2026-07-09 14:12:34', '2026-08-08 01:20:30'),
(3, 'cashier', 'cashier', 'Cashier - Basic POS access', 1, '2026-07-09 14:12:34', '2026-08-08 01:20:30'),
(4, 'branch_owner', 'branch_owner', 'Branch Owner - Branch level full access', 1, '2026-07-09 14:12:34', '2026-08-08 01:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(1, 1, 1, '2026-08-08 01:20:30'),
(2, 1, 2, '2026-08-08 01:20:30'),
(3, 1, 3, '2026-08-08 01:20:30'),
(4, 1, 4, '2026-08-08 01:20:30'),
(5, 1, 5, '2026-08-08 01:20:30'),
(6, 1, 6, '2026-08-08 01:20:30'),
(7, 1, 7, '2026-08-08 01:20:30'),
(8, 1, 8, '2026-08-08 01:20:30'),
(9, 1, 9, '2026-08-08 01:20:30'),
(10, 1, 10, '2026-08-08 01:20:30'),
(11, 1, 11, '2026-08-08 01:20:30'),
(12, 1, 12, '2026-08-08 01:20:30'),
(13, 1, 13, '2026-08-08 01:20:30'),
(14, 1, 14, '2026-08-08 01:20:30'),
(15, 1, 15, '2026-08-08 01:20:30'),
(16, 1, 16, '2026-08-08 01:20:30'),
(17, 1, 17, '2026-08-08 01:20:30'),
(18, 1, 18, '2026-08-08 01:20:30'),
(19, 1, 19, '2026-08-08 01:20:30'),
(20, 1, 20, '2026-08-08 01:20:30'),
(21, 1, 21, '2026-08-08 01:20:30'),
(22, 1, 22, '2026-08-08 01:20:30'),
(23, 1, 23, '2026-08-08 01:20:30'),
(24, 1, 24, '2026-08-08 01:20:30'),
(25, 1, 25, '2026-08-08 01:20:30'),
(26, 1, 26, '2026-08-08 01:20:30'),
(27, 1, 27, '2026-08-08 01:20:30'),
(28, 2, 1, '2026-08-08 01:20:30'),
(29, 2, 2, '2026-08-08 01:20:30'),
(30, 2, 3, '2026-08-08 01:20:30'),
(31, 2, 4, '2026-08-08 01:20:30'),
(32, 2, 5, '2026-08-08 01:20:30'),
(33, 2, 6, '2026-08-08 01:20:30'),
(34, 2, 7, '2026-08-08 01:20:30'),
(35, 2, 8, '2026-08-08 01:20:30'),
(36, 2, 9, '2026-08-08 01:20:30'),
(37, 2, 10, '2026-08-08 01:20:30'),
(38, 2, 11, '2026-08-08 01:20:30'),
(39, 2, 12, '2026-08-08 01:20:30'),
(40, 2, 13, '2026-08-08 01:20:30'),
(41, 2, 14, '2026-08-08 01:20:30'),
(42, 2, 16, '2026-08-08 01:20:30'),
(43, 2, 17, '2026-08-08 01:20:30'),
(44, 2, 18, '2026-08-08 01:20:30'),
(45, 2, 19, '2026-08-08 01:20:30'),
(46, 2, 20, '2026-08-08 01:20:30'),
(47, 2, 21, '2026-08-08 01:20:30'),
(48, 2, 22, '2026-08-08 01:20:30'),
(49, 2, 23, '2026-08-08 01:20:30'),
(50, 2, 25, '2026-08-08 01:20:30'),
(51, 2, 26, '2026-08-08 01:20:30'),
(61, 3, 1, '2026-08-08 01:20:30'),
(62, 3, 2, '2026-08-08 01:20:30'),
(63, 3, 3, '2026-08-08 01:20:30'),
(64, 3, 5, '2026-08-08 01:20:30'),
(65, 3, 11, '2026-08-08 01:20:30');

-- --------------------------------------------------------

--
-- Table structure for table `sales_history`
--

CREATE TABLE `sales_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `is_bogo` tinyint(1) DEFAULT 0,
  `sale_date` date NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_history`
--

INSERT INTO `sales_history` (`id`, `product_id`, `product_name`, `quantity_sold`, `unit_price`, `total_amount`, `is_bogo`, `sale_date`, `order_id`, `cashier_id`, `created_at`) VALUES
(1, 70, 'Bacon Cheese Burger', 1, 97.00, 291.00, 0, '2026-03-18', 49, 1, '2026-03-23 17:54:59'),
(2, 72, 'Crispy Chicken Chimichurri Burger', 1, 101.00, 202.00, 0, '2026-03-18', 49, 1, '2026-03-23 17:54:59'),
(3, 71, 'Black Pepper Burger', 3, 90.00, 270.00, 0, '2026-03-19', 50, 1, '2026-03-23 17:54:59'),
(4, 77, 'Minute Burger', 2, 42.00, 42.00, 0, '2026-03-19', 50, 1, '2026-03-23 17:54:59'),
(5, 72, 'Crispy Chicken Chimichurri Burger', 2, 101.00, 202.00, 0, '2026-03-20', 51, 1, '2026-03-23 17:54:59'),
(6, 57, 'Calamantea', 2, 25.00, 50.00, 0, '2026-03-20', 51, 1, '2026-03-23 17:54:59'),
(7, 77, 'Minute Burger', 2, 42.00, 84.00, 0, '2026-03-21', 52, 1, '2026-03-23 17:54:59'),
(8, 70, 'Bacon Cheese Burger', 1, 97.00, 97.00, 0, '2026-03-21', 52, 1, '2026-03-23 17:54:59'),
(9, 57, 'Calamantea', 2, 25.00, 25.00, 0, '2026-03-22', 53, 1, '2026-03-23 17:54:59'),
(10, 71, 'Black Pepper Burger', 1, 90.00, 180.00, 0, '2026-03-22', 53, 1, '2026-03-23 17:54:59'),
(11, 70, 'Bacon Cheese Burger', 3, 97.00, 194.00, 0, '2026-03-23', 54, 1, '2026-03-23 17:54:59'),
(12, 72, 'Crispy Chicken Chimichurri Burger', 2, 101.00, 202.00, 0, '2026-03-23', 54, 1, '2026-03-23 17:54:59'),
(13, 71, 'Black Pepper Burger', 2, 90.00, 90.00, 0, '2026-03-24', 55, 1, '2026-03-23 17:54:59'),
(14, 77, 'Minute Burger', 1, 42.00, 42.00, 0, '2026-03-24', 55, 1, '2026-03-23 17:54:59'),
(15, 72, 'Crispy Chicken Chimichurri Burger', 1, 101.00, 101.00, 0, '2026-03-18', 56, 1, '2026-03-23 17:54:59'),
(16, 57, 'Calamantea', 1, 25.00, 50.00, 0, '2026-03-18', 56, 1, '2026-03-23 17:54:59'),
(17, 77, 'Minute Burger', 3, 42.00, 126.00, 0, '2026-03-20', 57, 1, '2026-03-23 17:54:59'),
(18, 70, 'Bacon Cheese Burger', 1, 97.00, 194.00, 0, '2026-03-20', 57, 1, '2026-03-23 17:54:59'),
(19, 57, 'Calamantea', 2, 25.00, 75.00, 0, '2026-03-22', 58, 1, '2026-03-23 17:54:59'),
(20, 71, 'Black Pepper Burger', 1, 90.00, 180.00, 0, '2026-03-22', 58, 1, '2026-03-23 17:54:59'),
(21, 70, 'Bacon Cheese Burger', 3, 97.00, 291.00, 0, '2026-03-23', 59, 1, '2026-03-23 17:54:59'),
(22, 72, 'Crispy Chicken Chimichurri Burger', 1, 101.00, 101.00, 0, '2026-03-23', 59, 1, '2026-03-23 17:54:59'),
(23, 71, 'Black Pepper Burger', 3, 90.00, 90.00, 0, '2026-03-23', 60, 1, '2026-03-23 17:54:59'),
(24, 77, 'Minute Burger', 1, 42.00, 84.00, 0, '2026-03-23', 60, 1, '2026-03-23 17:54:59');

-- --------------------------------------------------------

--
-- Table structure for table `stock_receiving`
--

CREATE TABLE `stock_receiving` (
  `id` int(11) NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `received_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_receiving_items`
--

CREATE TABLE `stock_receiving_items` (
  `id` int(11) NOT NULL,
  `receiving_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `lead_time_days` int(11) DEFAULT 7,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `payment_terms`, `lead_time_days`, `is_active`, `created_at`) VALUES
(1, 'Minute Burger Commissary', 'Maria Santos', '09171234567', 'commissary@minuteburger.com', 'Cagayan de Oro City', 'Net 30', 3, 1, '2026-03-24 02:11:28'),
(2, 'Local Fresh Produce', 'Juan dela Cruz', '09189876543', 'fresh@localproduce.ph', 'Jasaan, Misamis Oriental', 'COD', 1, 1, '2026-03-24 02:11:28'),
(3, 'CDO Meat Supply', 'Pedro Reyes', '09201112233', 'orders@cdomeat.ph', 'Cagayan de Oro City', 'Net 15', 2, 1, '2026-03-24 02:11:28'),
(4, 'Oriental Bakery', 'Ana Garcia', '09223344556', 'orientalbakery@gmail.com', 'Jasaan, Misamis Oriental', 'COD', 1, 1, '2026-03-24 02:11:28'),
(5, 'Golden Beverage Corp', 'Robert Tan', '09334455667', 'sales@goldenbev.ph', 'Iligan City', 'Net 30', 5, 1, '2026-03-24 02:11:28'),
(6, 'Manila Ice Supply', 'Jose Mendoza', '09456789012', 'jose@manilice.com', 'Quezon City, Metro Manila', 'COD', 2, 1, '2026-03-24 03:03:35'),
(7, 'Pampanga Meat Corp', 'Lisa Bautista', '09567890123', 'lisa@pampmeat.com', 'San Fernando, Pampanga', 'Net 15', 3, 1, '2026-03-24 03:03:35'),
(8, 'Laguna Dairy Farm', 'Mark Villanueva', '09678901234', 'mark@lagunadairy.com', 'Santa Rosa, Laguna', 'Net 7', 2, 1, '2026-03-24 03:03:35'),
(9, 'Batangas Coffee Co.', 'Rosa Dimaculangan', '09789012345', 'rosa@batcoffee.com', 'Lipa City, Batangas', 'Net 30', 7, 1, '2026-03-24 03:03:35'),
(10, 'Cebu Spice Traders', 'Carlo Montero', '09890123456', 'carlo@cebuspice.com', 'Cebu City, Cebu', 'Net 15', 5, 1, '2026-03-24 03:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','cashier','branch_owner') NOT NULL DEFAULT 'cashier',
  `role_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `full_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_activity` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fname` varchar(100) DEFAULT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `password`, `role`, `role_id`, `branch_id`, `permissions`, `full_name`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `status`, `last_activity`, `created_at`, `fname`, `mname`, `lname`) VALUES
(1, 'Brix', '$2y$10$ji9B3CzfE5JVGU40yM8a3OJYclZeFy50LyzYBILlodZWaj.lPUSh.', 'cashier', 3, 1, '{\"dashboard_view\":false,\"products_view\":false,\"products_manage\":false,\"inventory_view\":true,\"inventory_manage\":false,\"pos_access\":true,\"transactions_view\":true,\"reports_view\":false,\"users_manage\":false,\"archive_view\":false}', 'Brix Cyrel H. Lico', 'Brix', 'Hallazgo', 'Lico', '', 'brixlico@gmail.com', 'active', '2026-09-01 00:24:02', '2025-11-11 18:24:38', NULL, NULL, NULL),
(2, 'Allan', '$2y$10$6t3mGSIGvB874ibHz9GEj.u6K3./gZJQ0hW5JhzEZhK5.NR9jQfNG', 'admin', 1, NULL, '{\"pos_access\": true, \"transactions_view\": true, \"inventory_manage\": true, \"reports_view\": true, \"users_manage\": true}', 'Allan Christian S. Uayan', NULL, NULL, NULL, NULL, 'allanuayan@gmailcom', 'active', '2026-08-31 20:39:24', '2025-11-11 18:24:38', NULL, NULL, NULL),
(3, 'wenggams', '$2y$10$CnyeUJ.HR4kXuD7lohgD0OvXAhdfjXAqa1kBLynJ8aCu9dbepBY0a', 'cashier', 3, 2, '{\"pos_access\":true,\"transactions_view\":true}', 'Joshua B. Gamalo', NULL, NULL, NULL, NULL, 'gamalo.joshua123@gmail.com', 'active', '2026-08-22 08:43:44', '2026-02-18 14:17:54', NULL, NULL, NULL),
(5, 'Joshua', '$2y$10$7KR23AmfHuyDUBbZ0zOkS.6Sz6A7u.ASfIdpLuWs5kwHyrq3JfCM2', 'manager', 2, 2, '{\"pos_access\":true,\"transactions_view\":true,\"inventory_manage\":true,\"reports_view\":true,\"users_manage\":true}', 'Joshua B. Gamalo', NULL, NULL, NULL, NULL, 'wenggams04@gmail.com', 'active', '2026-08-31 19:52:54', '2026-02-18 14:18:32', NULL, NULL, NULL),
(14, 'Merfern', '$2y$10$JwQV1ABNJ8Xmi6p2U0BWbOMdGyCnCvK6BR1gQkewLCDmPCLT8WWmu', 'admin', 1, NULL, NULL, 'Merfern L. Igot', NULL, NULL, NULL, NULL, 'merfern@gmail.com', 'active', '2026-08-22 09:32:44', '2026-07-15 12:18:34', NULL, NULL, NULL),
(15, 'Barbe', '$2y$10$w3t4dW2JPottmNCFrI4uMuzJC1nxV7GCj7lmVe6ty.FYpmnmWqH.G', 'manager', 2, 1, NULL, 'Barbe Jane A. Buadlart', NULL, NULL, NULL, NULL, 'barbejanebuadlart@gmail.com', 'active', '2026-08-31 21:08:58', '2026-07-15 12:18:34', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `x_reading_log`
--

CREATE TABLE `x_reading_log` (
  `id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `reading_time` time NOT NULL,
  `total_sales` decimal(10,2) NOT NULL,
  `total_transactions` int(11) NOT NULL,
  `average_transaction` decimal(10,2) NOT NULL,
  `highest_transaction` decimal(10,2) NOT NULL,
  `lowest_transaction` decimal(10,2) NOT NULL,
  `total_items_sold` int(11) DEFAULT 0,
  `cash_sales` decimal(10,2) DEFAULT 0.00,
  `generated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `x_reading_log`
--

INSERT INTO `x_reading_log` (`id`, `shift_id`, `reading_time`, `total_sales`, `total_transactions`, `average_transaction`, `highest_transaction`, `lowest_transaction`, `total_items_sold`, `cash_sales`, `generated_by`, `created_at`) VALUES
(6, 22, '07:21:12', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 3, '2026-05-23 23:21:12'),
(7, 25, '07:56:15', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 3, '2026-06-24 23:56:15'),
(9, 48, '16:06:14', 184.00, 1, 184.00, 184.00, 184.00, 2, 0.00, 5, '2026-08-19 08:06:14'),
(10, 48, '16:06:15', 184.00, 1, 184.00, 184.00, 184.00, 2, 0.00, 5, '2026-08-19 08:06:15'),
(11, 48, '16:06:16', 184.00, 1, 184.00, 184.00, 184.00, 2, 0.00, 5, '2026-08-19 08:06:16'),
(12, 48, '16:06:16', 184.00, 1, 184.00, 184.00, 184.00, 2, 0.00, 5, '2026-08-19 08:06:16'),
(13, 52, '01:22:49', 184.00, 1, 184.00, 184.00, 184.00, 2, 0.00, 5, '2026-08-21 17:22:49');

-- --------------------------------------------------------

--
-- Table structure for table `z_reading_log`
--

CREATE TABLE `z_reading_log` (
  `id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `closing_time` datetime NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `total_sales` decimal(10,2) NOT NULL,
  `total_transactions` int(11) NOT NULL,
  `average_transaction` decimal(10,2) NOT NULL,
  `highest_transaction` decimal(10,2) NOT NULL,
  `lowest_transaction` decimal(10,2) NOT NULL,
  `total_items_sold` int(11) DEFAULT 0,
  `expected_cash` decimal(10,2) DEFAULT 0.00,
  `actual_cash` decimal(10,2) DEFAULT 0.00,
  `cash_difference` decimal(10,2) DEFAULT 0.00,
  `cash_drop_total` decimal(10,2) DEFAULT 0.00,
  `opening_cash` decimal(10,2) DEFAULT 0.00,
  `closing_cash` decimal(10,2) DEFAULT 0.00,
  `shift_duration_hours` decimal(5,2) DEFAULT 0.00,
  `generated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `z_reading_log`
--

INSERT INTO `z_reading_log` (`id`, `shift_id`, `closing_time`, `start_time`, `end_time`, `total_sales`, `total_transactions`, `average_transaction`, `highest_transaction`, `lowest_transaction`, `total_items_sold`, `expected_cash`, `actual_cash`, `cash_difference`, `cash_drop_total`, `opening_cash`, `closing_cash`, `shift_duration_hours`, `generated_by`, `created_at`) VALUES
(24, 22, '2026-05-24 07:21:19', '2026-03-23 12:38:35', '2026-05-24 07:21:19', 0.00, 0, 0.00, 0.00, 0.00, 0, 894.00, 894.00, 0.00, 0.00, 894.00, 894.00, 999.99, 3, '2026-05-23 23:21:19'),
(25, 26, '2026-06-30 23:36:41', '2026-06-30 23:35:12', '2026-06-30 23:36:41', 0.00, 0, 0.00, 0.00, 0.00, 0, 21211.00, 21211.00, 0.00, 0.00, 21211.00, 21211.00, 0.02, 1, '2026-06-30 15:36:41'),
(26, 27, '2026-07-01 13:11:40', '2026-07-01 12:46:19', '2026-07-01 13:11:40', 0.00, 0, 0.00, 0.00, 0.00, 0, 6666.00, 6666.00, 0.00, 0.00, 6666.00, 6666.00, 0.42, 1, '2026-07-01 05:11:40'),
(27, 28, '2026-07-03 09:35:04', '2026-07-03 09:34:43', '2026-07-03 09:35:04', 0.00, 0, 0.00, 0.00, 0.00, 0, 1406.00, 1406.00, 0.00, 0.00, 1406.00, 1406.00, 0.01, 1, '2026-07-03 01:35:04'),
(28, 29, '2026-07-03 10:14:33', '2026-07-03 10:10:50', '2026-07-03 10:14:33', 58.00, 1, 58.00, 58.00, 58.00, 2, 1364.00, 1364.00, 0.00, 0.00, 1306.00, 1364.00, 0.06, 1, '2026-07-03 02:14:33'),
(29, 30, '2026-07-04 09:06:46', '2026-07-03 10:22:00', '2026-07-04 09:06:46', 0.00, 0, 0.00, 0.00, 0.00, 0, 1275.00, 1275.00, 0.00, 0.00, 1275.00, 1275.00, 22.75, 1, '2026-07-04 01:06:46'),
(30, 31, '2026-07-04 09:07:58', '2026-07-04 09:07:34', '2026-07-04 09:07:58', 270.00, 1, 270.00, 270.00, 270.00, 3, 1751.00, 1751.00, 0.00, 0.00, 1481.00, 1751.00, 0.01, 1, '2026-07-04 01:07:58'),
(38, 32, '2026-07-18 11:51:17', '2026-07-04 09:08:21', '2026-07-18 11:51:17', 189.00, 1, 189.00, 189.00, 189.00, 2, 1667.00, 1667.00, 0.00, 0.00, 1478.00, 1667.00, 338.72, 1, '2026-07-18 03:51:17'),
(39, 44, '2026-07-18 12:05:53', '2026-07-18 11:51:40', '2026-07-18 12:05:53', 0.00, 0, 0.00, 0.00, 0.00, 0, 1025.00, 1025.00, 0.00, 0.00, 1025.00, 1025.00, 0.24, 1, '2026-07-18 04:05:53'),
(41, 45, '2026-08-08 09:06:57', '2026-07-24 15:35:58', '2026-08-08 09:06:57', 0.00, 0, 0.00, 0.00, 0.00, 0, 500.00, 500.00, 0.00, 0.00, 500.00, 500.00, 353.52, 1, '2026-08-08 01:06:57'),
(42, 48, '2026-08-19 16:06:23', '2026-08-19 13:15:34', '2026-08-19 16:06:23', 184.00, 1, 184.00, 184.00, 184.00, 2, 184.00, 184.00, 0.00, 0.00, 0.00, 184.00, 2.85, 5, '2026-08-19 08:06:23'),
(43, 47, '2026-08-21 23:24:09', '2026-08-19 12:36:43', '2026-08-21 23:24:09', 324.00, 1, 324.00, 324.00, 324.00, 3, 1224.00, 1224.00, 0.00, 0.00, 900.00, 1224.00, 58.79, 1, '2026-08-21 15:24:09'),
(44, 49, '2026-08-22 00:42:37', '2026-08-21 21:17:25', '2026-08-22 00:42:37', 150.00, 1, 150.00, 150.00, 150.00, 3, 150.00, 150.00, 0.00, 0.00, 0.00, 150.00, 3.42, 5, '2026-08-21 16:42:37'),
(45, 52, '2026-08-22 01:22:56', '2026-08-22 00:52:07', '2026-08-22 01:22:56', 184.00, 1, 184.00, 184.00, 184.00, 2, 184.00, 184.00, 0.00, 0.00, 0.00, 184.00, 0.51, 5, '2026-08-21 17:22:56'),
(46, 53, '2026-08-22 02:23:21', '2026-08-22 02:23:16', '2026-08-22 02:23:21', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 5, '2026-08-21 18:23:21'),
(47, 36, '2026-08-22 11:34:48', '2026-07-15 23:38:19', '2026-08-22 11:34:48', 1258.00, 5, 251.60, 366.00, 184.00, 13, 1258.00, 1258.00, 0.00, 0.00, 0.00, 1258.00, 899.94, 15, '2026-08-22 03:34:48'),
(48, 55, '2026-08-29 22:08:50', '2026-08-29 22:06:37', '2026-08-29 22:08:50', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.04, 15, '2026-08-29 14:08:50'),
(49, 57, '2026-08-31 19:52:54', '2026-08-31 19:52:27', '2026-08-31 19:52:54', 182.00, 1, 182.00, 182.00, 182.00, 2, 182.00, 182.00, 0.00, 0.00, 0.00, 182.00, 0.01, 5, '2026-08-31 11:52:54'),
(50, 54, '2026-08-31 21:07:22', '2026-08-22 07:56:56', '2026-08-31 21:07:22', 1098.00, 7, 156.86, 196.00, 81.00, 14, 2776.00, 2776.00, 0.00, 0.00, 1678.00, 2776.00, 229.17, 1, '2026-08-31 13:07:22'),
(51, 58, '2026-08-31 21:21:51', '2026-08-31 21:09:10', '2026-08-31 21:21:51', 0.00, 0, 0.00, 0.00, 0.00, 0, 2213.00, 2213.00, 0.00, 0.00, 2213.00, 2213.00, 0.21, 1, '2026-08-31 13:21:51'),
(52, 59, '2026-08-31 22:20:14', '2026-08-31 22:18:32', '2026-08-31 22:20:14', 0.00, 0, 0.00, 0.00, 0.00, 0, 1925.00, 1925.00, 0.00, 0.00, 1925.00, 1925.00, 0.03, 1, '2026-08-31 14:20:14'),
(53, 60, '2026-08-31 22:42:08', '2026-08-31 22:41:46', '2026-08-31 22:42:08', 0.00, 0, 0.00, 0.00, 0.00, 0, 1829.00, 1829.00, 0.00, 0.00, 1829.00, 1829.00, 0.01, 1, '2026-08-31 14:42:08'),
(54, 61, '2026-08-31 23:03:50', '2026-08-31 23:03:45', '2026-08-31 23:03:50', 0.00, 0, 0.00, 0.00, 0.00, 0, 1738.00, 1738.00, 0.00, 0.00, 1738.00, 1738.00, 0.00, 1, '2026-08-31 15:03:50'),
(55, 62, '2026-08-31 23:09:34', '2026-08-31 23:09:28', '2026-08-31 23:09:34', 0.00, 0, 0.00, 0.00, 0.00, 0, 1713.00, 1713.00, 0.00, 0.00, 1713.00, 1713.00, 0.00, 1, '2026-08-31 15:09:34'),
(56, 63, '2026-08-31 23:34:31', '2026-08-31 23:34:16', '2026-08-31 23:34:31', 0.00, 0, 0.00, 0.00, 0.00, 0, 1617.00, 1617.00, 0.00, 0.00, 1617.00, 1617.00, 0.00, 1, '2026-08-31 15:34:31'),
(57, 64, '2026-09-01 00:24:02', '2026-09-01 00:23:44', '2026-09-01 00:24:02', 0.00, 0, 0.00, 0.00, 0.00, 0, 3000.00, 3000.00, 0.00, 0.00, 3000.00, 3000.00, 0.01, 1, '2026-08-31 16:24:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `backup_download_tokens`
--
ALTER TABLE `backup_download_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_token` (`token`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cashier_inventory_counts`
--
ALTER TABLE `cashier_inventory_counts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `counted_by` (`counted_by`),
  ADD KEY `counted_at` (`counted_at`);

--
-- Indexes for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`,`shift_date`),
  ADD KEY `status` (`status`),
  ADD KEY `started_by` (`started_by`),
  ADD KEY `closed_by` (`closed_by`);

--
-- Indexes for table `cash_drop_log`
--
ALTER TABLE `cash_drop_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_discount_type` (`discount_type`);

--
-- Indexes for table `ingredient_templates`
--
ALTER TABLE `ingredient_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_template_name_unit` (`item_name`,`unit`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_category` (`category`),
  ADD KEY `idx_inventory_supplier` (`supplier`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `idx_inventory_deleted_at` (`deleted_at`),
  ADD KEY `idx_inventory_branch` (`branch_id`),
  ADD KEY `idx_inventory_template` (`template_id`);

--
-- Indexes for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_inventory_id` (`inventory_id`),
  ADD KEY `idx_alert_type` (`alert_type`);

--
-- Indexes for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_id` (`inventory_id`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_batch_number` (`batch_number`),
  ADD KEY `idx_inventory_batches_expiry` (`expiry_date`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indexes for table `inventory_counts`
--
ALTER TABLE `inventory_counts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_counts_inventory` (`inventory_id`),
  ADD KEY `idx_counts_branch` (`branch_id`),
  ADD KEY `idx_counts_date` (`counted_at`);

--
-- Indexes for table `inventory_deliveries`
--
ALTER TABLE `inventory_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `idx_inventory_deliveries_status` (`status`);

--
-- Indexes for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_change_date` (`change_date`),
  ADD KEY `idx_inventory_id` (`inventory_id`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `update_date` (`update_date`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `idx_inventory_id` (`inventory_id`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_movement_type` (`movement_type`),
  ADD KEY `idx_inventory_movements_date` (`created_at`),
  ADD KEY `idx_movements_branch` (`branch_id`),
  ADD KEY `idx_movements_type` (`movement_type`),
  ADD KEY `idx_movements_date` (`created_at`);

--
-- Indexes for table `login_rate_limits`
--
ALTER TABLE `login_rate_limits`
  ADD PRIMARY KEY (`ip_address`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `idx_orders_date_time` (`date_time`),
  ADD KEY `idx_orders_cashier_id` (`cashier_id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `idx_orders_branch` (`branch_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_items_product_id` (`product_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_name` (`name`);

--
-- Indexes for table `permission_logs`
--
ALTER TABLE `permission_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_status_stock` (`status`,`stock`);

--
-- Indexes for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_template` (`template_id`);

--
-- Indexes for table `product_inventory_usage`
--
ALTER TABLE `product_inventory_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_inventory` (`product_id`,`inventory_id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`),
  ADD UNIQUE KEY `uq_roles_slug` (`slug`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_permission` (`role_id`,`permission_id`),
  ADD KEY `idx_rp_permission` (`permission_id`);

--
-- Indexes for table `sales_history`
--
ALTER TABLE `sales_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_date` (`sale_date`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `stock_receiving`
--
ALTER TABLE `stock_receiving`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_receiving_branch` (`branch_id`),
  ADD KEY `idx_receiving_date` (`received_date`);

--
-- Indexes for table `stock_receiving_items`
--
ALTER TABLE `stock_receiving_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_receiving_items_recv` (`receiving_id`),
  ADD KEY `idx_receiving_items_inv` (`inventory_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_name` (`supplier_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `user_id_2` (`user_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `users_ibfk_role` (`role_id`);

--
-- Indexes for table `x_reading_log`
--
ALTER TABLE `x_reading_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `reading_time` (`reading_time`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `z_reading_log`
--
ALTER TABLE `z_reading_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `closing_time` (`closing_time`),
  ADD KEY `generated_by` (`generated_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `backup_download_tokens`
--
ALTER TABLE `backup_download_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `cashier_inventory_counts`
--
ALTER TABLE `cashier_inventory_counts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `cash_drop_log`
--
ALTER TABLE `cash_drop_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ingredient_templates`
--
ALTER TABLE `ingredient_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_counts`
--
ALTER TABLE `inventory_counts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_deliveries`
--
ALTER TABLE `inventory_deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=376;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=409;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=308;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `permission_logs`
--
ALTER TABLE `permission_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=268;

--
-- AUTO_INCREMENT for table `product_inventory_usage`
--
ALTER TABLE `product_inventory_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `restock_requests`
--
ALTER TABLE `restock_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `sales_history`
--
ALTER TABLE `sales_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `stock_receiving`
--
ALTER TABLE `stock_receiving`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_receiving_items`
--
ALTER TABLE `stock_receiving_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `x_reading_log`
--
ALTER TABLE `x_reading_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `z_reading_log`
--
ALTER TABLE `z_reading_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD CONSTRAINT `cashier_shifts_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cashier_shifts_ibfk_2` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cashier_shifts_ibfk_3` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `cash_drop_log`
--
ALTER TABLE `cash_drop_log`
  ADD CONSTRAINT `cash_drop_log_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_drop_log_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_template` FOREIGN KEY (`template_id`) REFERENCES `ingredient_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  ADD CONSTRAINT `inventory_alerts_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_alerts_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_alerts_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD CONSTRAINT `inventory_batches_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD CONSTRAINT `inventory_categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_deliveries`
--
ALTER TABLE `inventory_deliveries`
  ADD CONSTRAINT `inventory_deliveries_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD CONSTRAINT `inventory_history_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `inventory_movements_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_movements_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_movements_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD CONSTRAINT `fk_pi_template` FOREIGN KEY (`template_id`) REFERENCES `ingredient_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_inventory_usage`
--
ALTER TABLE `product_inventory_usage`
  ADD CONSTRAINT `product_inventory_usage_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_inventory_usage_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_inventory_usage_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_history`
--
ALTER TABLE `sales_history`
  ADD CONSTRAINT `sales_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `users_ibfk_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `x_reading_log`
--
ALTER TABLE `x_reading_log`
  ADD CONSTRAINT `x_reading_log_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `x_reading_log_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `z_reading_log`
--
ALTER TABLE `z_reading_log`
  ADD CONSTRAINT `z_reading_log_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `z_reading_log_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
